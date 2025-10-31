<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Equipment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\EquipmentResource\Pages;
use App\Filament\Admin\Resources\EquipmentResource\RelationManagers;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Equipment';

    protected static ?string $navigationLabel = 'Equipment';

    protected static ?string $modelLabel = 'Equipment';

    protected static ?string $pluralModelLabel = 'Equipment';

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
                Forms\Components\TextInput::make('equipment_number')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->required()
                    ->maxLength(50)
                    ->label('Nomor Equipment'),
                Forms\Components\Select::make('plant_id')
                    ->prefixIcon('heroicon-o-building-office-2')
                    ->relationship('plant', 'name')
                    ->required()
                    ->label('Pabrik'),
                Forms\Components\Select::make('station_id')
                    ->prefixIcon('heroicon-o-building-library')
                    ->relationship('station', 'description')
                    ->searchable()
                    ->preload()
                    ->label('Station'),
                Forms\Components\Select::make('equipment_group_id')
                    ->prefixIcon('heroicon-o-rectangle-group')
                    ->relationship('equipmentGroup', 'name')
                    ->required()
                    ->nullable()
                    ->label('Grup Equipment'),
                Forms\Components\TextInput::make('company_code')
                    ->prefixIcon('heroicon-o-building-office')
                    ->maxLength(50)
                    ->label('Kode Perusahaan'),
                Forms\Components\TextInput::make('equipment_description')
                    ->prefixIcon('heroicon-o-cog-6-tooth')
                    ->maxLength(255)
                    ->label('Deskripsi Equipment'),
                Forms\Components\TextInput::make('object_number')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->maxLength(50)
                    ->label('Nomor Objek'),
                Forms\Components\TextInput::make('point')
                    ->prefixIcon('heroicon-o-map-pin')
                    ->maxLength(50)
                    ->label('Point'),
                Forms\Components\DateTimePicker::make('api_created_at')
                    ->label('Dibuat di API'),

                // Complete API Field Mapping
                Forms\Components\Section::make('API Data')
                    ->description('Data dari API eksternal')
                    ->schema([
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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Technical Specifications')
                    ->description('Spesifikasi teknis peralatan')
                    ->schema([
                        Forms\Components\TextInput::make('mrnug')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->maxLength(50)
                            ->label('MRNGU'),
                        Forms\Components\TextInput::make('eqtyp')
                            ->prefixIcon('heroicon-o-tag')
                            ->maxLength(50)
                            ->label('Equipment Type'),
                        Forms\Components\TextInput::make('eqart')
                            ->prefixIcon('heroicon-o-cog-6-tooth')
                            ->maxLength(100)
                            ->label('Equipment Art'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Maintenance Information')
                    ->description('Informasi pemeliharaan')
                    ->schema([
                        Forms\Components\TextInput::make('maintenance_planner_group')
                            ->prefixIcon('heroicon-o-users')
                            ->maxLength(100)
                            ->label('Grup Planner Pemeliharaan'),
                        Forms\Components\TextInput::make('maintenance_work_center')
                            ->prefixIcon('heroicon-o-building-office')
                            ->maxLength(100)
                            ->label('Work Center Pemeliharaan'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location Information')
                    ->description('Informasi lokasi')
                    ->schema([
                        Forms\Components\TextInput::make('functional_location')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->maxLength(255)
                            ->label('Lokasi Fungsional'),
                        Forms\Components\TextInput::make('description_func_location')
                            ->prefixIcon('heroicon-o-rectangle-group')
                            ->maxLength(255)
                            ->label('Deskripsi Lokasi Fungsional'),
                    ])
                    ->columns(1),

                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Status Aktif'),

                Forms\Components\Section::make('Images')
                    ->description('Upload gambar untuk equipment ini')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship('images')
                            ->label('Images')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->maxLength(255)
                                    ->required(),
                                Forms\Components\FileUpload::make('filepath')
                                    ->label('File')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->required(),
                            ])
                            ->addActionLabel('Tambah Gambar')
                            ->collapsible(),
                    ]),
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
                Tables\Columns\TextColumn::make('station.description')
                    ->sortable()
                    ->label('Stasiun'),
                // Tables\Columns\TextColumn::make('equipmentGroup.name')
                //     ->sortable()
                //     ->label('Grup Equipment'),
                Tables\Columns\TextColumn::make('equipment_description')
                    ->searchable()
                    ->label('Nama Equipment')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('company_code')
                    ->searchable()
                    ->label('Kode Perusahaan')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('object_number')
                    ->searchable()
                    ->label('Nomor Objek')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('point')
                    ->searchable()
                    ->label('Point')
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('mandt')
                    ->searchable()
                    ->label('MANDT')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('baujj')
                    ->searchable()
                    ->label('Tahun Pembuat')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('groes')
                    ->searchable()
                    ->label('Ukuran')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('herst')
                    ->searchable()
                    ->label('Pembuat')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Technical Specifications
                Tables\Columns\TextColumn::make('mrnug')
                    ->searchable()
                    ->label('MRNGU')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('eqtyp')
                    ->searchable()
                    ->label('Equipment Type')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('eqart')
                    ->searchable()
                    ->label('Equipment Art')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Maintenance Information
                Tables\Columns\TextColumn::make('maintenance_planner_group')
                    ->searchable()
                    ->label('Grup Planner')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('maintenance_work_center')
                    ->searchable()
                    ->label('Work Center')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Location Information
                Tables\Columns\TextColumn::make('functional_location')
                    ->searchable()
                    ->label('Lokasi Fungsional')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description_func_location')
                    ->searchable()
                    ->label('Desk Lokasi')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->groups([
                Tables\Grouping\Group::make('plant.name')
                    ->label('Pabrik')
                    ->collapsible(),
                Tables\Grouping\Group::make('equipmentGroup.name')
                    ->label('Grup Equipment')
                    ->collapsible(),
                Tables\Grouping\Group::make('is_active')
                    ->label('Status Aktif')
                    ->getTitleFromRecordUsing(fn($record) => $record->is_active ? 'Aktif' : 'Nonaktif')
                    ->collapsible(),
            ])
            ->emptyStateHeading('Belum ada data equipment')
            ->emptyStateDescription('Mulai dengan menambahkan equipment baru ke dalam sistem.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth')
            ->emptyStateActions([])
            ->paginated([15, 25, 50, 100])
            ->defaultPaginationPageOption(15);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RulesRelationManager::class,
            RelationManagers\RunningTimesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipment::route('/'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
            'view' => Pages\ViewEquipment::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'equipment_number',
            'equipment_description',
            'company_code',
            'herst',
            'eqart',
            'functional_location',
            'maintenance_work_center'
        ];
    }
}
