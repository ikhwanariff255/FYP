<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\AiPredictionService;
use App\Models\AiAnalysis;

class SensorData extends Model
{
    use HasFactory;

    protected $table = 'sensor_data';
    protected $primaryKey = 'data_id';
    public $timestamps = false;
    
    protected $fillable = [
        'device_id',
        'temperature',
        'ph_level',
        'turbidity',
        'timestamp',
    ];

    // Hubungan dengan jadual pond_device
    public function device()
    {
        return $this->belongsTo(PondDevice::class, 'device_id', 'device_id');
    }

    // Automatik jalankan AI prediction setiap kali data sensor baru dicipta
    protected static function booted()
    {
        static::created(function ($sensorData) {
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
                // Abaikan jika berlaku ralat masa belakang supaya data sensor tetap tersimpan
            }
        });
    }
}