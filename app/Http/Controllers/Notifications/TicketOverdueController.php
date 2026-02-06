<?php

/*
 * Function TicketOverdueController.php:
 * - Controller for handling ticket after due notifications (1 day past due).
 * - Uses TicketOverdueService to process and send after due alerts.
 * - Manages notifications for tickets 1 working day past their SLA due dates.
 * - Handles requests and responses for after due notifications.
 * - AFTER DUE: 1 working day AFTER sla_duedate (table: tickets_chronologies).
 * - Scheduled to run at 7:30 AM daily
 */

namespace App\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\TicketOverdueService;

class TicketOverdueController extends Controller
{
  protected $ticketOverdueService;

  public function __construct(TicketOverdueService $ticketOverdueService)
  {
    $this->ticketOverdueService = $ticketOverdueService;
  }

  /**
   * Send after due notifications (1 working day past due date)
   * Scheduled to run at 7:30 AM daily
   */
  public function sendAfterDueNotifications()
  {
    try {
      $stats = $this->ticketOverdueService->sendAfterDueNotifications();

      return response()->json([
        'success' => true,
        'message' => 'After due notifications (1 working day past due) processed successfully',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending after due notifications: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send after due notifications',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get after due tickets list (for dashboard/reporting)
   */
  public function getAfterDueTickets()
  {
    try {
      $afterDueTickets = $this->ticketOverdueService->getAfterDueTickets();

      return response()->json([
        'success' => true,
        'count' => $afterDueTickets->count(),
        'data' => $afterDueTickets
      ]);
    } catch (\Exception $e) {
      Log::error('Error getting after due tickets: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to get after due tickets',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
