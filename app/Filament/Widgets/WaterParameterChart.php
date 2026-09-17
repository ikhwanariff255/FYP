<?php

namespace App\Filament\Widgets;

use App\Models\SensorData;
use App\Models\PondDevice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WaterParameterChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Water Parameter History';
    protected int | string | array $columnSpan = ['md' => 2, 'xl' => 2];

    // Ubah deskripsi carta mengikut nama kolam
    public function getDescription(): ?string
    {
        $deviceId = $this->filters['device_id'] ?? null;
        $pond = PondDevice::find($deviceId);
        return 'Real-time feed for ' . ($pond->pond_name ?? 'Kolam');
    }

    protected function getData(): array
    {
        $deviceId = $this->filters['device_id'] ?? null;

        $query = SensorData::query();
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $data = $query->latest('timestamp')->take(10)->get()->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'pH',
                    'data' => $data->pluck('ph_level')->toArray(),
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => 'Temp °C',
                    'data' => $data->pluck('temperature')->toArray(),
                    'borderColor' => '#0ea5e9',
                ],
                [
                    'label' => 'Turbidity NTU',
                    'data' => $data->pluck('turbidity')->toArray(),
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $data->pluck('timestamp')->map(fn($date) => \Carbon\Carbon::parse($date)->format('H:i'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}