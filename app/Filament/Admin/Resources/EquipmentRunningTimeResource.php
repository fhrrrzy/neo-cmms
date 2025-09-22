<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipmentRunningTimeResource\Pages;
use App\Filament\Admin\Resources\EquipmentRunningTimeResource\RelationManagers;
use App\Models\EquipmentRunningTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentRunningTimeResource extends Resource
{
    protected static ?string $model = EquipmentRunningTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Manajemen Equipment';

    protected static ?string $navigationLabel = 'Data Running Time';

    protected static ?string $modelLabel = 'Data Running Time';

    protected static ?string $pluralModelLabel = 'Data Running Time';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('equipment_id')
                    ->relationship('equipment', 'equipment_number')
                    ->required()
                    ->label('Equipment'),
                Forms\Components\Select::make('plant_id')
                    ->relationship('plant', 'name')
                    ->required()
                    ->label('Pabrik'),
                Forms\Components\TextInput::make('point')
                    ->maxLength(50)
                    ->label('Point'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->label('Tanggal'),
                Forms\Components\DateTimePicker::make('date_time')
                    ->label('Tanggal & Waktu'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Deskripsi'),
                Forms\Components\TextInput::make('running_hours')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->label('Jam Berjalan'),
                Forms\Components\TextInput::make('cumulative_hours')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->label('Jam Kumulatif'),
                Forms\Components\TextInput::make('company_code')
                    ->maxLength(50)
                    ->label('Kode Perusahaan'),
                Forms\Components\TextInput::make('equipment_description')
                    ->maxLength(255)
                    ->label('Deskripsi Equipment'),
                Forms\Components\TextInput::make('object_number')
                    ->maxLength(50)
                    ->label('Nomor Objek'),
                Forms\Components\DateTimePicker::make('api_created_at')
                    ->label('Dibuat di API'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_number')
                    ->sortable()
                    ->label('Nomor Equipment'),
                Tables\Columns\TextColumn::make('plant.name')
                    ->sortable()
                    ->label('Pabrik'),
                Tables\Columns\TextColumn::make('point')
                    ->searchable()
                    ->label('Point'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('running_hours')
                    ->numeric()
                    ->sortable()
                    ->label('Jam Berjalan')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' jam'),
                Tables\Columns\TextColumn::make('cumulative_hours')
                    ->numeric()
                    ->sortable()
                    ->label('Jam Kumulatif')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' jam'),
                Tables\Columns\TextColumn::make('company_code')
                    ->searchable()
                    ->label('Kode Perusahaan'),
                Tables\Columns\TextColumn::make('equipment_description')
                    ->searchable()
                    ->label('Deskripsi')
                    ->limit(30),
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
            'index' => Pages\ListEquipmentRunningTimes::route('/'),
            'create' => Pages\CreateEquipmentRunningTime::route('/create'),
            'edit' => Pages\EditEquipmentRunningTime::route('/{record}/edit'),
        ];
    }
}
