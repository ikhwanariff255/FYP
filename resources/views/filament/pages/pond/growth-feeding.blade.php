<x-filament-panels::page>
@include('filament.pages.pond.partials.styles')

<div class="tg" wire:poll.30s>
@if (! $a)
    @include('filament.pages.pond.partials.empty')
@else
    @php
        $g = $a['growth']; $feed = $a['feed'];
        $perfTone = $g['performance_pct'] >= 90 ? 't-success' : ($g['performance_pct'] >= 75 ? 't-warning' : 't-danger');
    @endphp
    @include('filament.pages.pond.partials.header')

    {{-- KPI PERTUMBUHAN --}}
    <div class="tg-grid tg-grid-4">
        <div class="tg-card">
            <div class="tg-label">Estimated weight</div>
            <div class="tg-big" style="margin-top: .4rem;">{{ $g['current_weight'] }}<span class="tg-small"> g/fish</span></div>
            <div class="tg-bar"><div class="tone-fill t-primary" style="width: {{ $g['progress_pct'] }}%"></div></div>
            <div class="tg-tiny" style="margin-top: .45rem;">{{ $g['progress_pct'] }}% of {{ (int) $g['target_weight'] }} g target · +{{ $g['daily_gain'] }} g/day</div>
        </div>

        <div class="tg-card {{ $perfTone }}">
            <div class="tg-label">Growth performance</div>
            <div class="tg-big tone-text" style="margin-top: .4rem;">{{ $g['performance_pct'] }}%</div>
            <div class="tg-bar"><div class="tone-fill" style="width: {{ min(100, $g['performance_pct']) }}%"></div></div>
            <div class="tg-tiny" style="margin-top: .45rem;">of ideal-condition growth ({{ $g['ideal_weight_now'] }} g) · SGR {{ $g['sgr'] }}%/day</div>
        </div>

        <div class="tg-card">
            <div class="tg-label">Harvest forecast</div>
            @if ($g['days_to_harvest'] === 0)
                <div class="tg-big t-success tone-text" style="margin-top: .4rem;">Ready</div>
                <div class="tg-tiny" style="margin-top: .9rem;">Target reached. Stop feeding 24 h before harvest.</div>
            @elseif ($g['days_to_harvest'] === null)
                <div class="tg-big t-danger tone-text" style="margin-top: .4rem;">Stalled</div>
                <div class="tg-tiny" style="margin-top: .9rem;">No growth under recent conditions. Fix water quality first.</div>
            @else
                <div class="tg-big" style="margin-top: .4rem;">{{ $g['harvest_date']->format('d M Y') }}</div>
                <div class="tg-tiny" style="margin-top: .9rem;">In {{ $g['days_to_harvest'] }} days · based on {{ $g['projection_basis'] }}</div>
            @endif
        </div>

        <div class="tg-card">
            <div class="tg-label">Stock & biomass</div>
            <div class="tg-big" style="margin-top: .4rem;">{{ $g['biomass_kg'] }}<span class="tg-small"> kg</span></div>
            <div class="tg-tiny" style="margin-top: .9rem;">{{ number_format($g['alive']) }} / {{ number_format($g['stock']) }} fish · est. survival {{ $g['survival_pct'] }}%</div>
        </div>
    </div>

    {{-- CARTA PERTUMBUHAN --}}
    @php
        $s = $g['series'];
        $all = array_merge($s['actual'], $s['projected'], $s['ideal']);
        $xMax = max(30, max(array_column($all, 0)));
        $yMax = max($g['target_weight'] * 1.1, max(array_column($all, 1)));
        $yStep = $yMax > 600 ? 200 : ($yMax > 250 ? 100 : 50);
        $xStep = $xMax > 180 ? 60 : 30;
    @endphp
    <div class="tg-card">
        <div class="tg-row" style="align-items: flex-start;">
            <div>
                <h2 class="tg-h2">Growth trajectory</h2>
                <p class="tg-sub">Thermal-unit growth model driven by this pond's own temperature history</p>
            </div>
            <span class="tg-badge tone-bg t-primary">Day {{ $g['days'] }}</span>
        </div>

        <svg viewBox="0 0 640 240" class="tg-chart" style="width: 100%; height: auto; margin-top: .75rem;" role="img" aria-label="Fish weight over days of culture">
            @for ($y = 0; $y <= $yMax; $y += $yStep)
                <line x1="36" x2="628" y1="{{ $this->chartY($y, $yMax) }}" y2="{{ $this->chartY($y, $yMax) }}" stroke="var(--tg-line)"/>
                <text x="30" y="{{ $this->chartY($y, $yMax) + 3 }}" text-anchor="end">{{ $y }}g</text>
            @endfor
            @for ($x = 0; $x <= $xMax; $x += $xStep)
                <text x="{{ $this->chartX($x, $xMax) }}" y="234" text-anchor="middle">D{{ $x }}</text>
            @endfor

            <line x1="36" x2="628" y1="{{ $this->chartY($g['target_weight'], $yMax) }}" y2="{{ $this->chartY($g['target_weight'], $yMax) }}" stroke="rgb(var(--success-500))" stroke-dasharray="2 4" stroke-width="1.5"/>
            <text x="628" y="{{ $this->chartY($g['target_weight'], $yMax) - 5 }}" text-anchor="end" style="fill: rgb(var(--success-600)); font-weight: 700;">Target {{ (int) $g['target_weight'] }}g</text>

            <line x1="{{ $this->chartX($g['days'], $xMax) }}" x2="{{ $this->chartX($g['days'], $xMax) }}" y1="12" y2="216" stroke="var(--tg-muted)" stroke-dasharray="3 3" opacity=".6"/>

            <path d="{{ $this->chartPath($s['ideal'], $xMax, $yMax) }}" fill="none" stroke="rgb(var(--gray-400))" stroke-width="1.5" stroke-dasharray="5 4"/>
            <path d="{{ $this->chartPath($s['projected'], $xMax, $yMax) }}" fill="none" stroke="rgb(var(--primary-500))" stroke-width="2.5" stroke-dasharray="7 5" opacity=".75"/>
            <path d="{{ $this->chartPath($s['actual'], $xMax, $yMax) }}" fill="none" stroke="rgb(var(--primary-600))" stroke-width="3" stroke-linecap="round"/>
            <circle cx="{{ $this->chartX($g['days'], $xMax) }}" cy="{{ $this->chartY($g['current_weight'], $yMax) }}" r="5" fill="rgb(var(--primary-600))" stroke="var(--tg-card)" stroke-width="2"/>
            <text x="{{ $this->chartX($g['days'], $xMax) + 8 }}" y="{{ $this->chartY($g['current_weight'], $yMax) - 8 }}" style="font-weight: 700; fill: var(--tg-text);">{{ $g['current_weight'] }}g</text>
        </svg>

        <div class="tg-legend" style="margin-top: .5rem;">
            <span><i style="border-color: rgb(var(--primary-600));"></i>Estimated (actual conditions)</span>
            <span><i style="border-color: rgb(var(--primary-500)); border-top-style: dashed;"></i>Projection</span>
            <span><i style="border-color: rgb(var(--gray-400)); border-top-style: dashed;"></i>Ideal conditions</span>
        </div>
        @if ($a['ml'])
            <p class="tg-tiny" style="margin-top: .6rem;">For comparison, the Random Forest regressor's latest estimate is {{ round($a['ml']['estimated_weight'], 1) }} g.</p>
        @endif
    </div>

    {{-- PEMAKANAN --}}
    <div id="feeding" class="tg-grid tg-main">
        <div class="tg-card" style="display: flex; flex-direction: column; gap: .75rem;">
            <h2 class="tg-h2">Today's feeding plan</h2>
            <div class="tg-soft {{ $feed['withhold'] ? 't-danger' : ($feed['multiplier'] < 1 ? 't-warning' : 't-success') }} tone-edge">
                <div class="tg-label">Daily ration</div>
                @if ($feed['withhold'])
                    <div style="font-size: 1.4rem; font-weight: 800;" class="tone-text">Withhold feed</div>
                    <div class="tg-tiny">Because of {{ strtolower($feed['reason']) }}. Resume once it is resolved.</div>
                @else
                    <div style="font-size: 1.4rem; font-weight: 800;" class="tg-num">
                        {{ $feed['adjusted_kg'] < 1 ? round($feed['adjusted_kg'] * 1000) . ' g' : $feed['adjusted_kg'] . ' kg' }} <span class="tg-tiny">/ day</span>
                    </div>
                    <div class="tg-tiny">
                        {{ $feed['stage']['meals'] }} meals × {{ $feed['per_meal_g'] }} g · {{ $feed['stage']['rate'] }}% body weight
                        @if ($feed['multiplier'] < 1)
                            <br><strong class="tone-text">−{{ round((1 - $feed['multiplier']) * 100) }}%</strong> due to {{ strtolower($feed['reason']) }}
                        @endif
                    </div>
                @endif
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .6rem;">
                <div class="tg-soft"><div class="tg-label">Stage</div><div style="font-weight: 700;">{{ $feed['stage']['stage'] }}</div></div>
                <div class="tg-soft"><div class="tg-label">Protein</div><div style="font-weight: 700;">{{ $feed['stage']['protein'] }}</div></div>
                <div class="tg-soft"><div class="tg-label">Pellet</div><div style="font-weight: 700;">{{ $feed['stage']['pellet'] }}</div></div>
            </div>
            <ul class="tg-steps tg-tiny" style="margin-top: 0;">
                <li>Feed should be eaten within 15–20 minutes. Remove leftovers.</li>
                <li>Stop feeding 24 hours before harvest or transport.</li>
            </ul>
        </div>

        <div class="tg-card">
            <h2 class="tg-h2">Growth stage & feeding matrix</h2>
            <p class="tg-sub" style="margin-bottom: .75rem;">Stage chosen from the estimated weight ({{ $g['current_weight'] }} g)</p>
            <div style="overflow-x: auto;">
                <table class="tg-table">
                    <thead><tr><th>Stage</th><th>Weight</th><th>Feed %</th><th>Protein</th><th>Meals</th></tr></thead>
                    <tbody>
                        @php $prev = 0; @endphp
                        @foreach ($feed['table'] as $row)
                            <tr class="{{ $feed['stage']['stage'] === $row['stage'] ? 'is-now' : '' }}">
                                <td>{{ $row['stage'] }} @if ($feed['stage']['stage'] === $row['stage']) <span class="tg-badge tone-bg t-primary">NOW</span> @endif</td>
                                <td class="tg-num">{{ $row['max_weight'] ? "{$prev}–{$row['max_weight']} g" : "≥ {$prev} g" }}</td>
                                <td class="tg-num">{{ $row['rate'] }}%</td>
                                <td>{{ $row['protein'] }}</td>
                                <td class="tg-num">{{ $row['meals'] }}×</td>
                            </tr>
                            @php $prev = $row['max_weight']; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
</div>
</x-filament-panels::page>
