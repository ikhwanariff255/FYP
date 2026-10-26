<?php

namespace App\Observers;

use App\Models\SensorData;
use App\Models\AiAnalysis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SensorDataObserver
{
    public function created(SensorData $sensorData): void
    {
        if (AiAnalysis::where('data_id', $sensorData->data_id)->exists()) {
            return; 
        }

        try {
            // Ambil maklumat kolam (device)
            $device = $sensorData->device;
            
            // Tetapkan berat awal (default 5g jika tiada)
            $initialWeight = (float) ($device->initial_weight ?? 5.0);
            
            // Ambil tarikh mula ternakan
            $stockingDate = $device->stocking_date ? Carbon::parse($device->stocking_date) : Carbon::now();
            
            // Kira bilangan hari ternakan (minimum 1 hari)
            $daysInCulture = max(1, $stockingDate->diffInDays(Carbon::now()));

            // PENGIRAAN BERAT FORMULA UTAMA DI SINI (Dijamin takkan jadi 51.5g lagi!)
            $estimatedWeight = $initialWeight + ($daysInCulture * 0.5);
            $estimatedWeight = max($initialWeight, round($estimatedWeight, 2));

            // Tentukan status risiko kualiti air secara langsung
            $riskStatus = 'Stable';
            if ($sensorData->temperature < 24 || $sensorData->temperature > 32 || $sensorData->ph_level < 6.5 || $sensorData->ph_level > 8.5) {
                $riskStatus = 'Warning';
            }

            AiAnalysis::create([
                'data_id' => $sensorData->data_id,
                'risk_status' => $riskStatus,
                'estimated_weight' => $estimatedWeight,
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Ralat pada Observer AI: " . $e->getMessage());
        }
    }
}