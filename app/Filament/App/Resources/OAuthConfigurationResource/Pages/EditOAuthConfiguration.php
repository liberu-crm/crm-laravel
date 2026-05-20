<?php

namespace App\Filament\App\Resources\OAuthConfigurationResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\OAuthConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOAuthConfiguration extends EditRecord
{
    protected static string $resource = OAuthConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
