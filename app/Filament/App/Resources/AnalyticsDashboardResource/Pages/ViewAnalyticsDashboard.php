<?php

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Resources\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;

class ViewAnalyticsDashboard extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = AnalyticsDashboardResource::class;

    protected string $view = 'filament.app.resources.analytics-dashboard.view';

    public function getTitle(): string
    {
        return 'Analytics Dashboard';
    }

    protected function getHeaderWidgets(): array
    {
        return array_values(array_filter(AnalyticsDashboardResource::getWidgets()));
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }
}
