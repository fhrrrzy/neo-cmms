<?php

namespace App\Filament\Admin\Resources\StationResource\RelationManagers;

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
                Forms\Components\TextInput::make('equipment_number')->required()->maxLength(50)->label('Nomor Equipment'),
                Forms\Components\Select::make('equipment_group_id')->relationship('equipmentGroup', 'name')->required()->label('Grup Equipment'),
                Forms\Components\TextInput::make('equipment_description')->label('Deskripsi'),
                Forms\Components\TextInput::make('api_id')->label('ID API'),
                Forms\Components\TextInput::make('mandt')->label('MANDT'),
                Forms\Components\TextInput::make('baujj')->label('Tahun Pembuat'),
                Forms\Components\TextInput::make('groes')->label('Ukuran'),
                Forms\Components\TextInput::make('herst')->label('Pembuat'),
                Forms\Components\TextInput::make('eqart')->label('Equipment Art'),
                Forms\Components\TextInput::make('functional_location')->label('Lokasi Fungsional'),
                Forms\Components\TextInput::make('maintenance_work_center')->label('Work Center'),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_number')->label('Nomor Equipment')->searchable(),
                Tables\Columns\TextColumn::make('equipment_description')->label('Deskripsi')->searchable(),
                Tables\Columns\TextColumn::make('herst')->label('Pembuat')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('baujj')->label('Tahun Pembuat')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('groes')->label('Ukuran')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('eqart')->label('Equipment Art')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('functional_location')->label('Lokasi Fungsional')->searchable()->limit(20)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Aktif'),
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
        $data['plant_id'] = $this->ownerRecord->plant_id;
        $data['station_id'] = $this->ownerRecord->getKey();
        return $data;
    }
}
