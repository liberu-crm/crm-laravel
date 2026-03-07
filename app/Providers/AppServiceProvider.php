<?php

namespace App\Providers;

use App\Http\Livewire\CallManager;
use App\Http\Livewire\ContactCollaboration;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\DealCard;
use App\Http\Livewire\OpportunityPipeline;
use App\Http\Livewire\ReportCustomizer;
use App\Http\Livewire\TaskForm;
use App\Http\Livewire\TaskList;
use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the module manager as a singleton
        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager();
        });

        // Register the module service provider
        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('call-manager', CallManager::class);
        Livewire::component('contact-collaboration', ContactCollaboration::class);
        Livewire::component('dashboard', Dashboard::class);
        Livewire::component('deal-card', DealCard::class);
        Livewire::component('opportunity-pipeline', OpportunityPipeline::class);
        Livewire::component('report-customizer', ReportCustomizer::class);
        Livewire::component('task-form', TaskForm::class);
        Livewire::component('task-list', TaskList::class);
    }
}
