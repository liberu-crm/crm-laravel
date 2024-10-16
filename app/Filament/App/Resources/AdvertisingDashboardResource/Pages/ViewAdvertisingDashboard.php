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
        return [
            AdvertisingDashboardResource::getWidgets()[0],
            AdvertisingDashboardResource::getWidgets()[1],
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AdvertisingDashboardResource::getWidgets()[2],
        ];
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
