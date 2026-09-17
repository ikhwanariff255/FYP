<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PondDevice extends Model
{
    use HasFactory;

    protected $table = 'pond_device';
    protected $primaryKey = 'device_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'mac_address',
        'pond_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'device_id', 'device_id');
    }
}