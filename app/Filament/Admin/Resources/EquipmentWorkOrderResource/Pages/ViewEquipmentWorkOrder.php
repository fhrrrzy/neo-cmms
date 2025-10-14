<?php

namespace App\Filament\Admin\Resources\EquipmentWorkOrderResource\Pages;

use App\Filament\Admin\Resources\EquipmentWorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipmentWorkOrder extends ViewRecord
{
    protected static string $resource = EquipmentWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
