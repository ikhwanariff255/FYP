<?php

/*
|--------------------------------------------------------------------------
| Red Hybrid Tilapia (Oreochromis spp.) — Water Quality & Growth Settings
|--------------------------------------------------------------------------
| Satu sumber nilai ambang untuk semua analisis. Tukar di sini sahaja.
*/

return [

    /*
    | Band parameter air.
    |  optimal    : skor 100
    |  acceptable : skor turun secara linear ke 50 di tepi band ini
    |  critical   : skor turun ke 0 di nilai ini
    */
    'parameters' => [
        'temperature' => [
            'label'      => 'Temperature',
            'unit'       => '°C',
            'weight'     => 0.35,
            'optimal'    => [26.0, 32.0],
            'acceptable' => [22.0, 35.0],
            'critical'   => [18.0, 38.0],
        ],
        'ph_level' => [
            'label'      => 'pH Level',
            'unit'       => '',
            'weight'     => 0.40,
            'optimal'    => [6.5, 8.5],
            'acceptable' => [6.0, 9.0],
            'critical'   => [5.0, 10.0],
        ],
        'turbidity' => [
            'label'      => 'Turbidity',
            'unit'       => 'NTU',
            'weight'     => 0.25,
            'optimal'    => [0.0, 50.0],
            'acceptable' => [0.0, 100.0],
            'critical'   => [0.0, 200.0],
        ],
    ],

    // Bacaan di luar julat ini dianggap kerosakan sensor dan diabaikan.
    // DS18B20 memulangkan -127 (terputus) atau 85 (reset kuasa).
    'plausible' => [
        'temperature' => [5.0, 45.0],
        'ph_level'    => [0.5, 14.0],
        'turbidity'   => [0.0, 3000.0],
    ],
    'sensor_fault_values' => [
        'temperature' => [-127.0, 85.0],
    ],

    // Status sambungan peranti (saat sejak bacaan terakhir)
    'freshness' => [
        'online_seconds' => 120,
        'stale_seconds'  => 1800,
    ],

    // Perubahan pantas yang menyebabkan tekanan
    'stability' => [
        'temp_change_per_hour' => 2.0,  // °C dalam ~1 jam -> kejutan suhu
        'ph_swing_24h'         => 1.0,  // julat pH harian
        'temp_swing_24h'       => 4.0,  // julat suhu harian
    ],

    /*
    | Model pertumbuhan Thermal-unit Growth Coefficient (Iwama & Tautz, 1981):
    |   W_t^(1/3) = W_0^(1/3) + TGC × Σ (T_hari × f_persekitaran)
    | TGC 0.0015 memberi ~5 g -> ~500 g dalam ~150 hari pada 28 °C.
    | Kalibrasi semula menggunakan berat persampelan sebenar dari kolam.
    */
    'growth' => [
        'tgc'               => 0.0015,
        'default_temp'      => 28.0, // hari tanpa data sensor
        'ideal_temp'        => 29.0, // untuk lengkung "keadaan ideal"
        'zero_growth_low'   => 18.0, // f_T = 0 di bawah ini
        'zero_growth_high'  => 38.0, // f_T = 0 di atas ini
        'max_projection_days' => 365,
        // Kematian harian asas dan tambahan apabila persekitaran tertekan
        'base_daily_mortality'   => 0.0004,
        'stress_daily_mortality' => 0.006,
    ],

    // Jadual pemakanan berdasarkan berat purata ikan (g)
    'feeding' => [
        ['stage' => 'Fry',        'max_weight' => 5,    'rate' => 8.0, 'protein' => '40–45%', 'meals' => 5, 'pellet' => 'Powder / crumble'],
        ['stage' => 'Fingerling', 'max_weight' => 20,   'rate' => 5.0, 'protein' => '35–40%', 'meals' => 4, 'pellet' => '1.0–1.5 mm'],
        ['stage' => 'Juvenile',   'max_weight' => 100,  'rate' => 3.5, 'protein' => '32–35%', 'meals' => 3, 'pellet' => '2 mm'],
        ['stage' => 'Grow-out',   'max_weight' => 250,  'rate' => 2.5, 'protein' => '28–32%', 'meals' => 3, 'pellet' => '3 mm'],
        ['stage' => 'Finisher',   'max_weight' => null, 'rate' => 1.8, 'protein' => '25–28%', 'meals' => 2, 'pellet' => '4 mm'],
    ],

    // Had selamat ammonia tidak terion (NH3) untuk tilapia, mg/L
    'nh3_safe_mg_l' => 0.05,
];
