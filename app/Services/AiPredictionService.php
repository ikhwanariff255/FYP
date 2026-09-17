<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AiPredictionService
{
    public function predict(float $temperature, float $ph, float $turbidity): ?array
    {
        $process = new Process(['python', base_path('predict.py'), $temperature, $ph, $turbidity]);
        $process->run();

        if (!$process->isSuccessful()) {
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

        return null;
    }
}