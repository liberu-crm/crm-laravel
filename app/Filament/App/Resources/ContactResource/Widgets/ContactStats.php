<?php

namespace App\Filament\App\Resources\ContactResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Activity;

class ContactStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Contacts', Contact::count())
                ->description('Total number of contacts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Active Leads', Lead::where('status', 'active')->count())
                ->description('Number of active leads')
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),

            Stat::make('Open Deals', Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count())
                ->description('Number of open deals')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Recent Activities', Activity::where('created_at', '>', now()->subDays(7))->count())
                ->description('Activities in the last 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
