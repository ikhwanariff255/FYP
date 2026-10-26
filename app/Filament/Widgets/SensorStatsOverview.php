<?php

namespace App\Filament\Widgets;

use App\Models\SensorData;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SensorStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $deviceId = $this->filters['device_id'] ?? null;

        $query = SensorData::query();
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $latest = $query->latest('timestamp')->first();

        if (!$latest) {
            return [];
        }

        // Logik yang SAMA dengan skrip Python AI (Pakar Biologi Tilapia)
        $phColor = ($latest->ph_level < 6.5 || $latest->ph_level > 8.5) ? 'danger' : 'success';
        $tempColor = ($latest->temperature < 25.0 || $latest->temperature > 32.0) ? 'danger' : 'success';
        $turbColor = ($latest->turbidity > 25.0) ? 'danger' : 'success';

        return [
            Stat::make('Water pH', number_format($latest->ph_level, 2) . ' pH')
                ->description('Optimal: 6.5 - 8.5')
                ->descriptionIcon($phColor === 'danger' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($phColor),
                
            Stat::make('Temperature', number_format($latest->temperature, 1) . ' °C')
                ->description('Optimal: 25 - 32 °C')
                ->descriptionIcon($tempColor === 'danger' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($tempColor),
                
            Stat::make('Turbidity', number_format($latest->turbidity, 1) . ' NTU')
                ->description('Optimal: < 25 NTU')
                ->descriptionIcon($turbColor === 'danger' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($turbColor),
        ];
    }
}