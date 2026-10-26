<?php

namespace App\Services;

use App\Filament\Pages\FishGrowthAnalysis;
use App\Models\PondDevice;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Amaran kepada penternak melalui loceng notifikasi Filament.
 *
 * - Hantar sekali apabila keadaan air MENJADI lebih teruk (OK -> Warning -> Critical)
 * - Hantar "kembali normal" apabila pulih
 * - Tempoh bertenang (cooldown) supaya tiada spam setiap 5 saat
 * - Amaran peranti luar talian (lihat ponds:check-offline)
 */
class PondAlertService
{
    protected const RANK = ['ok' => 0, 'warning' => 1, 'critical' => 2];
    protected const ESCALATION_COOLDOWN_MIN = 60;
    protected const RECOVERY_COOLDOWN_MIN = 30;

    /** Notifikasi hanya aktif selepas jadual `notifications` wujud (php artisan migrate). */
    public static function enabled(): bool
    {
        if (Cache::get('notifications-table-exists')) {
            return true;
        }

        $exists = Schema::hasTable('notifications');
        if ($exists) {
            Cache::forever('notifications-table-exists', true);
        }

        return $exists;
    }

    /** Dipanggil selepas setiap bacaan sensor baharu disimpan. */
    public function evaluate(PondDevice $device): void
    {
        if (! static::enabled()) {
            return;
        }

        // Bacaan baharu tiba: jika sebelum ini luar talian, maklumkan ia sudah kembali
        if (Cache::pull($this->key('offline', $device))) {
            $this->send($device, Notification::make()
                ->title("{$device->pond_name}: device back online")
                ->body('Live readings are arriving again.')
                ->success());
        }

        $analysis = app(PondAnalysisService::class)->analyse($device);
        $waterRisks = collect($analysis['risks'])->where('category', '!=', 'System')->values();
        $top = $waterRisks->first();

        $level = match ($top['severity'] ?? null) {
            'critical' => 'critical',
            'warning'  => 'warning',
            default    => 'ok',
        };

        $previous = Cache::get($this->key('level', $device), 'ok');
        Cache::forever($this->key('level', $device), $level);

        if ($level === $previous) {
            return;
        }

        if (self::RANK[$level] > self::RANK[$previous]) {
            $sentKey = $this->key("sent-{$level}", $device);
            if (Cache::has($sentKey)) {
                return;
            }
            Cache::put($sentKey, true, now()->addMinutes(self::ESCALATION_COOLDOWN_MIN));

            $extra = $waterRisks->whereIn('severity', ['critical', 'warning'])->count() - 1;
            $notification = Notification::make()
                ->title("{$device->pond_name}: {$top['title']}")
                ->body("{$top['current']}. Do now: {$top['actions'][0]}" . ($extra > 0 ? " (+{$extra} more issue" . ($extra > 1 ? 's' : '') . ')' : ''));

            $this->send($device, $level === 'critical' ? $notification->danger() : $notification->warning(), 'Open action plan');

            return;
        }

        if ($level === 'ok' && ! Cache::has($this->key('recovered', $device))) {
            Cache::put($this->key('recovered', $device), true, now()->addMinutes(self::RECOVERY_COOLDOWN_MIN));

            $this->send($device, Notification::make()
                ->title("{$device->pond_name}: water back to normal")
                ->body('All key parameters are within the ideal range again.')
                ->success());
        }
    }

    /** Dipanggil oleh ponds:check-offline. Sekali bagi setiap gangguan. */
    public function deviceOffline(PondDevice $device, Carbon $lastReading): bool
    {
        if (! static::enabled() || Cache::has($this->key('offline', $device))) {
            return false;
        }
        Cache::forever($this->key('offline', $device), true);

        $this->send($device, Notification::make()
            ->title("{$device->pond_name}: device offline")
            ->body('No readings since ' . $lastReading->format('d M, H:i') . '. Check the ESP32 power and Wi-Fi.')
            ->danger()
            ->icon('heroicon-o-signal-slash'));

        return true;
    }

    /** Pemilik kolam, pengguna yang dipautkan, dan semua pentadbir. */
    public function recipients(PondDevice $device): Collection
    {
        return User::query()
            ->whereIn('role', ['Administrator', 'Admin'])
            ->orWhere('user_id', $device->user_id)
            ->orWhere('device_id', $device->device_id)
            ->get();
    }

    protected function send(PondDevice $device, Notification $notification, string $actionLabel = 'Open pond'): void
    {
        $url = FishGrowthAnalysis::getUrl(['pond' => $device->device_id], panel: 'admin');
        $recipients = $this->recipients($device);

        $notification
            ->actions([
                Action::make('open')
                    ->label($actionLabel)
                    ->url($url)
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);

        // Salinan ke Telegram bagi pengguna yang telah memautkan akaun (satu mesej bagi setiap chat)
        $telegram = app(TelegramService::class);
        $icon = ['danger' => '🔴', 'warning' => '🟠', 'success' => '🟢'][$notification->getStatus()] ?? '🔔';
        $text = "{$icon} <b>" . e($notification->getTitle()) . "</b>\n\n" . e($notification->getBody());

        foreach ($recipients->pluck('telegram_chat_id')->filter()->unique() as $chatId) {
            $telegram->send($chatId, $text, $actionLabel, $url);
        }
    }

    protected function key(string $type, PondDevice $device): string
    {
        return "pond-alert:{$type}:{$device->device_id}";
    }
}
