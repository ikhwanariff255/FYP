{{-- Pautan pantas antara halaman My Pond (berguna di telefon apabila menu sisi tersembunyi) --}}
@php
    $activeRisks = $a ? collect($a['risks'])->whereIn('severity', ['critical', 'warning'])->count() : 0;
    $pages = [
        [\App\Filament\Pages\FishGrowthAnalysis::class, 'Overview', 'heroicon-m-home'],
        [\App\Filament\Pages\Pond\WaterQuality::class, 'Water Quality', 'heroicon-m-beaker'],
        [\App\Filament\Pages\Pond\GrowthFeeding::class, 'Growth & Feeding', 'heroicon-m-chart-bar'],
        [\App\Filament\Pages\Pond\RisksActions::class, 'Risks & Actions', 'heroicon-m-shield-exclamation'],
    ];
@endphp
<nav class="tg-subnav" aria-label="Pond sections">
    @foreach ($pages as [$class, $label, $icon])
        <a href="{{ $this->pondUrl($class) }}" class="{{ static::class === $class ? 'is-active' : '' }}">
            <x-filament::icon :icon="$icon" style="width: .9rem; height: .9rem;" />
            {{ $label }}
            @if ($class === \App\Filament\Pages\Pond\RisksActions::class && $activeRisks)
                <span class="tg-count">{{ $activeRisks }}</span>
            @endif
        </a>
    @endforeach
</nav>
