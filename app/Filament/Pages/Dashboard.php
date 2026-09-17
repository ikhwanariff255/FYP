<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\PondDevice;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('device_id')
                    ->label('Pilih Kolam')
                    // Tukar 'device_id' kepada 'id' jika primary key jadual PondDevice kau ialah 'id'
                    ->options(PondDevice::pluck('pond_name', 'device_id')) 
                    ->default(fn () => PondDevice::first()?->device_id)
                    ->live(), // Ini membolehkan widget berubah secara automatik bila kolam ditukar
            ]);
    }
}