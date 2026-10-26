<?php

namespace App\Filament\Pages\Pond;

class RisksActions extends BasePondPage
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Risks & Actions';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'risks-actions';
    protected static ?string $title = 'Risks & Actions';
    protected static string $view = 'filament.pages.pond.risks-actions';
}
