<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AdvertisingAccountResource\Pages;

use App\Filament\App\Resources\AdvertisingAccountResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

/**
 * Read-only detail view for an AdvertisingAccount.
 *
 * Deliberately omits the encrypted `access_token` / `refresh_token` columns so
 * this page is never a secret-disclosure surface. Only the non-secret display
 * fields the table already shows are rendered here.
 */
class ViewAdvertisingAccount extends ViewRecord
{
    protected static string $resource = AdvertisingAccountResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('platform'),
            TextEntry::make('account_id'),
            IconEntry::make('status')->boolean(),
            TextEntry::make('last_sync')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
