<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PondDevice;
use App\Models\SensorData;
use App\Models\AiAnalysis;
use Carbon\Carbon;

class KolamBaruSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dapatkan atau cipta peranti Kolam Baru B1 dalam jadual POND_DEVICE
        // Anggap user_id = 1 adalah admin. Ubah mac_address mengikut keperluan.
        $device = PondDevice::firstOrCreate(
            ['pond_name' => 'TANGKI 2'],
            ['mac_address' => 'AA:BB:CC:DD:EE:TT', 'user_id' => 1] 
        );

        // 2. Senarai data dengan tarikh yang dah lepas (Ogos 2026)
        $historicalData = [
            ['temp' => 28.0, 'ph' => 7.5, 'turb' => 10.0, 'risk' => 'Stable', 'weight' => 50.2, 'date' => '2026-08-01 08:00:00'],
            ['temp' => 28.1, 'ph' => 7.4, 'turb' => 12.5, 'risk' => 'Stable', 'weight' => 52.5, 'date' => '2026-08-02 08:00:00'],
            ['temp' => 27.9, 'ph' => 7.6, 'turb' => 11.0, 'risk' => 'Stable', 'weight' => 54.8, 'date' => '2026-08-03 08:00:00'],
            ['temp' => 28.1, 'ph' => 7.5, 'turb' => 13.0, 'risk' => 'Stable', 'weight' => 57.1, 'date' => '2026-08-04 08:00:00'],
            ['temp' => 27.8, 'ph' => 7.3, 'turb' => 14.5, 'risk' => 'Stable', 'weight' => 59.3, 'date' => '2026-08-05 08:00:00'],
        ];

        foreach ($historicalData as $row) {
            // 3. Masukkan rekod fizikal ke dalam jadual SENSOR_DATA
            $sensor = SensorData::create([
                'device_id' => $device->id ?? $device->device_id, 
                'temperature' => $row['temp'],
                'ph_level' => $row['ph'],
                'turbidity' => $row['turb'],
                'timestamp' => $row['date'],
            ]);

            // 4. Masukkan rekod unjuran ke dalam jadual AI_ANALYSIS dan hubungkan dengan SENSOR_DATA
            AiAnalysis::create([
                'data_id' => $sensor->id ?? $sensor->data_id, 
                'risk_status' => $row['risk'],
                'estimated_weight' => $row['weight'],
                'created_at' => $row['date'],
            ]);
        }
    }
}