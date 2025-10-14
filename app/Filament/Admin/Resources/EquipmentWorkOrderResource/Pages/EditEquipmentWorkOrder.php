<?php

namespace App\Filament\Admin\Resources\EquipmentWorkOrderResource\Pages;

use App\Filament\Admin\Resources\EquipmentWorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentWorkOrder extends EditRecord
{
    protected static string $resource = EquipmentWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
