<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ContactUpdated;
use App\Listeners\AssignDefaultTeamRole;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\NotifyTeamMembers;
use App\Listeners\SendCRMEventNotification;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamMemberAdded;

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
        ContactUpdated::class => [
            NotifyTeamMembers::class,
        ],
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        TeamMemberAdded::class => [
            AssignDefaultTeamRole::class,
        ],
        // 'Illuminate\Auth\Events\Logout' => [
        //     App\Listeners\LogSuccessfulLogout,
        // ],
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
        'App\Events\NewTicket' => [
            SendCRMEventNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    #[\Override]
    public function boot(): void
    {
        Task::observe(TaskObserver::class);

        Event::listen(Logout::class, function ($event): void {
            app(AuditLogService::class)->log('logout', 'User logged out');
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    #[\Override]
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
