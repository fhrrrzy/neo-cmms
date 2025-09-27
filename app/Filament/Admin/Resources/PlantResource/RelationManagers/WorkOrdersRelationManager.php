<?php

namespace App\Filament\Admin\Resources\PlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Work Orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order')->required()->maxLength(50)->label('Nomor Order'),
                Forms\Components\Select::make('station_id')->relationship('station', 'description')->searchable()->preload()->label('Station'),
                Forms\Components\Select::make('order_type')->options([
                    'PM01' => 'Preventive Maintenance',
                    'PM02' => 'Corrective Maintenance',
                    'PM03' => 'Emergency Maintenance',
                ])->label('Tipe Order'),
                Forms\Components\Textarea::make('description')->label('Deskripsi'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')->label('Nomor Order')->searchable(),
                Tables\Columns\TextColumn::make('station.description')->label('Station'),
                Tables\Columns\TextColumn::make('order_type')->label('Tipe'),
                Tables\Columns\TextColumn::make('order_status')->label('Status'),
                Tables\Columns\TextColumn::make('created_on')->date()->label('Tanggal Dibuat'),
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
}
