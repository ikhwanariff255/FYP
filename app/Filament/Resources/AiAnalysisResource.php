<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiAnalysisResource\Pages;
use App\Models\AiAnalysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiAnalysisResource extends Resource
{
    protected static ?string $model = AiAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'AI Analyses';
    protected static ?string $pluralModelLabel = 'AI Analyses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('data_id')
                    ->relationship('sensorData', 'data_id')
                    ->required()
                    ->label('Sensor Data ID'),
                Forms\Components\TextInput::make('risk_status')
                    ->required()
                    ->maxLength(255)
                    ->label('Risk Status'),
                Forms\Components\TextInput::make('estimated_weight')
                    ->required()
                    ->numeric()
                    ->label('Estimated Weight (g)'),
                Forms\Components\DateTimePicker::make('created_at')
                    ->required()
                    ->default(now())
                    ->label('Analysis Time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('analysis_id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('data_id')
                    ->sortable()
                    ->label('Sensor Data ID'),
                Tables\Columns\TextColumn::make('risk_status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'danger', 'high' => 'danger',
                        'warning', 'moderate' => 'warning',
                        default => 'success',
                    })
                    ->label('Risk Status'),
                Tables\Columns\TextColumn::make('estimated_weight')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('Est. Weight (g)'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Analyzed At'),
            ])
            ->defaultSort('data_id', 'desc') // Susun ikut ID terkini di atas
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
            'index' => Pages\ListAiAnalyses::route('/'),
            'create' => Pages\CreateAiAnalysis::route('/create'),
            'edit' => Pages\EditAiAnalysis::route('/{record}/edit'),
        ];
    }
}