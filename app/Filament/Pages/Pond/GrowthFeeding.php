<?php

namespace App\Filament\Pages\Pond;

class GrowthFeeding extends BasePondPage
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Growth & Feeding';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'growth-feeding';
    protected static ?string $title = 'Growth & Feeding';
    protected static string $view = 'filament.pages.pond.growth-feeding';
}
