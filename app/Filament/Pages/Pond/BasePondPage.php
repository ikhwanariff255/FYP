<?php

namespace App\Filament\Pages\Pond;

use App\Services\PondAnalysisService;
use App\Services\TelegramService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Asas untuk semua halaman "My Pond": pemilihan kolam (diingati antara halaman),
 * skop kolam mengikut peranan, analisis, dan pembantu paparan.
 */
abstract class BasePondPage extends Page
{
    // Kolam yang dipilih (dalam URL ?pond=1 dan diingati dalam sesi)
    #[Url(as: 'pond')]
    public ?int $deviceId = null;

    protected ?array $analysisCache = null;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->isAdmin() ? 'Pond Analysis' : 'My Pond';
    }

    public function mount(): void
    {
        $this->deviceId ??= session('selected_pond');
        $this->ensureValidPond();
    }

    public function updatedDeviceId(): void
    {
        $this->ensureValidPond();
    }

    protected function ensureValidPond(): void
    {
        $ponds = $this->ponds();
        if (! $ponds->contains('device_id', $this->deviceId)) {
            $this->deviceId = $ponds->first()?->device_id;
        }
        session(['selected_pond' => $this->deviceId]);
    }

    /** Admin nampak semua kolam; pelanggan hanya kolam miliknya. */
    protected function ponds(): Collection
    {
        return auth()->user()->visiblePonds()->orderBy('pond_name')->get();
    }

    protected function analysis(): ?array
    {
        if ($this->analysisCache === null) {
            $device = $this->ponds()->firstWhere('device_id', $this->deviceId);
            $this->analysisCache = $device ? app(PondAnalysisService::class)->analyse($device) : [];
        }

        return $this->analysisCache ?: null;
    }

    protected function getViewData(): array
    {
        return [
            'ponds' => $this->ponds(),
            'a'     => $this->analysis(),
        ];
    }

    /** URL halaman lain untuk kolam yang sama. */
    public function pondUrl(string $page): string
    {
        return $page::getUrl(['pond' => $this->deviceId]);
    }

    // ───────────────────────────────────────────────────────────────
    // Pembantu paparan
    // ───────────────────────────────────────────────────────────────

    public function scoreTone(?int $score): string
    {
        return $score === null ? 't-gray' : ($score >= 85 ? 't-success' : ($score >= 60 ? 't-warning' : 't-danger'));
    }

    public function severityTone(string $severity): string
    {
        return ['critical' => 't-danger', 'warning' => 't-warning', 'watch' => 't-info'][$severity] ?? 't-gray';
    }

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

    // ───────────────────────────────────────────────────────────────
    // Telegram (butang pada Overview)
    // ───────────────────────────────────────────────────────────────

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
}
