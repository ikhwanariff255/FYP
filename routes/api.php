<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorDataController; // <-- Baris ini wajib ada

Route::post('/sensor/store', [SensorDataController::class, 'store']);
// Telegram bot webhook (pautan akaun, /status, /stop)
Route::post('/telegram/webhook', \App\Http\Controllers\Api\TelegramWebhookController::class)->name('telegram.webhook');
