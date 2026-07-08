<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use App\Models\Company;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('industry'),
            TextEntry::make('website'),
            TextEntry::make('city'),
            TextEntry::make('state'),
            TextEntry::make('size'),
            TextEntry::make('domain'),
            // Sensitive fields masked for masked-role (`free`) viewers, same as the
            // table column, so the detail view is not a masking bypass.
            TextEntry::make('phone_number')
                ->getStateUsing(fn (Company $record): mixed => $record->maskFor('phone_number', $record->phone_number)),
            TextEntry::make('annual_revenue')
                ->getStateUsing(fn (Company $record): mixed => $record->maskFor('annual_revenue', $record->annual_revenue)),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
