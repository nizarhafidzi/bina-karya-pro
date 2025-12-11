<?php

namespace App\Filament\App\Resources\CashFlowPlanResource\Pages;

use App\Filament\App\Resources\CashFlowPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashFlowPlan extends EditRecord
{
    protected static string $resource = CashFlowPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
