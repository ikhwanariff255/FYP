"""
Train and evaluate the two Random Forest models described in the proposal.

  RFC  : water condition -> Optimal / Warning / Critical
  RFR  : pond water history -> fish weight (g)

Data   : historical / secondary sources only (see tilapia_ml.py). No live ESP32 data.
Split  : 80/20 (stratified for RFC; by culture batch for RFR so no batch leaks into test)
Tuning : GridSearchCV, 5-fold, over number of trees, max depth and min samples per leaf
Output : ml/models/*.joblib, ml/reports/metrics.json, ml/reports/*.png, ml/reports/report.md,
         ml/data/*.csv

Usage  : python3 ml/train_models.py
"""

import csv
import json
import os
import sys
import time
from datetime import datetime

import joblib
import numpy as np
import sklearn
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.inspection import permutation_importance
from sklearn.linear_model import LinearRegression
from sklearn.metrics import (accuracy_score, confusion_matrix, f1_score, mean_absolute_error,
                             mean_squared_error, precision_recall_fscore_support, r2_score)
from sklearn.model_selection import GridSearchCV, GroupKFold, GroupShuffleSplit, StratifiedKFold, train_test_split
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import tilapia_ml as tm  # noqa: E402

HERE = os.path.dirname(os.path.abspath(__file__))
MODELS = os.path.join(HERE, 'models')
REPORTS = os.path.join(HERE, 'reports')
DATA = os.path.join(HERE, 'data')
SEED = 42


def log(msg):
    print(f'[{datetime.now():%H:%M:%S}] {msg}', flush=True)


def save_csv(path, header, X, y):
    with open(path, 'w', newline='') as f:
        w = csv.writer(f)
        w.writerow(header)
        for row, target in zip(X, y):
            w.writerow([round(float(v), 4) for v in row] + [target])


def class_metrics(y_true, y_pred):
    p, r, f, s = precision_recall_fscore_support(y_true, y_pred, labels=[0, 1, 2], zero_division=0)
    return {
        'accuracy': round(accuracy_score(y_true, y_pred), 4),
        'precision_macro': round(float(p.mean()), 4),
        'recall_macro': round(float(r.mean()), 4),
        'f1_macro': round(f1_score(y_true, y_pred, average='macro'), 4),
        'per_class': {tm.CLASSES[i]: {'precision': round(float(p[i]), 4), 'recall': round(float(r[i]), 4),
                                      'f1': round(float(f[i]), 4), 'support': int(s[i])} for i in range(3)},
        'confusion_matrix': confusion_matrix(y_true, y_pred, labels=[0, 1, 2]).tolist(),
    }


def reg_metrics(y_true, y_pred):
    mse = mean_squared_error(y_true, y_pred)
    return {'mse': round(float(mse), 2), 'rmse': round(float(np.sqrt(mse)), 2),
            'mae': round(float(mean_absolute_error(y_true, y_pred)), 2), 'r2': round(float(r2_score(y_true, y_pred)), 4)}


# ─────────────────────────────────────────────────────────────────────
# 1. Random Forest Classifier — risk level
# ─────────────────────────────────────────────────────────────────────

def train_classifier():
    log('Generating risk dataset from historical sources...')
    X, y, hist = tm.generate_risk_dataset(n=6000, seed=SEED)
    save_csv(os.path.join(DATA, 'risk_training_dataset.csv'), tm.RISK_FEATURES + ['risk_level'], X, [tm.CLASSES[i] for i in y])

    X_tr, X_te, y_tr, y_te = train_test_split(X, y, test_size=0.2, stratify=y, random_state=SEED)
    log(f'Train {len(X_tr)} / test {len(X_te)} rows')

    grid = GridSearchCV(
        RandomForestClassifier(random_state=SEED, class_weight='balanced', n_jobs=1),
        {'n_estimators': [100, 200], 'max_depth': [8, 16, None], 'min_samples_leaf': [1, 3]},
        cv=StratifiedKFold(5, shuffle=True, random_state=SEED), scoring='f1_macro', n_jobs=2,
    )
    t = time.time()
    grid.fit(X_tr, y_tr)
    log(f'RFC grid search done in {time.time() - t:.0f}s, best {grid.best_params_} (CV F1 {grid.best_score_:.3f})')
    rfc = grid.best_estimator_

    log('Training SVM comparison model...')
    svm = make_pipeline(StandardScaler(), SVC(C=10, gamma='scale', class_weight='balanced', random_state=SEED))
    svm.fit(X_tr, y_tr)

    perm = permutation_importance(rfc, X_te, y_te, n_repeats=10, random_state=SEED, scoring='f1_macro', n_jobs=1)

    # External check on the real secondary records (all within tilapia tolerance ranges)
    ex = hist['values']
    ex_X = np.column_stack([ex, np.zeros(len(ex)), np.full(len(ex), 0.3)])
    ex_pred = rfc.predict(ex_X)

    # Proposal test case TC-03 and companions
    cases = [
        ('TC-03 acidic + very murky', [29.0, 4.8, 180.0, 0.0, 0.4]),
        ('Ideal water', [28.5, 7.2, 15.0, 0.2, 0.3]),
        ('Warm afternoon', [33.0, 7.6, 20.0, 0.5, 0.4]),
        ('High pH + warm (toxic NH3)', [31.0, 8.9, 20.0, 0.3, 0.5]),
        ('Thermal shock in range', [28.0, 7.3, 15.0, 3.0, 0.3]),
        ('Cold spell', [21.0, 7.0, 10.0, -1.0, 0.3]),
    ]
    case_X = np.array([c[1] for c in cases])
    case_expected = tm.risk_label(*case_X.T)
    case_pred = rfc.predict(case_X)
    case_proba = rfc.predict_proba(case_X)

    result = {
        'features': tm.RISK_FEATURES,
        'classes': tm.CLASSES,
        'dataset_rows': int(len(X)),
        'class_counts': {tm.CLASSES[i]: int(c) for i, c in enumerate(np.bincount(y, minlength=3))},
        'best_params': grid.best_params_,
        'cv_f1_macro': round(float(grid.best_score_), 4),
        'test': class_metrics(y_te, rfc.predict(X_te)),
        'baselines': {
            'threshold_rule': class_metrics(y_te, tm.threshold_rule(X_te)),
            'svm': class_metrics(y_te, svm.predict(X_te)),
        },
        'feature_importance': {f: round(float(v), 4) for f, v in zip(tm.RISK_FEATURES, rfc.feature_importances_)},
        'permutation_importance': {f: round(float(v), 4) for f, v in zip(tm.RISK_FEATURES, perm.importances_mean)},
        'excel_validation': {
            'rows': int(len(ex)),
            'predicted': {tm.CLASSES[i]: int(c) for i, c in enumerate(np.bincount(ex_pred, minlength=3))},
        },
        'test_cases': [{
            'name': name, 'input': dict(zip(tm.RISK_FEATURES, vals)),
            'expected': tm.CLASSES[int(e)], 'predicted': tm.CLASSES[int(p)],
            'confidence': round(float(pr.max()), 3), 'pass': bool(e == p),
        } for (name, vals), e, p, pr in zip(cases, case_expected, case_pred, case_proba)],
    }

    joblib.dump({'model': rfc, 'features': tm.RISK_FEATURES, 'classes': tm.CLASSES,
                 'trained_at': datetime.now().isoformat(timespec='seconds'), 'sklearn': sklearn.__version__},
                os.path.join(MODELS, 'rf_risk_classifier.joblib'), compress=3)
    return result, (y_te, rfc.predict(X_te))


# ─────────────────────────────────────────────────────────────────────
# 2. Random Forest Regressor — fish weight
# ─────────────────────────────────────────────────────────────────────

def train_regressor():
    log('Generating growth dataset (virtual culture batches)...')
    X, y, groups = tm.generate_growth_dataset(n_batches=500, seed=7)
    save_csv(os.path.join(DATA, 'growth_training_dataset.csv'), tm.GROWTH_FEATURES + ['weight_g'], X, [round(v, 2) for v in y])

    tr, te = next(GroupShuffleSplit(n_splits=1, test_size=0.2, random_state=SEED).split(X, y, groups))
    X_tr, X_te, y_tr, y_te, g_tr = X[tr], X[te], y[tr], y[te], groups[tr]
    log(f'Train {len(X_tr)} rows ({len(set(g_tr))} batches) / test {len(X_te)} rows ({len(set(groups[te]))} batches)')

    grid = GridSearchCV(
        RandomForestRegressor(random_state=SEED, n_jobs=1),
        {'n_estimators': [100, 200], 'max_depth': [12, 20, None], 'min_samples_leaf': [1, 3, 5]},
        cv=GroupKFold(5), scoring='neg_root_mean_squared_error', n_jobs=2,
    )
    t = time.time()
    grid.fit(X_tr, y_tr, groups=g_tr)
    log(f'RFR grid search done in {time.time() - t:.0f}s, best {grid.best_params_} (CV RMSE {-grid.best_score_:.1f} g)')
    rfr = grid.best_estimator_

    lr = LinearRegression().fit(X_tr, y_tr)
    pred = rfr.predict(X_te)
    perm = permutation_importance(rfr, X_te, y_te, n_repeats=5, random_state=SEED, n_jobs=1)

    result = {
        'features': tm.GROWTH_FEATURES,
        'dataset_rows': int(len(X)),
        'batches': int(len(set(groups))),
        'best_params': grid.best_params_,
        'cv_rmse': round(float(-grid.best_score_), 2),
        'test': reg_metrics(y_te, pred),
        'baselines': {
            'tgc_formula': reg_metrics(y_te, tm.tgc_baseline(X_te)),
            'linear_regression': reg_metrics(y_te, lr.predict(X_te)),
        },
        'feature_importance': {f: round(float(v), 4) for f, v in zip(tm.GROWTH_FEATURES, rfr.feature_importances_)},
        'permutation_importance': {f: round(float(v), 4) for f, v in zip(tm.GROWTH_FEATURES, perm.importances_mean)},
    }

    joblib.dump({'model': rfr, 'features': tm.GROWTH_FEATURES,
                 'trained_at': datetime.now().isoformat(timespec='seconds'), 'sklearn': sklearn.__version__},
                os.path.join(MODELS, 'rf_growth_regressor.joblib'), compress=3)
    return result, (X_te, y_te, pred)


# ─────────────────────────────────────────────────────────────────────
# Figures + written report
# ─────────────────────────────────────────────────────────────────────

def make_figures(clf, clf_eval, reg, reg_eval):
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt

    plt.rcParams.update({'font.size': 10, 'axes.spines.top': False, 'axes.spines.right': False})
    blue, grey, orange = '#2563eb', '#9ca3af', '#f59e0b'

    # Confusion matrix
    cm = np.array(clf['test']['confusion_matrix'])
    fig, ax = plt.subplots(figsize=(4.6, 4))
    ax.imshow(cm, cmap='Blues')
    ax.set_xticks(range(3), tm.CLASSES); ax.set_yticks(range(3), tm.CLASSES)
    ax.set_xlabel('Predicted'); ax.set_ylabel('Actual'); ax.set_title('RFC confusion matrix (20% test set)')
    for i in range(3):
        for j in range(3):
            ax.text(j, i, cm[i, j], ha='center', va='center', color='white' if cm[i, j] > cm.max() / 2 else 'black', fontweight='bold')
    fig.tight_layout(); fig.savefig(os.path.join(REPORTS, 'rfc_confusion_matrix.png'), dpi=160); plt.close(fig)

    # Feature importance (both models)
    for name, res, fname in [('RFC', clf, 'rfc_feature_importance.png'), ('RFR', reg, 'rfr_feature_importance.png')]:
        items = sorted(res['permutation_importance'].items(), key=lambda kv: kv[1])
        fig, ax = plt.subplots(figsize=(6, 0.45 * len(items) + 1))
        ax.barh([k for k, _ in items], [v for _, v in items], color=blue)
        ax.set_title(f'{name} permutation feature importance (test set)'); ax.set_xlabel('Drop in score when shuffled')
        fig.tight_layout(); fig.savefig(os.path.join(REPORTS, fname), dpi=160); plt.close(fig)

    # Predicted vs actual weight
    X_te, y_te, pred = reg_eval
    fig, ax = plt.subplots(figsize=(4.8, 4.6))
    ax.scatter(y_te, tm.tgc_baseline(X_te), s=6, alpha=.35, color=grey, label='TGC formula (baseline)')
    ax.scatter(y_te, pred, s=6, alpha=.5, color=blue, label='Random Forest Regressor')
    lim = [0, float(max(y_te.max(), pred.max()))]
    ax.plot(lim, lim, color=orange, lw=1.5, label='Perfect prediction')
    ax.set_xlabel('Actual weight (g)'); ax.set_ylabel('Predicted weight (g)'); ax.set_title('RFR vs baseline (test batches)')
    ax.legend(frameon=False, fontsize=8)
    fig.tight_layout(); fig.savefig(os.path.join(REPORTS, 'rfr_predicted_vs_actual.png'), dpi=160); plt.close(fig)

    # Model comparison
    fig, axes = plt.subplots(1, 2, figsize=(9, 3.4))
    names = ['Threshold rule', 'SVM', 'Random Forest']
    vals = [clf['baselines']['threshold_rule']['f1_macro'], clf['baselines']['svm']['f1_macro'], clf['test']['f1_macro']]
    axes[0].bar(names, vals, color=[grey, grey, blue]); axes[0].set_ylim(0, 1); axes[0].set_title('Risk classification — macro F1')
    for i, v in enumerate(vals): axes[0].text(i, v + .02, f'{v:.3f}', ha='center')
    names = ['TGC formula', 'Linear Reg.', 'Random Forest']
    vals = [reg['baselines']['tgc_formula']['rmse'], reg['baselines']['linear_regression']['rmse'], reg['test']['rmse']]
    axes[1].bar(names, vals, color=[grey, grey, blue]); axes[1].set_title('Growth prediction — RMSE (g, lower is better)')
    for i, v in enumerate(vals): axes[1].text(i, v * 1.02, f'{v:.1f}', ha='center')
    fig.tight_layout(); fig.savefig(os.path.join(REPORTS, 'model_comparison.png'), dpi=160); plt.close(fig)


def write_report(m):
    c, r = m['classifier'], m['regressor']
    lines = [
        '# Random Forest Model Report',
        '',
        f"Generated {m['generated_at']} · scikit-learn {m['sklearn']}",
        '',
        '## Training data (historical sources only)',
        f"- Secondary dataset `Data_Model_IoTMLCQ_2024.xlsx`: {m['excel']['raw_rows']} rows, "
        f"{m['excel']['unique_rows']} unique after removing duplicated hourly copies. Used for normal operating conditions and as an external check.",
        '- Published tilapia knowledge: water-quality bands, un-ionised ammonia equation (Emerson et al., 1975) and the TGC growth model (Iwama & Tautz, 1981), used to add Warning/Critical cases and growth curves.',
        '- Live ESP32 readings are **not** used for training.',
        '',
        '## Random Forest Classifier (risk level)',
        f"- Dataset: {c['dataset_rows']} rows · classes {c['class_counts']}",
        f"- Split: 80/20 stratified · tuning: 5-fold GridSearchCV → `{c['best_params']}`",
        '',
        '| Model | Accuracy | Precision | Recall | F1 (macro) |',
        '|---|---|---|---|---|',
    ]
    for name, res in [('Threshold rule (old)', c['baselines']['threshold_rule']), ('SVM', c['baselines']['svm']), ('**Random Forest**', c['test'])]:
        lines.append(f"| {name} | {res['accuracy']:.3f} | {res['precision_macro']:.3f} | {res['recall_macro']:.3f} | {res['f1_macro']:.3f} |")
    lines += ['', 'Per class (Random Forest):', '', '| Class | Precision | Recall | F1 | Support |', '|---|---|---|---|---|']
    for k, v in c['test']['per_class'].items():
        lines.append(f"| {k} | {v['precision']:.3f} | {v['recall']:.3f} | {v['f1']:.3f} | {v['support']} |")
    lines += ['', 'Test cases:', '', '| Case | Expected | Predicted | Confidence | Pass |', '|---|---|---|---|---|']
    for t in c['test_cases']:
        lines.append(f"| {t['name']} | {t['expected']} | {t['predicted']} | {t['confidence']:.0%} | {'✅' if t['pass'] else '❌'} |")
    lines += ['', f"External check on the {c['excel_validation']['rows']} real Excel records: {c['excel_validation']['predicted']}", '']
    lines += [
        '## Random Forest Regressor (fish weight)',
        f"- Dataset: {r['dataset_rows']} weekly weighings from {r['batches']} culture batches",
        f"- Split: 80/20 by batch · tuning: 5-fold GroupKFold GridSearchCV → `{r['best_params']}`",
        '',
        '| Model | MSE | RMSE (g) | MAE (g) | R² |',
        '|---|---|---|---|---|',
    ]
    for name, res in [('TGC formula (baseline)', r['baselines']['tgc_formula']), ('Linear Regression', r['baselines']['linear_regression']), ('**Random Forest**', r['test'])]:
        lines.append(f"| {name} | {res['mse']:.1f} | {res['rmse']:.1f} | {res['mae']:.1f} | {res['r2']:.3f} |")
    lines += ['', '## Most important parameters (Objective I)', '']
    for title, res in [('Risk', c), ('Growth', r)]:
        top = sorted(res['permutation_importance'].items(), key=lambda kv: -kv[1])
        lines.append(f"- {title}: " + ', '.join(f'{k} ({v:.3f})' for k, v in top))
    lines += ['', '## Figures', '', '![](rfc_confusion_matrix.png)', '![](model_comparison.png)',
              '![](rfr_predicted_vs_actual.png)', '![](rfc_feature_importance.png)', '![](rfr_feature_importance.png)', '']
    with open(os.path.join(REPORTS, 'report.md'), 'w') as f:
        f.write('\n'.join(lines))


def main():
    for d in (MODELS, REPORTS, DATA):
        os.makedirs(d, exist_ok=True)

    clf, clf_eval = train_classifier()
    reg, reg_eval = train_regressor()
    hist = tm.load_excel_history()

    metrics = {
        'generated_at': datetime.now().isoformat(timespec='seconds'),
        'sklearn': sklearn.__version__,
        'excel': {'raw_rows': hist['raw_rows'], 'unique_rows': hist['unique_rows']},
        'classifier': clf,
        'regressor': reg,
    }
    with open(os.path.join(REPORTS, 'metrics.json'), 'w') as f:
        json.dump(metrics, f, indent=2, default=lambda o: o.item() if hasattr(o, 'item') else str(o))

    make_figures(clf, clf_eval, reg, reg_eval)
    write_report(metrics)
    log('Done. See ml/reports/report.md')


if __name__ == '__main__':
    main()
