<?php

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Resources\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;

class ViewAnalyticsDashboard extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = AnalyticsDashboardResource::class;

    protected static string $view = 'filament.app.resources.analytics-dashboard.view';

    public function getTitle(): string
    {
        return 'Analytics Dashboard';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsDashboardResource::getWidgets()[0],
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AnalyticsDashboardResource::getWidgets()[1],
            AnalyticsDashboardResource::getWidgets()[2],
        ];
    }
}