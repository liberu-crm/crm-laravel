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
});
require __DIR__.'/socialstream.php';require __DIR__.'/socialstream.php';require __DIR__.'/socialstream.php';