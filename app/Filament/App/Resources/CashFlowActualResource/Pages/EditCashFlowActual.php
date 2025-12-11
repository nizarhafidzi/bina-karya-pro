<?php

namespace App\Filament\App\Resources\CashFlowActualResource\Pages;

use App\Filament\App\Resources\CashFlowActualResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashFlowActual extends EditRecord
{
    protected static string $resource = CashFlowActualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
