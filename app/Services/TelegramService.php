<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Hantar mesej melalui Telegram Bot API dan urus pautan akaun <-> chat.
 * Token bot dibaca dari .env (TELEGRAM_BOT_TOKEN) — jangan simpan dalam kod.
 */
class TelegramService
{
    public function enabled(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    /** Token ada DAN migrasi telah dijalankan. */
    public function ready(): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        if (Cache::get('telegram-columns-exist')) {
            return true;
        }
        $exists = Schema::hasColumn('user', 'telegram_chat_id');
        if ($exists) {
            Cache::forever('telegram-columns-exist', true);
        }

        return $exists;
    }

    public function botUsername(): string
    {
        return (string) config('services.telegram.bot_username');
    }

    /** Rahsia webhook diterbitkan dari APP_KEY supaya tiada pemboleh ubah .env tambahan. */
    public function webhookSecret(): string
    {
        return substr(hash_hmac('sha256', 'telegram-webhook', (string) config('app.key')), 0, 48);
    }

    /** Pautan t.me dengan kod sekali guna untuk memautkan akaun ini. */
    public function linkUrl(User $user): string
    {
        if (! $user->telegram_link_token) {
            $user->forceFill(['telegram_link_token' => Str::random(32)])->save();
        }

        return 'https://t.me/' . $this->botUsername() . '?start=' . $user->telegram_link_token;
    }

    public function send(string $chatId, string $html, ?string $buttonText = null, ?string $buttonUrl = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $payload = [
            'chat_id'                  => $chatId,
            'text'                     => $html,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($buttonUrl) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => [[['text' => $buttonText ?? 'Open', 'url' => $buttonUrl]]]]);
        }

        try {
            $res = Http::timeout(5)->asForm()->post($this->api('sendMessage'), $payload);

            // Pengguna menyekat bot: putuskan pautan supaya tidak cuba lagi
            if ($res->json('error_code') === 403) {
                User::where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null]);
            }

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed: ' . $e->getMessage());

            return false;
        }
    }

    public function setWebhook(string $url): array
    {
        return Http::timeout(10)->asForm()->post($this->api('setWebhook'), [
            'url'             => $url,
            'secret_token'    => $this->webhookSecret(),
            'allowed_updates' => json_encode(['message']),
        ])->json() ?? [];
    }

    public function webhookInfo(): array
    {
        return Http::timeout(10)->get($this->api('getWebhookInfo'))->json() ?? [];
    }

    protected function api(string $method): string
    {
        return 'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/' . $method;
    }
}
