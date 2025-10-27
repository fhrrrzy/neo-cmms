<?php

namespace App\Filament\Admin\Resources\DailyPlantDataResource\Pages;

use App\Filament\Admin\Resources\DailyPlantDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyPlantData extends EditRecord
{
    protected static string $resource = DailyPlantDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
