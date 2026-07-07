<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Events\NewTicket;
use App\Filament\Portal\Resources\TicketResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * Inject the routing fields the customer never supplies. team_id is the
     * customer's own tenant (canCreate guarantees it is set); email_id is minted
     * here because the column is unique NOT NULL (the email intake path uses the
     * mail message-id — the portal has none).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = Auth::user();

        $data['user_id'] = $user->getKey();
        $data['team_id'] = $user->getAttribute('current_team_id');
        $data['source'] = 'portal';
        $data['email_id'] = 'portal-'.Str::uuid()->toString();
        $data['status'] = 'open';

        return $data;
    }

    protected function afterCreate(): void
    {
        NewTicket::dispatch($this->record);
    }
}
