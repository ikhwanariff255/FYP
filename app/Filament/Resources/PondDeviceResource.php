<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PondDeviceResource\Pages;
use App\Models\PondDevice;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Concerns\AdminOnlyResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PondDeviceResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = PondDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Pond Devices';
    protected static ?string $pluralModelLabel = 'Pond Devices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device & Pond Information')
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
                    ])->columns(3),

                Forms\Components\Section::make('Culture Batch Information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'Active' => 'Active',
                                'Maintenance' => 'Maintenance',
                                'Harvested' => 'Harvested',
                            ])
                            ->default('Active')
                            ->required(),
                        Forms\Components\DatePicker::make('stocking_date')
                            ->label('Stocking Date'),
                        Forms\Components\TextInput::make('stocking_density')
                            ->numeric()
                            ->label('Stocking Density (Fish Count)'),
                        Forms\Components\TextInput::make('initial_weight')
                            ->numeric()
                            ->label('Initial Weight (g)'),
                        Forms\Components\TextInput::make('target_weight')
                            ->numeric()
                            ->label('Target Weight (g)'),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Maintenance' => 'warning',
                        'Harvested' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stocking_date')
                    ->date()
                    ->label('Stocking Date')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('harvest')
                    ->label('Harvest')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Pond Harvest')
                    ->modalDescription('Are you sure you want to end the culture batch for this pond? The pond status will be changed to "Harvested" and IoT alerts will be paused.')
                    ->action(function (PondDevice $record) {
                        $record->update([
                            'status' => 'Harvested',
                        ]);
                    })
                    ->visible(fn (PondDevice $record) => $record->status !== 'Harvested'),

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