<?php

namespace App\Filament\App\Resources\AhsMasterResource\Pages;

use App\Filament\App\Resources\AhsMasterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAhsMasters extends ListRecords
{
    protected static string $resource = AhsMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
