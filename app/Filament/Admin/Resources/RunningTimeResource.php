<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RunningTimeResource\Pages;
use App\Filament\Admin\Resources\RunningTimeResource\RelationManagers;
use App\Models\RunningTime;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RunningTimeResource extends Resource
{
    protected static ?string $model = RunningTime::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Equipment Management';

    protected static ?string $navigationLabel = 'Running Time';

    protected static ?string $modelLabel = 'Running Time';

    protected static ?string $pluralModelLabel = 'Running Times';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment Information')
                    ->schema([
                        Forms\Components\TextInput::make('equipment_number')
                            ->required()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-cog-6-tooth'),

                        Forms\Components\Select::make('plant_id')
                            ->relationship('plant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon('heroicon-o-building-office'),
                    ])->columns(2),

                Forms\Components\Section::make('Running Time Details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required(),

                        Forms\Components\DateTimePicker::make('date_time'),

                        Forms\Components\TextInput::make('running_hours')
                            ->numeric()
                            ->step(0.01),

                        Forms\Components\TextInput::make('counter_reading')
                            ->numeric()
                            ->step(0.01),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('maintenance_text')
                            ->rows(3),

                        Forms\Components\TextInput::make('company_code')
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-building-office-2'),

                        Forms\Components\TextInput::make('equipment_description')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-cog-6-tooth'),

                        Forms\Components\TextInput::make('object_number')
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-hashtag'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('plant.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('running_hours')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('counter_reading')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company_code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('equipment_description')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

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
                    ->relationship('plant', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('running_hours')
                    ->form([
                        Forms\Components\TextInput::make('running_hours_from')
                            ->numeric(),
                        Forms\Components\TextInput::make('running_hours_until')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['running_hours_from'],
                                fn(Builder $query, $hours): Builder => $query->where('running_hours', '>=', $hours),
                            )
                            ->when(
                                $data['running_hours_until'],
                                fn(Builder $query, $hours): Builder => $query->where('running_hours', '<=', $hours),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('date', 'desc')
            ->groups([
                Tables\Grouping\Group::make('plant.name')
                    ->label('Plant')
                    ->collapsible(),
                Tables\Grouping\Group::make('date')
                    ->label('Date')
                    ->collapsible(),
            ])
            ->emptyStateActions([])
            ->emptyStateHeading('No running time records found')
            ->emptyStateDescription('Start by creating a new running time record.')
            ->emptyStateIcon('heroicon-o-clock');
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
            'index' => Pages\ListRunningTimes::route('/'),
            'view' => Pages\ViewRunningTime::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['equipment_number', 'equipment_description', 'company_code'];
    }
}
