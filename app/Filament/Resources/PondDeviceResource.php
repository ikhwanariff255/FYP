<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PondDeviceResource\Pages;
use App\Models\PondDevice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PondDeviceResource extends Resource
{
    protected static ?string $model = PondDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Pond Devices';
    protected static ?string $pluralModelLabel = 'Pond Devices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'username')
                    ->required()
                    ->label('Farmer / User'),
                Forms\Components\TextInput::make('mac_address')
                    ->required()
                    ->maxLength(255)
                    ->label('MAC Address'),
                Forms\Components\TextInput::make('pond_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Pond Name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('user.username')
                    ->searchable()
                    ->sortable()
                    ->label('Owner'),
                Tables\Columns\TextColumn::make('pond_name')
                    ->searchable()
                    ->sortable()
                    ->label('Pond Name'),
                Tables\Columns\TextColumn::make('mac_address')
                    ->searchable()
                    ->label('MAC Address'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPondDevices::route('/'),
            'create' => Pages\CreatePondDevice::route('/create'),
            'edit' => Pages\EditPondDevice::route('/{record}/edit'),
        ];
    }
}