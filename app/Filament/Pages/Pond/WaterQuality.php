<?php

namespace App\Filament\Pages\Pond;

class WaterQuality extends BasePondPage
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Water Quality';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'water-quality';
    protected static ?string $title = 'Water Quality';
    protected static string $view = 'filament.pages.pond.water-quality';
}
