<?php

namespace App\Filament\Widgets;

use App\Models\AiAnalysis;
use App\Models\PondDevice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GrowthForecastChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'AI Fish Growth Trajectory (g)';
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected function getData(): array
    {
        $deviceId = $this->filters['device_id'] ?? null;

        $analyses = AiAnalysis::whereHas('sensorData', function ($query) use ($deviceId) {
                if ($deviceId) {
                    $query->where('device_id', $deviceId);
                }
            })
            ->latest('data_id')
            ->take(10)
            ->get()
            ->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'Est. Weight (g)',
                    'data' => $analyses->pluck('estimated_weight')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $analyses->pluck('created_at')->map(fn($date) => \Carbon\Carbon::parse($date)->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}