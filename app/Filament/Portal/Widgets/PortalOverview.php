<?php

declare(strict_types=1);

namespace App\Filament\Portal\Widgets;

use App\Filament\Portal\Resources\DocumentResource;
use App\Filament\Portal\Resources\KnowledgeBaseArticleResource;
use App\Filament\Portal\Resources\TicketResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Portal landing overview. Each stat reuses the matching resource's
 * getEloquentQuery(), so the customer's scoping (tickets per-user, KB
 * team+published, documents own-contact) is inherited from one source of truth
 * — the counts can never show data the resource itself would hide.
 */
class PortalOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Open tickets', (string) TicketResource::getEloquentQuery()->where('status', '!=', 'closed')->count())
                ->icon('heroicon-o-lifebuoy'),
            Stat::make('Closed tickets', (string) TicketResource::getEloquentQuery()->where('status', 'closed')->count())
                ->icon('heroicon-o-check-circle'),
            Stat::make('Help articles', (string) KnowledgeBaseArticleResource::getEloquentQuery()->count())
                ->icon('heroicon-o-book-open'),
            Stat::make('Documents', (string) DocumentResource::getEloquentQuery()->count())
                ->icon('heroicon-o-document-text'),
        ];
    }
}
