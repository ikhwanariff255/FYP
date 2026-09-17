<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SensorData;

class WaterQualityChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Suhu Air Kolam (°C)';
    protected static ?int $sort = 1; // Susunan kedudukan di dashboard

    protected function getData(): array
    {
        // Ambil 20 rekod bacaan sensor terkini untuk dipaparkan pada graf
        $sensorData = SensorData::orderBy('timestamp', 'asc')->limit(20)->get();

        return [
            'datasets' => [
                [
                    'label' => 'Suhu Air (°C)',
                    'data' => $sensorData->pluck('temperature')->toArray(),
                    'borderColor' => '#3b82f6', // Warna biru terang
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $sensorData->pluck('timestamp')->map(function($date) {
                return date('d M H:i', strtotime($date));
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Jenis carta garis
    }
}