<?php

namespace App\Filament\Pages;

use App\Models\PondDevice;
use App\Services\PondAnalysisService;
use App\Services\TelegramService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class FishGrowthAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Growth & Risk Analysis';
    protected static ?string $title = 'Fish Growth Analysis & Risk Control';
    protected static string  $view = 'filament.pages.fish-growth-analysis';

    // Kolam yang dipilih (disimpan dalam URL: ?pond=1)
    #[Url(as: 'pond')]
    public ?int $deviceId = null;

    // Untuk penternak, halaman ini ialah "rumah" mereka
    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isAdmin() ? 'Growth & Risk Analysis' : 'My Pond';
    }

    public static function getNavigationIcon(): ?string
    {
        return auth()->user()?->isAdmin() ? 'heroicon-o-chart-bar' : 'heroicon-o-home';
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->isAdmin() ? 2 : -10;
    }

    public function getTitle(): string
    {
        return auth()->user()?->isAdmin() ? static::$title : 'My Pond';
    }

    public function mount(): void
    {
        $ponds = $this->ponds();

        if (! $ponds->contains('device_id', $this->deviceId)) {
            $this->deviceId = $ponds->first()?->device_id;
        }
    }

    /** Admin nampak semua kolam; pelanggan hanya kolam miliknya. */
    protected function ponds(): Collection
    {
        return auth()->user()->visiblePonds()->orderBy('pond_name')->get();
    }

    public function updatedDeviceId(): void
    {
        // Elak pengguna memilih kolam yang bukan miliknya
        if (! $this->ponds()->contains('device_id', $this->deviceId)) {
            $this->deviceId = $this->ponds()->first()?->device_id;
        }
    }

    protected function getViewData(): array
    {
        $ponds = $this->ponds();
        $device = $ponds->firstWhere('device_id', $this->deviceId);

        return [
            'ponds'    => $ponds,
            'a'        => $device ? app(PondAnalysisService::class)->analyse($device) : null,
            'telegram' => $this->telegramState(),
        ];
    }

    protected function telegramState(): array
    {
        $telegram = app(TelegramService::class);
        if (! $telegram->ready()) {
            return ['ready' => false];
        }

        $user = auth()->user();
        $connected = filled($user->telegram_chat_id);

        return [
            'ready'     => true,
            'connected' => $connected,
            'link'      => $connected ? null : $telegram->linkUrl($user),
        ];
    }

    public function disconnectTelegram(): void
    {
        $user = auth()->user();
        $chatId = $user->telegram_chat_id;
        $user->forceFill(['telegram_chat_id' => null])->save();

        if ($chatId) {
            app(TelegramService::class)->send($chatId, '🔕 Alerts stopped for <b>' . e($user->username) . '</b>.');
        }
    }

    // ───────────────────────────────────────────────────────────────
    // Pembantu SVG untuk carta
    // ───────────────────────────────────────────────────────────────

    /** Laluan SVG bagi siri [x, y] dalam kotak carta. */
    public function chartPath(array $points, float $xMax, float $yMax, int $w = 640, int $h = 240, int $pad = 36): string
    {
        if (count($points) < 2) {
            return '';
        }

        $out = [];
        foreach ($points as [$x, $y]) {
            $out[] = $this->chartX($x, $xMax, $w, $pad) . ',' . $this->chartY($y, $yMax, $h, $pad);
        }

        return 'M ' . implode(' L ', $out);
    }

    public function chartX(float $x, float $xMax, int $w = 640, int $pad = 36): float
    {
        return round($pad + ($x / max(1, $xMax)) * ($w - $pad - 12), 1);
    }

    public function chartY(float $y, float $yMax, int $h = 240, int $pad = 36): float
    {
        return round(($h - 24) - ($y / max(1, $yMax)) * ($h - 24 - 12), 1);
    }

    public function sparklinePath(array $data, int $width = 100, int $height = 30, int $padding = 2): string
    {
        if (count($data) < 2) {
            return '';
        }

        $min   = min($data);
        $max   = max($data);
        $range = ($max - $min) ?: 1;
        $step  = ($width - $padding * 2) / (count($data) - 1);

        $points = [];
        foreach ($data as $i => $v) {
            $x = $padding + $i * $step;
            $y = $height - $padding - (($v - $min) / $range) * ($height - $padding * 2);
            $points[] = round($x, 1) . ',' . round($y, 1);
        }

        return 'M ' . implode(' L ', $points);
    }
}
