<?php

namespace App\Filament\Admin\Resources;

use App\Models\EquipmentMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\EquipmentMaterialResource\Pages;

class EquipmentMaterialResource extends Resource
{
    protected static ?string $model = EquipmentMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Equipment';

    protected static ?string $navigationLabel = 'Equipment Materials';

    protected static ?string $modelLabel = 'Equipment Material';

    protected static ?string $pluralModelLabel = 'Equipment Materials';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('plant_id')
                            ->relationship('plant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('material_number')
                            ->maxLength(50)
                            ->required(),
                        Forms\Components\TextInput::make('reservation_number')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('reservation_item')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Material Details')
                    ->schema([
                        Forms\Components\Select::make('requirement_type')
                            ->options([
                                'AR' => 'AR - Assembly Requirement',
                                'PM' => 'PM - Preventive Maintenance',
                                'CM' => 'CM - Corrective Maintenance',
                            ]),
                        Forms\Components\Select::make('reservation_status')
                            ->options([
                                'A' => 'Active',
                                'B' => 'Blocked',
                                'C' => 'Completed',
                                'D' => 'Deleted',
                            ]),
                        Forms\Components\TextInput::make('storage_location')
                            ->maxLength(50),
                        Forms\Components\DatePicker::make('requirement_date'),
                        Forms\Components\TextInput::make('requirement_qty')
                            ->numeric(),
                        Forms\Components\TextInput::make('unit_of_measure')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('withdrawn_qty')
                            ->numeric(),
                        Forms\Components\TextInput::make('withdrawn_value')
                            ->numeric(),
                        Forms\Components\TextInput::make('currency')
                            ->maxLength(10),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status Flags')
                    ->schema([
                        Forms\Components\Toggle::make('deletion_flag'),
                        Forms\Components\Toggle::make('goods_receipt_flag'),
                        Forms\Components\Toggle::make('final_issue_flag'),
                        Forms\Components\Toggle::make('error_flag'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reservation_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requirement_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'AR' => 'info',
                        'PM' => 'success',
                        'CM' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reservation_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'danger',
                        'C' => 'info',
                        'D' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('plant.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('requirement_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_of_measure'),
                Tables\Columns\TextColumn::make('withdrawn_qty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('withdrawn_value')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('requirement_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('deletion_flag')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->deletion_flag === 'X'),
                Tables\Columns\IconColumn::make('error_flag')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->error_flag === 'X'),
                Tables\Columns\TextColumn::make('storage_location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plant_id')
                    ->relationship('plant', 'name'),
                Tables\Filters\SelectFilter::make('requirement_type')
                    ->options([
                        'AR' => 'Assembly Requirement',
                        'PM' => 'Preventive Maintenance',
                        'CM' => 'Corrective Maintenance',
                    ]),
                Tables\Filters\SelectFilter::make('reservation_status')
                    ->options([
                        'A' => 'Active',
                        'B' => 'Blocked',
                        'C' => 'Completed',
                        'D' => 'Deleted',
                    ]),
                Tables\Filters\TernaryFilter::make('deletion_flag')
                    ->queries(
                        true: fn(Builder $query) => $query->where('deletion_flag', 'X'),
                        false: fn(Builder $query) => $query->where('deletion_flag', '!=', 'X'),
                    ),
                Tables\Filters\TernaryFilter::make('error_flag')
                    ->queries(
                        true: fn(Builder $query) => $query->where('error_flag', 'X'),
                        false: fn(Builder $query) => $query->where('error_flag', '!=', 'X'),
                    ),
            ])
            ->groups([
                Tables\Grouping\Group::make('plant.name')
                    ->collapsible(),
                Tables\Grouping\Group::make('requirement_type')
                    ->getTitleFromRecordUsing(fn($record) => match ($record->requirement_type) {
                        'AR' => 'Assembly Requirement',
                        'PM' => 'Preventive Maintenance',
                        'CM' => 'Corrective Maintenance',
                        default => $record->requirement_type ?? 'Unknown',
                    })
                    ->collapsible(),
                Tables\Grouping\Group::make('reservation_status')
                    ->getTitleFromRecordUsing(fn($record) => match ($record->reservation_status) {
                        'A' => 'Active',
                        'B' => 'Blocked',
                        'C' => 'Completed',
                        'D' => 'Deleted',
                        default => $record->reservation_status ?? 'Unknown',
                    })
                    ->collapsible(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No equipment materials found')
            ->emptyStateDescription('Start by creating your first equipment material.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
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
            'index' => Pages\ListEquipmentMaterials::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['material_number', 'reservation_number'];
    }
}
