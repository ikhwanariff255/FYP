<x-filament-panels::page>
@include('filament.pages.pond.partials.styles')

<div class="tg" wire:poll.30s>
@if (! $a)
    @include('filament.pages.pond.partials.empty')
@else
    @php $h = $a['health']; $nh3 = $a['ammonia']; @endphp
    @include('filament.pages.pond.partials.header')

    {{-- PARAMETER SEMASA --}}
    <div class="tg-grid tg-grid-4">
        @foreach ($a['parameters'] as $key => $p)
            @php $tone = $p['status'] === 'optimal' ? 't-success' : ($p['status'] === 'warning' ? 't-warning' : 't-danger'); @endphp
            <div class="tg-card {{ $tone }}">
                <div class="tg-row">
                    <span class="tg-label">{{ $p['label'] }}</span>
                    <span class="tg-badge tone-bg">{{ $p['score'] }}/100</span>
                </div>
                <div class="tg-row" style="margin-top: .4rem; align-items: flex-end;">
                    <div class="tg-big">{{ $p['value'] }}<span class="tg-small"> {{ $p['unit'] }}</span></div>
                    @if (count($p['spark']) > 1)
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none" style="width: 6rem; height: 1.8rem;" aria-label="24 hour trend">
                            <path d="{{ $this->sparklinePath($p['spark']) }}" fill="none" stroke="rgb(var(--c))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @endif
                </div>
                <div class="tg-bar"><div class="tone-fill" style="width: {{ max(3, $p['score']) }}%"></div></div>
                <div class="tg-tiny" style="margin-top: .45rem;">Ideal {{ $p['ideal'] }} {{ $p['unit'] }}</div>
            </div>
        @endforeach

        @if ($nh3)
            @php $nt = $nh3['level'] === 'high' ? 't-danger' : ($nh3['level'] === 'elevated' ? 't-warning' : 't-success'); @endphp
            <div class="tg-card {{ $nt }}">
                <div class="tg-row">
                    <span class="tg-label">Toxic ammonia (NH₃)</span>
                    <span class="tg-badge tone-bg">{{ ucfirst($nh3['level']) }}</span>
                </div>
                <div class="tg-big" style="margin-top: .4rem;">{{ $nh3['fraction_pct'] }}<span class="tg-small"> % of TAN</span></div>
                <div class="tg-bar"><div class="tone-fill" style="width: {{ min(100, max(3, $nh3['fraction_pct'] * 5)) }}%"></div></div>
                <div class="tg-tiny" style="margin-top: .45rem;">Keep total ammonia below <strong>{{ $nh3['safe_tan'] }} mg/L</strong> at this pH & temperature</div>
            </div>
        @endif
    </div>

    {{-- JALUR KESIHATAN 24 JAM --}}
    <div class="tg-card">
        <div class="tg-row" style="flex-wrap: wrap;">
            <div>
                <h2 class="tg-h2">24-hour health timeline</h2>
                <p class="tg-sub">Hourly health index · hover a block for details</p>
            </div>
            <div class="tg-legend">
                <span><i class="t-success" style="border-color: rgb(var(--c)); border-top-width: 6px;"></i>≥ 85</span>
                <span><i class="t-warning" style="border-color: rgb(var(--c)); border-top-width: 6px;"></i>60–84</span>
                <span><i class="t-danger" style="border-color: rgb(var(--c)); border-top-width: 6px;"></i>&lt; 60</span>
                <span><i style="border-color: var(--tg-line); border-top-width: 6px;"></i>No data</span>
            </div>
        </div>
        <div class="tg-strip">
            @foreach ($a['hourly'] as $hr)
                <div class="{{ $this->scoreTone($hr['score']) }}"
                     style="background: {{ $hr['score'] === null ? 'var(--tg-line)' : 'rgba(var(--c), ' . ($hr['score'] >= 85 ? '.75' : '.9') . ')' }};"
                     title="{{ $hr['label'] }} — {{ $hr['score'] === null ? 'no data' : 'health ' . $hr['score'] . ' · ' . $hr['temp'] . ' °C · pH ' . $hr['ph'] . ' · ' . $hr['turb'] . ' NTU' }}"></div>
            @endforeach
        </div>
        <div class="tg-row tg-tiny" style="margin-top: .35rem;">
            <span>{{ $a['hourly'][0]['label'] }}</span><span>{{ $a['hourly'][12]['label'] }}</span><span>Now</span>
        </div>
        @if ($h && $h['penalties'])
            <p class="tg-tiny" style="margin-top: .5rem;">Stability penalty: {{ implode(' · ', $h['penalties']) }}</p>
        @endif
    </div>

    {{-- STATISTIK 24 JAM --}}
    <div class="tg-card">
        <h2 class="tg-h2">Last 24 hours</h2>
        <p class="tg-sub" style="margin-bottom: .75rem;">{{ number_format($a['snapshot']['readings_24h']) }} readings · {{ $a['snapshot']['invalid_24h'] }} faulty readings filtered out</p>
        <div style="overflow-x: auto;">
            <table class="tg-table">
                <thead><tr><th>Parameter</th><th>Now</th><th>Min</th><th>Average</th><th>Max</th><th>Time in ideal range</th><th>Ideal</th></tr></thead>
                <tbody>
                    @foreach ($a['parameters'] as $p)
                        <tr>
                            <td style="font-weight: 700;">{{ $p['label'] }}</td>
                            <td class="tg-num">{{ $p['value'] }} {{ $p['unit'] }}</td>
                            <td class="tg-num">{{ $p['stats']['min'] ?? '—' }}</td>
                            <td class="tg-num">{{ $p['stats']['avg'] ?? '—' }}</td>
                            <td class="tg-num">{{ $p['stats']['max'] ?? '—' }}</td>
                            <td>
                                @if ($p['in_range'] !== null)
                                    @php $rt = $p['in_range'] >= 90 ? 't-success' : ($p['in_range'] >= 70 ? 't-warning' : 't-danger'); @endphp
                                    <span class="tg-badge tone-bg {{ $rt }}">{{ $p['in_range'] }}%</span>
                                    @if ($p['hours_out'] > 0) <span class="tg-tiny">· out for ~{{ $p['hours_out'] }} h</span> @endif
                                @else
                                    <span class="tg-tiny">No data in 24 h</span>
                                @endif
                            </td>
                            <td class="tg-tiny">{{ $p['ideal'] }} {{ $p['unit'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
</div>
</x-filament-panels::page>
