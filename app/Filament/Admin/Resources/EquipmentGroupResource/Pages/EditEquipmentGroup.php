<?php

namespace App\Filament\Admin\Resources\EquipmentGroupResource\Pages;

use App\Filament\Admin\Resources\EquipmentGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentGroup extends EditRecord
{
    protected static string $resource = EquipmentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
