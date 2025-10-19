<?php

namespace App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource\Pages;

use App\Filament\Admin\Resources\EquipmentWorkOrderMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentWorkOrderMaterial extends EditRecord
{
    protected static string $resource = EquipmentWorkOrderMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
