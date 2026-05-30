<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AnalyticsDashboardResource\Pages\ViewAnalyticsDashboard;
use App\Models\Contact;
use Filament\Resources\Resource;

class AnalyticsDashboardResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Analytics Dashboard';

    public static function getWidgets(): array
    {
        return [
            //            Widgets\ContactStatsOverview::class,
            //            Widgets\SalesPipelineChart::class,
            //            Widgets\CustomerEngagementChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ViewAnalyticsDashboard::route('/'),
        ];
    }
}
