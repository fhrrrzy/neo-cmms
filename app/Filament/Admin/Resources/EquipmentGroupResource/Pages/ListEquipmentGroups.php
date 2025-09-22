<?php

namespace App\Filament\Admin\Resources\EquipmentGroupResource\Pages;

use App\Filament\Admin\Resources\EquipmentGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentGroups extends ListRecords
{
    protected static string $resource = EquipmentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
