<?php

namespace App\Observers;

use App\Models\SensorData;
use App\Models\AiAnalysis;
use App\Services\AiPredictionService;

class SensorDataObserver
{
    public function created(SensorData $sensorData): void
    {
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
    }
}