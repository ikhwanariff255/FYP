<?php

namespace App\Services;

class AiPredictionService
{
    public function predict($temperature, $phLevel, $turbidity, $device)
    {
        // KITA PAKSA NILAI INI UNTUK UJI SAMA ADA FAIL INI BETUL-BETUL DIPANGGIL ATAU TIDAK
        return [
            'risk_status' => 'Stable',
            'estimated_weight' => 99.99, // Jika masuk 99.99 dalam DB, maksudnya fail ini betul. Kalau masih 51.50, Laravel guna fail lain!
        ];
    }
}