<?php

namespace App\Filament\App\Resources\OAuthConfigurationResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OAuthConfigurationResource;
use Filament\Actions;
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
