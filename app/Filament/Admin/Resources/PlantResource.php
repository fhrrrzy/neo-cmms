<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlantResource\Pages;
use App\Filament\Admin\Resources\PlantResource\RelationManagers;
use App\Models\Plant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlantResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Pabrik';

    protected static ?string $modelLabel = 'Pabrik';

    protected static ?string $pluralModelLabel = 'Pabrik';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::query()->exists() ? 'primary' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('plant_code')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->required()
                    ->maxLength(255)
                    ->label('Kode Pabrik'),
                Forms\Components\TextInput::make('name')
                    ->prefixIcon('heroicon-o-building-office-2')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Pabrik'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Deskripsi'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Status Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plant_code')
                    ->searchable()
                    ->label('Kode Pabrik'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Pabrik'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status Aktif'),
                Tables\Columns\TextColumn::make('equipment_count')
                    ->counts('equipment')
                    ->label('Jumlah Equipment'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlants::route('/'),
            'create' => Pages\CreatePlant::route('/create'),
            'edit' => Pages\EditPlant::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['plant_code', 'name'];
    }
}
