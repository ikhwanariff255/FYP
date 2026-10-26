{{-- Amaran peranti luar talian --}}
@php $snap = $a['snapshot']; @endphp
@if (in_array($snap['connection'], ['offline', 'no-data']))
    <div class="tg-card t-danger tone-edge" style="border-left-width: 5px; {{ ($compact ?? false) ? 'padding: .8rem 1rem;' : '' }}">
        <div style="font-weight: 800;" class="tone-text">
            ⚠ {{ $snap['connection'] === 'no-data' ? 'No data from this pond yet' : 'Device offline since ' . $snap['latest_at']->format('d M, H:i') }}
        </div>
        <p class="tg-small" style="margin-top: .2rem;">
            {{ $snap['connection'] === 'no-data' ? 'Connect the ESP32 sensor to start monitoring.' : 'Values shown are the last known readings, not live water conditions.' }}
        </p>
        @unless ($compact ?? false)
            <ol class="tg-steps tg-small" style="margin-top: .5rem;">
                <li>Check the ESP32 is powered (LED on).</li>
                <li>Check the 2.4 GHz Wi-Fi or phone hotspot is on and in range.</li>
                <li>Restart the device. Readings should appear within a minute.</li>
            </ol>
        @endunless
    </div>
@endif
