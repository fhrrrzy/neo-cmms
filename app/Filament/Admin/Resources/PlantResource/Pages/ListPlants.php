<?php

namespace App\Filament\Admin\Resources\PlantResource\Pages;

use App\Filament\Admin\Resources\PlantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlants extends ListRecords
{
    protected static string $resource = PlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
