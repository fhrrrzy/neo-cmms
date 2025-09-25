<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WorkOrderResource\Pages;
use App\Filament\Admin\Resources\WorkOrderResource\RelationManagers;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Manajemen Equipment';

    protected static ?string $navigationLabel = 'Work Order';

    protected static ?string $modelLabel = 'Work Order';

    protected static ?string $pluralModelLabel = 'Work Orders';

    protected static ?int $navigationSort = 4;

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
                Forms\Components\Section::make('Informasi Work Order')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->required()
                            ->maxLength(50)
                            ->label('Nomor Order'),
                        Forms\Components\Select::make('order_type')
                            ->prefixIcon('heroicon-o-tag')
                            ->options([
                                'PM01' => 'Preventive Maintenance',
                                'PM02' => 'Corrective Maintenance',
                                'PM03' => 'Emergency Maintenance',
                            ])
                            ->label('Tipe Order'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('plant_id')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->relationship('plant', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Pabrik'),
                        Forms\Components\TextInput::make('plant_code')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->maxLength(20)
                            ->label('Kode Pabrik'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Tanggal')
                    ->schema([
                        Forms\Components\Select::make('order_status')
                            ->prefixIcon('heroicon-o-flag')
                            ->options([
                                '00' => 'Created',
                                '10' => 'Released',
                                '20' => 'Completed',
                                '30' => 'Closed',
                            ])
                            ->label('Status Order'),
                        Forms\Components\DatePicker::make('created_on')
                            ->label('Tanggal Dibuat'),
                        Forms\Components\DatePicker::make('technical_completion')
                            ->label('Tanggal Penyelesaian Teknis'),
                        Forms\Components\Toggle::make('completed')
                            ->label('Selesai')
                            ->formatStateUsing(fn($state) => $state === 'X'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('company_code')
                            ->prefixIcon('heroicon-o-building-office')
                            ->maxLength(10)
                            ->label('Kode Perusahaan'),
                        Forms\Components\TextInput::make('responsible_cctr')
                            ->prefixIcon('heroicon-o-user-group')
                            ->maxLength(50)
                            ->label('Responsible CCTR'),
                        Forms\Components\TextInput::make('cost_center')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->maxLength(50)
                            ->label('Cost Center'),
                        Forms\Components\TextInput::make('main_work_center')
                            ->prefixIcon('heroicon-o-cog')
                            ->maxLength(50)
                            ->label('Main Work Center'),
                        Forms\Components\TextInput::make('notification')
                            ->prefixIcon('heroicon-o-bell')
                            ->maxLength(50)
                            ->label('Notification'),
                        Forms\Components\TextInput::make('cause')
                            ->prefixIcon('heroicon-o-exclamation-triangle')
                            ->maxLength(50)
                            ->label('Cause'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Order'),
                Tables\Columns\TextColumn::make('order_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PM01' => 'success',
                        'PM02' => 'warning',
                        'PM03' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'PM01' => 'Preventive',
                        'PM02' => 'Corrective',
                        'PM03' => 'Emergency',
                        default => $state,
                    })
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->label('Deskripsi'),
                Tables\Columns\TextColumn::make('plant.name')
                    ->sortable()
                    ->label('Pabrik'),
                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '00' => 'gray',
                        '10' => 'info',
                        '20' => 'success',
                        '30' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '00' => 'Created',
                        '10' => 'Released',
                        '20' => 'Completed',
                        '30' => 'Closed',
                        default => $state,
                    })
                    ->label('Status'),
                Tables\Columns\IconColumn::make('completed')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->completed === 'X')
                    ->label('Selesai'),
                Tables\Columns\TextColumn::make('created_on')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Dibuat'),
                Tables\Columns\TextColumn::make('technical_completion')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Penyelesaian Teknis'),
                Tables\Columns\TextColumn::make('company_code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Kode Perusahaan'),
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
                Tables\Filters\SelectFilter::make('plant_id')
                    ->relationship('plant', 'name')
                    ->label('Pabrik'),
                Tables\Filters\SelectFilter::make('order_type')
                    ->options([
                        'PM01' => 'Preventive Maintenance',
                        'PM02' => 'Corrective Maintenance',
                        'PM03' => 'Emergency Maintenance',
                    ])
                    ->label('Tipe Order'),
                Tables\Filters\SelectFilter::make('order_status')
                    ->options([
                        '00' => 'Created',
                        '10' => 'Released',
                        '20' => 'Completed',
                        '30' => 'Closed',
                    ])
                    ->label('Status Order'),
                Tables\Filters\TernaryFilter::make('completed')
                    ->label('Selesai')
                    ->queries(
                        true: fn(Builder $query) => $query->where('completed', 'X'),
                        false: fn(Builder $query) => $query->where('completed', '!=', 'X'),
                    ),
                Tables\Filters\Filter::make('created_on')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_on', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_on', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->groups([
                Tables\Grouping\Group::make('plant.name')
                    ->label('Pabrik')
                    ->collapsible(),
                Tables\Grouping\Group::make('order_type')
                    ->label('Tipe Order')
                    ->getTitleFromRecordUsing(fn($record) => match ($record->order_type) {
                        'PM01' => 'Preventive Maintenance',
                        'PM02' => 'Corrective Maintenance',
                        'PM03' => 'Emergency Maintenance',
                        default => $record->order_type ?? 'Unknown',
                    })
                    ->collapsible(),
                Tables\Grouping\Group::make('order_status')
                    ->label('Status Order')
                    ->getTitleFromRecordUsing(fn($record) => match ($record->order_status) {
                        '00' => 'Created',
                        '10' => 'Released',
                        '20' => 'Completed',
                        '30' => 'Closed',
                        default => $record->order_status ?? 'Unknown',
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
            ->emptyStateHeading('Belum ada data work order')
            ->emptyStateDescription('Mulai dengan menambahkan work order baru ke dalam sistem.')
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Work Order'),
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
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order', 'description', 'company_code'];
    }
}
