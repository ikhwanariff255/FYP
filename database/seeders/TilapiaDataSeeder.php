<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PondDevice;
use App\Models\SensorData;
use App\Models\AiAnalysis;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class TilapiaDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan ada sekurang-kurangnya satu peranti kolam (PondDevice) untuk diikat dengan data sensor
        $device = PondDevice::firstOrCreate(
            ['mac_address' => 'AA:BB:CC:DD:EE:FF'],
            [
                'user_id' => 1, // Pastikan ID user 1 wujud (admin kita)
                'pond_name' => 'Kolam Utama A1',
            ]
        );

        $path = database_path('seeders/Data_Model_IoTMLCQ_2024.xlsx');
        
        if (!file_exists($path)) {
            $this->command->error("Fail Excel tidak dijumpai di direktori seeders!");
            return;
        }

        $this->command->info("Sedang membaca fail Excel... Sila tunggu sebentar.");

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Buang baris pertama (header)
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            // Rujuk susunan kolum dalam Excel:
            // Indeks 0 = Datetime, Indeks 5 = Temperature, Indeks 7 = pH, Indeks 8 = Turbidity
            // Indeks 2 = Average Fish Weight (g), Indeks 27 = Health Status
            $datetime = $row[0] ?? null;
            if (!$datetime) continue;

            $temperature = $row[5] ?? 28.0;
            $phLevel = $row[7] ?? 7.0;
            $turbidity = $row[8] ?? 5.0;
            $avgWeight = $row[2] ?? 100.0;
            $healthStatus = $row[27] ?? 'Normal';

            // 2. Simpan ke jadual sensor_data
            $sensorData = SensorData::create([
                'device_id' => $device->device_id,
                'temperature' => $temperature,
                'ph_level' => $phLevel,
                'turbidity' => $turbidity,
                'timestamp' => Carbon::parse($datetime),
            ]);

            // 3. Simpan ke jadual ai_analysis (berhubung dengan data_id yang baru)
            AiAnalysis::create([
                'data_id' => $sensorData->data_id,
                'risk_status' => $healthStatus,
                'estimated_weight' => $avgWeight,
                'created_at' => Carbon::parse($datetime),
            ]);

            $count++;
            // Hadkan kepada 500 rekod pertama dahulu untuk ujian pantas (boleh buang if ni kalau nak import semua)
            if ($count >= 500) {
                break;
            }
        }

        $this->command->info("Berjaya import $count data sensor dan analisis AI!");
    }
}