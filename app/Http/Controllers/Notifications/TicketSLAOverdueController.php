<?php

/*
  * Function TicketSLAOverdueController.php (NOTIFICATION CONTROLLER):
  *  - Manage SLA reminder and OVERDUE ticket email notifications
  *  - Handle Level 1 and Level 2 Pegawai Tugasan notifications
  *  - Send alerts for: SLA-1, due today, due date+1, and overdue tickets
  */

namespace App\Http\Controllers\Notifications;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\TicketSLAReminderService;

class TicketSLAOverdueController extends Controller
{
  protected $slaReminderService;

  public function __construct(TicketSLAReminderService $slaReminderService)
  {
    $this->slaReminderService = $slaReminderService;
  }

  /**
   * Send SLA-1 reminders (tickets due tomorrow)
   */
  public function sendBeforeDueReminders()
  {
    try {
      $stats = $this->slaReminderService->sendBeforeDueReminders();

      return response()->json([
        'success' => true,
        'message' => 'SLA-1 reminders (due tomorrow) processed',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending SLA-1 reminders: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send SLA-1 reminders',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Send due today reminders
   */
  public function sendDueTodayReminders()
  {
    try {
      $stats = $this->slaReminderService->sendDueTodayReminders();

      return response()->json([
        'success' => true,
        'message' => 'Due today reminders processed',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending due today reminders: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send due today reminders',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Send after due reminders (due date+1 - passed yesterday)
   */
  public function sendAfterDueReminders()
  {
    try {
      $stats = $this->slaReminderService->sendAfterDueReminders();

      return response()->json([
        'success' => true,
        'message' => 'After due (due date+1) reminders processed',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending after due reminders: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send after due reminders',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Send overdue notifications (past due date, more than 1 day)
   */
  public function sendOverdueNotifications()
  {
    try {
      $stats = $this->slaReminderService->sendOverdueNotifications();

      return response()->json([
        'success' => true,
        'message' => 'Overdue notifications processed',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending overdue notifications: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send overdue notifications',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Send all SLA notifications (combined: before due, due today, after due, overdue)
   */
  public function sendAllSLANotifications()
  {
    try {
      $allStats = [
        'before_due' => $this->slaReminderService->sendBeforeDueReminders(),
        'due_today' => $this->slaReminderService->sendDueTodayReminders(),
        'after_due' => $this->slaReminderService->sendAfterDueReminders(),
        'overdue' => $this->slaReminderService->sendOverdueNotifications(),
      ];

      $totalSent = array_sum(array_column($allStats, 'sent'));
      $totalFailed = array_sum(array_column($allStats, 'failed'));

      return response()->json([
        'success' => true,
        'message' => 'All SLA notifications processed',
        'summary' => [
          'total_sent' => $totalSent,
          'total_failed' => $totalFailed,
        ],
        'details' => $allStats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending all SLA notifications: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send all SLA notifications',
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
      $upcomingTickets = $this->slaReminderService->getUpcomingSLATickets($daysAhead);

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
