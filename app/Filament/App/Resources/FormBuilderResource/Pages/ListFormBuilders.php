<?php

namespace App\Filament\App\Resources\FormBuilderResource\Pages;

use App\Filament\App\Resources\FormBuilderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormBuilders extends ListRecords
{
    protected static string $resource = FormBuilderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
