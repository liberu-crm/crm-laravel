<?php

namespace App\Filament\App\Resources\SocialMediaPostResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\SocialMediaPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialMediaPost extends EditRecord
{
    protected static string $resource = SocialMediaPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
