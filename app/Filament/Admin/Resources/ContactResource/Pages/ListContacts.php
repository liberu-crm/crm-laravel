<?php

/**
 * ListContacts class.
 *
 * Provides the implementation for listing contacts in the Filament admin panel. This page
 * allows users to view, search, filter, and delete contacts.
 */

namespace App\Filament\Admin\Resources\ContactResource\Pages;

use App\Filament\Admin\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\CanDeleteRecords;
use Filament\Resources\Pages\ListRecords\Concerns\CanViewRecords;
use Filament\Resources\Pages\ListRecords\Concerns\HasFilters;
use Filament\Resources\Pages\ListRecords\Concerns\HasGlobalSearch;
use Filament\Resources\Pages\ListRecords\Concerns\HasSorting;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}