<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\PondDevice;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    // Penternak terus ke halaman "My Pond"; papan pemuka penuh untuk pentadbir sahaja
    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->redirect(FishGrowthAnalysis::getUrl());
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('device_id')
                    ->label('Select Pond')
                    // Tukar 'device_id' kepada 'id' jika primary key jadual PondDevice kau ialah 'id'
                    ->options(PondDevice::pluck('pond_name', 'device_id')) 
                    ->default(fn () => PondDevice::first()?->device_id)
                    ->live(), // Ini membolehkan widget berubah secara automatik bila kolam ditukar
            ]);
    }
}