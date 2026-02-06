<?php

/*
 * TicketSLAReminderService.php - SIMPLIFIED VERSION
 *
 * Handles 2 types of SLA notifications ONLY:
 * 1. REMINDER: 1 working day BEFORE sla_duedate (ALL statuses except 5, 7)
 * 2. OVERDUE: 1 working day AFTER sla_duedate (ALL statuses except 5, 7)
 *
 * All notifications refer to tickets_chronologies.sla_duedate ONLY
 * NO QUEUE - Direct email sending
 * Scheduled to run at 7:30 AM daily
 */

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Notifications\Ticket\ReminderNotification;
use App\Notifications\Ticket\SLAOverdueNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helpers;

class TicketSLAReminderService
{
  /**
   * Send reminder notifications (1 working day before SLA due date)
   * For ALL statuses except Batal (5) and Selesai (6)
   *
   * @return array
   */
  public function sendBeforeDueReminders(): array
  {
    Log::info('=== Starting SLA Reminder Notifications (1 working day before) ===');

    try {
      // Calculate target date (tomorrow in working days)
      $tomorrow = Helpers::addWorkingDays(Carbon::today(), 1);
      $tomorrowStart = $tomorrow->startOfDay();
      $tomorrowEnd = $tomorrow->copy()->endOfDay();

      Log::info("Target SLA due date: {$tomorrowStart->format('Y-m-d')} to {$tomorrowEnd->format('Y-m-d')}");

      // Get latest chronology per ticket with sla_duedate = tomorrow
      $chronologies = TicketChronology::select('tickets_chronologies.*')
        ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                                FROM tickets_chronologies
                                WHERE deleted_at IS NULL
                                GROUP BY ticket_id) latest'), function ($join) {
          $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
            ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
        })
        ->whereBetween('tickets_chronologies.sla_duedate', [$tomorrowStart, $tomorrowEnd])
        ->whereNotNull('tickets_chronologies.escalate_to')
        ->whereNull('tickets_chronologies.deleted_at')
        ->with([
          'ticket',
          'ticket.user',
          'ticket.status',
          'escalatedTo'
        ])
        ->get();

      Log::info("Found {$chronologies->count()} chronologies with SLA due tomorrow");

      $results = [
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => []
      ];

      foreach ($chronologies as $chronology) {
        $ticket = $chronology->ticket;

        // Skip if ticket doesn't exist
        if (!$ticket) {
          $results['skipped']++;
          Log::info("Skipped chronology {$chronology->id} - Ticket not found");
          continue;
        }

        // Skip if ticket is closed or cancelled
        if (in_array($ticket->tick_status, [5, 7])) {
          $results['skipped']++;
          Log::info("Skipped ticket {$ticket->ticket_nolog} - Status: {$ticket->tick_status}");
          continue;
        }

        // Skip if no assigned officer
        if (!$chronology->escalate_to || !$chronology->escalatedTo) {
          $results['skipped']++;
          Log::info("Skipped ticket {$ticket->ticket_nolog} - No assigned officer");
          continue;
        }

        try {
          $officer = $chronology->escalatedTo;

          // Skip if no email
          if (empty($officer->email)) {
            $results['skipped']++;
            Log::warning("Skipped ticket {$ticket->ticket_nolog} - Officer has no email");
            continue;
          }

          // Send notification
          $officer->notify(new ReminderNotification($ticket, $chronology));

          $results['sent']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'officer' => $officer->nama,
            'email' => $officer->email,
            'status' => 'sent',
            'sla_duedate' => $chronology->sla_duedate
          ];

          Log::info("✓ Reminder sent to {$officer->email} for ticket {$ticket->ticket_nolog}");
        } catch (\Exception $e) {
          $results['failed']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'error' => $e->getMessage(),
            'status' => 'failed'
          ];

          Log::error("✗ Failed to send reminder for ticket {$ticket->ticket_nolog}: {$e->getMessage()}");
        }
      }

      Log::info("=== Reminder Summary: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped ===");

      return $results;
    } catch (\Exception $e) {
      Log::error("Error in sendBeforeDueReminders: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Send overdue notifications (1 working day after SLA due date)
   * For ALL statuses except Batal (5) and Selesai (6)
   *
   * @return array
   */
  public function sendAfterDueReminders(): array
  {
    Log::info('=== Starting SLA Overdue Notifications (1 working day after) ===');

    try {
      // Calculate target date (yesterday in working days)
      $yesterday = Helpers::subtractWorkingDays(Carbon::today(), 1);
      $yesterdayStart = $yesterday->startOfDay();
      $yesterdayEnd = $yesterday->copy()->endOfDay();

      Log::info("Target SLA due date: {$yesterdayStart->format('Y-m-d')} to {$yesterdayEnd->format('Y-m-d')}");

      // Get latest chronology per ticket with sla_duedate = yesterday
      $chronologies = TicketChronology::select('tickets_chronologies.*')
        ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                                FROM tickets_chronologies
                                WHERE deleted_at IS NULL
                                GROUP BY ticket_id) latest'), function ($join) {
          $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
            ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
        })
        ->whereBetween('tickets_chronologies.sla_duedate', [$yesterdayStart, $yesterdayEnd])
        ->whereNotNull('tickets_chronologies.escalate_to')
        ->whereNull('tickets_chronologies.deleted_at')
        ->with([
          'ticket',
          'ticket.user',
          'ticket.status',
          'escalatedTo'
        ])
        ->get();

      Log::info("Found {$chronologies->count()} chronologies that became overdue yesterday");

      $results = [
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => []
      ];

      foreach ($chronologies as $chronology) {
        $ticket = $chronology->ticket;

        // Skip if ticket doesn't exist
        if (!$ticket) {
          $results['skipped']++;
          Log::info("Skipped chronology {$chronology->id} - Ticket not found");
          continue;
        }

        // Skip if ticket is closed or cancelled
        if (in_array($ticket->tick_status, [5, 7])) {
          $results['skipped']++;
          Log::info("Skipped ticket {$ticket->ticket_nolog} - Status: {$ticket->tick_status}");
          continue;
        }

        // Skip if no assigned officer
        if (!$chronology->escalate_to || !$chronology->escalatedTo) {
          $results['skipped']++;
          Log::info("Skipped ticket {$ticket->ticket_nolog} - No assigned officer");
          continue;
        }

        try {
          $officer = $chronology->escalatedTo;

          // Skip if no email
          if (empty($officer->email)) {
            $results['skipped']++;
            Log::warning("Skipped ticket {$ticket->ticket_nolog} - Officer has no email");
            continue;
          }

          // Send notification
          $officer->notify(new SLAOverdueNotification($ticket, $chronology));

          $results['sent']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'officer' => $officer->nama,
            'email' => $officer->email,
            'status' => 'sent',
            'sla_duedate' => $chronology->sla_duedate
          ];

          Log::info("✓ Overdue notification sent to {$officer->email} for ticket {$ticket->ticket_nolog}");
        } catch (\Exception $e) {
          $results['failed']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'error' => $e->getMessage(),
            'status' => 'failed'
          ];

          Log::error("✗ Failed to send overdue notification for ticket {$ticket->ticket_nolog}: {$e->getMessage()}");
        }
      }

      Log::info("=== Overdue Summary: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped ===");

      return $results;
    } catch (\Exception $e) {
      Log::error("Error in sendAfterDueReminders: {$e->getMessage()}");
      throw $e;
    }
  }
}
