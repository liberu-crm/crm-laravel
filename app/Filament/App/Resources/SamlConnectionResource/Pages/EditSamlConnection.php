<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SamlConnectionResource\Pages;

use App\Filament\App\Resources\SamlConnectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSamlConnection extends EditRecord
{
    protected static string $resource = SamlConnectionResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
