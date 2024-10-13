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