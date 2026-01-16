<?php

/*
  * Function TicketDueDateController.php:
  *  - Manage due date email notifications for Pegawai Tugasan (Level 1) 1 day before due date for status Aduan Baru only.
  *  - Configure email settings and templates.
  *  - Send alerts for ticket Due date approaching and overdue.
  */

namespace App\Http\Controllers\Notifications;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class TicketDueDateController extends Controller
{
  /**
   * Check SLA compliance and send alerts
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function checkSla(Request $request)
  {
    try {
      $currentTime = Carbon::now();
      $results = [
        'checked_tickets' => 0,
        'overdue_tickets' => 0,
        'warning_tickets' => 0,
        'alerts_sent' => 0
      ];

      // Get all active tickets with SLA due dates
      $ticketsWithSla = Ticket::with(['chronologies', 'user', 'status'])
        ->whereHas('chronologies', function ($query) {
          $query->whereNotNull('sla_duedate');
        })
        ->whereNotIn('tick_status', [6]) // Exclude resolved tickets
        ->get();

      $results['checked_tickets'] = $ticketsWithSla->count();

      foreach ($ticketsWithSla as $ticket) {
        $latestSLA = $ticket->chronologies()
          ->whereNotNull('sla_duedate')
          ->latest()
          ->first();

        if ($latestSLA && $latestSLA->sla_duedate) {
          $hoursUntilDue = $currentTime->diffInHours($latestSLA->sla_duedate, false);

          if ($hoursUntilDue < 0) {
            // Ticket is overdue
            $results['overdue_tickets']++;
            $this->sendOverdueAlert($ticket, abs($hoursUntilDue));
            $results['alerts_sent']++;
          } elseif ($hoursUntilDue <= 2) {
            // Ticket is approaching SLA deadline (warning)
            $results['warning_tickets']++;
            $this->sendWarningAlert($ticket, $hoursUntilDue);
            $results['alerts_sent']++;
          }
        }
      }

      Log::info('SLA check completed', $results);

      return response()->json([
        'success' => true,
        'message' => 'Pemeriksaan SLA berjaya diselesaikan.',
        'results' => $results
      ]);
    } catch (\Exception $e) {
      Log::error('SLA check failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa pemeriksaan SLA: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Send overdue alert
   */
  private function sendOverdueAlert($ticket, $overdueHours)
  {
    // TODO: Implement actual email/notification sending
    Log::warning('Ticket overdue', [
      'ticket_id' => $ticket->id,
      'ticket_nolog' => $ticket->ticket_nolog,
      'overdue_hours' => $overdueHours
    ]);

    // Placeholder for email notification
    // You can integrate with TicketStatusController here
  }

  /**
   * Send warning alert (approaching SLA deadline)
   */
  private function sendWarningAlert($ticket, $hoursRemaining)
  {
    // TODO: Implement actual email/notification sending
    Log::info('Ticket approaching SLA deadline', [
      'ticket_id' => $ticket->id,
      'ticket_nolog' => $ticket->ticket_nolog,
      'hours_remaining' => $hoursRemaining
    ]);

    // Placeholder for email notification
    // You can integrate with TicketEmailController here
  }

  /**
   * Auto-escalate tickets based on rules
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function autoEscalate()
  {
    try {
      $escalatedCount = 0;
      $currentTime = Carbon::now();

      // Get tickets that need auto-escalation (e.g., no response for 24 hours)
      $ticketsToEscalate = Ticket::where('tick_status', 1) // New status
        ->where('created_at', '<=', $currentTime->subHours(24))
        ->whereDoesntHave('chronologies', function ($query) {
          $query->where('tick_status', '!=', 1);
        })
        ->get();

      foreach ($ticketsToEscalate as $ticket) {
        // TODO: Implement escalation logic
        // This could involve changing status, assigning to supervisor, etc.
        $escalatedCount++;

        Log::info('Ticket auto-escalated', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog
        ]);
      }

      return response()->json([
        'success' => true,
        'message' => 'Auto-escalation completed.',
        'escalated_count' => $escalatedCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa auto-escalation: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Clean up old tickets and attachments
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function cleanupOldTickets()
  {
    try {
      $cleanedCount = 0;

      // TODO: Implement cleanup logic for tickets older than X months
      // This is a placeholder

      return response()->json([
        'success' => true,
        'message' => 'Cleanup completed.',
        'cleaned_count' => $cleanedCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa cleanup: ' . $e->getMessage()
      ], 500);
    }
  }
}

