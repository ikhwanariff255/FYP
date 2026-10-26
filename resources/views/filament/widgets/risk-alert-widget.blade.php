<x-filament-widgets::widget>
    {{-- @if($isAtRisk)
        <!-- KOTAK MERAH MEMANJANG (AT RISK) -->
        <div class="w-full rounded-xl bg-danger-600 p-5 shadow-lg animate-pulse border-2 border-danger-400 flex items-center gap-5 text-white">
            <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-white" />
            <div>
                <h2 class="text-2xl font-extrabold uppercase tracking-widest">Amaran Risiko Tinggi: {{ $pondName }}</h2>
                <p class="text-lg font-medium text-danger-100 mt-1">Please recheck your pond. Kualiti air berada pada tahap kritikal!</p>
            </div>
        </div>
    @else
        <!-- KOTAK HIJAU MEMANJANG (STABLE) -->
        <div class="w-full rounded-xl bg-success-500/10 p-4 border border-success-500 flex items-center gap-4 text-success-600 dark:text-success-400">
            <x-heroicon-o-check-circle class="w-8 h-8" />
            <div>
                <h2 class="text-lg font-bold uppercase">{{ $pondName }} - Status Selamat</h2>
                <p class="text-sm">Tiada risiko dikesan pada masa ini. AI mengesahkan kualiti air stabil.</p>
            </div>
        </div>
    @endif --}}
</x-filament-widgets::widget>