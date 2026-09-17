<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-4">
            <div>
                <h2 class="text-lg font-bold tracking-tight">AI Prediction & Harvest Forecast</h2>
                <p class="text-xs text-gray-500">{{ $pondName ?? 'Pond' }} - Growth & Maturity Analysis</p>
            </div>
        </div>

        <div class="mt-4 space-y-4">
            <!-- Kotak Berat Semasa -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-center">
                <span class="text-sm font-medium text-gray-500">Est. Current Fish Weight</span>
                <div class="text-3xl font-extrabold text-primary-600 mt-1">
                    {{ $ai->estimated_weight ?? '0' }} <span class="text-lg font-normal">g</span>
                </div>
                <span class="text-xs text-gray-400 mt-1 block">Auto-calculated by Random Forest</span>
            </div>

            <!-- Bahagian Forecast / Jangkaan Matang -->
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-xs text-gray-500 block">Target Size</span>
                    <span class="font-bold text-gray-700 dark:text-gray-200">{{ $targetWeight }} g</span>
                </div>
                <div class="p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="text-xs text-gray-500 block">Est. Harvest In</span>
                    <span class="font-bold text-primary-600">{{ $daysToHarvest }} Days</span>
                </div>
            </div>

            <!-- Status Maklumat Tambahan -->
            <div class="text-xs text-gray-500 space-y-1 pt-2 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between">
                    <span>Mortality Risk:</span>
                    <span class="font-semibold {{ ($ai->risk_status ?? 'Stable') == 'Stable' ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $ai->risk_status ?? 'Stable' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Est. Maturity Date:</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $estimatedHarvestDate }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>