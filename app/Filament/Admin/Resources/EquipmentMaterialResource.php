<?php

namespace App\Filament\Admin\Resources;

use App\Models\EquipmentMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentMaterialResource extends Resource
{
    protected static ?string $model = EquipmentMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Equipment';

    protected static ?string $navigationLabel = 'Equipment Material';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('workOrder.equipment_number')->label('Equipment')->placeholder('N/A'),
                Tables\Columns\TextColumn::make('material_number')->label('Material'),
                Tables\Columns\TextColumn::make('reservation_number')->label('Reservation'),
                Tables\Columns\TextColumn::make('requirement_date')->date()->label('Requirement Date'),
                Tables\Columns\TextColumn::make('requirement_qty')->numeric(3)->label('Req Qty'),
                Tables\Columns\TextColumn::make('withdrawn_qty')->numeric(3)->label('Withdrawn'),
                Tables\Columns\TextColumn::make('withdrawn_value')->money('IDR')->label('Value'),
                Tables\Columns\TextColumn::make('plant_id')->label('Plant')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('api_created_at')->dateTime()->label('API Created')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => EquipmentMaterialResource\Pages\ListEquipmentMaterials::route('/'),
        ];
    }
}
