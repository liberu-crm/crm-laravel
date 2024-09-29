<?php

namespace App\Filament\App\Resources\ContactResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Activity;
use Illuminate\Support\Facades\Cache;

class ContactStats extends BaseWidget
{
    protected function getStats(): array
    {
        return Cache::remember('contact_stats', now()->addMinutes(15), function () {
            return [
                Stat::make('Total Contacts', $this->getTotalContacts())
                    ->description('Total number of contacts')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('primary'),

                Stat::make('Active Leads', $this->getActiveLeads())
                    ->description('Number of active leads')
                    ->descriptionIcon('heroicon-m-flag')
                    ->color('warning'),

                Stat::make('Open Deals', $this->getOpenDeals())
                    ->description('Number of open deals')
                    ->descriptionIcon('heroicon-m-currency-dollar')
                    ->color('success'),

                Stat::make('Recent Activities', $this->getRecentActivities())
                    ->description('Activities in the last 7 days')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('info'),
            ];
        });
    }

    private function getTotalContacts(): int
    {
        return Contact::count();
    }

    private function getActiveLeads(): int
    {
        return Lead::where('status', 'active')->count();
    }

    private function getOpenDeals(): int
    {
        return Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count();
    }

    private function getRecentActivities(): int
    {
        return Activity::where('created_at', '>', now()->subDays(7))->count();
    }
}
