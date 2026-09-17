<?php

namespace App\Filament\Resources\SensorDataResource\Pages;

use App\Filament\Resources\SensorDataResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\AiAnalysis;

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

        // Guna laluan penuh python jika perlu, cth: 'C:\Python39\python.exe' atau 'python'
        $pythonPath = 'python'; 
        $scriptPath = base_path('python/predict.py');

        // Jalankan perintah terminal dan tangkap output AI
        $command = escapeshellcmd("$pythonPath \"$scriptPath\" $temp $ph $turb");
        $aiOutput = trim(shell_exec($command) ?? '');

        // Pecahkan hasil output AI (Format: Status|Berat)
        $parts = explode('|', $aiOutput);
        
        // STATUS RISIKO DIJANA 100% OLEH AI MODEL .pkl
        $riskStatus = !empty($parts[0]) ? trim($parts[0]) : 'Stable';
        $rawPredictedWeight = isset($parts[1]) ? floatval($parts[1]) : 50.0;

        // Logik Hibrid Berat (Hanya untuk kawal graf supaya tak melompat ke 280g)
        $previousAnalysis = AiAnalysis::whereHas('sensorData', function($q) use ($data) {
            $q->where('device_id', $data['device_id']);
        })->latest('data_id')->first();

        $previousWeight = $previousAnalysis ? $previousAnalysis->estimated_weight : 50.0;

        // Jika AI Classifier meramalkan 'At Risk', berat dikekalkan/stabil. Jika Stable, bertambah logik.
        if ($riskStatus === 'At Risk' || $riskStatus === 'Critical') {
            $finalEstimatedWeight = $previousWeight; 
        } else {
            $finalEstimatedWeight = $previousWeight + 1.5; 
        }

        // Simpan keputusan AI tulen ke pangkalan data
        AiAnalysis::create([
            'data_id' => $sensorData->getKey(),
            'risk_status' => $riskStatus,
            'estimated_weight' => $finalEstimatedWeight,
            'created_at' => $recordedTimestamp,
            'updated_at' => $recordedTimestamp,
        ]);

        return $sensorData;
    }
}