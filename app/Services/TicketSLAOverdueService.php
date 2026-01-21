<?php

/*
  * Function TicketSLAOverdueService.php:
  *  - Handle OVERDUE ticket email notifications for Pegawai Tugasan (Level 1 or 2).
  *  - Prepare notification content and recipients.
  *  - Log notification status and errors.
  */

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\SLAOverdueNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketSLAOverdueService
{
  /**
   * Find and send notifications for overdue tickets
   *
   * @return array Statistics of processed tickets
   */
  public function sendOverdueNotifications()
  {
    $stats = [
      'total_overdue' => 0,
      'sent' => 0,
      'failed' => 0,
      'errors' => []
    ];

    try {
      // Get overdue tickets from tickets_chronologies table
      $overdueChronologies = TicketChronology::whereNotNull('sla_duedate')
        ->where('sla_duedate', '<', Carbon::now())
        ->whereHas('ticket', function ($query) {
          // Only active tickets (not closed/resolved)
          $query->whereNotIn('tick_status', [5, 6]); // Assuming 5=Selesai, 6=Tutup
        })
        ->with(['ticket.status', 'ticket.user', 'escalatedTo'])
        ->get();

      $stats['total_overdue'] = $overdueChronologies->count();

      Log::info("Found {$stats['total_overdue']} overdue tickets");

      foreach ($overdueChronologies as $chronology) {
        try {
          $ticket = $chronology->ticket;

          if (!$ticket) {
            $stats['failed']++;
            $stats['errors'][] = "Ticket not found for chronology ID: {$chronology->id}";
            continue;
          }

          // Determine who to notify
          $recipients = $this->getNotificationRecipients($chronology);

          if (empty($recipients)) {
            Log::warning("No recipients found for ticket: {$ticket->ticket_nolog}");
            $stats['failed']++;
            $stats['errors'][] = "No recipients for ticket: {$ticket->ticket_nolog}";
            continue;
          }

          // Send notification to each recipient
          foreach ($recipients as $recipient) {
            try {
              $recipient->notify(new SLAOverdueNotification($ticket));
              Log::info("Overdue notification sent for ticket {$ticket->ticket_nolog} to {$recipient->email}");
            } catch (\Exception $e) {
              Log::error("Failed to send notification to {$recipient->email}: " . $e->getMessage());
              $stats['errors'][] = "Failed to send to {$recipient->email}: {$e->getMessage()}";
            }
          }

          $stats['sent']++;
        } catch (\Exception $e) {
          $stats['failed']++;
          $stats['errors'][] = $e->getMessage();
          Log::error("Error processing chronology {$chronology->id}: " . $e->getMessage());
        }
      }
    } catch (\Exception $e) {
      Log::error("Critical error in sendOverdueNotifications: " . $e->getMessage());
      $stats['errors'][] = "Critical error: {$e->getMessage()}";
    }

    return $stats;
  }

  /**
   * Get recipients for overdue notifications
   *
   * @param TicketChronology $chronology
   * @return array
   */
  protected function getNotificationRecipients(TicketChronology $chronology)
  {
    $recipients = [];

    // Send to escalated user (Pegawai Tugasan)
    if ($chronology->escalate_to && $chronology->escalatedTo) {
      $recipients[] = $chronology->escalatedTo;
    }

    // Also notify the ticket creator if different
    if ($chronology->ticket->user && !in_array($chronology->ticket->user->id, array_map(fn($r) => $r->id, $recipients))) {
      // Optionally add ticket creator - uncomment if needed
      // $recipients[] = $chronology->ticket->user;
    }

    return $recipients;
  }

  /**
   * Get overdue tickets list (for display/reporting)
   *
   * @return \Illuminate\Support\Collection
   */
  public function getOverdueTickets()
  {
    return TicketChronology::whereNotNull('sla_duedate')
      ->where('sla_duedate', '<', Carbon::now())
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'ticket.user', 'escalatedTo'])
      ->get()
      ->map(function ($chronology) {
        return [
          'ticket' => $chronology->ticket,
          'sla_duedate' => $chronology->sla_duedate,
          'days_overdue' => Carbon::now()->diffInDays($chronology->sla_duedate),
          'assigned_to' => $chronology->escalatedTo?->nama ?? 'N/A',
        ];
      });
  }
}
