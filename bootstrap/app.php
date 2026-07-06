<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetPermissionsTeamContext;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\VerifyTwilioRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/socialstream.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'twilio.verify' => VerifyTwilioRequest::class,
        ]);

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            // Scope Spatie permissions to the user's current team so per-team
            // roles resolve. Appended so the session guard is available.
            SetPermissionsTeamContext::class,
        ]);

        // Establish the tenant from the Sanctum user before route-model
        // binding runs, so the IsTenantModel global scope filters API queries.
        // SetPermissionsTeamContext does the same for per-team role resolution.
        $middleware->api(prepend: [
            SetTenantContext::class,
            SetPermissionsTeamContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
