<?php

namespace App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource\Pages;

use App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentWorkOrderMaterials extends ListRecords
{
    protected static string $resource = EquipmentWorkOrderMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
