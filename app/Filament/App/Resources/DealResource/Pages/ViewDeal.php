<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DealResource\Pages;

use App\Filament\App\Resources\DealResource;
use App\Models\Deal;
use App\Support\AccessContext;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            // Masked exactly like the DealResource table column so the detail
            // view is not a masking bypass for the `free` role.
            TextEntry::make('value')
                ->getStateUsing(fn (Deal $record): string => AccessContext::shouldMaskFields()
                    ? '[hidden]'
                    : '$'.number_format((float) $record->value, 2)),
            TextEntry::make('stage'),
            TextEntry::make('close_date')->date(),
            TextEntry::make('probability')->suffix('%'),
            TextEntry::make('contact.name')->label('Contact'),
            TextEntry::make('user.name')->label('Owner'),
            TextEntry::make('pipeline.name')->label('Pipeline'),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
