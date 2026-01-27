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
use App\Http\Controllers\Ticket\TicketSLAController;
use App\Http\Controllers\Notifications\TicketSLAOverdueController;

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

// ========================================
// SLA Notification Routes (Admin/Testing)
// ========================================
Route::middleware(['auth:sanctum'])->prefix('sla')->name('api.sla.')->group(function () {
  
  // Trigger SLA notifications manually (for testing or manual execution)
  Route::post('/notifications/before-due', [TicketSLAOverdueController::class, 'sendBeforeDueReminders'])
    ->name('notifications.before-due');
  
  Route::post('/notifications/due-today', [TicketSLAOverdueController::class, 'sendDueTodayReminders'])
    ->name('notifications.due-today');
  
  Route::post('/notifications/after-due', [TicketSLAOverdueController::class, 'sendAfterDueReminders'])
    ->name('notifications.after-due');
  
  Route::post('/notifications/overdue', [TicketSLAOverdueController::class, 'sendOverdueNotifications'])
    ->name('notifications.overdue');
  
  Route::post('/notifications/all', [TicketSLAOverdueController::class, 'sendAllSLANotifications'])
    ->name('notifications.all');
  
  // Get upcoming SLA tickets
  Route::get('/upcoming', [TicketSLAOverdueController::class, 'getUpcomingSLA'])
    ->name('upcoming');
  
  // Reporting routes
  Route::get('/tickets/overdue', [TicketSLAController::class, 'getOverdueTickets'])
    ->name('tickets.overdue');
  
  Route::get('/tickets/due-tomorrow', [TicketSLAController::class, 'getDueTomorrowTickets'])
    ->name('tickets.due-tomorrow');
  
  Route::get('/tickets/due-today', [TicketSLAController::class, 'getDueTodayTickets'])
    ->name('tickets.due-today');
  
  Route::get('/tickets/due-yesterday', [TicketSLAController::class, 'getDueYesterdayTickets'])
    ->name('tickets.due-yesterday');
  
  Route::get('/statistics', [TicketSLAController::class, 'getSlaStatistics'])
    ->name('statistics');
});
