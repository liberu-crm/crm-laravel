<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticsDashboards extends ListRecords
{
    protected static string $resource = AnalyticsDashboardResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
