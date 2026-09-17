<?php

namespace App\Providers;

use App\Models\SensorData;
use App\Observers\SensorDataObserver;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
{
    SensorData::observe(SensorDataObserver::class);
}


}
