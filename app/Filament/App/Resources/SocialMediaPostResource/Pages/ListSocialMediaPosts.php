<?php

namespace App\Filament\App\Resources\SocialMediaPostResource\Pages;

use App\Filament\App\Resources\SocialMediaPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialMediaPosts extends ListRecords
{
    protected static string $resource = SocialMediaPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
