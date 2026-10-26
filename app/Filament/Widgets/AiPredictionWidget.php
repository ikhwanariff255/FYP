<?php

namespace App\Filament\Widgets;

use App\Models\AiAnalysis;
use App\Models\PondDevice;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class AiPredictionWidget extends Widget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];
    protected static string $view = 'filament.widgets.ai-prediction-widget';

    public function getViewData(): array
    {
        $deviceId = $this->filters['device_id'] ?? null;

        $latestAnalysis = AiAnalysis::whereHas('sensorData', function ($query) use ($deviceId) {
                if ($deviceId) {
                    $query->where('device_id', $deviceId);
                }
            })
            ->latest('data_id')
            ->first();
        
        $sensor = $latestAnalysis ? $latestAnalysis->sensorData : null;
        $device = $sensor ? PondDevice::find($sensor->device_id) : null;
        $pondName = $device ? $device->pond_name : 'Tiada Data';

        // Selaraskan logik AI dengan pangkalan data sebenar
        $initialWeight = $device ? (float) ($device->initial_weight ?? 5.0) : 5.0;
        $targetWeight = $device ? (float) ($device->target_weight ?? 300.0) : 300.0;
        $currentWeight = $latestAnalysis ? (float) $latestAnalysis->estimated_weight : $initialWeight;
        
        
        $dailyGrowthRate = 2.5; 

        $remainingWeight = max(0, $targetWeight - $currentWeight);
        $daysToHarvest = $remainingWeight > 0 ? ceil($remainingWeight / $dailyGrowthRate) : 0;
        $estimatedHarvestDate = now()->addDays($daysToHarvest)->format('d M Y');

        return [
            'ai' => $latestAnalysis,
            'sensor' => $sensor,
            'pondName' => $pondName,
            'targetWeight' => $targetWeight,
            'daysToHarvest' => $daysToHarvest,
            'estimatedHarvestDate' => $estimatedHarvestDate,
        ];
    }
}