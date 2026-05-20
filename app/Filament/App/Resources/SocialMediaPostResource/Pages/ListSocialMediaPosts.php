<?php

namespace App\Filament\App\Resources\SocialMediaPostResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\SocialMediaPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialMediaPosts extends ListRecords
{
    protected static string $resource = SocialMediaPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
