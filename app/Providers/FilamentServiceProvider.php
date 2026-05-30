<?php

namespace App\Providers;

use App\Filament\App\Resources\ContactResource;
use Filament\Panel;
use Filament\PanelProvider;

class FilamentServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->resources([
                ContactResource::class,
                // Add other resources here
            ]);
    }
}
