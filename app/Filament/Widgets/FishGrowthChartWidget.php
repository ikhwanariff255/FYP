<?php

namespace App\Filament\Widgets;

use App\Models\SensorData;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FishGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Water Quality & Parameter Trends';
    
    // Paksa widget mengambil lebar penuh
    protected int | string | array $columnSpan = 'full';
    
    // Hadkan tinggi graf supaya tidak terlampau tinggi
    protected static ?string $maxHeight = '320px';

    public ?string $filter = '1d';

    protected function getFilters(): ?array
    {
        return [
            '5m' => 'Last 5 Minutes',
            '1h' => 'Last 1 Hour',
            '1d' => 'Last 24 Hours',
            '1w' => 'Last 7 Days',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $query = SensorData::query();

        match ($activeFilter) {
            '5m' => $query->where('timestamp', '>=', now()->subMinutes(5)),
            '1h' => $query->where('timestamp', '>=', now()->subHour()),
            '1d' => $query->where('timestamp', '>=', now()->subDay()),
            '1w' => $query->where('timestamp', '>=', now()->subDays(7)),
            default => $query->where('timestamp', '>=', now()->subDay()),
        };

        $data = $query->orderBy('timestamp', 'asc')->get();

        $labels = $data->map(function ($item) use ($activeFilter) {
            $time = Carbon::parse($item->timestamp);
            return match ($activeFilter) {
                '5m', '1h' => $time->format('H:i:s'),
                '1d'       => $time->format('H:i'),
                '1w'       => $time->format('M d, H:i'),
                default    => $time->format('H:i'),
            };
        });

        return [
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'data' => $data->pluck('temperature'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'pH Level',
                    'data' => $data->pluck('ph_level'),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Turbidity (NTU)',
                    'data' => $data->pluck('turbidity'),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}