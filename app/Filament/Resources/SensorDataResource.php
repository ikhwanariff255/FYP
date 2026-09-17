<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SensorDataResource\Pages;
use App\Models\SensorData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SensorDataResource extends Resource
{
    protected static ?string $model = SensorData::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sensor Data';
    protected static ?string $pluralModelLabel = 'Sensor Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('device_id')
                    ->relationship('device', 'pond_name')
                    ->required()
                    ->label('Pond Device'),
                Forms\Components\TextInput::make('temperature')
                    ->required()
                    ->numeric()
                    ->label('Temperature (°C)'),
                Forms\Components\TextInput::make('ph_level')
                    ->required()
                    ->numeric()
                    ->label('pH Level'),
                Forms\Components\TextInput::make('turbidity')
                    ->required()
                    ->numeric()
                    ->label('Turbidity (NTU)'),
                Forms\Components\DateTimePicker::make('timestamp')
                    ->required()
                    ->default(now())
                    ->label('Timestamp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('device.pond_name')
                    ->searchable()
                    ->sortable()
                    ->label('Pond Name'),
                Tables\Columns\TextColumn::make('temperature')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('Temp (°C)'),
                Tables\Columns\TextColumn::make('ph_level')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('pH'),
                Tables\Columns\TextColumn::make('turbidity')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('Turbidity'),
                Tables\Columns\TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable()
                    ->label('Recorded At'),
            ])
            ->defaultSort('timestamp', 'desc')
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
            'index' => Pages\ListSensorData::route('/'),
            'create' => Pages\CreateSensorData::route('/create'),
            'edit' => Pages\EditSensorData::route('/{record}/edit'),
        ];
    }
}