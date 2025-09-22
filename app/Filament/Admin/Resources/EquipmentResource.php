<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipmentResource\Pages;
use App\Filament\Admin\Resources\EquipmentResource\RelationManagers;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Manajemen Equipment';

    protected static ?string $navigationLabel = 'Equipment';

    protected static ?string $modelLabel = 'Equipment';

    protected static ?string $pluralModelLabel = 'Equipment';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('equipment_number')
                    ->required()
                    ->maxLength(50)
                    ->label('Nomor Equipment'),
                Forms\Components\Select::make('plant_id')
                    ->relationship('plant', 'name')
                    ->required()
                    ->label('Pabrik'),
                Forms\Components\Select::make('equipment_group_id')
                    ->relationship('equipmentGroup', 'name')
                    ->required()
                    ->label('Grup Equipment'),
                Forms\Components\TextInput::make('company_code')
                    ->maxLength(50)
                    ->label('Kode Perusahaan'),
                Forms\Components\TextInput::make('equipment_description')
                    ->maxLength(255)
                    ->label('Deskripsi Equipment'),
                Forms\Components\TextInput::make('object_number')
                    ->maxLength(50)
                    ->label('Nomor Objek'),
                Forms\Components\TextInput::make('point')
                    ->maxLength(50)
                    ->label('Point'),
                Forms\Components\DateTimePicker::make('api_created_at')
                    ->label('Dibuat di API'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Status Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_number')
                    ->searchable()
                    ->label('Nomor Equipment'),
                Tables\Columns\TextColumn::make('plant.name')
                    ->sortable()
                    ->label('Pabrik'),
                Tables\Columns\TextColumn::make('equipmentGroup.name')
                    ->sortable()
                    ->label('Grup Equipment'),
                Tables\Columns\TextColumn::make('equipment_description')
                    ->searchable()
                    ->label('Deskripsi')
                    ->limit(30),
                Tables\Columns\TextColumn::make('company_code')
                    ->searchable()
                    ->label('Kode Perusahaan'),
                Tables\Columns\TextColumn::make('object_number')
                    ->searchable()
                    ->label('Nomor Objek'),
                Tables\Columns\TextColumn::make('point')
                    ->searchable()
                    ->label('Point'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status Aktif'),
                Tables\Columns\TextColumn::make('running_times_count')
                    ->counts('runningTimes')
                    ->label('Data Running Time'),
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
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}
