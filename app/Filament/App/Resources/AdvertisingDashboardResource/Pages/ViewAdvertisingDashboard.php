<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AdvertisingDashboardResource\Pages;

use App\Filament\App\Resources\AdvertisingDashboardResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ViewAdvertisingDashboard extends Page
{
    protected static string $resource = AdvertisingDashboardResource::class;

    protected string $view = 'filament.app.resources.advertising-dashboard-resource.pages.view-advertising-dashboard';

    #[\Override]
    public function getTitle(): string
    {
        return 'Advertising Dashboard';
    }

    #[\Override]
    protected function getHeaderWidgets(): array
    {
        $widgets = AdvertisingDashboardResource::getWidgets();

        return array_slice($widgets, 0, 2);
    }

    #[\Override]
    protected function getFooterWidgets(): array
    {
        $widgets = AdvertisingDashboardResource::getWidgets();

        return array_slice($widgets, 2, 1);
    }

    #[\Override]
    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    #[\Override]
    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    #[\Override]
    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->action(fn () => $this->refresh())
                ->color('secondary'),
        ];
    }
}
