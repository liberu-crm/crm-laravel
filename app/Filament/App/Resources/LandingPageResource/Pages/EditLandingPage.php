<?php

namespace App\Filament\App\Resources\LandingPageResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
