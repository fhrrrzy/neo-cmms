<?php

namespace App\Filament\Admin\Resources\WorkOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentWorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'equipmentWorkOrders';

    protected static ?string $recordTitleAttribute = 'reservation';

    protected static ?string $title = 'Equipment Work Orders';

    protected static ?string $modelLabel = 'Equipment Work Order';

    protected static ?string $pluralModelLabel = 'Equipment Work Orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment Work Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('reservation')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->required()
                            ->maxLength(50)
                            ->label('Reservation Number'),
                        Forms\Components\TextInput::make('material')
                            ->prefixIcon('heroicon-o-cube')
                            ->maxLength(50)
                            ->label('Material Number'),
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
                        Forms\Components\Select::make('plant_id')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->relationship('plant', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Plant'),
                        Forms\Components\TextInput::make('equipment_number')
                            ->prefixIcon('heroicon-o-cog-6-tooth')
                            ->maxLength(50)
                            ->label('Equipment Number'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Material Details')
                    ->schema([
                        Forms\Components\TextInput::make('storage_location')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->maxLength(50)
                            ->label('Storage Location'),
                        Forms\Components\DatePicker::make('requirements_date')
                            ->label('Requirements Date'),
                        Forms\Components\TextInput::make('requirement_quantity')
                            ->prefixIcon('heroicon-o-calculator')
                            ->numeric()
                            ->label('Requirement Quantity'),
                        Forms\Components\TextInput::make('base_unit_of_measure')
                            ->prefixIcon('heroicon-o-scale')
                            ->maxLength(20)
                            ->label('Unit of Measure'),
                        Forms\Components\TextInput::make('quantity_withdrawn')
                            ->prefixIcon('heroicon-o-arrow-down-tray')
                            ->numeric()
                            ->label('Quantity Withdrawn'),
                        Forms\Components\TextInput::make('value_withdrawn')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->numeric()
                            ->label('Value Withdrawn'),
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
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status Flags')
                    ->schema([
                        Forms\Components\Toggle::make('item_deleted')
                            ->label('Item Deleted')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('movement_allowed')
                            ->label('Movement Allowed')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('final_issue')
                            ->label('Final Issue')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                        Forms\Components\Toggle::make('missing_part')
                            ->label('Missing Part')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reservation')
            ->columns([
                Tables\Columns\TextColumn::make('reservation')
                    ->searchable()
                    ->sortable()
                    ->label('Reservation Number'),
                Tables\Columns\TextColumn::make('material')
                    ->searchable()
                    ->sortable()
                    ->label('Material Number'),
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
                Tables\Columns\TextColumn::make('equipment_number')
                    ->searchable()
                    ->sortable()
                    ->label('Equipment Number'),
                Tables\Columns\TextColumn::make('requirement_quantity')
                    ->numeric()
                    ->sortable()
                    ->label('Required Qty'),
                Tables\Columns\TextColumn::make('base_unit_of_measure')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('quantity_withdrawn')
                    ->numeric()
                    ->sortable()
                    ->label('Withdrawn Qty'),
                Tables\Columns\TextColumn::make('value_withdrawn')
                    ->money('IDR')
                    ->sortable()
                    ->label('Withdrawn Value'),
                Tables\Columns\TextColumn::make('requirements_date')
                    ->date()
                    ->sortable()
                    ->label('Requirements Date'),
                Tables\Columns\IconColumn::make('item_deleted')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->item_deleted === 'X')
                    ->label('Deleted'),
                Tables\Columns\IconColumn::make('movement_allowed')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->movement_allowed === 'X')
                    ->label('Movement Allowed'),
                Tables\Columns\IconColumn::make('final_issue')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->final_issue === 'X')
                    ->label('Final Issue'),
                Tables\Columns\IconColumn::make('missing_part')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->missing_part === 'X')
                    ->label('Missing Part'),
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
                Tables\Filters\TernaryFilter::make('item_deleted')
                    ->label('Item Deleted')
                    ->queries(
                        true: fn(Builder $query) => $query->where('item_deleted', 'X'),
                        false: fn(Builder $query) => $query->where('item_deleted', '!=', 'X'),
                    ),
                Tables\Filters\TernaryFilter::make('movement_allowed')
                    ->label('Movement Allowed')
                    ->queries(
                        true: fn(Builder $query) => $query->where('movement_allowed', 'X'),
                        false: fn(Builder $query) => $query->where('movement_allowed', '!=', 'X'),
                    ),
                Tables\Filters\TernaryFilter::make('missing_part')
                    ->label('Missing Part')
                    ->queries(
                        true: fn(Builder $query) => $query->where('missing_part', 'X'),
                        false: fn(Builder $query) => $query->where('missing_part', '!=', 'X'),
                    ),
                Tables\Filters\Filter::make('requirements_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn(Builder $q, $date) => $q->whereDate('requirements_date', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, $date) => $q->whereDate('requirements_date', '<=', $date));
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No equipment work orders found')
            ->emptyStateDescription('This work order does not have any equipment work orders yet.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Equipment Work Order')
                    ->icon('heroicon-o-plus'),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}

