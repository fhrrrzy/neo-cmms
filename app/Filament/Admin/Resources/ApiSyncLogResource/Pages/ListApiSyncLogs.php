<?php

namespace App\Filament\Admin\Resources\ApiSyncLogResource\Pages;

use App\Filament\Admin\Resources\ApiSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiSyncLogs extends ListRecords
{
    protected static string $resource = ApiSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
