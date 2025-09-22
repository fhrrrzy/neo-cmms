<?php

namespace App\Filament\Admin\Resources\EquipmentRunningTimeResource\Pages;

use App\Filament\Admin\Resources\EquipmentRunningTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentRunningTimes extends ListRecords
{
    protected static string $resource = EquipmentRunningTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
