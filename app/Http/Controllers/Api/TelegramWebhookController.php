<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PondAnalysisService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

/**
 * Menerima mesej daripada Telegram (webhook).
 *   /start <kod>  pautkan akaun (dari butang "Connect Telegram")
 *   /status       ringkasan kolam semasa
 *   /stop         putuskan pautan
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $telegram)
    {
        // Hanya Telegram tahu rahsia ini (ditetapkan oleh telegram:set-webhook)
        if (! hash_equals($telegram->webhookSecret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $chatId = (string) $request->input('message.chat.id');
        $text = trim((string) $request->input('message.text'));
        if ($chatId === '' || $text === '') {
            return response()->json(['ok' => true]);
        }

        $reply = match (true) {
            str_starts_with($text, '/start') => $this->link($chatId, trim(substr($text, 6))),
            $text === '/status'              => $this->status($chatId),
            $text === '/stop'                => $this->unlink($chatId),
            default                          => $this->help(),
        };

        $telegram->send($chatId, $reply);

        return response()->json(['ok' => true]);
    }

    protected function link(string $chatId, string $token): string
    {
        $user = $token !== '' ? User::where('telegram_link_token', $token)->first() : null;

        if (! $user) {
            return "👋 Welcome to <b>TilapiaCare Alerts</b>.\n\nTo connect, log in to the website, open <b>My Pond</b> and tap <b>Connect Telegram</b>.";
        }

        $user->forceFill(['telegram_chat_id' => $chatId, 'telegram_link_token' => null])->save();
        $ponds = $user->visiblePonds()->pluck('pond_name')->implode(', ') ?: 'no pond yet';

        return "✅ Connected to <b>" . e($user->username) . "</b>.\n\nYou will get alerts for: <b>" . e($ponds) . "</b>\n\n/status — current pond condition\n/stop — stop alerts";
    }

    protected function unlink(string $chatId): string
    {
        User::where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null]);

        return '🔕 Alerts stopped. Tap <b>Connect Telegram</b> on the website to turn them on again.';
    }

    protected function status(string $chatId): string
    {
        $users = User::where('telegram_chat_id', $chatId)->get();
        if ($users->isEmpty()) {
            return $this->help();
        }

        $ponds = $users->flatMap(fn ($u) => $u->visiblePonds()->get())->unique('device_id');
        $service = app(PondAnalysisService::class);
        $lines = [];

        foreach ($ponds as $pond) {
            $a = $service->analyse($pond);
            $snap = $a['snapshot'];
            $worst = collect($a['risks'])->where('category', '!=', 'System')->first()['severity'] ?? null;

            [$icon, $state] = match (true) {
                $snap['current'] === null => ['⚪', 'No data'],
                $worst === 'critical'     => ['🔴', 'Act now'],
                $worst === 'warning'      => ['🟠', 'Needs attention'],
                default                   => ['🟢', 'Healthy'],
            };

            $line = "{$icon} <b>" . e($pond->pond_name) . "</b>: {$state}";
            if ($snap['current']) {
                $c = $snap['current'];
                $line .= "\n    " . round($c['temperature'], 1) . ' °C · pH ' . round($c['ph_level'], 2) . ' · ' . round($c['turbidity']) . ' NTU · ' . $a['growth']['current_weight'] . ' g/fish';
            }
            if (in_array($snap['connection'], ['offline', 'stale'])) {
                $line .= "\n    ⚠ Device offline, last reading " . $snap['latest_at']->diffForHumans();
            }
            $lines[] = $line;
        }

        return $lines ? implode("\n\n", $lines) : 'No ponds linked to your account yet.';
    }

    protected function help(): string
    {
        return "<b>TilapiaCare Alerts</b>\n\n/status — current pond condition\n/stop — stop alerts\n\nNot connected yet? Open <b>My Pond</b> on the website and tap <b>Connect Telegram</b>.";
    }
}
