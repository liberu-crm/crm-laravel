<?php

namespace App\Filament\App\Resources\FormBuilderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\FormBuilderResource;
use Filament\Actions;
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
