<x-filament-panels::page>
<style>
    /* Gaya berskop untuk halaman ini — guna pemboleh ubah warna Filament supaya mod gelap automatik */
    .tg { --tg-card: #fff; --tg-ring: rgba(var(--gray-950), .06); --tg-muted: rgb(var(--gray-500)); --tg-text: rgb(var(--gray-950)); --tg-soft: rgb(var(--gray-50)); --tg-line: rgba(var(--gray-950), .08);
          display: flex; flex-direction: column; gap: 1.25rem; color: var(--tg-text); }
    .dark .tg { --tg-card: rgb(var(--gray-900)); --tg-ring: rgba(255,255,255,.1); --tg-muted: rgb(var(--gray-400)); --tg-text: #fff; --tg-soft: rgba(255,255,255,.04); --tg-line: rgba(255,255,255,.08); }
    .tg [x-cloak] { display: none !important; }
    .tg-card { background: var(--tg-card); border-radius: .75rem; box-shadow: 0 0 0 1px var(--tg-ring), 0 1px 2px rgba(0,0,0,.04); padding: 1.25rem; min-width: 0; }
    .tg-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr; }
    @media (min-width: 640px)  { .tg-grid-2, .tg-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .tg-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .tg-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .tg-main { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    @media (min-width: 1280px) { .tg-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .tg-h2 { font-size: 1rem; font-weight: 700; margin: 0; }
    .tg-sub { font-size: .75rem; color: var(--tg-muted); margin-top: .15rem; }
    .tg-label { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--tg-muted); }
    .tg-big { font-size: 1.75rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .tg-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .tg-wrap { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .tg-small { font-size: .75rem; color: var(--tg-muted); }
    .tg-tiny { font-size: .68rem; color: var(--tg-muted); }
    .tg-num { font-variant-numeric: tabular-nums; }
    .tg-bar { height: .4rem; border-radius: 999px; background: var(--tg-line); overflow: hidden; margin-top: .6rem; }
    .tg-bar > div { height: 100%; border-radius: 999px; }
    .tg-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; font-size: .68rem; font-weight: 700; border-radius: .4rem; white-space: nowrap; }
    .tg-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .8rem; font-size: .75rem; font-weight: 700; border-radius: .5rem; border: 0; cursor: pointer; background: var(--tg-soft); color: var(--tg-text); box-shadow: 0 0 0 1px var(--tg-ring); }
    .tg-btn:hover { filter: brightness(.96); }
    .tg-btn-primary { background: rgb(var(--primary-600)); color: #fff; box-shadow: none; }
    .tg-select { font-size: .85rem; font-weight: 600; border-radius: .5rem; padding: .4rem 2rem .4rem .7rem; background-color: var(--tg-card); color: var(--tg-text); border: 1px solid var(--tg-ring); }
    .tg-soft { background: var(--tg-soft); border-radius: .6rem; padding: .75rem; box-shadow: 0 0 0 1px var(--tg-ring); }

    /* Nada warna */
    .t-success { --c: var(--success-500); --ct: var(--success-600); }
    .t-warning { --c: var(--warning-500); --ct: var(--warning-600); }
    .t-danger  { --c: var(--danger-500);  --ct: var(--danger-600); }
    .t-info    { --c: var(--info-500);    --ct: var(--info-600); }
    .t-primary { --c: var(--primary-500); --ct: var(--primary-600); }
    .t-gray    { --c: var(--gray-400);    --ct: var(--gray-500); }
    .dark .t-success { --ct: var(--success-400); } .dark .t-warning { --ct: var(--warning-400); } .dark .t-danger { --ct: var(--danger-400); }
    .dark .t-info { --ct: var(--info-400); } .dark .t-primary { --ct: var(--primary-400); } .dark .t-gray { --ct: var(--gray-400); }
    .tone-text { color: rgb(var(--ct)); }
    .tone-bg { background: rgba(var(--c), .1); color: rgb(var(--ct)); box-shadow: 0 0 0 1px rgba(var(--c), .25); }
    .tone-fill { background: rgb(var(--c)); }
    .tone-edge { border-left: 3px solid rgb(var(--c)); }

    /* Hero */
    .tg-hero { position: relative; overflow: hidden; border-radius: .75rem; padding: 1.5rem; color: #fff;
               background: radial-gradient(120% 140% at 100% 0%, rgba(var(--primary-500), .35) 0%, transparent 55%), rgb(var(--gray-900)); box-shadow: 0 0 0 1px rgba(255,255,255,.08); }
    .dark .tg-hero { background: radial-gradient(120% 140% at 100% 0%, rgba(var(--primary-500), .25) 0%, transparent 55%), rgb(var(--gray-950)); }
    .tg-hero .tg-sub, .tg-hero .tg-label { color: rgb(var(--gray-400)); }
    .tg-hero-row { display: flex; flex-direction: column; gap: 1.25rem; }
    @media (min-width: 1024px) { .tg-hero-row { flex-direction: row; align-items: center; justify-content: space-between; } }
    .tg-hero .tg-select { background-color: rgba(255,255,255,.06); color: #fff; border-color: rgba(255,255,255,.15); }
    .tg-hero .tg-select option { color: #000; }
    .tg-dot { width: .5rem; height: .5rem; border-radius: 999px; display: inline-block; }
    .tg-pulse { animation: tgPulse 1.6s ease-in-out infinite; }
    @keyframes tgPulse { 0%,100% { opacity: 1 } 50% { opacity: .35 } }

    /* Jalur kesihatan 24 jam */
    .tg-strip { display: grid; grid-template-columns: repeat(24, minmax(0, 1fr)); gap: 3px; margin-top: .75rem; }
    .tg-strip > div { height: 2.25rem; border-radius: .3rem; }

    /* Senarai risiko */
    .tg-risk { display: flex; flex-direction: column; gap: .6rem; padding: .9rem 1rem; border-radius: .6rem; background: var(--tg-soft); box-shadow: 0 0 0 1px var(--tg-ring); }
    .tg-factor { display: flex; justify-content: space-between; gap: .5rem; font-size: .75rem; padding: .35rem 0; border-bottom: 1px dashed var(--tg-line); }
    .tg-factor:last-child { border-bottom: 0; }

    /* Modal */
    .tg-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(3, 7, 18, .55); backdrop-filter: blur(3px); }
    .tg-modal-box { width: 100%; max-height: 90vh; overflow-y: auto; background: var(--tg-card); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 0 0 1px var(--tg-ring), 0 20px 50px rgba(0,0,0,.3); }
    .tg-steps { margin: .5rem 0 0; padding-left: 1.2rem; font-size: .85rem; line-height: 1.55; }
    .tg-steps li { margin-bottom: .35rem; }
    .tg-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .tg-table th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--tg-muted); padding: .5rem; border-bottom: 1px solid var(--tg-line); }
    .tg-table td { padding: .55rem .5rem; border-bottom: 1px solid var(--tg-line); }
    .tg-table tr.is-now td { background: rgba(var(--primary-500), .1); font-weight: 700; }

    /* Carta */
    .tg-chart text { font-size: 10px; fill: var(--tg-muted); }
    .tg-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .72rem; color: var(--tg-muted); }
    .tg-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .tg-legend i { display: inline-block; width: 1.1rem; height: 0; border-top: 2px solid; }
</style>

@php
    $toneForScore = fn ($s) => $s === null ? 't-gray' : ($s >= 85 ? 't-success' : ($s >= 60 ? 't-warning' : 't-danger'));
    $sevTone = ['critical' => 't-danger', 'warning' => 't-warning', 'watch' => 't-info'];
    $connTone = ['online' => 't-success', 'stale' => 't-warning', 'offline' => 't-danger', 'no-data' => 't-gray'];
    $connText = ['online' => 'Live', 'stale' => 'Delayed', 'offline' => 'Offline', 'no-data' => 'No data'];
@endphp

<div class="tg" wire:poll.30s x-data="{ riskOpen: false, risk: null, feedOpen: false, methodOpen: false }">

@if (! $a)
    <div class="tg-card">
        <h2 class="tg-h2">No pond assigned</h2>
        <p class="tg-sub">Register a pond device (MAC address) to your account to see growth and risk analysis.</p>
    </div>
@else
    @php
        $snap = $a['snapshot']; $g = $a['growth']; $h = $a['health']; $feed = $a['feed']; $dz = $a['disease']; $nh3 = $a['ammonia'];
        $healthScore = $h['score'] ?? null;
        $activeRisks = collect($a['risks'])->whereIn('severity', ['critical', 'warning'])->count();
    @endphp

    {{-- 1. HERO --}}
    <div class="tg-hero">
        <div class="tg-hero-row">
            <div style="flex: 1; min-width: 0;">
                <div class="tg-wrap">
                    <select wire:model.live="deviceId" class="tg-select" aria-label="Select pond">
                        @foreach ($ponds as $pond)
                            <option value="{{ $pond->device_id }}">{{ $pond->pond_name }}</option>
                        @endforeach
                    </select>
                    <span class="tg-badge tone-bg {{ $connTone[$snap['connection']] }}">
                        <span class="tg-dot tone-fill {{ $snap['connection'] === 'online' ? 'tg-pulse' : '' }}"></span>
                        {{ $connText[$snap['connection']] }}
                    </span>
                </div>
                <h1 style="margin: .9rem 0 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -.01em;">
                    Red Tilapia · {{ $a['device']->pond_name }}
                </h1>
                <p class="tg-sub" style="margin-top: .35rem;">
                    Last reading {{ $snap['latest_at']?->diffForHumans() ?? 'never' }}
                    · {{ number_format($snap['readings_24h']) }} readings in 24 h
                    · Day {{ $g['days'] }} of culture
                </p>
                <div class="tg-wrap" style="margin-top: 1rem;">
                    @php $live = $snap['connection'] === 'online' || $snap['connection'] === 'stale'; @endphp
                    <span class="tg-badge tone-bg {{ $live ? $toneForScore($healthScore) : 't-gray' }}">{{ $live ? '' : 'Last known: ' }}{{ $h['level'] ?? 'No data' }}</span>
                    <span class="tg-badge tone-bg t-primary">{{ $g['stage']['stage'] }} · {{ $g['current_weight'] }} g</span>
                    @if ($activeRisks)
                        <span class="tg-badge tone-bg t-danger">{{ $activeRisks }} active risk{{ $activeRisks > 1 ? 's' : '' }}</span>
                    @endif
                    <button type="button" class="tg-btn" style="background: rgba(255,255,255,.08); color: #fff;" wire:click="$refresh">
                        <x-filament::icon icon="heroicon-m-arrow-path" style="width: .9rem; height: .9rem;" wire:loading.class="tg-pulse" /> Refresh
                    </button>
                    <button type="button" class="tg-btn" style="background: rgba(255,255,255,.08); color: #fff;" @click="methodOpen = true">
                        <x-filament::icon icon="heroicon-m-academic-cap" style="width: .9rem; height: .9rem;" /> How it works
                    </button>
                    @if ($telegram['ready'])
                        @if ($telegram['connected'])
                            <span class="tg-badge tone-bg t-success">✓ Telegram alerts on</span>
                            <button type="button" class="tg-btn" style="background: rgba(255,255,255,.08); color: #fff;"
                                    wire:click="disconnectTelegram" wire:confirm="Stop Telegram alerts on this account?">Disconnect</button>
                        @else
                            <a href="{{ $telegram['link'] }}" target="_blank" rel="noopener" class="tg-btn" style="background: #229ED9; color: #fff; text-decoration: none;">
                                <x-filament::icon icon="heroicon-m-paper-airplane" style="width: .9rem; height: .9rem;" /> Connect Telegram
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            @php $circ = round(2 * M_PI * 52, 2); @endphp
            <div style="position: relative; width: 9rem; height: 9rem; flex-shrink: 0; align-self: center; opacity: {{ $live ? 1 : .55 }};" class="{{ $toneForScore($healthScore) }}">
                <svg viewBox="0 0 120 120" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="10"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="rgb(var(--c))" stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ round($circ * (1 - ($healthScore ?? 0) / 100), 2) }}"
                            style="transition: stroke-dashoffset .8s ease;"/>
                </svg>
                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-size: 2rem; font-weight: 900;" class="tg-num">{{ $healthScore ?? '—' }}</span>
                    <span class="tg-label">{{ $live ? 'Health index' : 'Last known' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 1b. AMARAN PERANTI LUAR TALIAN --}}
    @if (in_array($snap['connection'], ['offline', 'no-data']))
        <div class="tg-card t-danger tone-edge" style="border-left-width: 5px;">
            <div class="tg-row" style="align-items: flex-start;">
                <div>
                    <div style="font-weight: 800; font-size: 1rem;" class="tone-text">
                        ⚠ {{ $snap['connection'] === 'no-data' ? 'No data from this pond yet' : 'Device offline since ' . $snap['latest_at']->format('d M, H:i') }}
                    </div>
                    <p class="tg-small" style="margin-top: .25rem;">
                        {{ $snap['connection'] === 'no-data' ? 'Connect the ESP32 sensor to start monitoring.' : 'Values below are the last known readings, not live water conditions.' }}
                    </p>
                </div>
            </div>
            <ol class="tg-steps tg-small" style="margin-top: .5rem;">
                <li>Check the ESP32 is powered (LED on).</li>
                <li>Check the 2.4 GHz Wi-Fi or phone hotspot is on and in range.</li>
                <li>Restart the device. Readings should appear here within a minute.</li>
            </ol>
        </div>
    @endif

    {{-- 1c. SEKILAS PANDANG: 3 soalan penternak --}}
    @php
        $waterRisks = collect($a['risks'])->where('category', '!=', 'System')->values();
        $topRisk = $waterRisks->first() ?? collect($a['risks'])->first();
        $worst = $waterRisks->first()['severity'] ?? null;
        [$stateText, $stateTone, $stateIcon] = match (true) {
            $snap['current'] === null  => ['No data', 't-gray', '—'],
            $worst === 'critical'      => ['Act now', 't-danger', '✕'],
            $worst === 'warning'       => ['Needs attention', 't-warning', '!'],
            $worst === 'watch'         => ['Keep an eye on it', 't-info', 'i'],
            default                    => ['Healthy', 't-success', '✓'],
        };
    @endphp
    <div class="tg-grid tg-grid-3">
        <div class="tg-card {{ $stateTone }} tone-edge" style="border-left-width: 5px;">
            <div class="tg-label">Is my water OK?</div>
            <div class="tg-row" style="justify-content: flex-start; margin-top: .5rem;">
                <span class="tone-bg" style="width: 2.4rem; height: 2.4rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.1rem; flex-shrink: 0;">{{ $stateIcon }}</span>
                <div>
                    <div style="font-size: 1.35rem; font-weight: 800;" class="tone-text">{{ $stateText }}</div>
                    <div class="tg-tiny">
                        @if ($snap['current'])
                            {{ round($snap['current']['temperature'], 1) }} °C · pH {{ round($snap['current']['ph_level'], 2) }} · {{ round($snap['current']['turbidity']) }} NTU
                            {{ $snap['connection'] === 'online' ? '' : '(last known)' }}
                        @else
                            Waiting for the first reading
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="tg-card">
            <div class="tg-label">What should I do?</div>
            @if ($topRisk)
                <div style="font-weight: 700; margin-top: .5rem;">{{ $topRisk['actions'][0] }}</div>
                <button type="button" class="tg-btn tg-btn-primary" style="margin-top: .6rem;"
                        x-on:click="risk = {{ \Illuminate\Support\Js::from($topRisk) }}; riskOpen = true">
                    Full plan: {{ $topRisk['title'] }}
                </button>
            @else
                <div style="font-weight: 700; margin-top: .5rem;">Nothing urgent. Keep the normal routine.</div>
                <div class="tg-tiny" style="margin-top: .3rem;">Today: {{ $feed['stage']['meals'] }} meals of {{ $feed['per_meal_g'] }} g feed.</div>
            @endif
        </div>

        <div class="tg-card">
            <div class="tg-label">How are my fish growing?</div>
            <div style="font-size: 1.35rem; font-weight: 800; margin-top: .5rem;" class="tg-num">{{ $g['current_weight'] }} g <span class="tg-small">per fish</span></div>
            <div class="tg-tiny">
                {{ $g['performance_pct'] }}% of ideal growth ·
                @if ($g['days_to_harvest'] === 0) ready to harvest
                @elseif ($g['days_to_harvest'] === null) growth stalled
                @else harvest ~{{ $g['harvest_date']->format('d M Y') }}
                @endif
            </div>
        </div>
    </div>

    {{-- 2. KPI --}}
    <div class="tg-grid tg-grid-4">
        <div class="tg-card">
            <div class="tg-label">Estimated weight</div>
            <div class="tg-big" style="margin-top: .4rem;">{{ $g['current_weight'] }}<span class="tg-small"> g/fish</span></div>
            <div class="tg-bar"><div class="tone-fill t-primary" style="width: {{ $g['progress_pct'] }}%"></div></div>
            <div class="tg-tiny" style="margin-top: .45rem;">{{ $g['progress_pct'] }}% of {{ (int) $g['target_weight'] }} g target · +{{ $g['daily_gain'] }} g/day</div>
        </div>

        <div class="tg-card">
            <div class="tg-label">Growth performance</div>
            <div class="tg-big {{ $g['performance_pct'] >= 90 ? 't-success' : ($g['performance_pct'] >= 75 ? 't-warning' : 't-danger') }} tone-text" style="margin-top: .4rem;">{{ $g['performance_pct'] }}%</div>
            <div class="tg-bar"><div class="tone-fill {{ $g['performance_pct'] >= 90 ? 't-success' : ($g['performance_pct'] >= 75 ? 't-warning' : 't-danger') }}" style="width: {{ min(100, $g['performance_pct']) }}%"></div></div>
            <div class="tg-tiny" style="margin-top: .45rem;">of ideal-condition growth ({{ $g['ideal_weight_now'] }} g) · SGR {{ $g['sgr'] }}%/day</div>
        </div>

        <div class="tg-card">
            <div class="tg-label">Harvest forecast</div>
            @if ($g['days_to_harvest'] === 0)
                <div class="tg-big t-success tone-text" style="margin-top: .4rem;">Ready</div>
                <div class="tg-tiny" style="margin-top: .9rem;">Target weight reached. Stop feeding 24 h before harvest.</div>
            @elseif ($g['days_to_harvest'] === null)
                <div class="tg-big t-danger tone-text" style="margin-top: .4rem;">Stalled</div>
                <div class="tg-tiny" style="margin-top: .9rem;">No growth under recent conditions. Fix water quality first.</div>
            @else
                <div class="tg-big" style="margin-top: .4rem;">{{ $g['harvest_date']->format('d M Y') }}</div>
                <div class="tg-tiny" style="margin-top: .9rem;">In {{ $g['days_to_harvest'] }} days · based on {{ $g['projection_basis'] }}</div>
            @endif
        </div>

        <div class="tg-card">
            <div class="tg-label">Aeromonas risk</div>
            @if ($dz)
                <div class="tg-row" style="margin-top: .4rem;">
                    <div class="tg-big {{ $dz['tone'] === 'danger' ? 't-danger' : ($dz['tone'] === 'warning' ? 't-warning' : 't-success') }} tone-text">{{ $dz['level'] }}</div>
                    <span class="tg-small tg-num">{{ $dz['score'] }}/100</span>
                </div>
                <div class="tg-bar"><div class="tone-fill {{ $dz['tone'] === 'danger' ? 't-danger' : ($dz['tone'] === 'warning' ? 't-warning' : 't-success') }}" style="width: {{ max(3, $dz['score']) }}%"></div></div>
                <div class="tg-tiny" style="margin-top: .45rem;">Early-warning index, not a diagnosis</div>
            @else
                <div class="tg-big" style="margin-top: .4rem;">—</div>
                <div class="tg-tiny" style="margin-top: .9rem;">Needs sensor data</div>
            @endif
        </div>
    </div>

    {{-- 3. CARTA PERTUMBUHAN + BIOMAS & MAKANAN --}}
    @php
        $s = $g['series'];
        $all = array_merge($s['actual'], $s['projected'], $s['ideal']);
        $xMax = max(30, max(array_column($all, 0)));
        $yMax = max($g['target_weight'] * 1.1, max(array_column($all, 1)));
        $yStep = $yMax > 600 ? 200 : ($yMax > 250 ? 100 : 50);
        $xStep = $xMax > 180 ? 60 : 30;
    @endphp
    <div class="tg-grid tg-main">
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

                {{-- Garis sasaran --}}
                <line x1="36" x2="628" y1="{{ $this->chartY($g['target_weight'], $yMax) }}" y2="{{ $this->chartY($g['target_weight'], $yMax) }}" stroke="rgb(var(--success-500))" stroke-dasharray="2 4" stroke-width="1.5"/>
                <text x="628" y="{{ $this->chartY($g['target_weight'], $yMax) - 5 }}" text-anchor="end" style="fill: rgb(var(--success-600)); font-weight: 700;">Target {{ (int) $g['target_weight'] }}g</text>

                {{-- Hari ini --}}
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
                <p class="tg-tiny" style="margin-top: .6rem;">
                    For comparison, the Random Forest regressor's latest estimate is {{ round($a['ml']['estimated_weight'], 1) }} g.
                </p>
            @endif
        </div>

        <div class="tg-card" style="display: flex; flex-direction: column; gap: .75rem;">
            <div class="tg-row">
                <h2 class="tg-h2">Biomass & feeding</h2>
                <button type="button" class="tg-btn" @click="feedOpen = true">Feeding matrix</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: .6rem;">
                <div class="tg-soft">
                    <div class="tg-label">Fish alive</div>
                    <div style="font-size: 1.15rem; font-weight: 800;" class="tg-num">{{ number_format($g['alive']) }}<span class="tg-tiny"> / {{ number_format($g['stock']) }}</span></div>
                    <div class="tg-tiny">Est. survival {{ $g['survival_pct'] }}%</div>
                </div>
                <div class="tg-soft">
                    <div class="tg-label">Biomass</div>
                    <div style="font-size: 1.15rem; font-weight: 800;" class="tg-num">{{ $g['biomass_kg'] }}<span class="tg-tiny"> kg</span></div>
                    <div class="tg-tiny">{{ $g['stage']['stage'] }} stage</div>
                </div>
            </div>

            <div class="tg-soft {{ $feed['withhold'] ? 't-danger' : ($feed['multiplier'] < 1 ? 't-warning' : 't-success') }} tone-edge">
                <div class="tg-label">Today's feed</div>
                @if ($feed['withhold'])
                    <div style="font-size: 1.4rem; font-weight: 800;" class="tone-text">Withhold feed</div>
                    <div class="tg-tiny">Because of {{ strtolower($feed['reason']) }}. Resume once it is resolved.</div>
                @else
                    <div style="font-size: 1.4rem; font-weight: 800;" class="tg-num">
                        {{ $feed['adjusted_kg'] < 1 ? round($feed['adjusted_kg'] * 1000) . ' g' : $feed['adjusted_kg'] . ' kg' }}
                        <span class="tg-tiny">/ day</span>
                    </div>
                    <div class="tg-tiny">
                        {{ $feed['stage']['meals'] }} meals × {{ $feed['per_meal_g'] }} g · {{ $feed['stage']['rate'] }}% body weight
                        @if ($feed['multiplier'] < 1)
                            <br><strong class="tone-text">−{{ round((1 - $feed['multiplier']) * 100) }}%</strong> due to {{ strtolower($feed['reason']) }}
                        @endif
                    </div>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: .6rem;">
                <div class="tg-soft"><div class="tg-label">Protein</div><div style="font-weight: 700;">{{ $feed['stage']['protein'] }}</div></div>
                <div class="tg-soft"><div class="tg-label">Pellet</div><div style="font-weight: 700;">{{ $feed['stage']['pellet'] }}</div></div>
            </div>
        </div>
    </div>

    {{-- 4. KUALITI AIR --}}
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
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none" style="width: 6rem; height: 1.8rem;">
                            <path d="{{ $this->sparklinePath($p['spark']) }}" fill="none" stroke="rgb(var(--c))" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @endif
                </div>
                <div class="tg-bar"><div class="tone-fill" style="width: {{ max(3, $p['score']) }}%"></div></div>
                <div class="tg-tiny" style="margin-top: .45rem;">
                    Ideal {{ $p['ideal'] }} {{ $p['unit'] }}
                    @if ($p['stats'])
                        · 24 h {{ $p['stats']['min'] }}–{{ $p['stats']['max'] }} · <strong>{{ $p['in_range'] }}%</strong> in range
                    @endif
                </div>
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
                <div class="tg-tiny" style="margin-top: .45rem;">At this pH & temperature keep total ammonia below <strong>{{ $nh3['safe_tan'] }} mg/L</strong></div>
            </div>
        @endif
    </div>

    {{-- 5. JALUR KESIHATAN 24 JAM --}}
    <div class="tg-card">
        <div class="tg-row">
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
                <div class="{{ $toneForScore($hr['score']) }}"
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

    {{-- 6. RISIKO & PELAN TINDAKAN --}}
    <div class="tg-grid tg-main">
        <div class="tg-card">
            <div class="tg-row" style="margin-bottom: .9rem;">
                <h2 class="tg-h2">Risks & action plans</h2>
                @if (count($a['risks']))
                    <span class="tg-badge tone-bg {{ $activeRisks ? 't-danger' : 't-info' }}">{{ count($a['risks']) }} item{{ count($a['risks']) > 1 ? 's' : '' }}</span>
                @endif
            </div>

            @forelse ($a['risks'] as $risk)
                <div class="tg-risk {{ $sevTone[$risk['severity']] }} tone-edge" style="margin-bottom: .6rem;">
                    <div class="tg-row" style="align-items: flex-start;">
                        <div style="min-width: 0;">
                            <div class="tg-wrap">
                                <span class="tg-badge tone-bg">{{ ucfirst($risk['severity']) }}</span>
                                <span class="tg-tiny">{{ $risk['category'] }}</span>
                            </div>
                            <div style="font-weight: 700; margin-top: .35rem;">{{ $risk['title'] }}</div>
                            <div class="tg-small" style="margin-top: .15rem;">{{ $risk['current'] }} · ideal {{ $risk['ideal'] }}</div>
                            <div class="tg-tiny" style="margin-top: .15rem;">{{ $risk['evidence'] }}</div>
                        </div>
                        <button type="button" class="tg-btn tg-btn-primary" style="flex-shrink: 0;"
                                x-on:click="risk = {{ \Illuminate\Support\Js::from($risk) }}; riskOpen = true">Action plan</button>
                    </div>
                </div>
            @empty
                <div class="tg-risk t-success tone-edge">
                    <div style="font-weight: 700;">No active risks</div>
                    <div class="tg-small">All key parameters meet red tilapia farming standards. Keep the routine.</div>
                </div>
            @endforelse
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="tg-card">
                <h2 class="tg-h2" style="margin-bottom: .75rem;">Smart insights</h2>
                @foreach ($a['insights'] as $in)
                    <div class="tg-soft t-{{ $in['tone'] }} tone-edge" style="margin-bottom: .5rem;">
                        <div style="font-weight: 700; font-size: .85rem;">{{ $in['title'] }}</div>
                        <div class="tg-tiny" style="margin-top: .15rem;">{{ $in['text'] }}</div>
                    </div>
                @endforeach
            </div>

            @if ($dz && $dz['factors'])
                <div class="tg-card">
                    <h2 class="tg-h2">Aeromonas risk factors</h2>
                    <p class="tg-sub" style="margin-bottom: .5rem;">What is driving the index ({{ $dz['score'] }}/100)</p>
                    @foreach ($dz['factors'] as $f)
                        <div class="tg-factor"><span>{{ $f['label'] }}</span><strong class="tg-num">+{{ $f['points'] }}</strong></div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

    {{-- MODAL: Pelan tindakan risiko --}}
    <div class="tg-modal" x-show="riskOpen" x-cloak x-transition.opacity @keydown.escape.window="riskOpen = false">
        <div class="tg-modal-box" style="max-width: 36rem;" @click.outside="riskOpen = false">
            <template x-if="risk">
                <div>
                    <div class="tg-row">
                        <div>
                            <span class="tg-badge tone-bg" :class="{ 't-danger': risk.severity === 'critical', 't-warning': risk.severity === 'warning', 't-info': risk.severity === 'watch' }" x-text="risk.severity.toUpperCase()"></span>
                            <h3 class="tg-h2" style="font-size: 1.15rem; margin-top: .4rem;" x-text="risk.title"></h3>
                        </div>
                        <button type="button" class="tg-btn" @click="riskOpen = false">Close</button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: 1rem;">
                        <div class="tg-soft"><div class="tg-label">Current</div><div style="font-weight: 700;" x-text="risk.current"></div></div>
                        <div class="tg-soft"><div class="tg-label">Target</div><div style="font-weight: 700;" x-text="risk.ideal"></div></div>
                    </div>
                    <div class="tg-tiny" style="margin-top: .6rem;" x-text="risk.evidence"></div>
                    <div style="margin-top: 1rem;">
                        <div class="tg-label">Why it matters</div>
                        <p style="font-size: .85rem; margin: .3rem 0 0;" x-text="risk.impact"></p>
                    </div>
                    <div class="tg-soft t-success tone-edge" style="margin-top: 1rem;">
                        <div class="tg-label">What to do now</div>
                        <ol class="tg-steps">
                            <template x-for="step in risk.actions"><li x-text="step"></li></template>
                        </ol>
                        <p class="tg-tiny" style="margin-top: .4rem;" x-show="risk.feed < 1"
                           x-text="risk.feed <= 0 ? 'Feeding: withhold feed until resolved.' : 'Feeding: reduce ration by ' + Math.round((1 - risk.feed) * 100) + '%.'"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL: Matriks pemakanan --}}
    @if ($a)
    <div class="tg-modal" x-show="feedOpen" x-cloak x-transition.opacity @keydown.escape.window="feedOpen = false">
        <div class="tg-modal-box" style="max-width: 48rem;" @click.outside="feedOpen = false">
            <div class="tg-row">
                <div>
                    <h3 class="tg-h2" style="font-size: 1.15rem;">Growth stage & feeding matrix</h3>
                    <p class="tg-sub">Stage is chosen from the estimated fish weight ({{ $a['growth']['current_weight'] }} g)</p>
                </div>
                <button type="button" class="tg-btn" @click="feedOpen = false">Close</button>
            </div>
            <div style="overflow-x: auto; margin-top: 1rem;">
                <table class="tg-table">
                    <thead><tr><th>Stage</th><th>Weight</th><th>Feed (% BW/day)</th><th>Protein</th><th>Meals/day</th><th>Pellet</th></tr></thead>
                    <tbody>
                        @php $prev = 0; @endphp
                        @foreach ($a['feed']['table'] as $i => $row)
                            <tr class="{{ $a['feed']['stage']['stage'] === $row['stage'] ? 'is-now' : '' }}">
                                <td>{{ $row['stage'] }} @if ($a['feed']['stage']['stage'] === $row['stage']) <span class="tg-badge tone-bg t-primary">NOW</span> @endif</td>
                                <td class="tg-num">{{ $row['max_weight'] ? "{$prev}–{$row['max_weight']} g" : "≥ {$prev} g" }}</td>
                                <td class="tg-num">{{ $row['rate'] }}%</td>
                                <td>{{ $row['protein'] }}</td>
                                <td class="tg-num">{{ $row['meals'] }}</td>
                                <td>{{ $row['pellet'] }}</td>
                            </tr>
                            @php $prev = $row['max_weight']; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
            <ul class="tg-steps tg-small" style="margin-top: 1rem;">
                <li>Feed rations are reduced automatically when a risk is active (for example, −50% at critical temperature, withheld at critical high pH).</li>
                <li>Feed should be eaten within 15–20 minutes. Remove leftovers to protect water quality.</li>
                <li>Stop feeding 24 hours before harvest or transport.</li>
            </ul>
        </div>
    </div>
    @endif

    {{-- MODAL: Cara ia berfungsi (metodologi) --}}
    <div class="tg-modal" x-show="methodOpen" x-cloak x-transition.opacity @keydown.escape.window="methodOpen = false">
        <div class="tg-modal-box" style="max-width: 44rem;" @click.outside="methodOpen = false">
            <div class="tg-row">
                <h3 class="tg-h2" style="font-size: 1.15rem;">How the analysis works</h3>
                <button type="button" class="tg-btn" @click="methodOpen = false">Close</button>
            </div>
            <div style="font-size: .85rem; line-height: 1.6; margin-top: .75rem;">
                <p><strong>1. Clean data.</strong> Readings that are physically impossible (for example −127 °C from a disconnected DS18B20, or pH outside 0–14) are discarded. The current value is the median of the last 15 valid readings, so a single spike cannot trigger a false alarm.</p>
                <p style="margin-top: .6rem;"><strong>2. Parameter scores.</strong> Each parameter scores 100 inside the optimal band (temperature 26–32 °C, pH 6.5–8.5, turbidity &lt; 50 NTU), falls linearly to 50 at the edge of the acceptable band, and to 0 at the critical limit.</p>
                <p style="margin-top: .6rem;"><strong>3. Health index.</strong> 60% weighted average (pH 40%, temperature 35%, turbidity 25%) plus 40% of the worst score, so one bad parameter cannot be hidden by good ones. Large 24-hour pH or temperature swings subtract a stability penalty.</p>
                <p style="margin-top: .6rem;"><strong>4. Growth model.</strong> Thermal-unit Growth Coefficient (Iwama &amp; Tautz, 1981): W<sub>t</sub><sup>1/3</sup> = W<sub>0</sub><sup>1/3</sup> + TGC × Σ(T × f). It uses the daily mean temperature measured in this pond, where f reduces growth outside 26–32 °C and under poor pH or turbidity. The projection assumes the last 7 days' conditions continue.</p>
                <p style="margin-top: .6rem;"><strong>5. Toxic ammonia.</strong> The share of total ammonia in the toxic un-ionised NH₃ form is calculated from pH and temperature (Emerson et al., 1975). Tilapia should be kept below about 0.05 mg/L NH₃.</p>
                <p style="margin-top: .6rem;"><strong>6. Aeromonas early warning.</strong> A transparent points index combining known stress triggers for Motile Aeromonas Septicaemia: warm water, poor water quality, high organic load, unstable pH and the vulnerable juvenile stage. It is not a diagnosis.</p>
                <p style="margin-top: .6rem;"><strong>7. ML second opinion.</strong> The Random Forest classifier's latest result is compared with the rule-based result, and any disagreement is shown as an insight.</p>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
