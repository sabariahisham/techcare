<?php

/*
 * API Routes
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 * ✅ Routes ni return JSON data (bukan HTML)
 * ✅ Dipanggil oleh JavaScript/AJAX
 * ✅ Guna API authentication (Sanctum)
 * ✅ Tidak perlu CSRF token
 * ✅ AJAX/JSON/Mobile API → api.php, Browser navigation → web.php
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SlaController;
use App\Http\Controllers\Ticket\TicketDueDateController;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

// SLA related routes
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('/sla-data/{categoryId}/{subcategoryId}', [SlaController::class, 'getSlaData']);
  Route::get('/officers-by-sla/{categoryId}/{subcategoryId}/{severity}', [SlaController::class, 'getOfficersBySla']);
});

// Ticket Due Date Testing Routes (Admin only)
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('/tickets/test-due-date-reminders', [TicketDueDateController::class, 'testDueDateReminders'])
    ->name('api.tickets.test-duedate-reminders');
});
