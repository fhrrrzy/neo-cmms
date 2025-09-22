<?php

namespace App\Filament\Admin\Resources\EquipmentRunningTimeResource\Pages;

use App\Filament\Admin\Resources\EquipmentRunningTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentRunningTime extends EditRecord
{
    protected static string $resource = EquipmentRunningTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
