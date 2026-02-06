<?php

/*
 * Function TicketReminderService.php:
 *  - sla_duedate - 1 working day (REMINDER)
 *  - Handle SLA reminder notifications.
 *  - Find tickets 1 working day BEFORE their SLA due dates for Pegawai Tugasan (Level 1 or 2).
 *  - REMINDER: 1 working day BEFORE sla_duedate (table: tickets_chronologies)
 *  - Prepare notification content and recipients.
 *  - Log notification status and errors.
 *  - Scheduled to run at 7:30 AM daily
 */

namespace App\Services;

use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\ReminderNotification;
use Carbon\Carbon;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Log;

class TicketReminderService
{
  /**
   * Send reminders for tickets with SLA due 1 working day from today
   * Logic: Find tickets where sla_duedate = today + 1 working day
   * This notification will be sent at 7:30 AM daily (via scheduler)
   *
   * @return array Statistics of processed tickets
   */
  public function sendBeforeDueReminders()
  {
    // Calculate tomorrow in working days (skip weekends & holidays)
    $tomorrow = Helpers::addWorkingDays(Carbon::today(), 1);
    $tomorrowStart = $tomorrow->startOfDay();
    $tomorrowEnd = $tomorrow->copy()->endOfDay();

    $stats = [
      'type' => 'Reminder (1 Day Before Due)',
      'total_found' => 0,
      'sent' => 0,
      'failed' => 0,
      'skipped' => 0,
      'errors' => []
    ];

    try {
      // Get chronologies with SLA due dates in the specified range
      $chronologies = TicketChronology::whereNotNull('sla_duedate')
        ->whereBetween('sla_duedate', [$tomorrowStart, $tomorrowEnd])
        ->whereHas('ticket', function ($query) {
          // Only active tickets (not resolved/closed)
          // Status 5 = Selesai, 6 = Tutup
          $query->whereNotIn('tick_status', [5, 6]);
        })
        ->with(['ticket.status', 'ticket.user', 'escalatedTo', 'ticket.category', 'ticket.subCategory'])
        ->get();

      $stats['total_found'] = $chronologies->count();

      Log::info("SLA Reminder: Found {$stats['total_found']} tickets due 1 working day from now", [
        'start_date' => $tomorrowStart->format('Y-m-d H:i:s'),
        'end_date' => $tomorrowEnd->format('Y-m-d H:i:s'),
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
            $recipient->notify(new ReminderNotification($ticket));

            Log::info("SLA Reminder sent successfully", [
              'ticket_nolog' => $ticket->ticket_nolog,
              'recipient' => $recipient->email,
              'sla_duedate' => $chronology->sla_duedate->format('Y-m-d H:i:s'),
              'level' => $chronology->level_pegtugas_id
            ]);

            $stats['sent']++;
          } catch (\Exception $e) {
            Log::error("Failed to send SLA reminder notification", [
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
          Log::error("Error processing chronology for SLA reminder", [
            'chronology_id' => $chronology->id,
            'error' => $e->getMessage()
          ]);
        }
      }
    } catch (\Exception $e) {
      Log::error("Critical error in SLA reminder service", [
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
   * Get all tickets with upcoming SLA due dates (for dashboard/reporting)
   *
   * @param int $daysAhead Number of days to look ahead
   * @return \Illuminate\Support\Collection
   */
  public function getUpcomingSLATickets(int $daysAhead = 7)
  {
    $startDate = Carbon::now();
    $endDate = Carbon::now()->addDays($daysAhead);

    return TicketChronology::whereNotNull('sla_duedate')
      ->whereBetween('sla_duedate', [$startDate, $endDate])
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'ticket.user', 'ticket.category', 'ticket.subCategory', 'escalatedTo'])
      ->get()
      ->map(function ($chronology) {
        $daysUntilDue = Carbon::now()->diffInDays($chronology->sla_duedate, false);

        return [
          'ticket' => $chronology->ticket,
          'sla_duedate' => $chronology->sla_duedate,
          'days_until_due' => $daysUntilDue,
          'level_pegtugas' => $chronology->level_pegtugas_id,
          'assigned_to' => $chronology->escalatedTo?->nama ?? 'N/A',
          'sla_severity' => $chronology->sla_severity,
        ];
      });
  }
}
