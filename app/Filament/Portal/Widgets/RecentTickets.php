<?php

declare(strict_types=1);

namespace App\Filament\Portal\Widgets;

use App\Filament\Portal\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The customer's recent tickets on the portal dashboard. Reuses
 * TicketResource::getEloquentQuery() (where user_id = auth), so the feed
 * inherits the same per-user scoping as the rest of the portal — it can never
 * surface a ticket the resource itself would hide.
 */
class RecentTickets extends TableWidget
{
    protected static ?string $heading = 'Recent tickets';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => TicketResource::getEloquentQuery()->latest('updated_at')->limit(5))
            ->columns([
                TextColumn::make('subject')->limit(60)->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('updated_at')->dateTime()->since()->sortable(),
            ])
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->paginated(false)
            ->emptyStateHeading('No tickets yet');
    }
}
