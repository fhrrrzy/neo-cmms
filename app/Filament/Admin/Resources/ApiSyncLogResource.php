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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sync_type')
                    ->required()
                    ->options([
                        'equipment' => 'Equipment',
                        'running_time' => 'Running Time',
                        'full' => 'Full Sync',
                    ])
                    ->label('Tipe Sinkronisasi'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->label('Status'),
                Forms\Components\TextInput::make('records_processed')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('Record Diproses'),
                Forms\Components\TextInput::make('records_success')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('Record Berhasil'),
                Forms\Components\TextInput::make('records_failed')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('Record Gagal'),
                Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull()
                    ->label('Pesan Error'),
                Forms\Components\DateTimePicker::make('sync_started_at')
                    ->label('Mulai Sinkronisasi'),
                Forms\Components\DateTimePicker::make('sync_completed_at')
                    ->label('Selesai Sinkronisasi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sync_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'equipment' => 'info',
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
                Tables\Actions\Action::make('run_sync_now')
                    ->label('Run Full Sync Now')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('primary')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->subMonthNoOverflow()->startOfMonth()->toDateString())
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now()->toDateString())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $start = $data['start_date'] ?? null;
                        $end = $data['end_date'] ?? null;
                        // Null plant list means: sync all active plants
                        ConcurrentSyncJob::dispatch(
                            null,
                            $start,
                            $end,
                            $start,
                            $end
                        )->onQueue('high');
                    })
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
            'index' => Pages\ListApiSyncLogs::route('/'),
            'create' => Pages\CreateApiSyncLog::route('/create'),
            'edit' => Pages\EditApiSyncLog::route('/{record}/edit'),
        ];
    }
}
