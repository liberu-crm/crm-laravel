<?php

namespace App\Filament\App\Resources\FormBuilderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\FormBuilderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormBuilder extends EditRecord
{
    protected static string $resource = FormBuilderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
