<?php

/*
 * Function TicketSLAReminderService.php:
 *  - Handle SLA reminder notifications (before due, due today, after due)
 *  - Menguruskan flow eskalasi Level 1 → Level 2
 *  - Prepare notification content and recipients based on chronology
 *  - Log notification status and errors
 */

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\SLAReminderNotification;
use App\Notifications\Ticket\SLAOverdueNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\Helpers;

class TicketSLAReminderService
{
  /**
   * Send reminders for tickets with SLA due 1 working day from today (SLA-1)
   * Logic: Find tickets where sla_duedate = today + 1 working day
   *
   * @return array Statistics of processed tickets
   */
  public function sendBeforeDueReminders()
  {
    // Calculate tomorrow in working days (skip weekends & holidays)
    $tomorrow = Helpers::addWorkingDays(Carbon::today(), 1);
    $tomorrowStart = $tomorrow->startOfDay();
    $tomorrowEnd = $tomorrow->copy()->endOfDay();

    return $this->sendReminders($tomorrowStart, $tomorrowEnd, 'before_due', 'SLA-1 (Due 1 Working Day)');
  }

  /**
   * Send reminders for tickets with SLA due today
   * Only send if today is a working day
   *
   * @return array Statistics of processed tickets
   */
  public function sendDueTodayReminders()
  {
    // Only send if today is a working day
    if (!Helpers::isWorkingDay(Carbon::today())) {
      return [
        'type' => 'Due Today',
        'total_found' => 0,
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => [],
        'message' => 'Skipped: Today is not a working day'
      ];
    }

    $today = Carbon::today()->startOfDay();
    $todayEnd = Carbon::today()->endOfDay();

    return $this->sendReminders($today, $todayEnd, 'due_today', 'Due Today');
  }

  /**
   * Send reminders for tickets that passed due date 1 working day ago (due date+1)
   * Logic: Find tickets where sla_duedate = today - 1 working day (start of working day)
   *
   * @return array Statistics of processed tickets
   */
  public function sendAfterDueReminders()
  {
    // Calculate yesterday in working days (skip weekends & holidays)
    $yesterday = Helpers::subtractWorkingDays(Carbon::today(), 1);
    $yesterdayStart = $yesterday->startOfDay();
    $yesterdayEnd = $yesterday->copy()->endOfDay();

    return $this->sendReminders($yesterdayStart, $yesterdayEnd, 'after_due', 'Due Date+1 Working Day');
  }

  /**
   * Core method to send reminders based on date range
   *
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @param string $reminderType
   * @param string $logLabel
   * @return array
   */
  protected function sendReminders(Carbon $startDate, Carbon $endDate, string $reminderType, string $logLabel)
  {
    $stats = [
      'type' => $logLabel,
      'total_found' => 0,
      'sent' => 0,
      'failed' => 0,
      'skipped' => 0,
      'errors' => []
    ];

    try {
      // Get chronologies with SLA due dates in the specified range
      $chronologies = TicketChronology::whereNotNull('sla_duedate')
        ->whereBetween('sla_duedate', [$startDate, $endDate])
        ->whereHas('ticket', function ($query) {
          // Only active tickets (not resolved/closed)
          // Status 5 = Selesai, 6 = Tutup
          $query->whereNotIn('tick_status', [5, 6]);
        })
        ->with(['ticket.status', 'ticket.user', 'escalatedTo', 'ticket.category', 'ticket.subCategory'])
        ->get();

      $stats['total_found'] = $chronologies->count();

      Log::info("SLA Reminder: Found {$stats['total_found']} tickets for {$logLabel}", [
        'start_date' => $startDate->format('Y-m-d H:i:s'),
        'end_date' => $endDate->format('Y-m-d H:i:s'),
      ]);

      foreach ($chronologies as $chronology) {
        try {
          $ticket = $chronology->ticket;

          if (!$ticket) {
            $stats['skipped']++;
            $stats['errors'][] = "Ticket not found for chronology ID: {$chronology->id}";
            continue;
          }

          // Get recipients (Pegawai Tugasan from this chronology)
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
            $recipient->notify(new SLAReminderNotification($ticket, $reminderType));

            Log::info("SLA Reminder sent: {$logLabel}", [
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
      Log::error("Critical error in SLA reminder service: {$logLabel}", [
        'error' => $e->getMessage()
      ]);
      $stats['errors'][] = "Critical error: {$e->getMessage()}";
    }

    return $stats;
  }

  /**
   * Send overdue notifications for tickets that have passed their SLA due date
   * by more than 1 working day (not including today's after_due reminder)
   *
   * @return array Statistics of processed tickets
   */
  public function sendOverdueNotifications()
  {
    $stats = [
      'type' => 'Overdue',
      'total_found' => 0,
      'sent' => 0,
      'failed' => 0,
      'skipped' => 0,
      'errors' => []
    ];

    try {
      $currentTime = Carbon::now();

      // Get date 1 working day ago (this is handled by after_due)
      // So overdue should be anything before that
      $oneDayAgo = Helpers::subtractWorkingDays(Carbon::today(), 1);
      $overdueThreshold = $oneDayAgo->copy()->subDay()->endOfDay(); // Before yesterday working day

      // Get chronologies with overdue SLA (past threshold)
      $chronologies = TicketChronology::whereNotNull('sla_duedate')
        ->where('sla_duedate', '<', $overdueThreshold)
        ->whereHas('ticket', function ($query) {
          // Only active tickets
          $query->whereNotIn('tick_status', [5, 6]);
        })
        ->with(['ticket.status', 'ticket.user', 'escalatedTo'])
        ->get();

      $stats['total_found'] = $chronologies->count();

      Log::info("SLA Overdue: Found {$stats['total_found']} overdue tickets", [
        'threshold_date' => $overdueThreshold->format('Y-m-d H:i:s')
      ]);

      foreach ($chronologies as $chronology) {
        try {
          $ticket = $chronology->ticket;

          if (!$ticket) {
            $stats['skipped']++;
            continue;
          }

          $recipient = $this->getRecipient($chronology);

          if (!$recipient || empty($recipient->email)) {
            $stats['skipped']++;
            continue;
          }

          // Send overdue notification
          $recipient->notify(new SLAOverdueNotification($ticket));

          Log::info("SLA Overdue notification sent", [
            'ticket_nolog' => $ticket->ticket_nolog,
            'recipient' => $recipient->email,
            'sla_duedate' => $chronology->sla_duedate->format('Y-m-d H:i:s'),
            'days_overdue' => $currentTime->diffInDays($chronology->sla_duedate)
          ]);

          $stats['sent']++;
        } catch (\Exception $e) {
          $stats['failed']++;
          $stats['errors'][] = $e->getMessage();
          Log::error("Error sending overdue notification: " . $e->getMessage());
        }
      }
    } catch (\Exception $e) {
      Log::error("Critical error in sendOverdueNotifications: " . $e->getMessage());
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
