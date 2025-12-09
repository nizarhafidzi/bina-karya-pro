<?php

namespace App\Filament\App\Resources\AhsMasterResource\Pages;

use App\Filament\App\Resources\AhsMasterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAhsMaster extends EditRecord
{
    protected static string $resource = AhsMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
