{{-- Pengepala ringkas untuk halaman butiran: pilih kolam + status sambungan --}}
@php
    $snap = $a['snapshot'];
    $connTone = ['online' => 't-success', 'stale' => 't-warning', 'offline' => 't-danger', 'no-data' => 't-gray'][$snap['connection']];
    $connText = ['online' => 'Live', 'stale' => 'Delayed', 'offline' => 'Offline', 'no-data' => 'No data'][$snap['connection']];
@endphp
<div class="tg-card" style="display: flex; flex-direction: column; gap: .9rem;">
    <div class="tg-row" style="flex-wrap: wrap;">
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
        <span class="tg-tiny">
            Last reading {{ $snap['latest_at']?->diffForHumans() ?? 'never' }} · Day {{ $a['growth']['days'] }} of culture
        </span>
    </div>
    @include('filament.pages.pond.partials.subnav')
</div>
@include('filament.pages.pond.partials.offline', ['compact' => true])
