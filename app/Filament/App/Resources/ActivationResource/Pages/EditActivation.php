<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ActivationResource\Pages;

use App\Filament\App\Resources\ActivationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActivation extends EditRecord
{
    protected static string $resource = ActivationResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
