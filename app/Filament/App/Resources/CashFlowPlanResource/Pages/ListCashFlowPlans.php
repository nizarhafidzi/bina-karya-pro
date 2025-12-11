<?php

namespace App\Filament\App\Resources\CashFlowPlanResource\Pages;

use App\Filament\App\Resources\CashFlowPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashFlowPlans extends ListRecords
{
    protected static string $resource = CashFlowPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
