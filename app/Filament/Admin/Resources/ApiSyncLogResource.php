<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ApiSyncLogResource\Pages;
use App\Filament\Admin\Resources\ApiSyncLogResource\RelationManagers;
use App\Models\ApiSyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use App\Jobs\ConcurrentSyncJob;
use App\Models\Plant;

class ApiSyncLogResource extends Resource
{
    protected static ?string $model = ApiSyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sinkronisasi';

    protected static ?string $navigationLabel = 'Log Sinkronisasi';

    protected static ?string $modelLabel = 'Log Sinkronisasi';

    protected static ?string $pluralModelLabel = 'Log Sinkronisasi';

    protected static ?int $navigationSort = 1;

    // This resource is read-only via table; no create/edit form needed.

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sync_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'equipment' => 'info',
                        'equipment_material' => 'info',
                        'equipment_work_orders' => 'info',
                        'running_time' => 'warning',
                        'work_order' => 'primary',
                        'full' => 'success',
                        default => 'gray',
                    })
                    ->label('Tipe Sinkronisasi'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('records_processed')
                    ->numeric()
                    ->sortable()
                    ->label('Diproses'),
                Tables\Columns\TextColumn::make('records_success')
                    ->numeric()
                    ->sortable()
                    ->label('Berhasil'),
                Tables\Columns\TextColumn::make('records_failed')
                    ->numeric()
                    ->sortable()
                    ->label('Gagal'),
                Tables\Columns\TextColumn::make('success_rate')
                    ->getStateUsing(
                        fn(ApiSyncLog $record): float =>
                        $record->records_processed > 0
                            ? ($record->records_success / $record->records_processed) * 100
                            : 0
                    )
                    ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                    ->label('Tingkat Keberhasilan'),
                Tables\Columns\TextColumn::make('sync_started_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Mulai'),
                Tables\Columns\TextColumn::make('sync_completed_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Selesai'),
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
            ->headerActions([
                Tables\Actions\Action::make('run_sync')
                    ->label('Run Sync')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('primary')
                    ->form([
                        Forms\Components\Toggle::make('all_plants')
                            ->inline(false)
                            ->accepted()
                            ->helperText('Centang untuk sinkron semua plant aktif'),
                        Forms\Components\Select::make('plants')
                            ->multiple()
                            ->searchable()
                            ->options(fn() => Plant::where('is_active', true)->pluck('name', 'plant_code')),
                        Forms\Components\CheckboxList::make('types')
                            ->options([
                                'equipment' => 'Equipment',
                                'running_time' => 'Running Time',
                                'work_orders' => 'Work Orders',
                                'equipment_work_orders' => 'Equipment Work Orders',
                                'equipment_material' => 'Equipment Material',
                            ]),
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false)
                            ->required(fn($get) => $get('types') !== ['equipment'])
                            ->helperText('Required for Running Time, Work Orders, Equipment Work Orders, and Equipment Material APIs. Not needed for Equipment only.'),
                        Forms\Components\DatePicker::make('end_date')
                            ->native(false)
                            ->required(fn($get) => $get('types') !== ['equipment'])
                            ->helperText('Required for Running Time, Work Orders, Equipment Work Orders, and Equipment Material APIs. Not needed for Equipment only.'),
                    ])
                    ->action(function (array $data): void {
                        $start = $data['start_date'] ?? null;
                        $end = $data['end_date'] ?? null;
                        $all = (bool) ($data['all_plants'] ?? false);
                        $plants = $data['plants'] ?? null; // array of plant_code

                        $types = array_values($data['types'] ?? []);

                        // If only equipment is selected, we don't need dates
                        $onlyEquipment = $types === ['equipment'];

                        if ($onlyEquipment) {
                            // Equipment doesn't need date ranges, use defaults
                            $start = null;
                            $end = null;
                        }

                        if ($all || empty($plants)) {
                            // all plants
                            ConcurrentSyncJob::dispatch(
                                null,
                                $start,
                                $end,
                                $start,
                                $end,
                                $types ?: null
                            )->onQueue('high');
                            \Filament\Notifications\Notification::make()
                                ->title('Sync started')
                                ->body($onlyEquipment
                                    ? 'Job created for all plants (equipment only - no date range needed). We\'ll notify you once it\'s done.'
                                    : 'Job created for all plants. We\'ll notify you once it\'s done.')
                                ->success()
                                ->send();
                        } else {
                            // selected plants (single job with selected list)
                            ConcurrentSyncJob::dispatch(
                                array_values($plants),
                                $start,
                                $end,
                                $start,
                                $end,
                                $types ?: null
                            )->onQueue('high');
                            \Filament\Notifications\Notification::make()
                                ->title('Sync started')
                                ->body($onlyEquipment
                                    ? 'Job created for selected plants (equipment only - no date range needed). We\'ll notify you once it\'s done.'
                                    : 'Job created for selected plants. We\'ll notify you once it\'s done.')
                                ->success()
                                ->send();
                        }
                    })
                    ->modalSubmitActionLabel('Run Sync')
                    ->modalWidth('lg')
            ])
            ->actions([])
            ->bulkActions([]);
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
            'index' => Pages\ListApiSyncLogs::route('/'),
            'create' => Pages\CreateApiSyncLog::route('/create'),
            'edit' => Pages\EditApiSyncLog::route('/{record}/edit'),
        ];
    }
}
