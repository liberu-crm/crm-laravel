<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

use App\Http\Controllers\OAuthController;

// ... existing routes

Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/reports/contact-interactions', [ReportController::class, 'generateContactInteractionsReport'])->name('reports.contact-interactions');
    Route::get('/reports/sales-pipeline', [ReportController::class, 'generateSalesPipelineReport'])->name('reports.sales-pipeline');
    Route::get('/reports/customer-engagement', [ReportController::class, 'generateCustomerEngagementReport'])->name('reports.customer-engagement');

    Route::get('/analytics-dashboard', function () {
        return view('analytics-dashboard');
    })->name('analytics-dashboard');

    // Twilio routes
    Route::prefix('twilio')->group(function () {
        Route::post('/initiate-call', [TwilioController::class, 'initiateCall'])->name('twilio.initiate-call');
        Route::post('/twiml/outbound', [TwilioController::class, 'handleOutboundCall'])->name('twilio.twiml.outbound');
        Route::post('/twiml/inbound', [TwilioController::class, 'handleInboundCall'])->name('twilio.twiml.inbound');
        Route::post('/recording/callback', [TwilioController::class, 'handleRecordingCallback'])->name('twilio.recording.callback');
        Route::post('/start-recording', [TwilioController::class, 'startRecording'])->name('twilio.start-recording');
        Route::post('/stop-recording', [TwilioController::class, 'stopRecording'])->name('twilio.stop-recording');
    });
});
require __DIR__.'/socialstream.php';require __DIR__.'/socialstream.php';require __DIR__.'/socialstream.php';