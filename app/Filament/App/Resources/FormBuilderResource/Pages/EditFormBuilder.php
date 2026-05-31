<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\FormBuilderResource\Pages;

use App\Filament\App\Resources\FormBuilderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormBuilder extends EditRecord
{
    protected static string $resource = FormBuilderResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
