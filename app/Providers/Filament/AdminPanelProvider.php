<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(\App\Filament\Pages\Auth\Register::class) // TAMBAH BARIS INI
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Blue,
            ])
            ->brandName('Monitoring System')
            ->brandLogo(asset('images/fish-logo.svg')) // Pilihan: Letak logo ikan anda di public/images/
            ->brandLogoHeight('3rem')
            // Loceng notifikasi (amaran risiko & peranti luar talian); aktif selepas `php artisan migrate`
            ->databaseNotifications(fn () => \App\Services\PondAlertService::enabled())
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Susunan Dashboard: Kotak Ringkasan -> Analisis AI & Graf
                // \App\Filament\Widgets\RiskAlertWidget::class,
                \App\Filament\Widgets\SensorStatsOverview::class,
                \App\Filament\Widgets\AiPredictionWidget::class,
                \App\Filament\Widgets\GrowthForecastChart::class,
                \App\Filament\Widgets\WaterParameterChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
