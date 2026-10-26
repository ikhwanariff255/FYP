<?php

namespace App\Filament\Widgets;

use App\Models\AiAnalysis;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class GrowthForecastChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'AI Fish Growth Trajectory (g)';
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    public ?string $filter = '24h';

    protected function getFilters(): ?array
    {
        return [
            '1h'  => 'Last 1 Hour',
            '24h' => 'Last 24 Hours',
            '7d'  => 'Last 7 Days',
            '30d' => 'Last 30 Days',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $deviceId = $this->filters['device_id'] ?? null;

        $query = AiAnalysis::query()
            ->whereHas('sensorData', function ($q) use ($deviceId) {
                if ($deviceId) {
                    $q->where('device_id', $deviceId);
                }
            });

        match ($activeFilter) {
            '1h'  => $query->where('created_at', '>=', now()->subHour()),
            '24h' => $query->where('created_at', '>=', now()->subHours(24)),
            '7d'  => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            default => $query->where('created_at', '>=', now()->subHours(24)),
        };

        $analyses = $query->orderBy('created_at', 'asc')->get();

        // 1. Ensure Labels are UNIQUE (prevent vertical looping)
        $labels = $analyses->pluck('created_at')->map(function ($date) use ($activeFilter) {
            $carbonDate = Carbon::parse($date);
            return match ($activeFilter) {
                '1h', '24h' => $carbonDate->format('H:i:s'), // Add seconds to prevent duplicates
                '7d', '30d' => $carbonDate->format('d M H:i'), // Add time so each day isn't grouped together
                default     => $carbonDate->format('d M H:i'),
            };
        })->toArray();

        // 2. Smooth the Data (Y-Axis) using a moving average
        $rawWeights = $analyses->pluck('estimated_weight')->toArray();
        $smoothedWeights = $this->smoothData($rawWeights, 5); // Window of 5 smooths out sudden spikes

        return [
            'datasets' => [
                [
                    'label' => 'Est. Weight (g)',
                    'data' => $smoothedWeights,
                    'borderColor' => '#10b981', // Tailwind Emerald-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // 3. Chart Configuration for a clean, elegant look
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false, // Tooltip shows even if not hovering exactly on a dot
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false, // Hide vertical grid lines for a cleaner look
                    ],
                    'ticks' => [
                        'maxTicksLimit' => 6, // Only show 6 labels on the X-axis to avoid clutter
                        'maxRotation' => 45,
                        'color' => '#9ca3af', // Gray text
                    ],
                ],
                'y' => [
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)', // Very faint horizontal grid lines
                    ],
                    'beginAtZero' => false, // Dynamically scale Y so you can see the actual growth
                    'ticks' => [
                        'color' => '#9ca3af',
                    ],
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 0, // Hide the ugly dots
                    'hitRadius' => 20, // But make the hover area larger so tooltips still work easily
                ],
                'line' => [
                    'borderWidth' => 3,
                    'tension' => 0.4, // Smooth curve
                    'cubicInterpolationMode' => 'monotone', // Prevents the line from dipping below 0
                ],
            ],
        ];
    }

    /**
     * Apply a moving average to smooth out erratic AI predictions.
     */
    private function smoothData(array $data, int $window = 5): array
    {
        $count = count($data);
        if ($count < $window) {
            return $data;
        }

        $smoothed = [];
        for ($i = 0; $i < $count; $i++) {
            $start = max(0, $i - $window + 1);
            $slice = array_slice($data, $start, $i - $start + 1);
            $smoothed[] = round(array_sum($slice) / count($slice), 2);
        }

        return $smoothed;
    }
}