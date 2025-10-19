<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource\Pages;
use App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource\RelationManagers;
use App\Models\EquipmentWorkOrderMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentWorkOrderMaterialResource extends Resource
{
    protected static ?string $model = EquipmentWorkOrderMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Equipment';

    protected static ?string $navigationLabel = 'Equipment Work Order Materials';

    protected static ?string $modelLabel = 'Equipment Work Order Material';

    protected static ?string $pluralModelLabel = 'Equipment Work Order Materials';

    protected static ?int $navigationSort = 6;

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
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->relationship('plant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Plant'),
                        Forms\Components\TextInput::make('order_number')
                            ->prefixIcon('heroicon-o-document-text')
                            ->maxLength(50)
                            ->label('Order Number'),
                        Forms\Components\TextInput::make('material_number')
                            ->prefixIcon('heroicon-o-cube')
                            ->maxLength(50)
                            ->label('Material Number'),
                        Forms\Components\TextInput::make('equipment_number')
                            ->prefixIcon('heroicon-o-cog-6-tooth')
                            ->maxLength(50)
                            ->label('Equipment Number'),
                        Forms\Components\TextInput::make('reservation_number')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->maxLength(50)
                            ->label('Reservation Number'),
                        Forms\Components\TextInput::make('material_description')
                            ->maxLength(255)
                            ->label('Material Description'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Material Details')
                    ->schema([
                        Forms\Components\Select::make('requirement_type')
                            ->prefixIcon('heroicon-o-tag')
                            ->options([
                                'AR' => 'AR - Assembly Requirement',
                                'PM' => 'PM - Preventive Maintenance',
                                'CM' => 'CM - Corrective Maintenance',
                            ])
                            ->label('Requirement Type'),
                        Forms\Components\Select::make('reservation_status')
                            ->prefixIcon('heroicon-o-flag')
                            ->options([
                                'A' => 'Active',
                                'B' => 'Blocked',
                                'C' => 'Completed',
                                'D' => 'Deleted',
                            ])
                            ->label('Reservation Status'),
                        Forms\Components\TextInput::make('storage_location')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->maxLength(50)
                            ->label('Storage Location'),
                        Forms\Components\DatePicker::make('requirement_date')
                            ->label('Requirement Date'),
                        Forms\Components\TextInput::make('requirement_qty')
                            ->prefixIcon('heroicon-o-calculator')
                            ->numeric()
                            ->label('Requirement Quantity'),
                        Forms\Components\TextInput::make('unit_of_measure')
                            ->prefixIcon('heroicon-o-scale')
                            ->maxLength(20)
                            ->label('Unit of Measure'),
                        Forms\Components\TextInput::make('withdrawn_qty')
                            ->prefixIcon('heroicon-o-arrow-down-tray')
                            ->numeric()
                            ->label('Withdrawn Quantity'),
                        Forms\Components\TextInput::make('withdrawn_value')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->numeric()
                            ->label('Withdrawn Value'),
                        Forms\Components\TextInput::make('currency')
                            ->prefixIcon('heroicon-o-banknotes')
                            ->maxLength(10)
                            ->label('Currency'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Start Time'),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('End Time'),
                        Forms\Components\TextInput::make('service_duration')
                            ->prefixIcon('heroicon-o-clock')
                            ->numeric()
                            ->label('Service Duration'),
                        Forms\Components\TextInput::make('service_dur_unit')
                            ->prefixIcon('heroicon-o-timer')
                            ->maxLength(10)
                            ->label('Service Duration Unit'),
                        Forms\Components\TextInput::make('movement_type')
                            ->prefixIcon('heroicon-o-arrow-right-left')
                            ->maxLength(10)
                            ->label('Movement Type'),
                        Forms\Components\TextInput::make('gl_account')
                            ->prefixIcon('heroicon-o-calculator')
                            ->maxLength(50)
                            ->label('GL Account'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status Flags')
                    ->schema([
                        Forms\Components\Toggle::make('deletion_flag')
                            ->label('Deletion Flag')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('goods_receipt_flag')
                            ->label('Goods Receipt Flag')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('final_issue_flag')
                            ->label('Final Issue Flag')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('error_flag')
                            ->label('Error Flag')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('quantity_is_fixed')
                            ->label('Quantity is Fixed')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('acct_manually')
                            ->label('Account Manually')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('receiving_plant')
                            ->prefixIcon('heroicon-o-building-office')
                            ->maxLength(50)
                            ->label('Receiving Plant'),
                        Forms\Components\TextInput::make('receiving_storage_loc')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->maxLength(50)
                            ->label('Receiving Storage Location'),
                        Forms\Components\TextInput::make('goods_recipient')
                            ->prefixIcon('heroicon-o-user')
                            ->maxLength(100)
                            ->label('Goods Recipient'),
                        Forms\Components\TextInput::make('material_group')
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->maxLength(50)
                            ->label('Material Group'),
                        Forms\Components\TextInput::make('commitment_item_1')
                            ->prefixIcon('heroicon-o-list-bullet')
                            ->maxLength(50)
                            ->label('Commitment Item 1'),
                        Forms\Components\TextInput::make('commitment_item_2')
                            ->prefixIcon('heroicon-o-list-bullet')
                            ->maxLength(50)
                            ->label('Commitment Item 2'),
                        Forms\Components\TextInput::make('funds_center')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->maxLength(50)
                            ->label('Funds Center'),
                        Forms\Components\TextInput::make('production_order')
                            ->prefixIcon('heroicon-o-document-text')
                            ->maxLength(50)
                            ->label('Production Order'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->label('Order Number'),
                Tables\Columns\TextColumn::make('material_number')
                    ->searchable()
                    ->sortable()
                    ->label('Material Number'),
                Tables\Columns\TextColumn::make('equipment_number')
                    ->searchable()
                    ->sortable()
                    ->label('Equipment Number'),
                Tables\Columns\TextColumn::make('reservation_number')
                    ->searchable()
                    ->sortable()
                    ->label('Reservation Number'),
                Tables\Columns\TextColumn::make('requirement_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'AR' => 'info',
                        'PM' => 'success',
                        'CM' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'AR' => 'Assembly Requirement',
                        'PM' => 'Preventive Maintenance',
                        'CM' => 'Corrective Maintenance',
                        default => $state,
                    })
                    ->label('Requirement Type'),
                Tables\Columns\TextColumn::make('reservation_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'danger',
                        'C' => 'info',
                        'D' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'A' => 'Active',
                        'B' => 'Blocked',
                        'C' => 'Completed',
                        'D' => 'Deleted',
                        default => $state,
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('plant.name')
                    ->sortable()
                    ->label('Plant'),
                Tables\Columns\TextColumn::make('requirement_qty')
                    ->numeric()
                    ->sortable()
                    ->label('Required Qty'),
                Tables\Columns\TextColumn::make('unit_of_measure')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('withdrawn_qty')
                    ->numeric()
                    ->sortable()
                    ->label('Withdrawn Qty'),
                Tables\Columns\TextColumn::make('withdrawn_value')
                    ->money('IDR')
                    ->sortable()
                    ->label('Withdrawn Value'),
                Tables\Columns\TextColumn::make('requirement_date')
                    ->date()
                    ->sortable()
                    ->label('Requirements Date'),
                Tables\Columns\IconColumn::make('deletion_flag')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->deletion_flag === 'X')
                    ->label('Deleted'),
                Tables\Columns\IconColumn::make('error_flag')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->error_flag === 'X')
                    ->label('Error'),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Start Time'),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('End Time'),
                Tables\Columns\TextColumn::make('service_duration')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Service Duration'),
                Tables\Columns\TextColumn::make('storage_location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Storage Location'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plant_id')
                    ->relationship('plant', 'name')
                    ->label('Plant'),
                Tables\Filters\SelectFilter::make('requirement_type')
                    ->options([
                        'AR' => 'Assembly Requirement',
                        'PM' => 'Preventive Maintenance',
                        'CM' => 'Corrective Maintenance',
                    ])
                    ->label('Requirement Type'),
                Tables\Filters\SelectFilter::make('reservation_status')
                    ->options([
                        'A' => 'Active',
                        'B' => 'Blocked',
                        'C' => 'Completed',
                        'D' => 'Deleted',
                    ])
                    ->label('Reservation Status'),
                Tables\Filters\TernaryFilter::make('deletion_flag')
                    ->label('Deletion Flag')
                    ->queries(
                        true: fn(Builder $query) => $query->where('deletion_flag', 'X'),
                        false: fn(Builder $query) => $query->where('deletion_flag', '!=', 'X'),
                    ),
                Tables\Filters\TernaryFilter::make('error_flag')
                    ->label('Error Flag')
                    ->queries(
                        true: fn(Builder $query) => $query->where('error_flag', 'X'),
                        false: fn(Builder $query) => $query->where('error_flag', '!=', 'X'),
                    ),
                Tables\Filters\Filter::make('requirement_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn(Builder $q, $date) => $q->whereDate('requirement_date', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, $date) => $q->whereDate('requirement_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->groups([
                Tables\Grouping\Group::make('plant.name')
                    ->label('Plant')
                    ->collapsible(),
                Tables\Grouping\Group::make('requirement_type')
                    ->label('Requirement Type')
                    ->getTitleFromRecordUsing(fn($record) => match ($record->requirement_type) {
                        'AR' => 'Assembly Requirement',
                        'PM' => 'Preventive Maintenance',
                        'CM' => 'Corrective Maintenance',
                        default => $record->requirement_type ?? 'Unknown',
                    })
                    ->collapsible(),
                Tables\Grouping\Group::make('reservation_status')
                    ->label('Reservation Status')
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No equipment work order materials found')
            ->emptyStateDescription('Start by creating your first equipment work order material.')
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Equipment Work Order Material')
                    ->icon('heroicon-o-plus'),
            ])
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
            'index' => Pages\ListEquipmentWorkOrderMaterials::route('/'),
            'create' => Pages\CreateEquipmentWorkOrderMaterial::route('/create'),
            'edit' => Pages\EditEquipmentWorkOrderMaterial::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'material_number', 'equipment_number', 'reservation_number'];
    }
}
