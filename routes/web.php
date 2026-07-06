<?php

declare(strict_types=1);

use App\Http\Controllers\ContactListController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LeadFormController;
use App\Http\Controllers\OAuthConfigurationController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TwilioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController as JetstreamTeamInvitationController;

// Health check endpoints (for Kubernetes liveness/readiness probes)
Route::get('/health/startup', fn () => response()->json(['status' => 'starting']))->name('health.startup');
Route::get('/health/live', fn () => response()->json(['status' => 'live']))->name('health.live');
Route::get('/health/ready', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['status' => 'ready']);
    } catch (Exception) {
        return response()->json(['status' => 'not ready', 'error' => 'Database unavailable'], 503);
    }
})->name('health.ready');

// Contact list API routes (no auth required for testing and public access)
// Specific routes must be defined before the wildcard {created_at?} route to avoid conflicts
Route::delete('/contacts/bulk/delete', [ContactListController::class, 'bulkDelete'])->name('contacts.bulk.delete')->middleware('auth');
Route::get('/contacts/autocomplete', [ContactListController::class, 'autocomplete'])->name('contacts.autocomplete');
// Optional {created_at?} path parameter ensures Carbon objects are serialized via __toString()
// when passed to route() helper, unlike query string params which are skipped by http_build_query()
Route::get('/contacts/{created_at?}', [ContactListController::class, 'index'])->name('contacts.list')
    ->where('created_at', '.+');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

// Twilio TwiML routes (public, Twilio callback, signature-verified)
Route::post('/twilio/twiml/outbound', [TwilioController::class, 'handleOutboundCall'])->middleware('twilio.verify')->name('twilio.twiml.outbound');
Route::post('/twilio/recording/callback', [TwilioController::class, 'handleRecordingCallback'])->middleware('twilio.verify')->name('twilio.recording.callback');

// Email tracking routes (public, no auth required)
Route::get('/email/track/pixel/{tracking_id}', [EmailTrackingController::class, 'pixel'])->name('email.tracking.pixel');
Route::get('/email/track/link/{tracking_id}', [EmailTrackingController::class, 'link'])->name('email.tracking.link');

// Route::middleware(['guest'])->group(function () {
//     Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
//     Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
// });

// Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function (): void {
    // Route::get('/app', [HomeController::class, 'app'])->name('app');
    // Route::get('/admin', [HomeController::class, 'admin'])->middleware('admin')->name('admin');

    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
    Route::get('/knowledge-base/{article}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
    Route::post('/quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');

    Route::post('/team-invitations', [TeamInvitationController::class, 'sendInvitation'])->name('team-invitations.send');
    Route::post('/team-invitations/{invitationId}/accept', [TeamInvitationController::class, 'acceptInvitation'])->name('team-invitations.accept');
    Route::delete('/team-invitations/{invitationId}', [JetstreamTeamInvitationController::class, 'destroy'])->name('team-invitations.destroy');

    Route::prefix('social-connections')->group(function (): void {
        Route::get('/', [OAuthConfigurationController::class, 'index'])->name('oauth.configurations.index');
        Route::get('/create', [OAuthConfigurationController::class, 'create'])->name('oauth.configurations.create');
        Route::post('/', [OAuthConfigurationController::class, 'store'])->name('oauth.configurations.store');
        Route::delete('/{configuration}', [OAuthConfigurationController::class, 'destroy'])->name('oauth.configurations.destroy');
    });

    Route::prefix('oauth')->group(function (): void {
        Route::get('/{service}/auth/{configId}', [OAuthConfigurationController::class, 'authenticate'])->name('oauth.authenticate');
        Route::get('/{service}/callback', [OAuthConfigurationController::class, 'oauthCallback'])->name('oauth.configurations.callback');
    });
});

Route::post('/forms/{leadForm}/submit', [LeadFormController::class, 'submit'])->name('forms.submit');
