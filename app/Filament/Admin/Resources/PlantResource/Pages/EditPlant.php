<?php

namespace App\Filament\Admin\Resources\PlantResource\Pages;

use App\Filament\Admin\Resources\PlantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlant extends EditRecord
{
    protected static string $resource = PlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
