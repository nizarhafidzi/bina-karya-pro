<?php

namespace App\Filament\App\Resources\WbsResource\Pages;

use App\Filament\App\Resources\WbsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWbs extends EditRecord
{
    protected static string $resource = WbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
