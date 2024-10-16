<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\App\Resources\ContactResource;

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