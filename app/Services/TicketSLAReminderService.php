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
use App\Notifications\Ticket\AfterDueNotification;
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
    Log::channel('sla')->info('[SLA][REMINDER][START] Starting reminder notifications process', [
      'process' => 'reminder',
      'type' => 'before_due',
      'working_days_offset' => 1,
      'timestamp' => now()->toDateTimeString()
    ]);

    try {
      // Calculate target date (tomorrow in working days)
      $tomorrow = Helpers::addWorkingDays(Carbon::today(), 1);
      $tomorrowStart = $tomorrow->startOfDay();
      $tomorrowEnd = $tomorrow->copy()->endOfDay();

      Log::channel('sla')->info('[SLA][REMINDER][QUERY] Target SLA due date calculated', [
        'target_date' => $tomorrowStart->format('Y-m-d'),
        'date_range_start' => $tomorrowStart->format('Y-m-d H:i:s'),
        'date_range_end' => $tomorrowEnd->format('Y-m-d H:i:s')
      ]);

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

      Log::channel('sla')->info('[SLA][REMINDER][FOUND] Chronologies retrieved', [
        'count' => $chronologies->count(),
        'target_date' => $tomorrowStart->format('Y-m-d')
      ]);

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
            Log::channel('sla')->warning('[SLA][REMINDER][SKIPPED] Officer has no email', [
              'ticket' => $ticket->ticket_nolog,
              'officer_id' => $officer->id,
              'reason' => 'no_email_address'
            ]);
            continue;
          }

          // Send notification
          Log::channel('mail')->info('[MAIL][SLA_REMINDER][SENDING] Preparing reminder email', [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($officer->email),
            'recipient_name' => $officer->nama,
            'ticket_status' => $ticket->status->st_name ?? 'N/A',
            'sla_duedate' => $chronology->sla_duedate->format('Y-m-d'),
            'notification_type' => 'reminder'
          ]);

          $officer->notify(new ReminderNotification($ticket));

          $results['sent']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'officer' => $officer->nama,
            'email' => $officer->email,
            'status' => 'sent',
            'sla_duedate' => $chronology->sla_duedate
          ];

          Log::channel('sla')->info('[SLA][REMINDER][SENT] Notification sent successfully', [
            'ticket' => $ticket->ticket_nolog,
            'officer_id' => $officer->id,
            'recipient_name' => $officer->nama,
            'recipient_email' => $officer->email,
            'ticket_status' => $ticket->status->st_name ?? 'N/A',
            'sla_duedate' => $chronology->sla_duedate->format('Y-m-d')
          ]);

          Log::channel('mail')->info('[MAIL][SLA_REMINDER][SUCCESS] Email sent successfully', [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($officer->email),
            'recipient_name' => $officer->nama,
            'ticket_status' => $ticket->status->st_name ?? 'N/A'
          ]);
        } catch (\Exception $e) {
          $results['failed']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'error' => $e->getMessage(),
            'status' => 'failed'
          ];

          Log::channel('sla')->error('[SLA][REMINDER][FAILED] Failed to send notification', [
            'ticket' => $ticket->ticket_nolog,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
          ]);

          Log::channel('mail')->error('[MAIL][SLA_REMINDER][FAILED] Email sending failed', [
            'ticket' => $ticket->ticket_nolog,
            'error_message' => $e->getMessage()
          ]);
        }
      }

      Log::channel('sla')->info('[SLA][REMINDER][COMPLETE] Process completed', [
        'sent' => $results['sent'],
        'failed' => $results['failed'],
        'skipped' => $results['skipped'],
        'total_processed' => $chronologies->count()
      ]);

      return $results;
    } catch (\Exception $e) {
      Log::channel('sla')->critical('[SLA][REMINDER][ERROR] Critical error in reminder process', [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'stack_trace' => $e->getTraceAsString()
      ]);
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
    Log::channel('sla')->info('[SLA][OVERDUE][START] Starting overdue notifications process', [
      'process' => 'overdue',
      'type' => 'after_due',
      'working_days_offset' => -1,
      'timestamp' => now()->toDateTimeString()
    ]);

    try {
      // Calculate target date (yesterday in working days)
      $yesterday = Helpers::subtractWorkingDays(Carbon::today(), 1);
      $yesterdayStart = $yesterday->startOfDay();
      $yesterdayEnd = $yesterday->copy()->endOfDay();

      Log::channel('sla')->info('[SLA][OVERDUE][QUERY] Target SLA due date calculated', [
        'target_date' => $yesterdayStart->format('Y-m-d'),
        'date_range_start' => $yesterdayStart->format('Y-m-d H:i:s'),
        'date_range_end' => $yesterdayEnd->format('Y-m-d H:i:s')
      ]);

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

      Log::channel('sla')->info('[SLA][OVERDUE][FOUND] Chronologies retrieved', [
        'count' => $chronologies->count(),
        'target_date' => $yesterdayStart->format('Y-m-d')
      ]);

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
            Log::channel('sla')->warning('[SLA][OVERDUE][SKIPPED] Officer has no email', [
              'ticket' => $ticket->ticket_nolog,
              'officer_id' => $officer->id,
              'reason' => 'no_email_address'
            ]);
            continue;
          }

          // Send notification
          Log::channel('mail')->info('[MAIL][SLA_OVERDUE][SENDING] Preparing overdue email', [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($officer->email),
            'recipient_name' => $officer->nama,
            'ticket_status' => $ticket->status->st_name ?? 'N/A',
            'sla_duedate' => $chronology->sla_duedate->format('Y-m-d'),
            'notification_type' => 'overdue'
          ]);

          $officer->notify(new AfterDueNotification($ticket));

          $results['sent']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'officer' => $officer->nama,
            'email' => $officer->email,
            'status' => 'sent',
            'sla_duedate' => $chronology->sla_duedate
          ];

          Log::channel('sla')->info('[SLA][OVERDUE][SENT] Notification sent successfully', [
            'ticket' => $ticket->ticket_nolog,
            'officer_id' => $officer->id,
            'recipient_name' => $officer->nama,
            'recipient_email' => $officer->email,
            'ticket_status' => $ticket->status->st_name ?? 'N/A',
            'sla_duedate' => $chronology->sla_duedate->format('Y-m-d')
          ]);

          Log::channel('mail')->info('[MAIL][SLA_OVERDUE][SUCCESS] Email sent successfully', [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($officer->email),
            'recipient_name' => $officer->nama,
            'ticket_status' => $ticket->status->st_name ?? 'N/A'
          ]);
        } catch (\Exception $e) {
          $results['failed']++;
          $results['details'][] = [
            'ticket' => $ticket->ticket_nolog,
            'error' => $e->getMessage(),
            'status' => 'failed'
          ];

          Log::channel('sla')->error('[SLA][OVERDUE][FAILED] Failed to send notification', [
            'ticket' => $ticket->ticket_nolog,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
          ]);

          Log::channel('mail')->error('[MAIL][SLA_OVERDUE][FAILED] Email sending failed', [
            'ticket' => $ticket->ticket_nolog,
            'error_message' => $e->getMessage()
          ]);
        }
      }

      Log::channel('sla')->info('[SLA][OVERDUE][COMPLETE] Process completed', [
        'sent' => $results['sent'],
        'failed' => $results['failed'],
        'skipped' => $results['skipped'],
        'total_processed' => $chronologies->count()
      ]);

      return $results;
    } catch (\Exception $e) {
      Log::channel('sla')->critical('[SLA][OVERDUE][ERROR] Critical error in overdue process', [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'stack_trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Mask email address for privacy in logs
   * Example: john.doe@example.com -> j***e@example.com
   *
   * @param string $email
   * @return string
   */
  private function maskEmail(string $email): string
  {
    if (empty($email) || !str_contains($email, '@')) {
      return '***';
    }

    [$local, $domain] = explode('@', $email, 2);
    $localLen = strlen($local);

    if ($localLen <= 2) {
      return substr($local, 0, 1) . '***@' . $domain;
    }

    return substr($local, 0, 1) . '***' . substr($local, -1) . '@' . $domain;
  }
}
