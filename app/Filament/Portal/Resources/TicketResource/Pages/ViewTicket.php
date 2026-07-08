<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Filament\Portal\Resources\TicketResource;
use App\Models\Message;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Owner-scoped lifecycle: the record resolves through the scoped
            // resource query, so a customer only ever transitions their own ticket.
            Action::make('close')
                ->label('Close ticket')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->getAttribute('status') !== 'closed')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'closed']);
                    Notification::make()->title('Ticket closed')->success()->send();
                }),
            Action::make('reopen')
                ->label('Reopen ticket')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->getRecord()->getAttribute('status') === 'closed')
                ->action(function (): void {
                    $this->getRecord()->update(['status' => 'open']);
                    Notification::make()->title('Ticket reopened')->success()->send();
                }),
            // The record is already ownership-scoped (TicketResource::getEloquentQuery),
            // so a customer can only stream their own ticket's file, off the private disk.
            Action::make('download_attachment')
                ->label('Download attachment')
                ->icon('heroicon-o-paper-clip')
                ->visible(fn (): bool => filled($this->getRecord()->getAttribute('attachment')))
                ->action(fn () => Storage::disk('local')->download($this->getRecord()->getAttribute('attachment'))),
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

    // The record is ownership-scoped by TicketResource::getEloquentQuery, so the
    // thread only ever renders the customer's own ticket's messages (their replies
    // and any staff replies persisted via ReplyToPortalTicket).
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')
                ->columns(2)
                ->schema([
                    TextEntry::make('subject'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('priority')->badge(),
                    TextEntry::make('body')->label('Description')->columnSpanFull(),
                ]),
            Section::make('Conversation')
                ->schema([
                    RepeatableEntry::make('messages')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('sender'),
                            TextEntry::make('timestamp')->dateTime(),
                            TextEntry::make('content')->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
