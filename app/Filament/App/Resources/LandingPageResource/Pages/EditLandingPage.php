<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\LandingPageResource\Pages;

use App\Filament\App\Resources\LandingPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
