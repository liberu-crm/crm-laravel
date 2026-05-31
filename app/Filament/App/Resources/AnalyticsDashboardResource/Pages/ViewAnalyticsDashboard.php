<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AnalyticsDashboardResource\Pages;

use App\Filament\App\Resources\AnalyticsDashboardResource;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;

class ViewAnalyticsDashboard extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = AnalyticsDashboardResource::class;

    protected string $view = 'filament.app.resources.analytics-dashboard.view';

    #[\Override]
    public function getTitle(): string
    {
        return 'Analytics Dashboard';
    }

    #[\Override]
    protected function getHeaderWidgets(): array
    {
        return array_values(array_filter(AnalyticsDashboardResource::getWidgets()));
    }

    #[\Override]
    protected function getFooterWidgets(): array
    {
        return [];
    }

    #[\Override]
    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    #[\Override]
    public function getFooterWidgetsColumns(): int|array
    {
        return 2;
    }
}
