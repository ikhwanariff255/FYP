<?php

namespace App\Filament\Resources\SensorDataResource\Pages;

use App\Filament\Resources\SensorDataResource;
use App\Models\AiAnalysis;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class CreateSensorData extends CreateRecord
{
    protected static string $resource = SensorDataResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $sensorData = static::getModel()::create($data);

        $temp = $data['temperature'];
        $ph = $data['ph_level'];
        $turb = $data['turbidity'];
        $recordedTimestamp = $data['timestamp'] ?? now();

        // 1. Dapatkan Maklumat Kolam
        $device = $sensorData->device;
        $initialWeight = (float) ($device->initial_weight ?? 5.0); // Tarik berat awal kolam
        
        $stockingDate = $device->stocking_date ? Carbon::parse($device->stocking_date) : Carbon::now();
        $recordedDate = Carbon::parse($recordedTimestamp);
        
        // 2. Kira Umur Ikan (Berdasarkan tarikh rekod dimasukkan)
        $daysInCulture = max(1, $stockingDate->diffInDays($recordedDate));

        $pythonPath = 'C:\laragon\bin\python\python-3.10\python.exe'; 
        $scriptPath = base_path('predict.py');

        // 3. Hantar 5 parameter ke Python!
        $process = new Process([
            $pythonPath, 
            $scriptPath, 
            (string)$temp, 
            (string)$ph, 
            (string)$turb, 
            (string)$daysInCulture,
            (string)$initialWeight // <-- Hantar Parameter Ke-5
        ]);
        
        $process->setEnv([
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\Windows',
            'TEMP' => getenv('TEMP') ?: 'C:\Windows\Temp',
        ]);
        
        $process->run();

        $aiOutput = '';
        if ($process->isSuccessful()) {
            $aiOutput = trim($process->getOutput());
        }

        $lines = explode("\n", $aiOutput);
        $cleanOutput = '';
        foreach ($lines as $line) {
            if (str_contains($line, '|')) {
                $cleanOutput = trim($line);
                break;
            }
        }

        $parts = explode('|', $cleanOutput);
        $riskStatus = !empty($parts[0]) && $parts[0] !== 'Error' ? trim($parts[0]) : 'Stable';
        $estimatedWeight = isset($parts[1]) ? floatval($parts[1]) : $initialWeight; // Fallback ke berat awal

        // 4. Simpan ke database
        AiAnalysis::create([
            'data_id' => $sensorData->getKey(),
            'risk_status' => $riskStatus,
            'estimated_weight' => $estimatedWeight,
            'days_in_culture' => $daysInCulture,
            'created_at' => $recordedTimestamp,
            'updated_at' => $recordedTimestamp,
        ]);

        return $sensorData;
    }
}