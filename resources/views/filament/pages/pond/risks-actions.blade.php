<x-filament-panels::page>
@include('filament.pages.pond.partials.styles')

<div class="tg" wire:poll.30s>
@if (! $a)
    @include('filament.pages.pond.partials.empty')
@else
    @php $dz = $a['disease']; @endphp
    @include('filament.pages.pond.partials.header')

    <div class="tg-grid tg-main">
        {{-- RISIKO + PELAN TINDAKAN --}}
        <div style="display: flex; flex-direction: column; gap: .75rem; min-width: 0;">
            @forelse ($a['risks'] as $risk)
                @php $tone = $this->severityTone($risk['severity']); @endphp
                <details id="risk-{{ $risk['key'] }}" class="tg-card {{ $tone }} tone-edge" style="border-left-width: 5px;" @if ($risk['severity'] !== 'watch') open @endif>
                    <summary>
                        <div class="tg-row" style="align-items: flex-start;">
                            <div style="min-width: 0;">
                                <div class="tg-wrap">
                                    <span class="tg-badge tone-bg">{{ ucfirst($risk['severity']) }}</span>
                                    <span class="tg-tiny">{{ $risk['category'] }}</span>
                                </div>
                                <div style="font-weight: 800; font-size: 1rem; margin-top: .35rem;">{{ $risk['title'] }}</div>
                                <div class="tg-small" style="margin-top: .15rem;">{{ $risk['current'] }} · target {{ $risk['ideal'] }}</div>
                            </div>
                            <span class="tg-chev tg-small" aria-hidden="true">›</span>
                        </div>
                    </summary>

                    <div style="margin-top: .9rem; display: flex; flex-direction: column; gap: .75rem;">
                        <p class="tg-tiny" style="margin: 0;">{{ $risk['evidence'] }}</p>
                        <div>
                            <div class="tg-label">Why it matters</div>
                            <p style="font-size: .85rem; margin: .25rem 0 0;">{{ $risk['impact'] }}</p>
                        </div>
                        <div class="tg-soft t-success tone-edge">
                            <div class="tg-label">What to do now</div>
                            <ol class="tg-steps">
                                @foreach ($risk['actions'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                            @if ($risk['feed'] < 1)
                                <p class="tg-tiny" style="margin-top: .35rem;">
                                    <strong>Feeding:</strong> {{ $risk['feed'] <= 0 ? 'withhold feed until resolved.' : 'reduce the ration by ' . round((1 - $risk['feed']) * 100) . '%.' }}
                                </p>
                            @endif
                        </div>
                    </div>
                </details>
            @empty
                <div class="tg-card t-success tone-edge" style="border-left-width: 5px;">
                    <div style="font-weight: 800; font-size: 1rem;" class="tone-text">✓ No active risks</div>
                    <div class="tg-small">All key parameters meet red tilapia farming standards. Keep the normal routine.</div>
                </div>
            @endforelse
        </div>

        {{-- PANDANGAN + FAKTOR AEROMONAS --}}
        <div style="display: flex; flex-direction: column; gap: 1.25rem; min-width: 0;">
            @if ($dz)
                @php $dzTone = $dz['tone'] === 'danger' ? 't-danger' : ($dz['tone'] === 'warning' ? 't-warning' : 't-success'); @endphp
                <div class="tg-card {{ $dzTone }}">
                    <div class="tg-row">
                        <h2 class="tg-h2">Aeromonas risk</h2>
                        <span class="tg-badge tone-bg">{{ $dz['level'] }} · {{ $dz['score'] }}/100</span>
                    </div>
                    <div class="tg-bar"><div class="tone-fill" style="width: {{ max(3, $dz['score']) }}%"></div></div>
                    <p class="tg-tiny" style="margin: .5rem 0;">Early-warning index, not a diagnosis.</p>
                    @forelse ($dz['factors'] as $f)
                        <div class="tg-factor"><span>{{ $f['label'] }}</span><strong class="tg-num">+{{ $f['points'] }}</strong></div>
                    @empty
                        <div class="tg-small">No risk factors right now.</div>
                    @endforelse
                </div>
            @endif

            <div class="tg-card">
                <h2 class="tg-h2" style="margin-bottom: .75rem;">Smart insights</h2>
                @foreach ($a['insights'] as $in)
                    <div class="tg-soft t-{{ $in['tone'] }} tone-edge" style="margin-bottom: .5rem;">
                        <div style="font-weight: 700; font-size: .85rem;">{{ $in['title'] }}</div>
                        <div class="tg-tiny" style="margin-top: .15rem;">{{ $in['text'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- CARA IA BERFUNGSI --}}
    <details class="tg-card">
        <summary class="tg-row">
            <div>
                <h2 class="tg-h2">How the analysis works</h2>
                <p class="tg-sub">Methods and references behind the scores and alerts</p>
            </div>
            <span class="tg-chev" aria-hidden="true">›</span>
        </summary>
        <div style="font-size: .85rem; line-height: 1.6; margin-top: .75rem;">
            <p><strong>1. Clean data.</strong> Readings that are physically impossible (for example −127 °C from a disconnected DS18B20, or pH outside 0–14) are discarded. The current value is the median of the last 15 valid readings, so a single spike cannot trigger a false alarm.</p>
                            <p style="margin-top: .6rem;"><strong>2. Parameter scores.</strong> Each parameter scores 100 inside the optimal band (temperature 26–32 °C, pH 6.5–8.5, turbidity &lt; 50 NTU), falls linearly to 50 at the edge of the acceptable band, and to 0 at the critical limit.</p>
                            <p style="margin-top: .6rem;"><strong>3. Health index.</strong> 60% weighted average (pH 40%, temperature 35%, turbidity 25%) plus 40% of the worst score, so one bad parameter cannot be hidden by good ones. Large 24-hour pH or temperature swings subtract a stability penalty.</p>
                            <p style="margin-top: .6rem;"><strong>4. Growth model.</strong> Thermal-unit Growth Coefficient (Iwama &amp; Tautz, 1981): W<sub>t</sub><sup>1/3</sup> = W<sub>0</sub><sup>1/3</sup> + TGC × Σ(T × f). It uses the daily mean temperature measured in this pond, where f reduces growth outside 26–32 °C and under poor pH or turbidity. The projection assumes the last 7 days' conditions continue.</p>
                            <p style="margin-top: .6rem;"><strong>5. Toxic ammonia.</strong> The share of total ammonia in the toxic un-ionised NH₃ form is calculated from pH and temperature (Emerson et al., 1975). Tilapia should be kept below about 0.05 mg/L NH₃.</p>
                            <p style="margin-top: .6rem;"><strong>6. Aeromonas early warning.</strong> A transparent points index combining known stress triggers for Motile Aeromonas Septicaemia: warm water, poor water quality, high organic load, unstable pH and the vulnerable juvenile stage. It is not a diagnosis.</p>
                            <p style="margin-top: .6rem;"><strong>7. ML second opinion.</strong> The Random Forest classifier's latest result is compared with the rule-based result, and any disagreement is shown as an insight.</p>
        </div>
    </details>
@endif
</div>
</x-filament-panels::page>
