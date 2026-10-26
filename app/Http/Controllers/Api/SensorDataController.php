<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SensorData;
use App\Models\PondDevice; // Pastikan nama model peranti/kolam anda betul
use App\Models\AiAnalysis;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class SensorDataController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Data dari ESP32 / Postman
        $request->validate([
            'mac_address' => 'required|string',
            'temperature' => 'required|numeric',
            'ph_level' => 'required|numeric',
            'turbidity' => 'required|numeric',
        ]);

        // 2. Cari Kolam berdasarkan MAC Address
        $device = PondDevice::where('mac_address', $request->mac_address)->first();
        
        if (!$device) {
            return response()->json(['error' => 'Device (MAC Address) tidak dijumpai dalam pangkalan data'], 404);
        }

        // 3. Simpan Data Sensor Mentah
        $sensorData = SensorData::create([
            'device_id' => $device->device_id,// Guna ID peranti yang betul
            'temperature' => $request->temperature,
            'ph_level' => $request->ph_level,
            'turbidity' => $request->turbidity,
            'timestamp' => now(), // Masa sebenar dari server
        ]);

        // 4. Sediakan Maklumat untuk AI
        $initialWeight = (float) ($device->initial_weight ?? 5.0);
        $stockingDate = $device->stocking_date ? Carbon::parse($device->stocking_date) : Carbon::now();
        $daysInCulture = max(1, $stockingDate->diffInDays(now()));

        $pythonPath = 'C:\laragon\bin\python\python-3.10\python.exe';
        $scriptPath = base_path('predict.py');

        // 5. Hantar ke Python
        $process = new Process([
            $pythonPath, 
            $scriptPath, 
            (string)$request->temperature, 
            (string)$request->ph_level, 
            (string)$request->turbidity, 
            (string)$daysInCulture,
            (string)$initialWeight
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
        $estimatedWeight = isset($parts[1]) ? floatval($parts[1]) : $initialWeight;

        // 6. Simpan hasil AI
        AiAnalysis::create([
            'data_id' => $sensorData->getKey(),
            'risk_status' => $riskStatus,
            'estimated_weight' => $estimatedWeight,
            'days_in_culture' => $daysInCulture,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Pulangkan Respon ke ESP32 / Postman
        return response()->json([
            'status' => 'success',
            'message' => 'Data saved and analyzed by AI.',
            'ai_result' => [
                'risk_status' => $riskStatus,
                'estimated_weight' => $estimatedWeight
            ]
        ], 201);
    }
}