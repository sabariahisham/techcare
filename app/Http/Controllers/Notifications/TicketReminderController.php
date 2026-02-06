<?php

/*
 * Function TicketReminderController.php:
 * - Controller for handling ticket reminder notifications.
 * - Uses TicketReminderService to process and send reminders.
 * - Manages reminders for tickets nearing their SLA due dates.
 * - Handles requests and responses for reminder notifications.
 * - Supports reminders: 1 working day BEFORE sla_duedate (table: tickets_chronologies).
 * - Scheduled to run at 7:30 AM daily
 */

namespace App\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\TicketReminderService;

class TicketReminderController extends Controller
{
  protected $ticketReminderService;

  public function __construct(TicketReminderService $ticketReminderService)
  {
    $this->ticketReminderService = $ticketReminderService;
  }

  /**
   * Send reminder notifications (1 working day before due date)
   * Scheduled to run at 7:30 AM daily
   */
  public function sendBeforeDueReminders()
  {
    try {
      $stats = $this->ticketReminderService->sendBeforeDueReminders();

      return response()->json([
        'success' => true,
        'message' => 'Reminder notifications (1 day before due) processed successfully',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending reminder notifications: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send reminder notifications',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get upcoming SLA tickets (for dashboard)
   */
  public function getUpcomingSLA(Request $request)
  {
    try {
      $daysAhead = $request->input('days', 7);
      $upcomingTickets = $this->ticketReminderService->getUpcomingSLATickets($daysAhead);

      return response()->json([
        'success' => true,
        'count' => $upcomingTickets->count(),
        'days_ahead' => $daysAhead,
        'data' => $upcomingTickets
      ]);
    } catch (\Exception $e) {
      Log::error('Error getting upcoming SLA tickets: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to get upcoming SLA tickets',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
