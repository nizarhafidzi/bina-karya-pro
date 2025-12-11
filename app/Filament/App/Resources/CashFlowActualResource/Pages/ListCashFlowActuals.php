<?php

namespace App\Filament\App\Resources\CashFlowActualResource\Pages;

use App\Filament\App\Resources\CashFlowActualResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashFlowActuals extends ListRecords
{
    protected static string $resource = CashFlowActualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
