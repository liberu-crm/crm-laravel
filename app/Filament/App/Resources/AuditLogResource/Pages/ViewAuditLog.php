<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AuditLogResource\Pages;

use App\Filament\App\Resources\AuditLogResource;
use App\Models\AuditLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('user.name')->label('By'),
            TextEntry::make('action'),
            TextEntry::make('auditable_type')->label('Subject'),
            TextEntry::make('auditable_id'),
            TextEntry::make('ip_address'),
            TextEntry::make('description')->columnSpanFull(),
            // changes is a nested field=>[old,new] diff; resolve to a pretty-JSON
            // string so any shape renders (a KeyValueEntry only handles flat scalar
            // maps, and an array state on a TextEntry hits the multiple-value path).
            TextEntry::make('changes')
                ->columnSpanFull()
                ->getStateUsing(fn (AuditLog $record): string => filled($record->changes)
                    ? (string) json_encode($record->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : '—'),
        ]);
    }
}
