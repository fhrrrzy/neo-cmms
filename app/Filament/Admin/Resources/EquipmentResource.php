<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Equipment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\EquipmentResource\Pages;
use CodeWithKyrian\FilamentDateRange\Forms\Components\DateRangePicker;
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
                Tables\Columns\TextColumn::make('summed_jam_jalan')
                    ->label('Jam Jalan (Periode)')
                    ->getStateUsing(function ($record) {
                        $filters = request()->input('tableFilters') ?? [];
                        // Support CodeWithKyrian state shape
                        $byLocation = $filters['by_location'] ?? [];
                        $data = is_array($byLocation) ? ($byLocation['data'] ?? $byLocation) : [];
                        $range = $data['date_range'] ?? null;
                        $from = is_array($range) && !empty($range['start']) ? $range['start'] : now()->subWeek()->toDateString();
                        $until = is_array($range) && !empty($range['end']) ? $range['end'] : now()->toDateString();
                        $sum = $record->runningTimes()
                            ->whereBetween('date', [$from, $until])
                            ->sum('running_hours');

                        return number_format((float) $sum, 2);
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $filters = request()->input('tableFilters') ?? [];
                        $byLocation = $filters['by_location'] ?? [];
                        $data = is_array($byLocation) ? ($byLocation['data'] ?? $byLocation) : [];
                        $range = $data['date_range'] ?? null;
                        $from = is_array($range) && !empty($range['start']) ? $range['start'] : now()->subWeek()->toDateString();
                        $until = is_array($range) && !empty($range['end']) ? $range['end'] : now()->toDateString();

                        $query->orderByRaw(
                            "(select coalesce(sum(rt.running_hours),0) from running_times rt where rt.equipment_number = equipment.equipment_number and rt.date between ? and ?) " . ($direction === 'asc' ? 'asc' : 'desc'),
                            [$from, $until]
                        );
                    })
                    ->alignRight(),
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

                // API Fields Columns
                Tables\Columns\TextColumn::make('api_id')
                    ->searchable()
                    ->label('ID API')
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

                Tables\Columns\TextColumn::make('running_times_count')
                    ->label('Data Jam Jalan (Periode)')
                    ->getStateUsing(function ($record) {
                        $filters = request()->input('tableFilters') ?? [];
                        $byLocation = $filters['by_location'] ?? [];
                        $data = is_array($byLocation) ? ($byLocation['data'] ?? $byLocation) : [];
                        $range = $data['date_range'] ?? null;
                        $from = is_array($range) && !empty($range['start']) ? $range['start'] : now()->subWeek()->toDateString();
                        $until = is_array($range) && !empty($range['end']) ? $range['end'] : now()->toDateString();
                        return (string) $record->runningTimes()
                            ->whereBetween('date', [$from, $until])
                            ->count();
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $filters = request()->input('tableFilters') ?? [];
                        $byLocation = $filters['by_location'] ?? [];
                        $data = is_array($byLocation) ? ($byLocation['data'] ?? $byLocation) : [];
                        $range = $data['date_range'] ?? null;
                        $from = is_array($range) && !empty($range['start']) ? $range['start'] : now()->subWeek()->toDateString();
                        $until = is_array($range) && !empty($range['end']) ? $range['end'] : now()->toDateString();
                        $query->orderByRaw(
                            "(select count(*) from running_times rt where rt.equipment_number = equipment.equipment_number and rt.date between ? and ?) " . ($direction === 'asc' ? 'asc' : 'desc'),
                            [$from, $until]
                        );
                    }),
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
                Tables\Filters\Filter::make('by_location')
                    ->form([
                        Forms\Components\Select::make('regional_uuid')
                            ->label('Regional')
                            ->options(fn() => \App\Models\Region::query()->orderBy('name')->pluck('name', 'uuid'))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('plant_uuid', null);
                                $set('station_id', null);
                            }),
                        Forms\Components\Select::make('plant_uuid')
                            ->label('Pabrik')
                            ->options(function (callable $get) {
                                $regionalUuid = $get('regional_uuid');
                                if (!$regionalUuid) {
                                    return [];
                                }
                                $region = \App\Models\Region::where('uuid', $regionalUuid)->first();
                                if (!$region) {
                                    return [];
                                }
                                return \App\Models\Plant::query()
                                    ->where('regional_id', $region->id)
                                    ->orderBy('name')
                                    ->pluck('name', 'uuid');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn(callable $get): bool => empty($get('regional_uuid')))
                            ->afterStateUpdated(function (callable $set) {
                                $set('station_id', null);
                            }),
                        Forms\Components\Select::make('station_id')
                            ->label('Stasiun')
                            ->options(function (callable $get) {
                                $plantUuid = $get('plant_uuid');
                                if (!$plantUuid) {
                                    return [];
                                }
                                $plant = \App\Models\Plant::where('uuid', $plantUuid)->first();
                                if (!$plant) {
                                    return [];
                                }
                                return \App\Models\Station::query()
                                    ->where('plant_id', $plant->id)
                                    ->orderBy('description')
                                    ->pluck('description', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn(callable $get): bool => empty($get('plant_uuid'))),
                        DateRangePicker::make('date_range')
                            ->label('Periode Jam Jalan')
                            ->default([
                                'start' => now()->subWeek()->toDateString(),
                                'end' => now()->toDateString(),
                            ])
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->columnSpan(2)
                            ->separator(' sampai '),
                    ])
                    ->columns(5)
                    ->query(function (Builder $query, array $data): Builder {
                        // Do not filter equipment by date range; range only affects sum/count columns
                        if (!empty($data['station_id'])) {
                            return $query->where('station_id', $data['station_id']);
                        }
                        if (!empty($data['plant_uuid'])) {
                            return $query->whereHas('plant', function (Builder $q) use ($data) {
                                $q->where('uuid', $data['plant_uuid']);
                            });
                        }
                        if (!empty($data['regional_uuid'])) {
                            return $query->whereHas('plant', function (Builder $q) use ($data) {
                                $q->whereHas('region', function (Builder $r) use ($data) {
                                    $r->where('uuid', $data['regional_uuid']);
                                });
                            });
                        }
                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
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
            'view' => Pages\ViewEquipment::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'equipment_number',
            'equipment_description',
            'company_code',
            'api_id',
            'herst',
            'eqart',
            'functional_location',
            'maintenance_work_center'
        ];
    }
}
