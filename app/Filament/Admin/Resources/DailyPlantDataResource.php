<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DailyPlantDataResource\Pages;
use App\Filament\Admin\Resources\DailyPlantDataResource\RelationManagers;
use App\Models\DailyPlantData;
use App\Jobs\ConcurrentSyncJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DailyPlantDataResource extends Resource
{
    protected static ?string $model = DailyPlantData::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Daily Plant Data';

    protected static ?string $modelLabel = 'Daily Plant Data';

    protected static ?string $pluralModelLabel = 'Daily Plant Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('plant_id')
                    ->relationship('plant', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\Toggle::make('is_mengolah'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plant.name')
                    ->label('Plant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_mengolah')
                    ->label('Is Mengolah')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plant_id')
                    ->relationship('plant', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync')
                    ->label('Sync from API')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false)
                            ->required()
                            ->helperText('Sync all regions from this date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->native(false)
                            ->required()
                            ->helperText('Sync all regions until this date'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $startDate = $data['start_date'] ?? now()->subDays(3)->toDateString();
                            $endDate = $data['end_date'] ?? now()->toDateString();

                            ConcurrentSyncJob::dispatch(
                                null, // No specific plant codes, sync all regions
                                $startDate, // running time start
                                $endDate, // running time end
                                $startDate, // work order start
                                $endDate, // work order end
                                ['daily_plant_data'] // types
                            )->onQueue('high');

                            Notification::make()
                                ->title('Sync Started')
                                ->success()
                                ->body('Daily plant data sync job has been queued for all regions. You will be notified when it completes.')
                                ->send();

                            return redirect(request()->header('Referer'));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListDailyPlantData::route('/'),
            'create' => Pages\CreateDailyPlantData::route('/create'),
            'edit' => Pages\EditDailyPlantData::route('/{record}/edit'),
        ];
    }
}
