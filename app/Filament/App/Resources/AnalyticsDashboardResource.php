<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AnalyticsDashboardResource\Pages;
use App\Models\Contact;
use App\Models\Deal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\ChartWidget;

class AnalyticsDashboardResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Analytics Dashboard';

    public static function getWidgets(): array
    {
        return [
            Widgets\ContactStatsOverview::class,
            Widgets\SalesPipelineChart::class,
            Widgets\CustomerEngagementChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewAnalyticsDashboard::route('/'),
        ];
    }
}
