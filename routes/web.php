<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\OAuthConfigurationController;
use App\Http\Controllers\QuoteRequestController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Route::middleware(['guest'])->group(function () {
//     Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
//     Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
// });

// Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // Route::get('/app', [HomeController::class, 'app'])->name('app');
    // Route::get('/admin', [HomeController::class, 'admin'])->middleware('admin')->name('admin');
    
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
    Route::get('/knowledge-base/{article}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
    Route::post('/quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');

    Route::prefix('oauth')->group(function () {
        Route::get('/configurations', [OAuthConfigurationController::class, 'index'])->name('oauth.configurations.index');
        Route::get('/configurations/create', [OAuthConfigurationController::class, 'create'])->name('oauth.configurations.create');
        Route::post('/configurations', [OAuthConfigurationController::class, 'store'])->name('oauth.configurations.store');
        Route::delete('/configurations/{configuration}', [OAuthConfigurationController::class, 'destroy'])->name('oauth.configurations.destroy');
        
        Route::get('/{service}/auth/{configId}', [OAuthConfigurationController::class, 'authenticate'])->name('oauth.authenticate');
        Route::get('/{service}/callback', [OAuthConfigurationController::class, 'callback'])->name('oauth.callback');
        Route::get('/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    });
});