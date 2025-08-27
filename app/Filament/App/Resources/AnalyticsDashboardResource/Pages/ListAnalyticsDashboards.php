<?php

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticsDashboards extends ListRecords
{
    protected static string $resource = AnalyticsDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
