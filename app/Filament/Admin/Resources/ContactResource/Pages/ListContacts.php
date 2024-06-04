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
    use HasGlobalSearch, HasFilters, HasSorting, CanDeleteRecords, CanViewRecords;

    protected function getTableFilters(): array
    {
        return [
            Filter::make('created_at')
                ->date(),
        ];
    }
    protected function getTableBulkActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getGlobalSearchColumns(): array
    {
        return ['name', 'email'];
    }

    public static function getRoutes(): \Illuminate\Routing\RouteCollectionInterface
    {
        return Route::middleware(['auth', 'can:viewContacts'])->group(function () {
            Route::get('/contacts', static::class)->name('contacts.list');
        });
    }
