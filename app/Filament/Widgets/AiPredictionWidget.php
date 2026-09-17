<?php

namespace App\Filament\Widgets;

use App\Models\AiAnalysis;
use App\Models\PondDevice;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

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
        $pondName = $sensor ? PondDevice::find($sensor->device_id)?->pond_name : 'Tiada Data';

        // Logik Kiraan Forecast ke Saiz Matang (Sasaran: 300g)
        $currentWeight = $latestAnalysis ? $latestAnalysis->estimated_weight : 50;
        $targetWeight = 300.0; // Sasaran berat pasaran (gram)
        $dailyGrowthRate = 2.0; // Anggaran purata pembesaran 2 gram sehari untuk tilapia sihat

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