<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use App\Observers\AuditObserver;
use App\Support\SsoLogoutState;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, fn (): ModuleManager => new ModuleManager);
        // Request-scoped holder for the SSO single-logout redirect URL.
        $this->app->singleton(SsoLogoutState::class);
        $this->app->register(ModuleServiceProvider::class);
    }

    public function boot(): void
    {
        // Audit core tenant models. Never observe AuditLog itself -> infinite recursion.
        foreach ([Contact::class, Deal::class, Lead::class, Opportunity::class, Task::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
