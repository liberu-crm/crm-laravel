<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Core resources
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('deals', DealController::class);
    Route::apiResource('tasks', TaskController::class);

    // Bulk operations — contacts
    Route::post('contacts/bulk/update', [ContactController::class, 'bulkUpdate']);
    Route::post('contacts/bulk/delete', [ContactController::class, 'bulkDelete']);
    Route::post('contacts/bulk/assign', [ContactController::class, 'bulkAssign']);

    // Bulk operations — deals
    Route::post('deals/bulk/update', [DealController::class, 'bulkUpdate']);
    Route::post('deals/bulk/delete', [DealController::class, 'bulkDelete']);
    Route::post('deals/bulk/assign', [DealController::class, 'bulkAssign']);

    // Bulk operations — tasks
    Route::post('tasks/bulk/update', [TaskController::class, 'bulkUpdate']);
    Route::post('tasks/bulk/delete', [TaskController::class, 'bulkDelete']);
    Route::post('tasks/bulk/assign', [TaskController::class, 'bulkAssign']);

    // Webhook management
    Route::get('webhooks/events', [WebhookController::class, 'events']);
    Route::post('webhooks/{webhook}/secret', [WebhookController::class, 'regenerateSecret']);
    Route::apiResource('webhooks', WebhookController::class);
});

