<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdvertisingDashboardResource\Pages;
use App\Models\AdvertisingAccount;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\ChartWidget;

class AdvertisingDashboardResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Advertising Dashboard';

    public static function getWidgets(): array
    {
        return [
      //      Widgets\AdPerformanceWidget::class,
      //      Widgets\CampaignOverviewWidget::class,
      //      Widgets\PlatformComparisonWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewAdvertisingDashboard::route('/'),
        ];
    }
}
