<?php

namespace App\Filament\App\Resources\OAuthConfigurationResource\Pages;

use App\Filament\App\Resources\OAuthConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOAuthConfigurations extends ListRecords
{
    protected static string $resource = OAuthConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
