<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Filament\Portal\Resources\TicketResource;
use App\Models\Message;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-arrow-uturn-left')
                ->schema([
                    Textarea::make('content')->label('Your reply')->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Ticket $ticket */
                    $ticket = $this->getRecord(); // already ownership-scoped by resolveRecord

                    $message = new Message([
                        'channel' => 'portal',
                        'sender' => Auth::user()->email,
                        'content' => $data['content'],
                        'priority' => $ticket->priority ?: 'medium',
                        'status' => 'unread',
                        // ponytail: messages.account_id is a legacy NOT NULL int with no
                        // default; 0 sentinel when the ticket has no source account.
                        'account_id' => (int) ($ticket->account_id ?? 0),
                        'metadata' => [],
                        'timestamp' => now(),
                        'ticket_id' => $ticket->id,
                    ]);

                    // team_id is system-managed (not fillable on Message). Set it
                    // directly so the reply carries the ticket's tenant and stays
                    // visible to staff on the team-scoped app panel; the portal is
                    // non-tenant, so IsTenantModel's creating hook won't stamp it.
                    $message->setAttribute('team_id', $ticket->getAttribute('team_id'));
                    $message->save();

                    Notification::make()->title('Reply sent')->success()->send();
                }),
        ];
    }
}
