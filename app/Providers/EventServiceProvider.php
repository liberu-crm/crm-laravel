<?php

namespace App\Providers;

use App\Models\Task;
use App\Observers\TaskObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\SendCRMEventNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\ContactUpdated' => [
            'App\Listeners\NotifyTeamMembers',
        ],
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\LogSuccessfulLogout',
        ],
        // Add CRM event listeners
        'App\Events\NewLead' => [
            SendCRMEventNotification::class,
        ],
        'App\Events\DealClosed' => [
            SendCRMEventNotification::class,
        ],
        'App\Events\TaskOverdue' => [
            SendCRMEventNotification::class,
        ],
        'App\Events\NewComment' => [
            SendCRMEventNotification::class,
        ],
        'App\Events\MeetingScheduled' => [
            SendCRMEventNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);

        Event::listen('Illuminate\Auth\Events\Logout', function ($event) {
            app(AuditLogService::class)->log('logout', 'User logged out');
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
