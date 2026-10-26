<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\PondDevice;
use App\Models\SensorData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Enjin analisis pertumbuhan & risiko kolam tilapia.
 *
 * Semua keputusan boleh diterangkan (explainable): setiap risiko membawa
 * bukti, kesan dan tindakan, dan model pertumbuhan menggunakan sejarah
 * suhu sebenar daripada sensor (model TGC).
 */
class PondAnalysisService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('aquaculture');
    }

    public function analyse(PondDevice $device): array
    {
        $now = now();

        $snapshot = $this->currentSnapshot($device, $now);
        $hourly   = $this->hourlyBuckets($device, $now);
        $stats    = $this->stats24h($hourly);

        $scores = [];
        foreach ($this->cfg['parameters'] as $key => $p) {
            if ($snapshot['current'] === null) {
                break;
            }
            $value = $snapshot['current'][$key];
            $score = $this->score($key, $value);
            $scores[$key] = [
                'label'    => $p['label'],
                'unit'     => $p['unit'],
                'value'    => round($value, $key === 'turbidity' ? 1 : 2),
                'score'    => (int) round($score),
                'status'   => $this->statusFor($score),
                'ideal'    => $this->idealText($key),
                'in_range' => $stats[$key]['in_range_pct'] ?? null,
                'hours_out'=> $this->hoursOutOfRange($hourly, $key),
                'stats'    => $stats[$key] ?? null,
                'spark'    => array_values(array_map(fn ($b) => round($b[$key]['avg'], 2), $hourly)),
            ];
        }

        $health  = $snapshot['current'] ? $this->healthIndex($scores, $stats) : null;
        $ammonia = $snapshot['current'] ? $this->ammonia($snapshot['current']['temperature'], $snapshot['current']['ph_level']) : null;
        $growth  = $this->growth($device, $now);
        $disease = $snapshot['current'] ? $this->diseaseRisk($snapshot['current'], $health['score'], $stats, $growth['current_weight']) : null;
        $ml      = $this->latestMlAnalysis($device);

        $risks = $this->risks($snapshot, $scores, $stats, $hourly, $ammonia, $disease, $ml);
        $feed  = $this->feeding($growth, $risks);

        return [
            'device'      => $device,
            'snapshot'    => $snapshot,
            'parameters'  => $scores,
            'health'      => $health,
            'hourly'      => $this->hourlyHealthStrip($hourly, $now),
            'ammonia'     => $ammonia,
            'disease'     => $disease,
            'growth'      => $growth,
            'feed'        => $feed,
            'risks'       => $risks,
            'ml'          => $ml,
            'insights'    => $this->insights($growth, $ml, $risks, $snapshot, $health),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Data: bacaan semasa, baldi sejam, data harian
    // ─────────────────────────────────────────────────────────────

    /** Tapis bacaan yang mustahil secara fizikal (kerosakan sensor). */
    protected function scopeValid(Builder $q): Builder
    {
        foreach ($this->cfg['plausible'] as $col => [$lo, $hi]) {
            $q->whereBetween($col, [$lo, $hi]);
        }
        foreach ($this->cfg['sensor_fault_values'] as $col => $values) {
            $q->whereNotIn($col, $values);
        }

        return $q;
    }

    protected function currentSnapshot(PondDevice $device, Carbon $now): array
    {
        $latest = SensorData::where('device_id', $device->device_id)->latest('timestamp')->first();

        // Median 15 bacaan sah terakhir (~1 minit) supaya satu lonjakan tidak mencetuskan amaran palsu
        $recent = $this->scopeValid(SensorData::where('device_id', $device->device_id))
            ->latest('timestamp')
            ->take(15)
            ->get();

        $current = null;
        if ($recent->isNotEmpty()) {
            $current = [];
            foreach (array_keys($this->cfg['parameters']) as $key) {
                $current[$key] = $this->median($recent->pluck($key)->map(fn ($v) => (float) $v)->all());
            }
        }

        $total24 = SensorData::where('device_id', $device->device_id)
            ->where('timestamp', '>=', $now->copy()->subDay())
            ->count();
        $valid24 = $this->scopeValid(SensorData::where('device_id', $device->device_id))
            ->where('timestamp', '>=', $now->copy()->subDay())
            ->count();

        $ageSeconds = $latest ? Carbon::parse($latest->timestamp)->diffInSeconds($now) : null;
        $fresh = $this->cfg['freshness'];

        return [
            'current'        => $current,
            'latest_at'      => $latest ? Carbon::parse($latest->timestamp) : null,
            'age_seconds'    => $ageSeconds,
            'connection'     => $ageSeconds === null ? 'no-data'
                : ($ageSeconds <= $fresh['online_seconds'] ? 'online'
                : ($ageSeconds <= $fresh['stale_seconds'] ? 'stale' : 'offline')),
            'readings_24h'   => $total24,
            'invalid_24h'    => $total24 - $valid24,
            'invalid_pct'    => $total24 > 0 ? round(($total24 - $valid24) / $total24 * 100, 1) : 0,
        ];
    }

    /** Purata/min/maks dan kiraan "dalam julat" bagi setiap jam, 24 jam terakhir. */
    protected function hourlyBuckets(PondDevice $device, Carbon $now): array
    {
        $p = $this->cfg['parameters'];

        $rows = $this->scopeValid(SensorData::where('device_id', $device->device_id))
            ->where('timestamp', '>=', $now->copy()->subDay()->startOfHour())
            ->selectRaw("DATE_FORMAT(`timestamp`, '%Y-%m-%d %H:00:00') as hour, COUNT(*) as n")
            ->selectRaw('AVG(temperature) t_avg, MIN(temperature) t_min, MAX(temperature) t_max')
            ->selectRaw('AVG(ph_level) p_avg, MIN(ph_level) p_min, MAX(ph_level) p_max')
            ->selectRaw('AVG(turbidity) u_avg, MIN(turbidity) u_min, MAX(turbidity) u_max')
            ->selectRaw('SUM(temperature BETWEEN ? AND ?) t_in', $p['temperature']['optimal'])
            ->selectRaw('SUM(ph_level BETWEEN ? AND ?) p_in', $p['ph_level']['optimal'])
            ->selectRaw('SUM(turbidity BETWEEN ? AND ?) u_in', $p['turbidity']['optimal'])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $map = ['temperature' => 't', 'ph_level' => 'p', 'turbidity' => 'u'];

        return $rows->map(function ($r) use ($map) {
            $bucket = ['hour' => $r->hour, 'n' => (int) $r->n];
            foreach ($map as $key => $k) {
                $bucket[$key] = [
                    'avg' => (float) $r->{"{$k}_avg"},
                    'min' => (float) $r->{"{$k}_min"},
                    'max' => (float) $r->{"{$k}_max"},
                    'in'  => (int) $r->{"{$k}_in"},
                ];
            }

            return $bucket;
        })->all();
    }

    protected function stats24h(array $hourly): array
    {
        $stats = [];
        $n = array_sum(array_column($hourly, 'n'));
        if ($n === 0) {
            return $stats;
        }

        foreach (array_keys($this->cfg['parameters']) as $key) {
            $weightedAvg = array_sum(array_map(fn ($b) => $b[$key]['avg'] * $b['n'], $hourly)) / $n;
            $min = min(array_map(fn ($b) => $b[$key]['min'], $hourly));
            $max = max(array_map(fn ($b) => $b[$key]['max'], $hourly));

            $stats[$key] = [
                'avg'          => round($weightedAvg, 2),
                'min'          => round($min, 2),
                'max'          => round($max, 2),
                'swing'        => round($max - $min, 2),
                'in_range_pct' => (int) round(array_sum(array_map(fn ($b) => $b[$key]['in'], $hourly)) / $n * 100),
            ];
        }

        return $stats;
    }

    /** Berapa jam berturut-turut (dari jam terkini) parameter berada di luar julat optimum. */
    protected function hoursOutOfRange(array $hourly, string $key): int
    {
        $hours = 0;
        foreach (array_reverse($hourly) as $b) {
            if ($b['n'] === 0 || $b[$key]['in'] / $b['n'] >= 0.5) {
                break;
            }
            $hours++;
        }

        return $hours;
    }

    // ─────────────────────────────────────────────────────────────
    // Skor parameter & indeks kesihatan
    // ─────────────────────────────────────────────────────────────

    /** Skor 0–100: 100 dalam optimum, 50 di tepi "acceptable", 0 di tepi "critical". */
    public function score(string $key, float $v): float
    {
        $p = $this->cfg['parameters'][$key];
        [$oLo, $oHi] = $p['optimal'];
        [$aLo, $aHi] = $p['acceptable'];
        [$cLo, $cHi] = $p['critical'];

        if ($v >= $oLo && $v <= $oHi) {
            return 100;
        }

        if ($v < $oLo) {
            if ($v >= $aLo) {
                return 100 - 50 * ($oLo - $v) / max(0.0001, $oLo - $aLo);
            }

            return $v <= $cLo ? 0 : 50 - 50 * ($aLo - $v) / max(0.0001, $aLo - $cLo);
        }

        if ($v <= $aHi) {
            return 100 - 50 * ($v - $oHi) / max(0.0001, $aHi - $oHi);
        }

        return $v >= $cHi ? 0 : 50 - 50 * ($v - $aHi) / max(0.0001, $cHi - $aHi);
    }

    protected function statusFor(float $score): string
    {
        return $score >= 100 ? 'optimal' : ($score >= 50 ? 'warning' : 'critical');
    }

    protected function idealText(string $key): string
    {
        [$lo, $hi] = $this->cfg['parameters'][$key]['optimal'];

        return $lo <= 0 ? "< {$hi}" : "{$lo} – {$hi}";
    }

    /**
     * Indeks kesihatan = 60% purata berwajaran + 40% skor terendah.
     * Komponen "terendah" menghalang satu parameter teruk disembunyikan oleh yang lain.
     */
    protected function healthIndex(array $scores, array $stats): array
    {
        $weighted = 0;
        foreach ($scores as $key => $s) {
            $weighted += $this->cfg['parameters'][$key]['weight'] * $s['score'];
        }
        $min = min(array_column($scores, 'score'));
        $score = 0.6 * $weighted + 0.4 * $min;

        $penalties = [];
        $st = $this->cfg['stability'];
        if (($stats['ph_level']['swing'] ?? 0) >= $st['ph_swing_24h']) {
            $score -= 5;
            $penalties[] = 'pH swing ' . $stats['ph_level']['swing'] . ' in 24 h';
        }
        if (($stats['temperature']['swing'] ?? 0) >= $st['temp_swing_24h']) {
            $score -= 5;
            $penalties[] = 'Temperature swing ' . $stats['temperature']['swing'] . ' °C in 24 h';
        }

        $score = (int) max(0, min(100, round($score)));

        return [
            'score'     => $score,
            'level'     => $score >= 85 ? 'Low Risk' : ($score >= 60 ? 'Moderate Risk' : 'High Risk'),
            'tone'      => $score >= 85 ? 'success' : ($score >= 60 ? 'warning' : 'danger'),
            'penalties' => $penalties,
        ];
    }

    /** Skor kesihatan bagi setiap jam (untuk jalur 24 jam). */
    protected function hourlyHealthStrip(array $hourly, Carbon $now): array
    {
        $byHour = collect($hourly)->keyBy('hour');
        $strip = [];

        for ($i = 23; $i >= 0; $i--) {
            $hour = $now->copy()->subHours($i)->startOfHour();
            $b = $byHour->get($hour->format('Y-m-d H:00:00'));

            $score = null;
            if ($b) {
                $s = [];
                foreach ($this->cfg['parameters'] as $key => $p) {
                    $s[$key] = $this->score($key, $b[$key]['avg']);
                }
                $weighted = array_sum(array_map(fn ($k) => $this->cfg['parameters'][$k]['weight'] * $s[$k], array_keys($s)));
                $score = (int) round(0.6 * $weighted + 0.4 * min($s));
            }

            $strip[] = [
                'label' => $hour->format('H:00'),
                'score' => $score,
                'temp'  => $b ? round($b['temperature']['avg'], 1) : null,
                'ph'    => $b ? round($b['ph_level']['avg'], 2) : null,
                'turb'  => $b ? round($b['turbidity']['avg'], 1) : null,
            ];
        }

        return $strip;
    }

    // ─────────────────────────────────────────────────────────────
    // Ammonia tidak terion (Emerson et al., 1975)
    // ─────────────────────────────────────────────────────────────

    protected function ammonia(float $temp, float $ph): array
    {
        $pKa = 0.09018 + 2729.92 / ($temp + 273.15);
        $fraction = 1 / (1 + pow(10, $pKa - $ph));
        $safeTan = $this->cfg['nh3_safe_mg_l'] / max(0.000001, $fraction);

        return [
            'fraction_pct' => round($fraction * 100, 2),
            'safe_tan'     => round(min($safeTan, 99), 2),
            'level'        => $fraction >= 0.10 ? 'high' : ($fraction >= 0.03 ? 'elevated' : 'low'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Indeks amaran awal Aeromonas (heuristik, bukan diagnosis)
    // ─────────────────────────────────────────────────────────────

    protected function diseaseRisk(array $current, int $health, array $stats, float $weight): array
    {
        $factors = [];
        $score = 0;

        $t = $current['temperature'];
        if ($t >= 32) {
            $score += 35;
            $factors[] = ['label' => 'Very warm water (' . round($t, 1) . ' °C)', 'points' => 35];
        } elseif ($t >= 30) {
            $score += 20;
            $factors[] = ['label' => 'Warm water (' . round($t, 1) . ' °C) favours Aeromonas growth', 'points' => 20];
        } elseif ($t < 22) {
            $score += 15;
            $factors[] = ['label' => 'Cold stress suppresses immunity', 'points' => 15];
        }

        $wq = (int) round((100 - $health) * 0.35);
        if ($wq > 0) {
            $score += $wq;
            $factors[] = ['label' => "Water quality stress (health {$health}/100)", 'points' => $wq];
        }

        if ($current['turbidity'] > $this->cfg['parameters']['turbidity']['optimal'][1]) {
            $score += 15;
            $factors[] = ['label' => 'High organic load / turbidity', 'points' => 15];
        }

        if (($stats['ph_level']['swing'] ?? 0) >= $this->cfg['stability']['ph_swing_24h']) {
            $score += 10;
            $factors[] = ['label' => 'Unstable pH over 24 h', 'points' => 10];
        }

        if ($weight < 20) {
            $score += 10;
            $factors[] = ['label' => 'Vulnerable life stage (< 20 g)', 'points' => 10];
        }

        $score = min(100, $score);

        return [
            'score'   => $score,
            'level'   => $score >= 60 ? 'High' : ($score >= 30 ? 'Moderate' : 'Low'),
            'tone'    => $score >= 60 ? 'danger' : ($score >= 30 ? 'warning' : 'success'),
            'factors' => $factors,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Model pertumbuhan TGC berdasarkan sejarah suhu sebenar
    // ─────────────────────────────────────────────────────────────

    /** Faktor pertumbuhan suhu 0–1 (1 dalam julat optimum). */
    public function tempGrowthFactor(float $t): float
    {
        $g = $this->cfg['growth'];
        [$oLo, $oHi] = $this->cfg['parameters']['temperature']['optimal'];

        if ($t <= $g['zero_growth_low'] || $t >= $g['zero_growth_high']) {
            return 0;
        }
        if ($t < $oLo) {
            return ($t - $g['zero_growth_low']) / ($oLo - $g['zero_growth_low']);
        }
        if ($t > $oHi) {
            return ($g['zero_growth_high'] - $t) / ($g['zero_growth_high'] - $oHi);
        }

        return 1;
    }

    /** Faktor kualiti air 0.5–1 daripada skor pH & kekeruhan. */
    protected function waterQualityFactor(float $ph, float $turbidity): float
    {
        return 0.5 + 0.5 * min($this->score('ph_level', $ph), $this->score('turbidity', $turbidity)) / 100;
    }

    protected function growth(PondDevice $device, Carbon $now): array
    {
        $g   = $this->cfg['growth'];
        $tgc = $g['tgc'];

        $w0     = max(0.1, (float) ($device->initial_weight ?: 5.0));
        $target = max($w0 + 1, (float) ($device->target_weight ?: 300.0));
        $stock  = (int) ($device->stocking_density ?: 0);

        $stockingAssumed = ! $device->stocking_date;
        $stocking = $device->stocking_date
            ? Carbon::parse($device->stocking_date)->startOfDay()
            : Carbon::parse(SensorData::where('device_id', $device->device_id)->min('timestamp') ?? $now)->startOfDay();
        $days = max(0, $stocking->diffInDays($now->copy()->startOfDay()));

        $daily = $this->scopeValid(SensorData::where('device_id', $device->device_id))
            ->where('timestamp', '>=', $stocking)
            ->selectRaw('DATE(`timestamp`) as d, AVG(temperature) t, AVG(ph_level) p, AVG(turbidity) u')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        // Simulasi hari demi hari sejak stok
        $cube = pow($w0, 1 / 3);
        $idealCube = $cube;
        $survival = 1.0;
        $actual = [[0, round($w0, 1)]];
        $ideal  = [[0, round($w0, 1)]];
        $sensorDays = 0;
        $recentEffective = [];
        $prevWeight = $w0;

        for ($d = 1; $d <= $days; $d++) {
            $date = $stocking->copy()->addDays($d - 1)->toDateString();
            $row = $daily->get($date);

            if ($row) {
                $t = (float) $row->t;
                $fq = $this->waterQualityFactor((float) $row->p, (float) $row->u);
                $sensorDays++;
            } else {
                $t = $g['default_temp'];
                $fq = 1.0;
            }

            $f = $this->tempGrowthFactor($t) * $fq;
            $prevWeight = pow($cube, 3);
            $cube += $tgc * $t * $f;
            $idealCube += $tgc * $g['ideal_temp'];
            $survival *= 1 - ($g['base_daily_mortality'] + $g['stress_daily_mortality'] * (1 - $f));

            if ($row) {
                $recentEffective[] = $t * $f;
            }

            $actual[] = [$d, round(pow($cube, 3), 1)];
            $ideal[]  = [$d, round(pow($idealCube, 3), 1)];
        }

        $weight = pow($cube, 3);

        // Unjuran: guna purata keadaan 7 hari sensor terakhir
        $recentEffective = array_slice($recentEffective, -7);
        $projRate = $recentEffective
            ? array_sum($recentEffective) / count($recentEffective)
            : $g['default_temp'];
        $projBasis = $recentEffective ? 'last ' . count($recentEffective) . ' day(s) of sensor data' : 'assumed 28 °C';

        $projected = [[$days, round($weight, 1)]];
        $daysToHarvest = 0;
        if ($weight < $target) {
            if ($projRate <= 0) {
                $daysToHarvest = null; // tiada pertumbuhan pada keadaan semasa
            } else {
                $c = $cube;
                for ($i = 1; $i <= $g['max_projection_days']; $i++) {
                    $c += $tgc * $projRate;
                    $projected[] = [$days + $i, round(pow($c, 3), 1)];
                    if (pow($c, 3) >= $target) {
                        $daysToHarvest = $i;
                        break;
                    }
                }
                if ($daysToHarvest === 0) {
                    $daysToHarvest = null;
                }
            }
        }

        // Lanjutkan lengkung ideal hingga sasaran supaya carta lengkap
        $idealDaysToTarget = (int) ceil((pow($target, 1 / 3) - pow($w0, 1 / 3)) / ($tgc * $g['ideal_temp']));
        $endDay = max($idealDaysToTarget, end($projected)[0]);
        for ($d = $days + 1; $d <= $endDay; $d++) {
            $idealCube += $tgc * $g['ideal_temp'];
            $ideal[] = [$d, round(min(pow($idealCube, 3), $target * 1.15), 1)];
        }

        $idealNow = $ideal[$days][1] ?? $weight;
        $alive = (int) round($stock * $survival);
        $stage = $this->stageFor($weight);

        return [
            'stocking_date'      => $stocking,
            'stocking_assumed'   => $stockingAssumed,
            'days'               => $days,
            'sensor_days'        => $sensorDays,
            'initial_weight'     => round($w0, 1),
            'target_weight'      => round($target, 1),
            'current_weight'     => round($weight, 1),
            'daily_gain'         => round($weight - $prevWeight, 2),
            'sgr'                => $days > 0 ? round((log($weight) - log($w0)) / $days * 100, 2) : 0,
            'ideal_weight_now'   => round($idealNow, 1),
            'performance_pct'    => (int) round($weight / max(0.1, $idealNow) * 100),
            'progress_pct'       => (int) round(min(100, $weight / $target * 100)),
            'days_to_harvest'    => $daysToHarvest,
            'harvest_date'       => $daysToHarvest === null ? null : $now->copy()->addDays($daysToHarvest),
            'ideal_days_total'   => $idealDaysToTarget,
            'projection_basis'   => $projBasis,
            'stock'              => $stock,
            'survival_pct'       => round($survival * 100, 1),
            'alive'              => $alive,
            'biomass_kg'         => round($alive * $weight / 1000, 2),
            'stage'              => $stage,
            'series'             => [
                'actual'    => $this->downsample($actual),
                'projected' => $this->downsample($projected),
                'ideal'     => $this->downsample($ideal),
            ],
        ];
    }

    public function stageFor(float $weight): array
    {
        foreach ($this->cfg['feeding'] as $i => $row) {
            if ($row['max_weight'] === null || $weight < $row['max_weight']) {
                return $row + ['index' => $i];
            }
        }

        return end($this->cfg['feeding']);
    }

    // ─────────────────────────────────────────────────────────────
    // Risiko boleh diterangkan + pelan tindakan
    // ─────────────────────────────────────────────────────────────

    protected function risks(array $snap, array $scores, array $stats, array $hourly, ?array $ammonia, ?array $disease, ?array $ml): array
    {
        $risks = [];
        $p = $this->cfg['parameters'];

        // Sambungan peranti
        if (in_array($snap['connection'], ['offline', 'stale', 'no-data'])) {
            $risks[] = [
                'key'      => 'connection',
                'title'    => $snap['connection'] === 'no-data' ? 'No sensor data received' : 'Sensor ' . ($snap['connection'] === 'offline' ? 'offline' : 'delayed'),
                'severity' => $snap['connection'] === 'stale' ? 'watch' : 'critical',
                'category' => 'System',
                'current'  => $snap['latest_at'] ? 'Last reading ' . $snap['latest_at']->diffForHumans() : 'Never',
                'ideal'    => 'Reading every 5 s',
                'evidence' => 'The analysis below uses the last known readings.',
                'impact'   => 'Without live data, sudden water-quality changes cannot be detected in time.',
                'actions'  => [
                    'Check the ESP32 power supply and 2.4 GHz Wi-Fi connection.',
                    'Confirm the MQTT broker is reachable and the device MAC is registered to this pond.',
                    'Open the ESP32 serial monitor and look for reconnect errors.',
                ],
                'feed'     => 1.0,
            ];
        }

        if ($snap['invalid_pct'] >= 5) {
            $risks[] = [
                'key'      => 'sensor-fault',
                'title'    => 'Sensor fault readings',
                'severity' => 'watch',
                'category' => 'System',
                'current'  => $snap['invalid_24h'] . ' of ' . $snap['readings_24h'] . ' readings (' . $snap['invalid_pct'] . '%)',
                'ideal'    => '< 5% invalid',
                'evidence' => 'Physically impossible values were filtered out of this analysis.',
                'impact'   => 'Faulty probes can hide real problems or raise false alarms.',
                'actions'  => [
                    'Check the DS18B20 wiring and 4.7 kΩ pull-up resistor (−127 °C means disconnected).',
                    'Recalibrate the pH probe with pH 4.0 and 7.0 buffer solutions.',
                    'Clean probe surfaces of algae and slime.',
                ],
                'feed'     => 1.0,
            ];
        }

        if ($scores) {
            $evidence = function (string $key) use ($scores) {
                $s = $scores[$key];
                $parts = [];
                if ($s['hours_out'] > 0) {
                    $parts[] = "out of range for ~{$s['hours_out']} h";
                }
                if ($s['in_range'] !== null) {
                    $parts[] = "{$s['in_range']}% of last 24 h in range";
                }

                return ucfirst(implode(' · ', $parts) ?: 'Just started');
            };

            // Suhu
            $t = $scores['temperature'];
            if ($t['value'] > $p['temperature']['optimal'][1]) {
                $crit = $t['status'] === 'critical';
                $risks[] = [
                    'key' => 'temp-high', 'title' => 'High water temperature', 'category' => 'Temperature',
                    'severity' => $crit ? 'critical' : 'warning',
                    'current' => "{$t['value']} °C", 'ideal' => "{$t['ideal']} °C", 'evidence' => $evidence('temperature'),
                    'impact' => 'Warm water holds less dissolved oxygen while fish need more. Above 32 °C appetite drops and Aeromonas outbreaks become more likely.',
                    'actions' => [
                        'Run aerators or air pumps continuously, especially 2 a.m.–7 a.m. and on hot afternoons.',
                        'Shade 50–70% of the tank or canvas pond surface with shade netting.',
                        'Exchange 10–20% of the water with cooler source water, preferably in the evening.',
                        'Feed only in the cool morning and evening.',
                        'Avoid netting, grading or moving fish until the temperature is back below 32 °C.',
                    ],
                    'feed' => $crit ? 0.5 : 0.7,
                ];
            } elseif ($t['value'] < $p['temperature']['optimal'][0]) {
                $crit = $t['status'] === 'critical';
                $risks[] = [
                    'key' => 'temp-low', 'title' => 'Low water temperature', 'category' => 'Temperature',
                    'severity' => $crit ? 'critical' : 'warning',
                    'current' => "{$t['value']} °C", 'ideal' => "{$t['ideal']} °C", 'evidence' => $evidence('temperature'),
                    'impact' => 'Metabolism and appetite slow down. Uneaten feed rots and fouls the water, and cold stress weakens immunity.',
                    'actions' => [
                        'Remove uneaten feed after 30 minutes.',
                        'Cover the tank at night with a canvas or plastic sheet to keep heat in.',
                        'Raise the water level to buffer temperature swings.',
                        'Delay stocking new fry until the water is above 26 °C.',
                    ],
                    'feed' => $crit ? 0.4 : 0.7,
                ];
            }

            // pH
            $ph = $scores['ph_level'];
            if ($ph['value'] < $p['ph_level']['optimal'][0]) {
                $crit = $ph['status'] === 'critical';
                $risks[] = [
                    'key' => 'ph-low', 'title' => 'Acidic water (low pH)', 'category' => 'pH',
                    'severity' => $crit ? 'critical' : 'warning',
                    'current' => "pH {$ph['value']}", 'ideal' => "pH {$ph['ideal']}", 'evidence' => $evidence('ph_level'),
                    'impact' => 'Acidic water damages gills, increases mucus and slows growth. Below pH 5 mortality rises sharply.',
                    'actions' => [
                        'Apply agricultural lime (CaCO₃) at 10–20 g per m³, pre-dissolved and spread evenly. Re-test after 6–12 h.',
                        'Test total alkalinity. If below 50 mg/L CaCO₃, add lime gradually over several days.',
                        'Exchange about 20% of the water with source water of known neutral pH.',
                    ],
                    'feed' => $crit ? 0.5 : 0.7,
                ];
            } elseif ($ph['value'] > $p['ph_level']['optimal'][1]) {
                $crit = $ph['status'] === 'critical';
                $risks[] = [
                    'key' => 'ph-high', 'title' => 'Alkaline water (high pH)', 'category' => 'pH',
                    'severity' => $crit ? 'critical' : 'warning',
                    'current' => "pH {$ph['value']}", 'ideal' => "pH {$ph['ideal']}", 'evidence' => $evidence('ph_level'),
                    'impact' => 'High pH turns ammonia into its toxic un-ionised form (NH₃). Afternoon pH peaks usually come from algae blooms.',
                    'actions' => [
                        'Exchange 20–30% of the water with clean source water.',
                        'Withhold feed for 12–24 h to cut ammonia production.',
                        'Reduce algae: shade the pond and stop any fertilising.',
                        'Test total ammonia (TAN) and compare it with the safe limit on the ammonia card.',
                    ],
                    'feed' => $crit ? 0.0 : 0.5,
                ];
            }

            // Kekeruhan
            $u = $scores['turbidity'];
            if ($u['value'] > $p['turbidity']['optimal'][1]) {
                $crit = $u['status'] === 'critical';
                $risks[] = [
                    'key' => 'turbidity', 'title' => 'Murky water (high turbidity)', 'category' => 'Turbidity',
                    'severity' => $crit ? 'critical' : 'warning',
                    'current' => "{$u['value']} NTU", 'ideal' => "{$u['ideal']} NTU", 'evidence' => $evidence('turbidity'),
                    'impact' => 'Suspended solids clog gills and signal excess organic waste, uneaten feed or a dense algae bloom, which bacteria thrive on.',
                    'actions' => [
                        'Siphon bottom sludge and uneaten feed.',
                        'Clean or backwash the filter media.',
                        'Exchange 20–30% of the water.',
                        'Check that feed is eaten within 15–20 minutes; reduce the ration if not.',
                    ],
                    'feed' => $crit ? 0.6 : 0.8,
                ];
            }

            // Perubahan suhu pantas: median semasa berbanding purata ~1–2 jam lalu
            if (count($hourly) >= 2) {
                $prev = $hourly[count($hourly) - 2]['temperature']['avg'];
                $delta = round($t['value'] - $prev, 1);
                if (abs($delta) >= $this->cfg['stability']['temp_change_per_hour']) {
                    $risks[] = [
                        'key' => 'temp-shock', 'title' => 'Rapid temperature change', 'category' => 'Temperature',
                        'severity' => 'watch',
                        'current' => ($delta > 0 ? '+' : '') . "{$delta} °C in ~1 h", 'ideal' => '< ' . $this->cfg['stability']['temp_change_per_hour'] . ' °C per hour',
                        'evidence' => 'Compared with the previous hour\'s average.',
                        'impact' => 'Sudden temperature changes stress tilapia and suppress immunity, even within the normal range.',
                        'actions' => [
                            'Check for direct sun on the tank, or cold inflow or rain.',
                            'Add new water slowly, from a source close to the pond temperature.',
                            'Increase aeration until the temperature stabilises.',
                        ],
                        'feed' => 0.9,
                    ];
                }
            }

            // Ayunan pH 24 jam
            if (($stats['ph_level']['swing'] ?? 0) >= $this->cfg['stability']['ph_swing_24h']) {
                $risks[] = [
                    'key' => 'ph-swing', 'title' => 'Unstable pH (large daily swing)', 'category' => 'pH',
                    'severity' => 'watch',
                    'current' => 'pH ' . $stats['ph_level']['min'] . ' → ' . $stats['ph_level']['max'], 'ideal' => 'Swing < ' . $this->cfg['stability']['ph_swing_24h'],
                    'evidence' => 'Range over the last 24 h.',
                    'impact' => 'Large daily pH swings mean weak buffering (low alkalinity) or a heavy algae bloom. Both stress the fish.',
                    'actions' => [
                        'Test total alkalinity; aim for 50–150 mg/L CaCO₃.',
                        'Apply agricultural lime to increase buffering capacity.',
                        'Reduce the algae load with a partial water exchange or shading.',
                    ],
                    'feed' => 0.9,
                ];
            }
        }

        // Ammonia
        if ($ammonia && $ammonia['level'] === 'high') {
            $risks[] = [
                'key' => 'ammonia', 'title' => 'High ammonia toxicity potential', 'category' => 'Ammonia',
                'severity' => 'warning',
                'current' => $ammonia['fraction_pct'] . '% as toxic NH₃', 'ideal' => 'Keep TAN < ' . $ammonia['safe_tan'] . ' mg/L',
                'evidence' => 'Calculated from current pH and temperature (Emerson et al., 1975).',
                'impact' => 'At this pH and temperature a large share of any ammonia is toxic NH₃, which burns gills and stunts growth.',
                'actions' => [
                    'Test total ammonia nitrogen (TAN) with a test kit today.',
                    'Halve or withhold feed until TAN is confirmed below the safe limit.',
                    'Exchange 20–30% of the water if TAN exceeds the limit.',
                ],
                'feed' => 0.6,
            ];
        }

        // Aeromonas
        if ($disease && $disease['level'] !== 'Low') {
            $high = $disease['level'] === 'High';
            $risks[] = [
                'key' => 'aeromonas', 'title' => 'Aeromonas infection risk', 'category' => 'Disease',
                'severity' => $high ? 'critical' : 'watch',
                'current' => "Risk index {$disease['score']}/100", 'ideal' => '< 30',
                'evidence' => implode(' · ', array_column($disease['factors'], 'label')),
                'impact' => 'Conditions favour Motile Aeromonas Septicaemia (A. hydrophila / A. dhakensis). Warm water, poor water quality and stress are the main triggers. This is an early warning, not a diagnosis.',
                'actions' => [
                    'Check fish twice a day for red spots or bleeding, fin or tail rot, swollen belly, pop-eye, lethargy or gasping at the surface.',
                    'Remove dead or weak fish immediately and dispose of them away from the pond.',
                    'Increase aeration and avoid handling, grading or transport.',
                    'Disinfect nets and equipment between tanks.',
                    'If sick fish appear, contact the Department of Fisheries (DOF) or a fish veterinarian before using any antibiotics.',
                ],
                'feed' => $high ? 0.5 : 0.8,
            ];
        }

        // Model ML sebagai pendapat kedua
        $ruleRisk = collect($risks)->contains(fn ($r) => in_array($r['severity'], ['critical', 'warning']) && $r['category'] !== 'System');
        if ($ml && $ml['risk_status'] === 'At Risk' && ! $ruleRisk) {
            $risks[] = [
                'key' => 'ml', 'title' => 'ML model flagged a risk', 'category' => 'AI',
                'severity' => 'watch',
                'current' => 'Random Forest: At Risk', 'ideal' => 'Stable',
                'evidence' => 'The rule-based checks did not find a parameter out of range, so this is a borderline combination.',
                'impact' => 'The classifier learned that this combination of temperature, pH and turbidity often precedes problems.',
                'actions' => [
                    'Watch the next few hours of readings closely.',
                    'Do a quick visual check of fish behaviour and feeding response.',
                ],
                'feed' => 1.0,
            ];
        }

        $rank = ['critical' => 0, 'warning' => 1, 'watch' => 2];
        usort($risks, fn ($a, $b) => $rank[$a['severity']] <=> $rank[$b['severity']]);

        return $risks;
    }

    // ─────────────────────────────────────────────────────────────
    // Cadangan pemakanan
    // ─────────────────────────────────────────────────────────────

    protected function feeding(array $growth, array $risks): array
    {
        $stage = $growth['stage'];
        $baseKg = $growth['biomass_kg'] * $stage['rate'] / 100;

        $multiplier = 1.0;
        $reason = null;
        foreach ($risks as $r) {
            if ($r['feed'] < $multiplier) {
                $multiplier = $r['feed'];
                $reason = $r['title'];
            }
        }

        $adjustedKg = $baseKg * $multiplier;
        $meals = $stage['meals'];

        return [
            'stage'        => $stage,
            'base_kg'      => round($baseKg, 2),
            'adjusted_kg'  => round($adjustedKg, 2),
            'per_meal_g'   => $meals > 0 ? (int) round($adjustedKg * 1000 / $meals) : 0,
            'multiplier'   => $multiplier,
            'reason'       => $reason,
            'withhold'     => $multiplier <= 0,
            'table'        => $this->cfg['feeding'],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // ML (Random Forest) — keputusan terkini sebagai perbandingan
    // ─────────────────────────────────────────────────────────────

    protected function latestMlAnalysis(PondDevice $device): ?array
    {
        $a = AiAnalysis::whereHas('sensorData', fn ($q) => $q->where('device_id', $device->device_id))
            ->latest('data_id')
            ->first();

        return $a ? [
            'risk_status'      => $a->risk_status,
            'estimated_weight' => (float) $a->estimated_weight,
            'at'               => $a->created_at ? Carbon::parse($a->created_at) : null,
        ] : null;
    }

    protected function insights(array $growth, ?array $ml, array $risks, array $snap, ?array $health): array
    {
        $out = [];

        $perf = $growth['performance_pct'];
        $out[] = [
            'tone'  => $perf >= 90 ? 'success' : ($perf >= 75 ? 'warning' : 'danger'),
            'title' => "Growing at {$perf}% of ideal",
            'text'  => "Estimated {$growth['current_weight']} g vs {$growth['ideal_weight_now']} g under ideal conditions on day {$growth['days']}. SGR {$growth['sgr']}%/day.",
        ];

        if ($growth['sensor_days'] < $growth['days']) {
            $missing = $growth['days'] - $growth['sensor_days'];
            $out[] = [
                'tone'  => 'info',
                'title' => "{$missing} day(s) without sensor data",
                'text'  => 'The growth model assumed ' . config('aquaculture.growth.default_temp') . ' °C and good water on those days. Keep the device online for a more accurate estimate.',
            ];
        }

        if ($growth['stocking_assumed']) {
            $out[] = [
                'tone'  => 'warning',
                'title' => 'Stocking date not set',
                'text'  => 'Using the first sensor reading instead. Set the stocking date in Pond Devices for an accurate growth curve.',
            ];
        }

        if ($ml && $snap['current']) {
            $mlRisk = $ml['risk_status'] === 'At Risk';
            $ruleRisk = $health && $health['score'] < 85;
            $out[] = [
                'tone'  => $mlRisk === $ruleRisk ? 'success' : 'info',
                'title' => $mlRisk === $ruleRisk ? 'ML model agrees with rule-based analysis' : 'ML model and rules disagree',
                'text'  => 'Random Forest classifier: ' . $ml['risk_status'] . '. Rule-based health index: ' . ($health['score'] ?? '—') . '/100.',
            ];
        }

        if (! collect($risks)->contains(fn ($r) => $r['severity'] !== 'watch') && $snap['current']) {
            $out[] = [
                'tone'  => 'success',
                'title' => 'All key parameters within ideal range',
                'text'  => 'Keep the current feeding plan and maintenance routine.',
            ];
        }

        return $out;
    }

    // ─────────────────────────────────────────────────────────────
    // Pembantu
    // ─────────────────────────────────────────────────────────────

    protected function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    /** Kurangkan titik siri kepada ~60 untuk carta. */
    protected function downsample(array $points, int $max = 60): array
    {
        $n = count($points);
        if ($n <= $max) {
            return $points;
        }

        $step = ($n - 1) / ($max - 1);
        $out = [];
        for ($i = 0; $i < $max; $i++) {
            $out[] = $points[(int) round($i * $step)];
        }

        return $out;
    }
}
