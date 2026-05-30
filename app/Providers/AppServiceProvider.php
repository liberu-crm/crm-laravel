<?php

namespace App\Providers;

use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager());
        $this->app->register(ModuleServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
