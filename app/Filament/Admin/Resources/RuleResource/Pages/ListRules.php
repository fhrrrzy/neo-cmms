<?php

namespace App\Filament\Admin\Resources\RuleResource\Pages;

use App\Filament\Admin\Resources\RuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRules extends ListRecords
{
    protected static string $resource = RuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
