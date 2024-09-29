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
        return [
            $this->getCachedStat('total_contacts', 'Total Contacts', 'heroicon-m-user-group', 'primary'),
            $this->getCachedStat('active_leads', 'Active Leads', 'heroicon-m-flag', 'warning'),
            $this->getCachedStat('open_deals', 'Open Deals', 'heroicon-m-currency-dollar', 'success'),
            $this->getCachedStat('recent_activities', 'Recent Activities', 'heroicon-m-clock', 'info'),
        ];
    }

    private function getCachedStat(string $key, string $label, string $icon, string $color): Stat
    {
        $value = Cache::remember("contact_stats_{$key}", now()->addMinutes(5), function () use ($key) {
            return $this->{"get" . studly_case($key)}();
        });

        return Stat::make($label, $value)
            ->description($this->getDescription($key))
            ->descriptionIcon($icon)
            ->color($color);
    }

    private function getDescription(string $key): string
    {
        $descriptions = [
            'total_contacts' => 'Total number of contacts',
            'active_leads' => 'Number of active leads',
            'open_deals' => 'Number of open deals',
            'recent_activities' => 'Activities in the last 7 days',
        ];

        return $descriptions[$key] ?? '';
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
