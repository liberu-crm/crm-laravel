<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SsoConnectionResource\Pages;

use App\Filament\App\Resources\SsoConnectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSsoConnection extends EditRecord
{
    protected static string $resource = SsoConnectionResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
