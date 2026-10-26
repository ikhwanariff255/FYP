<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Pond\BasePondPage;

/**
 * "Overview" — ringkasan pantas kolam. Butiran penuh di halaman Water Quality,
 * Growth & Feeding dan Risks & Actions.
 */
class FishGrowthAnalysis extends BasePondPage
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Overview';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.fish-growth-analysis';

    public function getTitle(): string
    {
        return 'Pond Overview';
    }

    protected function getViewData(): array
    {
        return parent::getViewData() + ['telegram' => $this->telegramState()];
    }
}
