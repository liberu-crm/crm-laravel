<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SocialMediaPostResource\Pages;

use App\Filament\App\Resources\SocialMediaPostResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSocialMediaPost extends ViewRecord
{
    protected static string $resource = SocialMediaPostResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('content')->columnSpanFull(),
            TextEntry::make('status')->badge(),
            TextEntry::make('platforms')->badge(),
            TextEntry::make('link'),
            TextEntry::make('scheduled_at')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
