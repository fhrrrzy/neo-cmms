<?php

namespace App\Filament\Admin\Resources\PlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentRelationManager extends RelationManager
{
    protected static string $relationship = 'equipment';

    protected static ?string $title = 'Equipment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('equipment_number')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->required()
                    ->maxLength(50)
                    ->label('Nomor Equipment'),
                Forms\Components\Select::make('equipment_group_id')
                    ->prefixIcon('heroicon-o-rectangle-group')
                    ->relationship('equipmentGroup', 'name')
                    ->required()
                    ->label('Grup Equipment'),
                Forms\Components\TextInput::make('equipment_description')
                    ->prefixIcon('heroicon-o-cog-6-tooth')
                    ->maxLength(255)
                    ->label('Deskripsi Equipment'),
                Forms\Components\TextInput::make('company_code')
                    ->prefixIcon('heroicon-o-building-office')
                    ->maxLength(50)
                    ->label('Kode Perusahaan'),
                Forms\Components\TextInput::make('object_number')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->maxLength(50)
                    ->label('Nomor Objek'),
                Forms\Components\TextInput::make('point')
                    ->prefixIcon('heroicon-o-map-pin')
                    ->maxLength(50)
                    ->label('Point'),
                Forms\Components\TextInput::make('api_id')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->maxLength(255)
                    ->label('ID API'),
                Forms\Components\TextInput::make('mandt')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->maxLength(50)
                    ->label('MANDT'),
                Forms\Components\TextInput::make('baujj')
                    ->prefixIcon('heroicon-o-calendar')
                    ->maxLength(50)
                    ->label('Tahun Pembuat'),
                Forms\Components\TextInput::make('groes')
                    ->prefixIcon('heroicon-o-cube')
                    ->maxLength(255)
                    ->label('Ukuran'),
                Forms\Components\TextInput::make('herst')
                    ->prefixIcon('heroicon-o-building-storefront')
                    ->maxLength(255)
                    ->label('Pembuat'),
                Forms\Components\TextInput::make('mrnug')
                    ->maxLength(50)
                    ->label('MRNGU'),
                Forms\Components\TextInput::make('eqtyp')
                    ->maxLength(50)
                    ->label('Equipment Type'),
                Forms\Components\TextInput::make('eqart')
                    ->maxLength(100)
                    ->label('Equipment Art'),
                Forms\Components\TextInput::make('maintenance_planner_group')
                    ->maxLength(100)
                    ->label('Grup Planner Pemeliharaan'),
                Forms\Components\TextInput::make('maintenance_work_center')
                    ->maxLength(100)
                    ->label('Work Center Pemeliharaan'),
                Forms\Components\TextInput::make('functional_location')
                    ->maxLength(255)
                    ->label('Lokasi Fungsional'),
                Forms\Components\TextInput::make('description_func_location')
                    ->maxLength(255)
                    ->label('Deskripsi Lokasi Fungsional'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Status Aktif'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('equipment_number')
            ->columns([
                Tables\Columns\TextColumn::make('equipment_number')
                    ->searchable()
                    ->label('Nomor Equipment'),
                Tables\Columns\TextColumn::make('equipmentGroup.name')
                    ->sortable()
                    ->label('Grup Equipment'),
                Tables\Columns\TextColumn::make('equipment_description')
                    ->searchable()
                    ->label('Deskripsi')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('herst')
                    ->searchable()
                    ->label('Pembuat')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('baujj')
                    ->searchable()
                    ->label('Tahun Pembuat')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('groes')
                    ->searchable()
                    ->label('Ukuran')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('eqart')
                    ->searchable()
                    ->label('Equipment Art')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('functional_location')
                    ->searchable()
                    ->label('Lokasi Fungsional')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status Aktif'),
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['plant_id'] = $this->ownerRecord->getKey();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure association remains with current plant when editing
        $data['plant_id'] = $this->ownerRecord->getKey();

        return $data;
    }
}
