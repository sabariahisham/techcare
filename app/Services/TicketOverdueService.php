<?php

/*
  * Function TicketOverdueService.php:
  *  - sla_duedate + 1 working day (AFTER DUE)
  *  - Handle SLA AFTER DUE notifications (1 day past due).
  *  - Find tickets 1 working day AFTER their SLA due dates for Pegawai Tugasan (Level 1 or 2).
  *  - AFTER DUE: 1 working day AFTER sla_duedate (table: tickets_chronologies)
  *  - Prepare notification content and recipients.
  *  - Log notification status and errors.
  *  - Scheduled to run at 7:30 AM daily
  */

namespace App\Services;

use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\AfterDueNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\Helpers;

class TicketOverdueService
{
  /**
   * Send after due notifications for tickets that passed due date 1 working day ago
   * Logic: Find tickets where sla_duedate = today - 1 working day
   * This notification will be sent at 7:30 AM daily (via scheduler)
   *
   * @return array Statistics of processed tickets
   */
  public function sendAfterDueNotifications()
  {
    // Calculate yesterday in working days (skip weekends & holidays)
    $yesterday = Helpers::subtractWorkingDays(Carbon::today(), 1);
    $yesterdayStart = $yesterday->startOfDay();
    $yesterdayEnd = $yesterday->copy()->endOfDay();

    $stats = [
      'type' => 'After Due (1 Working Day Past Due)',
      'total_found' => 0,
      'sent' => 0,
      'failed' => 0,
      'skipped' => 0,
      'errors' => []
    ];

    try {
      // Get chronologies with SLA due dates that passed 1 working day ago
      $chronologies = TicketChronology::whereNotNull('sla_duedate')
        ->whereBetween('sla_duedate', [$yesterdayStart, $yesterdayEnd])
        ->whereHas('ticket', function ($query) {
          // Only active tickets (not resolved/closed)
          // Status 5 = Selesai, 6 = Tutup
          $query->whereNotIn('tick_status', [5, 6]);
        })
        ->with(['ticket.status', 'ticket.user', 'escalatedTo', 'ticket.category', 'ticket.subCategory'])
        ->get();

      $stats['total_found'] = $chronologies->count();

      Log::info("After Due Notification: Found {$stats['total_found']} tickets 1 working day past due", [
        'start_date' => $yesterdayStart->format('Y-m-d H:i:s'),
        'end_date' => $yesterdayEnd->format('Y-m-d H:i:s'),
      ]);

      foreach ($chronologies as $chronology) {
        try {
          $ticket = $chronology->ticket;

          if (!$ticket) {
            $stats['skipped']++;
            $stats['errors'][] = "Ticket not found for chronology ID: {$chronology->id}";
            continue;
          }

          // Get recipient (Pegawai Tugasan from this chronology)
          $recipient = $this->getRecipient($chronology);

          if (!$recipient) {
            Log::warning("No recipient found for ticket: {$ticket->ticket_nolog}", [
              'chronology_id' => $chronology->id,
              'escalate_to' => $chronology->escalate_to
            ]);
            $stats['skipped']++;
            $stats['errors'][] = "No recipient for ticket: {$ticket->ticket_nolog}";
            continue;
          }

          // Check if recipient has email
          if (empty($recipient->email)) {
            Log::warning("Recipient has no email for ticket: {$ticket->ticket_nolog}", [
              'recipient_id' => $recipient->id,
              'recipient_name' => $recipient->name
            ]);
            $stats['skipped']++;
            $stats['errors'][] = "No email for recipient: {$recipient->name}";
            continue;
          }

          // Send notification
          try {
            $recipient->notify(new AfterDueNotification($ticket));

            Log::info("After Due notification sent successfully", [
              'ticket_nolog' => $ticket->ticket_nolog,
              'recipient' => $recipient->email,
              'sla_duedate' => $chronology->sla_duedate->format('Y-m-d H:i:s'),
              'level' => $chronology->level_pegtugas_id
            ]);

            $stats['sent']++;
          } catch (\Exception $e) {
            Log::error("Failed to send After Due notification", [
              'ticket_nolog' => $ticket->ticket_nolog,
              'recipient' => $recipient->email,
              'error' => $e->getMessage()
            ]);
            $stats['failed']++;
            $stats['errors'][] = "Failed to send to {$recipient->email}: {$e->getMessage()}";
          }
        } catch (\Exception $e) {
          $stats['failed']++;
          $stats['errors'][] = "Error processing chronology {$chronology->id}: {$e->getMessage()}";
          Log::error("Error processing chronology for After Due notification", [
            'chronology_id' => $chronology->id,
            'error' => $e->getMessage()
          ]);
        }
      }
    } catch (\Exception $e) {
      Log::error("Critical error in After Due notification service", [
        'error' => $e->getMessage()
      ]);
      $stats['errors'][] = "Critical error: {$e->getMessage()}";
    }

    return $stats;
  }

  /**
   * Get recipient for notification (Pegawai Tugasan from chronology)
   *
   * @param TicketChronology $chronology
   * @return User|null
   */
  protected function getRecipient(TicketChronology $chronology)
  {
    // Send to the officer assigned in this chronology (escalate_to)
    if ($chronology->escalate_to && $chronology->escalatedTo) {
      return $chronology->escalatedTo;
    }

    return null;
  }

  /**
   * Get after due tickets list (for display/reporting)
   *
   * @return \Illuminate\Support\Collection
   */
  public function getAfterDueTickets()
  {
    $yesterday = Helpers::subtractWorkingDays(Carbon::today(), 1);

    return TicketChronology::whereNotNull('sla_duedate')
      ->whereDate('sla_duedate', '=', $yesterday)
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'ticket.user', 'escalatedTo'])
      ->get()
      ->map(function ($chronology) {
        return [
          'ticket' => $chronology->ticket,
          'sla_duedate' => $chronology->sla_duedate,
          'days_overdue' => 1,
          'assigned_to' => $chronology->escalatedTo?->nama ?? 'N/A',
        ];
      });
  }
}
