<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StationResource\Pages;
use App\Models\Station;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Station';

    protected static ?string $modelLabel = 'Station';

    protected static ?string $pluralModelLabel = 'Stations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('plant_id')
                    ->relationship('plant', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Plant'),
                Forms\Components\TextInput::make('cost_center')
                    ->required()
                    ->maxLength(50)
                    ->label('Cost Center'),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->label('Stasiun'),
                Forms\Components\TextInput::make('keterangan')
                    ->default('OBJEK')
                    ->required()
                    ->maxLength(50)
                    ->label('Keterangan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plant.name')->label('Plant')->sortable()->searchable(),
                // Tables\Columns\TextColumn::make('cost_center')->label('Cost Center')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Stasiun')->searchable(),
                Tables\Columns\TextColumn::make('keterangan')->label('Keterangan'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Dibuat'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Diperbarui'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plant_id')->relationship('plant', 'name')->label('Plant'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListStations::route('/'),
            'create' => Pages\CreateStation::route('/create'),
            'edit' => Pages\EditStation::route('/{record}/edit'),
        ];
    }
}
