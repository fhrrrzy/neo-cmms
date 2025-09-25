<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Regional';

    protected static ?string $modelLabel = 'Regional';

    protected static ?string $pluralModelLabel = 'Regional';

    protected static ?int $navigationSort = 0;

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
                Forms\Components\TextInput::make('no')
                    ->prefixIcon('heroicon-o-numbered-list')
                    ->numeric()
                    ->required()
                    ->label('No'),
                Forms\Components\TextInput::make('name')
                    ->prefixIcon('heroicon-o-map')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Regional'),
                Forms\Components\Select::make('category')
                    ->prefixIcon('heroicon-o-rectangle-group')
                    ->options([
                        'palmco' => 'palmco',
                        'supporting_co' => 'supporting_co',
                    ])
                    ->required()
                    ->label('Kategori'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('no')->label('No')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->sortable(),
                Tables\Columns\TextColumn::make('plants_count')->counts('plants')->label('Jumlah Pabrik'),
            ])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\RegionResource\RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'category'];
    }
}
