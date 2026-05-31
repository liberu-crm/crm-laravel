<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, fn (): \App\Modules\ModuleManager => new ModuleManager());
        $this->app->register(ModuleServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
