<?php

namespace App\Filament\App\Resources\DashboardWidgetResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\DashboardWidgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDashboardWidgets extends ListRecords
{
    protected static string $resource = DashboardWidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
