<?php

namespace App\Filament\App\Resources\LandingPagesResource\Pages;

use App\Filament\App\Resources\LandingPagesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingPages extends EditRecord
{
    protected static string $resource = LandingPagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
