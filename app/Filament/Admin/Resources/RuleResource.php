<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RuleResource\Pages;
use App\Filament\Admin\Resources\RuleResource\RelationManagers;
use App\Models\Rule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RuleResource extends Resource
{
    protected static ?string $model = Rule::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationGroup = 'Manajemen Equipment';

    protected static ?string $navigationLabel = 'Aturan';

    protected static ?string $modelLabel = 'Aturan';

    protected static ?string $pluralModelLabel = 'Aturan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Aturan'),
                
                Forms\Components\Section::make('Target Aturan')
                    ->schema([
                        Forms\Components\Select::make('equipment_group_id')
                            ->relationship('equipmentGroup', 'name')
                            ->label('Grup Equipment')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('equipment_id', null)),
                        
                        Forms\Components\Select::make('equipment_id')
                            ->relationship('equipment', 'equipment_number')
                            ->label('Equipment Spesifik')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('equipment_group_id', null)),
                    ])
                    ->columns(2)
                    ->description('Pilih salah satu: Grup Equipment atau Equipment Spesifik'),
                
                Forms\Components\Section::make('Konfigurasi Aturan')
                    ->schema([
                        Forms\Components\Repeater::make('rules')
                            ->schema([
                                Forms\Components\TextInput::make('number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->label('Nomor/Jumlah')
                                    ->placeholder('Contoh: 1000'),
                                Forms\Components\TextInput::make('action')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Aksi')
                                    ->placeholder('Contoh: Maintenance Required'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Aturan')
                            ->label('Daftar Aturan'),
                    ]),
                
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->label('Status Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Aturan'),
                
                Tables\Columns\TextColumn::make('target_name')
                    ->label('Target')
                    ->getStateUsing(fn (Rule $record): string => $record->target_name),
                
                Tables\Columns\TextColumn::make('rules_count')
                    ->label('Jumlah Aturan')
                    ->getStateUsing(fn (Rule $record): int => count($record->rules ?? [])),
                
                Tables\Columns\TextColumn::make('rules_preview')
                    ->label('Preview Aturan')
                    ->getStateUsing(function (Rule $record): string {
                        $rules = $record->rules ?? [];
                        if (empty($rules)) return 'Tidak ada aturan';
                        
                        $preview = collect($rules)->take(2)->map(function ($rule) {
                            return $rule['number'] . ' → ' . $rule['action'];
                        })->join(', ');
                        
                        return count($rules) > 2 ? $preview . '...' : $preview;
                    })
                    ->limit(50),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status Aktif'),
                
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
            'index' => Pages\ListRules::route('/'),
            'create' => Pages\CreateRule::route('/create'),
            'edit' => Pages\EditRule::route('/{record}/edit'),
        ];
    }
}
