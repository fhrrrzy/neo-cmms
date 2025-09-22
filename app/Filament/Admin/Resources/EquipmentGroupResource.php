<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipmentGroupResource\Pages;
use App\Filament\Admin\Resources\EquipmentGroupResource\RelationManagers;
use App\Models\EquipmentGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentGroupResource extends Resource
{
    protected static ?string $model = EquipmentGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Manajemen Equipment';

    protected static ?string $navigationLabel = 'Grup Equipment';

    protected static ?string $modelLabel = 'Grup Equipment';

    protected static ?string $pluralModelLabel = 'Grup Equipment';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Grup'),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Grup'),
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
            'index' => Pages\ListEquipmentGroups::route('/'),
            'create' => Pages\CreateEquipmentGroup::route('/create'),
            'edit' => Pages\EditEquipmentGroup::route('/{record}/edit'),
        ];
    }
}
