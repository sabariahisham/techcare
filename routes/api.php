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
use App\Http\Controllers\Notifications\TicketReminderController;
use App\Http\Controllers\Notifications\TicketOverdueController;

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

  // REMINDER: 1 working day BEFORE sla_duedate
  Route::post('/notifications/reminder', [TicketReminderController::class, 'sendBeforeDueReminders'])
    ->name('notifications.reminder');

  // AFTER DUE: 1 working day AFTER sla_duedate
  Route::post('/notifications/after-due', [TicketOverdueController::class, 'sendAfterDueNotifications'])
    ->name('notifications.after-due');

  // Get upcoming SLA tickets (for dashboard)
  Route::get('/upcoming', [TicketReminderController::class, 'getUpcomingSLA'])
    ->name('upcoming');

  // Get after due tickets (for reporting)
  Route::get('/after-due-tickets', [TicketOverdueController::class, 'getAfterDueTickets'])
    ->name('after-due-tickets');

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
