<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\QuoteRequestController;

// ... existing routes

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
Route::get('/knowledge-base/{article}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
Route::post('/quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');

// ... rest of the existing routes

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthConfigurationController;

// ... existing routes ...

Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/configurations', [OAuthConfigurationController::class, 'index'])
        ->name('oauth.configurations.index');
    Route::get('/oauth/configurations/create', [OAuthConfigurationController::class, 'create'])
        ->name('oauth.configurations.create');
    Route::post('/oauth/configurations', [OAuthConfigurationController::class, 'store'])
        ->name('oauth.configurations.store');
    
    // OAuth authentication routes
    Route::get('/oauth/{provider}', [OAuthConfigurationController::class, 'redirectToProvider'])
        ->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [OAuthConfigurationController::class, 'handleProviderCallback'])
        ->name('oauth.callback');
});

use App\Http\Controllers\OAuthConfigurationController;

// OAuth Configuration Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/configurations', [OAuthConfigurationController::class, 'index'])
        ->name('oauth.configurations.index');
    Route::get('/oauth/configurations/create', [OAuthConfigurationController::class, 'create'])
        ->name('oauth.configurations.create');
    Route::post('/oauth/configurations', [OAuthConfigurationController::class, 'store'])
        ->name('oauth.configurations.store');
    Route::delete('/oauth/configurations/{configuration}', [OAuthConfigurationController::class, 'destroy'])
        ->name('oauth.configurations.destroy');
    
    // OAuth Authentication Routes
    Route::get('/oauth/{service}/auth/{configId}', [OAuthConfigurationController::class, 'authenticate'])
        ->name('oauth.authenticate');
    Route::get('/oauth/{service}/callback', [OAuthConfigurationController::class, 'callback'])
        ->name('oauth.callback');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;

// ... existing routes ...

Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/{provider}/redirect', [OAuthController::class, 'redirect'])
        ->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])
        ->name('oauth.callback');
});