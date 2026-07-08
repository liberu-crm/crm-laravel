<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ContactResource\Pages;

use App\Filament\App\Resources\ContactResource;
use App\Models\Contact;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('last_name'),
            TextEntry::make('status'),
            TextEntry::make('source'),
            TextEntry::make('industry'),
            TextEntry::make('lifecycle_stage'),
            TextEntry::make('company.name')->label('Company'),
            // Sensitive fields masked for masked-role (`free`) viewers, same as the
            // table column, so the detail view is not a masking bypass.
            TextEntry::make('email')
                ->getStateUsing(fn (Contact $record): mixed => $record->maskFor('email', $record->email)),
            TextEntry::make('phone_number')
                ->getStateUsing(fn (Contact $record): mixed => $record->maskFor('phone_number', $record->phone_number)),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
