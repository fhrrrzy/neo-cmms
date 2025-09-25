<?php

namespace App\Filament\Admin\Resources\EquipmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RunningTimesRelationManager extends RelationManager
{
    protected static string $relationship = 'runningTimes';

    protected static ?string $title = 'Jam Jalan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')->required()->label('Tanggal'),
                Forms\Components\TextInput::make('running_hours')->numeric()->required()->label('Jam Jalan'),
                Forms\Components\TextInput::make('counter_reading')->numeric()->label('Counter'),
                Forms\Components\Textarea::make('maintenance_text')->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->label('Tanggal'),
                Tables\Columns\TextColumn::make('running_hours')->label('Jam Jalan'),
                Tables\Columns\TextColumn::make('counter_reading')->label('Counter'),
                Tables\Columns\TextColumn::make('maintenance_text')->label('Catatan')->limit(40),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
