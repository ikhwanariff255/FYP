<?php

namespace App\Filament\Widgets;

use App\Models\AiAnalysis;
use App\Models\PondDevice;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RiskAlertWidget extends Widget
{
    use InteractsWithPageFilters;

    // Jadikan kotak ini memanjang penuh (full width)
    protected int | string | array $columnSpan = 'full';
    
    // Susun nombor 1 supaya letak paling atas sekali
    protected static ?int $sort = 1; 

    protected static string $view = 'filament.widgets.risk-alert-widget';

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

        $isAtRisk = $latestAnalysis && $latestAnalysis->risk_status === 'At Risk';
        
        $pondName = 'Kolam';
        if ($latestAnalysis && $latestAnalysis->sensorData) {
            $pondName = PondDevice::find($latestAnalysis->sensorData->device_id)?->pond_name ?? $pondName;
        }

        return [
            'isAtRisk' => $isAtRisk,
            'pondName' => $pondName,
        ];
    }
}