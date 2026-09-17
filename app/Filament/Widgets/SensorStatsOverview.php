<?php

namespace App\Filament\Widgets;

use App\Models\SensorData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // Tambah ini

class SensorStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters; // Tambah ini

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Tangkap ID peranti dari dropdown
        $deviceId = $this->filters['device_id'] ?? null;

        $query = SensorData::query();
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $latest = $query->latest('timestamp')->first();

        if (!$latest) {
            return [];
        }

        $phColor = ($latest->ph_level < 6.5 || $latest->ph_level > 8.0) ? 'danger' : 'success';
        $tempColor = ($latest->temperature > 30) ? 'warning' : 'success';

        return [
            Stat::make('Water pH', number_format($latest->ph_level, 2) . ' pH')
                ->description('Range: 6.5 - 8.0')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($phColor),
            Stat::make('Temperature', number_format($latest->temperature, 1) . ' °C')
                ->description('Range: 26 - 30°C')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($tempColor),
            Stat::make('Turbidity', number_format($latest->turbidity, 1) . ' NTU')
                ->description('Range: 0 - 20 NTU')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}