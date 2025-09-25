<?php

namespace App\Filament\Admin\Resources\RunningTimeResource\Pages;

use App\Filament\Admin\Resources\RunningTimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRunningTime extends EditRecord
{
    protected static string $resource = RunningTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
