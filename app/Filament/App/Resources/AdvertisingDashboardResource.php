<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AdvertisingDashboardResource\Pages\ViewAdvertisingDashboard;
use App\Models\AdvertisingAccount;
use Filament\Resources\Resource;

class AdvertisingDashboardResource extends Resource
{
    protected static ?string $model = AdvertisingAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Advertising Dashboard';

    #[\Override]
    public static function getWidgets(): array
    {
        return [
            //      Widgets\AdPerformanceWidget::class,
            //      Widgets\CampaignOverviewWidget::class,
            //      Widgets\PlatformComparisonWidget::class,
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ViewAdvertisingDashboard::route('/'),
        ];
    }
}
