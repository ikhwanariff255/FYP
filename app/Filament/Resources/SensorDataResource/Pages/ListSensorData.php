<?php

namespace App\Filament\Resources\SensorDataResource\Pages;

use App\Filament\Resources\SensorDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSensorData extends ListRecords
{
    protected static string $resource = SensorDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
