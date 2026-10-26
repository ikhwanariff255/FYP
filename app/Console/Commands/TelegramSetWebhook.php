<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--info : Only show the current webhook status}';
    protected $description = 'Point the Telegram bot at this server so it can receive /start, /status and /stop';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->enabled()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return self::FAILURE;
        }

        if (! $this->option('info')) {
            $url = route('telegram.webhook');
            $result = $telegram->setWebhook($url);
            $this->line(($result['ok'] ?? false) ? "<info>Webhook set:</info> {$url}" : '<error>Failed:</error> ' . ($result['description'] ?? 'unknown error'));
        }

        $info = $telegram->webhookInfo()['result'] ?? [];
        $this->table(['url', 'pending updates', 'last error'], [[
            $info['url'] ?? '-',
            $info['pending_update_count'] ?? 0,
            $info['last_error_message'] ?? '-',
        ]]);

        return self::SUCCESS;
    }
}
