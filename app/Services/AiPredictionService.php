<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class AiPredictionService
{
    public function predict(float $temperature, float $ph, float $turbidity): ?array
    {
        $pythonPath = 'C:\laragon\bin\python\python-3.10\python.exe';
        
        // KUNCI PENYELESAIAN: Tambah 'SystemRoot' ke dalam environment variables
       $process = new Process(
            [$pythonPath, base_path('predict.py'), $temperature, $ph, $turbidity],
            null,
            [
                'SystemRoot' => 'C:\Windows',
                'PATH' => getenv('PATH'),
                'USERPROFILE' => getenv('USERPROFILE'),
                'LOCALAPPDATA' => getenv('LOCALAPPDATA')
            ] 
        );
        
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('AI Process Failed: ' . $process->getErrorOutput());
            return null;
        }

        $output = trim($process->getOutput());
        $parts = explode('|', $output);

        if (count($parts) === 2) {
            return [
                'risk_status' => $parts[0],
                'estimated_weight' => (float)$parts[1],
            ];
        }

        Log::error('AI Output Format Error: ' . $output);
        return null;
    }
}