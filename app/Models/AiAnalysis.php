<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAnalysis extends Model
{
    use HasFactory;

    protected $table = 'ai_analysis';
    protected $primaryKey = 'analysis_id';
    public $timestamps = false;

    protected $fillable = [
        'data_id',
        'risk_status',
        'estimated_weight',
        'created_at',
    ];

    public function sensorData()
    {
        return $this->belongsTo(SensorData::class, 'data_id', 'data_id');
    }
}