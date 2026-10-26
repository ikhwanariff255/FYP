"""
Tilapia ML — historical training dataset and Random Forest models.

Training data comes ONLY from historical / secondary sources:
  1. Data_Model_IoTMLCQ_2024.xlsx  (secondary historical water-quality records, 2024)
  2. Published tilapia knowledge   (water-quality bands, TGC growth model,
                                    un-ionised ammonia equation)

Live ESP32 readings are NEVER used for training. They are only fed into the
trained models at prediction time (predict.py).
"""

import json
import math
import os
import subprocess
import xml.etree.ElementTree as ET
import zipfile

import numpy as np

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
EXCEL_PATH = os.path.join(ROOT, 'Data_Model_IoTMLCQ_2024.xlsx')

CLASSES = ['Optimal', 'Warning', 'Critical']
RISK_FEATURES = ['temperature', 'ph_level', 'turbidity', 'temp_change_1h', 'ph_swing_24h']
GROWTH_FEATURES = [
    'initial_weight', 'days_in_culture', 'cum_degree_days',
    'mean_temp_7d', 'mean_ph_7d', 'mean_turbidity_7d',
    'pct_days_temp_optimal', 'pct_days_ph_optimal', 'pct_days_turbidity_optimal',
]

# ─────────────────────────────────────────────────────────────────────
# Literature constants (same source of truth as the Laravel app)
# ─────────────────────────────────────────────────────────────────────

FALLBACK_BANDS = {
    'temperature': {'optimal': [26.0, 32.0], 'acceptable': [22.0, 35.0], 'critical': [18.0, 38.0]},
    'ph_level':    {'optimal': [6.5, 8.5],   'acceptable': [6.0, 9.0],   'critical': [5.0, 10.0]},
    'turbidity':   {'optimal': [0.0, 50.0],  'acceptable': [0.0, 100.0], 'critical': [0.0, 200.0]},
}
TGC_MEAN = 0.0015           # thermal-unit growth coefficient (Iwama & Tautz, 1981)
ZERO_GROWTH = (18.0, 38.0)  # temperature limits where tilapia growth stops


def load_bands():
    """Read the water-quality bands from config/aquaculture.php so training and app agree."""
    try:
        out = subprocess.run(
            ['php', '-r', 'echo json_encode((include "config/aquaculture.php")["parameters"]);'],
            cwd=ROOT, capture_output=True, text=True, timeout=10, check=True,
        )
        cfg = json.loads(out.stdout)
        return {k: {b: [float(x) for x in cfg[k][b]] for b in ('optimal', 'acceptable', 'critical')} for k in FALLBACK_BANDS}
    except Exception:
        return FALLBACK_BANDS


BANDS = load_bands()


def band_score(key, v):
    """0–100 score: 100 inside optimal, 50 at the acceptable edge, 0 at the critical edge (vectorised)."""
    b = BANDS[key]
    (oLo, oHi), (aLo, aHi), (cLo, cHi) = b['optimal'], b['acceptable'], b['critical']
    v = np.asarray(v, dtype=float)
    s = np.full(v.shape, 100.0)

    def lin(x, x0, x1, y0, y1):
        return y0 + (y1 - y0) * (x - x0) / (x1 - x0 if x1 != x0 else 1e-9)

    lo1 = (v < oLo) & (v >= aLo)
    lo2 = (v < aLo) & (v > cLo)
    hi1 = (v > oHi) & (v <= aHi)
    hi2 = (v > aHi) & (v < cHi)
    s[lo1] = lin(v[lo1], oLo, aLo, 100, 50)
    s[lo2] = lin(v[lo2], aLo, cLo, 50, 0)
    s[hi1] = lin(v[hi1], oHi, aHi, 100, 50)
    s[hi2] = lin(v[hi2], aHi, cHi, 50, 0)
    s[(v <= cLo) | (v >= cHi)] = 0
    s[(v >= oLo) & (v <= oHi)] = 100
    return s


def status_level(score):
    """0 = Optimal, 1 = Warning, 2 = Critical."""
    return np.where(score >= 100, 0, np.where(score >= 50, 1, 2))


def nh3_fraction(temp, ph):
    """Share of total ammonia in toxic un-ionised NH3 form (Emerson et al., 1975)."""
    pka = 0.09018 + 2729.92 / (np.asarray(temp) + 273.15)
    return 1 / (1 + 10 ** (pka - np.asarray(ph)))


def temp_growth_factor(t):
    t = np.asarray(t, dtype=float)
    lo, hi = ZERO_GROWTH
    oLo, oHi = BANDS['temperature']['optimal']
    f = np.ones_like(t)
    f = np.where(t < oLo, (t - lo) / (oLo - lo), f)
    f = np.where(t > oHi, (hi - t) / (hi - oHi), f)
    return np.clip(f, 0, 1)


def quality_growth_factor(ph, turb):
    return 0.5 + 0.5 * np.minimum(band_score('ph_level', ph), band_score('turbidity', turb)) / 100


# ─────────────────────────────────────────────────────────────────────
# Source 1: secondary historical dataset (Excel)
# ─────────────────────────────────────────────────────────────────────

def _col_index(ref):
    letters = ''.join(c for c in ref if c.isalpha())
    n = 0
    for c in letters:
        n = n * 26 + ord(c) - 64
    return n - 1


def read_excel_columns(path=EXCEL_PATH, wanted=('Temperature (°C)', 'pH', 'Turbidity (NTU)')):
    """Minimal .xlsx reader (standard library only) returning {column: [values]}."""
    ns = '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
    with zipfile.ZipFile(path) as z:
        shared = []
        if 'xl/sharedStrings.xml' in z.namelist():
            for si in ET.fromstring(z.read('xl/sharedStrings.xml')).iter(ns + 'si'):
                shared.append(''.join(t.text or '' for t in si.iter(ns + 't')))
        sheet = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))

    header, data = {}, {w: [] for w in wanted}
    for r_i, row in enumerate(sheet.iter(ns + 'row')):
        cells = {}
        for c in row.iter(ns + 'c'):
            v = c.find(ns + 'v')
            if v is None:
                continue
            val = shared[int(v.text)] if c.get('t') == 's' else v.text
            cells[_col_index(c.get('r'))] = val
        if r_i == 0:
            header = {i: name for i, name in cells.items()}
            continue
        for i, name in header.items():
            if name in data:
                try:
                    data[name].append(float(cells.get(i)))
                except (TypeError, ValueError):
                    data[name].append(np.nan)
    return data


def load_excel_history():
    """Clean the Excel data: drop missing values and duplicated hourly copies."""
    d = read_excel_columns()
    arr = np.column_stack([d['Temperature (°C)'], d['pH'], d['Turbidity (NTU)']])
    raw_rows = len(arr)
    arr = arr[~np.isnan(arr).any(axis=1)]
    unique = np.unique(np.round(arr, 4), axis=0)
    return {
        'raw_rows': raw_rows,
        'unique_rows': len(unique),
        'values': unique,          # columns: temperature, ph, turbidity
        'mean': unique.mean(axis=0),
        'std': unique.std(axis=0),
    }


# ─────────────────────────────────────────────────────────────────────
# Risk classification dataset
# ─────────────────────────────────────────────────────────────────────

def risk_label(temp, ph, turb, d_temp, ph_swing):
    """Expert labelling function from published tilapia water-quality knowledge.

    Base level = worst single parameter. Interaction rules capture stressors that a
    single threshold misses (toxic ammonia, warm + organic load, thermal shock, pH swing).
    """
    level = np.maximum.reduce([
        status_level(band_score('temperature', temp)),
        status_level(band_score('ph_level', ph)),
        status_level(band_score('turbidity', turb)),
    ])

    nh3 = nh3_fraction(temp, ph)
    level = np.where(nh3 >= 0.20, 2, np.where(nh3 >= 0.10, np.maximum(level, 1), level))

    # Warm water + high organic load favours Aeromonas
    level = np.where((temp >= 30) & (turb > BANDS['turbidity']['optimal'][1]), np.minimum(level + 1, 2), level)

    ad = np.abs(d_temp)
    level = np.where(ad >= 4, 2, np.where(ad >= 2, np.maximum(level, 1), level))
    level = np.where(ph_swing >= 2, 2, np.where(ph_swing >= 1, np.maximum(level, 1), level))
    return level


def generate_risk_dataset(n=6000, seed=42):
    """Normal conditions are bootstrapped from the Excel history; stress scenarios cover
    the published tolerance ranges. Labels come from the TRUE condition, while the
    features carry sensor measurement error (as a real probe would)."""
    rng = np.random.default_rng(seed)
    hist = load_excel_history()

    n_norm = int(n * 0.55)
    n_stress = n - n_norm

    base = hist['values'][rng.integers(0, len(hist['values']), n_norm)]
    t_norm = base[:, 0] + rng.normal(0, 1.2, n_norm)
    ph_norm = base[:, 1] + rng.normal(0, 0.25, n_norm)
    tu_norm = base[:, 2] * np.exp(rng.normal(1.2, 0.9, n_norm))

    t_str = rng.uniform(18, 38, n_stress)
    ph_str = rng.uniform(4.0, 10.5, n_stress)
    tu_str = np.exp(rng.uniform(np.log(1), np.log(250), n_stress))

    temp = np.concatenate([t_norm, t_str])
    ph = np.concatenate([ph_norm, ph_str])
    turb = np.concatenate([tu_norm, tu_str])

    d_temp = rng.normal(0, 0.5, n)
    swing = np.abs(rng.normal(0.3, 0.15, n))
    shock = rng.random(n) < 0.2
    d_temp[shock] = rng.uniform(-5, 5, shock.sum())
    unstable = rng.random(n) < 0.2
    swing[unstable] = rng.uniform(0.5, 3, unstable.sum())

    y = risk_label(temp, ph, turb, d_temp, swing)

    X = np.column_stack([
        temp + rng.normal(0, 0.2, n),
        ph + rng.normal(0, 0.1, n),
        np.clip(turb * rng.normal(1, 0.08, n), 0, None),
        d_temp + rng.normal(0, 0.3, n),
        np.clip(swing + rng.normal(0, 0.1, n), 0, None),
    ])
    idx = rng.permutation(n)
    return X[idx], y[idx], hist


def threshold_rule(X):
    """Baseline: the old single-parameter threshold approach (no interactions, no trends)."""
    return np.maximum.reduce([
        status_level(band_score('temperature', X[:, 0])),
        status_level(band_score('ph_level', X[:, 1])),
        status_level(band_score('turbidity', X[:, 2])),
    ])


# ─────────────────────────────────────────────────────────────────────
# Growth regression dataset
# ─────────────────────────────────────────────────────────────────────

def growth_features(w0, temps, phs, turbs, day):
    """Features at a given day, computed only from the pond's water history (as the app will)."""
    t, p, u = temps[:day], phs[:day], turbs[:day]
    to, po, uo = BANDS['temperature']['optimal'], BANDS['ph_level']['optimal'], BANDS['turbidity']['optimal']
    return [
        w0, day, float(t.sum()),
        float(t[-7:].mean()), float(p[-7:].mean()), float(u[-7:].mean()),
        float(((t >= to[0]) & (t <= to[1])).mean()),
        float(((p >= po[0]) & (p <= po[1])).mean()),
        float(((u >= uo[0]) & (u <= uo[1])).mean()),
    ]


def simulate_batch(rng, hist):
    """One virtual culture batch: daily water history -> growth via the TGC model with
    batch-to-batch variability, daily growth noise and weekly weighing error."""
    w0 = math.exp(rng.uniform(math.log(1), math.log(50)))
    days = int(rng.integers(60, 181))
    tgc = float(np.clip(rng.normal(TGC_MEAN, 0.00015), 0.0012, 0.0018))

    # Daily temperature: historical mean shifted to a Malaysian outdoor tank, plus events
    mu = hist['mean'][0] + rng.uniform(-1.5, 3.5)
    d = np.arange(days)
    temps = mu + rng.uniform(0, 1) * np.sin(2 * np.pi * d / 120 + rng.uniform(0, 6)) + rng.normal(0, 0.7, days)
    phs = rng.normal(7.5, 0.3) + rng.normal(0, 0.15, days)
    turbs = math.exp(rng.uniform(math.log(3), math.log(40))) * np.exp(rng.normal(0, 0.3, days))

    i = 0
    while i < days:
        r = rng.random()
        if r < 0.03:      # heat wave / cold spell
            L = int(rng.integers(2, 7)); temps[i:i + L] += rng.choice([-1, 1]) * rng.uniform(3, 6); i += L
        elif r < 0.05:    # acid rain / alkaline algae episode
            L = int(rng.integers(2, 6)); phs[i:i + L] += rng.choice([-1, 1]) * rng.uniform(1, 2.5); i += L
        elif r < 0.08:    # algae bloom / sludge
            L = int(rng.integers(3, 8)); turbs[i:i + L] *= rng.uniform(3, 8); i += L
        else:
            i += 1

    f = temp_growth_factor(temps) * quality_growth_factor(phs, turbs)
    noise = np.clip(rng.normal(1, 0.05, days), 0.5, 1.5)
    cube = w0 ** (1 / 3) + np.cumsum(tgc * temps * f * noise)
    weights = cube ** 3

    rows, targets = [], []
    for day in range(7, days + 1, 7):  # weekly weighing
        rows.append(growth_features(w0, temps, phs, turbs, day))
        targets.append(float(weights[day - 1] * rng.normal(1, 0.06)))
    return rows, targets


def generate_growth_dataset(n_batches=500, seed=7):
    rng = np.random.default_rng(seed)
    hist = load_excel_history()
    X, y, groups = [], [], []
    for b in range(n_batches):
        rows, targets = simulate_batch(rng, hist)
        X += rows; y += targets; groups += [b] * len(rows)
    return np.array(X), np.array(y), np.array(groups)


def tgc_baseline(X):
    """Standard TGC formula with a single published coefficient: W = (W0^(1/3) + TGC × ΣT)^3."""
    return (X[:, 0] ** (1 / 3) + TGC_MEAN * X[:, 2]) ** 3
