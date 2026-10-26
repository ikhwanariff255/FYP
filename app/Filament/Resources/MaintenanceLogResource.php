<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceLogResource\Pages;
use App\Models\MaintenanceLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceLogResource extends Resource
{
    protected static ?string $model = MaintenanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Maintenance Logs';
    protected static ?string $pluralModelLabel = 'Maintenance Logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Penternak sentiasa direkod sebagai diri sendiri (lihat CreateMaintenanceLog)
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'username')
                    ->required()
                    ->visible(fn () => auth()->user()->isAdmin())
                    ->label('Farmer / User'),
                Forms\Components\Select::make('device_id')
                    ->relationship('device', 'pond_name', fn (Builder $query) => $query
                        ->whereIn('device_id', auth()->user()->visiblePonds()->pluck('device_id')))
                    ->default(fn () => auth()->user()->visiblePonds()->value('device_id'))
                    ->required()
                    ->label('Pond'),
                Forms\Components\Textarea::make('activity_desc')
                    ->required()
                    ->columnSpanFull()
                    ->label('Activity Description'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->label('Date'),
            ]);
    }

    public static function table(Table $table): Table // <-- Betulkan di sini (guna Table $table)
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('user.username')
                    ->searchable()
                    ->sortable()
                    ->label('User'),
                Tables\Columns\TextColumn::make('device.pond_name')
                    ->searchable()
                    ->sortable()
                    ->label('Pond Name'),
                Tables\Columns\TextColumn::make('activity_desc')
                    ->limit(50)
                    ->searchable()
                    ->label('Description'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Penternak hanya nampak log untuk kolam miliknya
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->unless($user->isAdmin(), fn ($q) => $q->whereIn('device_id', $user->visiblePonds()->pluck('device_id')));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceLogs::route('/'),
            'create' => Pages\CreateMaintenanceLog::route('/create'),
            'edit' => Pages\EditMaintenanceLog::route('/{record}/edit'),
        ];
    }
}