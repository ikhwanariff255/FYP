<?php

namespace App\Observers;

use App\Models\SensorData;
use App\Models\AiAnalysis;
use App\Services\AiPredictionService;
use Illuminate\Support\Facades\Log;

class SensorDataObserver
{
    public function created(SensorData $sensorData): void
    {
        // PENYELESAIAN ISU 1: Hentikan proses jika analisis AI sudah wujud untuk data ini
        if (AiAnalysis::where('data_id', $sensorData->data_id)->exists()) {
            return; 
        }

        try {
            $aiService = new AiPredictionService();
            $result = $aiService->predict(
                (float)$sensorData->temperature, 
                (float)$sensorData->ph_level, 
                (float)$sensorData->turbidity
            );

            if ($result) {
                AiAnalysis::create([
                    'data_id' => $sensorData->data_id,
                    'risk_status' => $result['risk_status'],
                    'estimated_weight' => $result['estimated_weight'],
                    'created_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Ralat pada Observer AI: " . $e->getMessage());
        }
    }
}