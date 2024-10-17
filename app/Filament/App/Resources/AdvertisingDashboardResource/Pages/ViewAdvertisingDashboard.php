<?php

namespace App\Filament\App\Resources\AdvertisingDashboardResource\Pages;

use App\Filament\App\Resources\AdvertisingDashboardResource;
use Filament\Resources\Pages\Page;
use Filament\Pages\Actions;
use Filament\Forms\Components\Card;

class ViewAdvertisingDashboard extends Page
{
    protected static string $resource = AdvertisingDashboardResource::class;

    protected static string $view = 'filament.app.resources.advertising-dashboard-resource.pages.view-advertising-dashboard';

    public function getTitle(): string
    {
        return 'Advertising Dashboard';
    }

    protected function getHeaderWidgets(): array
    {
        $widgets = AdvertisingDashboardResource::getWidgets();
        return array_slice($widgets, 0, 2);
    }

    protected function getFooterWidgets(): array
    {
       $widgets = AdvertisingDashboardResource::getWidgets();
        return array_slice($widgets, 2, 1);
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->action(fn () => $this->refresh())
                ->color('secondary'),
        ];
    }
}
