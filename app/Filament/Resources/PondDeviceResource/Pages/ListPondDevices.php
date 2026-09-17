<?php

namespace App\Filament\Resources\PondDeviceResource\Pages;

use App\Filament\Resources\PondDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPondDevices extends ListRecords
{
    protected static string $resource = PondDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
