<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OAuthConfigurationResource\Pages;

use App\Filament\App\Resources\OAuthConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOAuthConfiguration extends EditRecord
{
    protected static string $resource = OAuthConfigurationResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
