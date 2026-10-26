<x-filament-panels::page>
@include('filament.pages.pond.partials.styles')

<div class="tg" wire:poll.30s>
@if (! $a)
    @include('filament.pages.pond.partials.empty')
@else
    @php
        $snap = $a['snapshot']; $g = $a['growth']; $h = $a['health']; $feed = $a['feed']; $dz = $a['disease'];
        $healthScore = $h['score'] ?? null;
        $live = in_array($snap['connection'], ['online', 'stale']);
        $connTone = ['online' => 't-success', 'stale' => 't-warning', 'offline' => 't-danger', 'no-data' => 't-gray'][$snap['connection']];
        $connText = ['online' => 'Live', 'stale' => 'Delayed', 'offline' => 'Offline', 'no-data' => 'No data'][$snap['connection']];

        $waterRisks = collect($a['risks'])->where('category', '!=', 'System')->values();
        $activeRisks = $waterRisks->whereIn('severity', ['critical', 'warning'])->count();
        $topRisk = $waterRisks->first();
        $worst = $topRisk['severity'] ?? null;
        [$stateText, $stateTone, $stateIcon] = match (true) {
            $snap['current'] === null => ['No data', 't-gray', '—'],
            $worst === 'critical'     => ['Act now', 't-danger', '✕'],
            $worst === 'warning'      => ['Needs attention', 't-warning', '!'],
            $worst === 'watch'        => ['Keep an eye on it', 't-info', 'i'],
            default                   => ['Healthy', 't-success', '✓'],
        };
        $risksUrl  = $this->pondUrl(\App\Filament\Pages\Pond\RisksActions::class);
        $waterUrl  = $this->pondUrl(\App\Filament\Pages\Pond\WaterQuality::class);
        $growthUrl = $this->pondUrl(\App\Filament\Pages\Pond\GrowthFeeding::class);
    @endphp

    {{-- HERO --}}
    <div class="tg-hero">
        <div class="tg-hero-row">
            <div style="flex: 1; min-width: 0;">
                <div class="tg-wrap">
                    <select wire:model.live="deviceId" class="tg-select" aria-label="Select pond">
                        @foreach ($ponds as $pond)
                            <option value="{{ $pond->device_id }}">{{ $pond->pond_name }}</option>
                        @endforeach
                    </select>
                    <span class="tg-badge tone-bg {{ $connTone }}">
                        <span class="tg-dot tone-fill {{ $snap['connection'] === 'online' ? 'tg-pulse' : '' }}"></span>{{ $connText }}
                    </span>
                </div>
                <h1 style="margin: .9rem 0 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -.01em;">
                    Red Tilapia · {{ $a['device']->pond_name }}
                </h1>
                <p class="tg-sub" style="margin-top: .35rem;">
                    Last reading {{ $snap['latest_at']?->diffForHumans() ?? 'never' }} · Day {{ $g['days'] }} of culture · {{ $g['stage']['stage'] }} stage
                </p>
                <div class="tg-wrap" style="margin-top: 1rem;">
                    <button type="button" class="tg-btn" style="background: rgba(255,255,255,.08); color: #fff;" wire:click="$refresh">
                        <x-filament::icon icon="heroicon-m-arrow-path" style="width: .9rem; height: .9rem;" wire:loading.class="tg-pulse" /> Refresh
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
            <div style="position: relative; width: 8.5rem; height: 8.5rem; flex-shrink: 0; align-self: center; opacity: {{ $live ? 1 : .55 }};" class="{{ $this->scoreTone($healthScore) }}">
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

    @include('filament.pages.pond.partials.subnav')
    @include('filament.pages.pond.partials.offline')

    {{-- 3 SOALAN PENTERNAK --}}
    <div class="tg-grid tg-grid-3">
        <div class="tg-card {{ $stateTone }} tone-edge" style="border-left-width: 5px;">
            <div class="tg-label">Is my water OK?</div>
            <div class="tg-row" style="justify-content: flex-start; margin-top: .5rem;">
                <span class="tone-bg" style="width: 2.4rem; height: 2.4rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.1rem; flex-shrink: 0;">{{ $stateIcon }}</span>
                <div>
                    <div style="font-size: 1.35rem; font-weight: 800;" class="tone-text">{{ $stateText }}</div>
                    <div class="tg-tiny">
                        @if ($snap['current'])
                            {{ round($snap['current']['temperature'], 1) }} °C · pH {{ round($snap['current']['ph_level'], 2) }} · {{ round($snap['current']['turbidity']) }} NTU {{ $live ? '' : '(last known)' }}
                        @else
                            Waiting for the first reading
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="tg-card">
            <div class="tg-label">What should I do?</div>
            @if ($topRisk && $topRisk['severity'] !== 'watch')
                <div style="font-weight: 700; margin-top: .5rem;">{{ $topRisk['actions'][0] }}</div>
                <a href="{{ $risksUrl }}#risk-{{ $topRisk['key'] }}" class="tg-btn tg-btn-primary" style="margin-top: .6rem; text-decoration: none;">
                    Full plan: {{ $topRisk['title'] }} →
                </a>
            @else
                <div style="font-weight: 700; margin-top: .5rem;">Nothing urgent. Keep the normal routine.</div>
                <div class="tg-tiny" style="margin-top: .3rem;">Feed {{ $feed['stage']['meals'] }} meals of {{ $feed['per_meal_g'] }} g today.</div>
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

    {{-- JUBIN RINGKASAN → HALAMAN BUTIRAN --}}
    <div class="tg-grid tg-grid-4">
        <a href="{{ $waterUrl }}" class="tg-card tg-tile">
            <div class="tg-label">Water quality</div>
            <div class="tg-big {{ $this->scoreTone($healthScore) }} tone-text">{{ $healthScore ?? '—' }}<span class="tg-small">/100</span></div>
            <div class="tg-wrap">
                @foreach ($a['parameters'] as $p)
                    <span class="tg-badge tone-bg {{ $p['status'] === 'optimal' ? 't-success' : ($p['status'] === 'warning' ? 't-warning' : 't-danger') }}">
                        {{ $p['status'] === 'optimal' ? '✓' : '!' }} {{ $p['label'] }}
                    </span>
                @endforeach
            </div>
            <span class="tg-more">Trends & 24 h timeline →</span>
        </a>

        <a href="{{ $growthUrl }}" class="tg-card tg-tile">
            <div class="tg-label">Growth</div>
            <div class="tg-big">{{ $g['current_weight'] }}<span class="tg-small"> g</span></div>
            <div class="tg-bar" style="margin-top: .2rem;"><div class="tone-fill t-primary" style="width: {{ $g['progress_pct'] }}%"></div></div>
            <div class="tg-tiny">{{ $g['progress_pct'] }}% of {{ (int) $g['target_weight'] }} g target · {{ $g['performance_pct'] }}% of ideal</div>
            <span class="tg-more">Growth chart & harvest →</span>
        </a>

        <a href="{{ $growthUrl }}#feeding" class="tg-card tg-tile {{ $feed['withhold'] ? 't-danger' : ($feed['multiplier'] < 1 ? 't-warning' : '') }}">
            <div class="tg-label">Feed today</div>
            @if ($feed['withhold'])
                <div class="tg-big tone-text">Withhold</div>
                <div class="tg-tiny">Because of {{ strtolower($feed['reason']) }}</div>
            @else
                <div class="tg-big">{{ $feed['adjusted_kg'] < 1 ? round($feed['adjusted_kg'] * 1000) . ' g' : $feed['adjusted_kg'] . ' kg' }}</div>
                <div class="tg-tiny">
                    {{ $feed['stage']['meals'] }} × {{ $feed['per_meal_g'] }} g
                    @if ($feed['multiplier'] < 1) · <strong class="tone-text">−{{ round((1 - $feed['multiplier']) * 100) }}%</strong> @endif
                </div>
            @endif
            <span class="tg-more">Feeding plan →</span>
        </a>

        <a href="{{ $risksUrl }}" class="tg-card tg-tile {{ $activeRisks ? 't-danger' : 't-success' }}">
            <div class="tg-label">Risks</div>
            <div class="tg-big tone-text">{{ $activeRisks ?: 'None' }}<span class="tg-small">{{ $activeRisks ? ' active' : '' }}</span></div>
            <div class="tg-tiny">Aeromonas risk: <strong>{{ $dz['level'] ?? '—' }}</strong></div>
            <span class="tg-more">Action plans →</span>
        </a>
    </div>
@endif
</div>
</x-filament-panels::page>
