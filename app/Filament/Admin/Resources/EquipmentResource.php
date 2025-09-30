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
                        Forms\Components\Select::make('regional_id')
                            ->label('Regional')
                            ->options(fn() => \App\Models\Region::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('plant_id', null);
                                $set('station_id', null);
                            }),
                        Forms\Components\Select::make('plant_id')
                            ->label('Pabrik')
                            ->options(function (callable $get) {
                                $regionalId = $get('regional_id');
                                if (!$regionalId) {
                                    return [];
                                }
                                return \App\Models\Plant::query()
                                    ->where('regional_id', $regionalId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn(callable $get): bool => empty($get('regional_id')))
                            ->afterStateUpdated(function (callable $set) {
                                $set('station_id', null);
                            }),
                        Forms\Components\Select::make('station_id')
                            ->label('Stasiun')
                            ->options(function (callable $get) {
                                $plantId = $get('plant_id');
                                if (!$plantId) {
                                    return [];
                                }
                                return \App\Models\Station::query()
                                    ->where('plant_id', $plantId)
                                    ->orderBy('description')
                                    ->pluck('description', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn(callable $get): bool => empty($get('plant_id'))),
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
                        if (!empty($data['plant_id'])) {
                            return $query->where('plant_id', $data['plant_id']);
                        }
                        if (!empty($data['regional_id'])) {
                            return $query->whereHas('plant', function (Builder $q) use ($data) {
                                $q->where('regional_id', $data['regional_id']);
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
        return ['equipment_number', 'equipment_description', 'company_code'];
    }
}
