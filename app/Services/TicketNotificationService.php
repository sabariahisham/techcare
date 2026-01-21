<?php

/*
  * Function TicketNotificationService.php:
  *  - Handle ticket-related email notifications.
  *  - Send notifications for new tickets and status changes.
  *  - Manage notification content and recipients.
  */

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\PengaduStatusChangedNotification;
use App\Notifications\Ticket\PegawaiTugasanStatusChangedNotification;
use Illuminate\Support\Facades\Log;

class TicketNotificationService
{
  /**
   * Send notifications for newly created ticket
   * Sends to both ticket owner and assigned officer
   *
   * @param Ticket $ticket
   * @param User $changedBy
   * @param string|null $notes
   * @return array Returns status of notifications sent
   */
  public function sendNewTicketNotifications(Ticket $ticket, User $changedBy, ?string $notes = null): array
  {
    $results = [
      'owner' => false,
      'officer' => false,
      'errors' => []
    ];

    try {
      // Refresh ticket to get relationships
      $ticket->refresh();
      $ticket->load('user', 'category', 'subCategory');

      // Send to ticket owner
      $results['owner'] = $this->sendToOwner($ticket, $changedBy, $notes);

      // Add delay to prevent rate limiting (especially for free mail services)
      // Mailtrap free plan: max 2 emails per second
      if ($results['owner']) {
        sleep(2); // Wait 2 seconds between emails
      }

      // Send to assigned officer
      $results['officer'] = $this->sendToAssignedOfficer($ticket, $changedBy);
    } catch (\Exception $e) {
      Log::error('Failed to send new ticket notifications', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      $results['errors'][] = $e->getMessage();
    }

    return $results;
  }

  /**
   * Send notification to ticket owner (Pengadu)
   *
   * @param Ticket $ticket
   * @param User $changedBy
   * @param string|null $notes
   * @return bool
   */
  private function sendToOwner(Ticket $ticket, User $changedBy, ?string $notes = null): bool
  {
    try {
      $ticketOwner = $ticket->user;

      if (!$ticketOwner || !$ticketOwner->email) {
        Log::warning('Ticket owner has no email address', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'user_id' => $ticket->user_id
        ]);
        return false;
      }

      $notificationMessage = $notes ?? 'Tiket anda telah berjaya dicipta dan akan diproses dalam masa terdekat.';

      $ticketOwner->notify(new PengaduStatusChangedNotification(
        $ticket,
        null, // No old status (new ticket)
        1, // Status "Aduan Baru"
        $changedBy,
        $notificationMessage
      ));

      Log::info('New ticket notification sent to owner', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'recipient' => $ticketOwner->email,
        'recipient_name' => $ticketOwner->nama
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send notification to ticket owner', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Send notification to assigned officer (Pegawai Tugasan Level 1)
   *
   * @param Ticket $ticket
   * @param User $changedBy
   * @return bool
   */
  private function sendToAssignedOfficer(Ticket $ticket, User $changedBy): bool
  {
    try {
      // Get chronology with assigned officer at Level 1
      $chronology = TicketChronology::where('ticket_id', $ticket->id)
        ->where('level_pegtugas_id', 1)
        ->whereNotNull('escalate_to')
        ->first();

      if (!$chronology || !$chronology->escalate_to) {
        Log::info('No Level 1 officer assigned to ticket yet', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog
        ]);
        return false;
      }

      $assignedOfficer = User::find($chronology->escalate_to);

      if (!$assignedOfficer || !$assignedOfficer->email) {
        Log::warning('Assigned Level 1 officer has no email address', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'officer_id' => $chronology->escalate_to,
          'level_pegtugas_id' => $chronology->level_pegtugas_id
        ]);
        return false;
      }

      $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
        $ticket,
        null,
        1,
        $changedBy,
        'Tiket baru telah ditugaskan kepada anda. Sila ambil tindakan yang sewajarnya.',
        1 // Level 1
      ));

      Log::info('New ticket notification sent to assigned Level 1 officer', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'officer_id' => $assignedOfficer->id,
        'officer_email' => $assignedOfficer->email,
        'officer_name' => $assignedOfficer->nama,
        'level_pegtugas_id' => $chronology->level_pegtugas_id
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send notification to assigned Level 1 officer', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Send notification for ticket status change
   *
   * @param Ticket $ticket
   * @param int $oldStatus
   * @param int $newStatus
   * @param User $changedBy
   * @param string|null $notes
   * @return bool
   */
  public function sendStatusChangeNotification(
    Ticket $ticket,
    int $oldStatus,
    int $newStatus,
    User $changedBy,
    ?string $notes = null
  ): bool {
    try {
      $ticket->refresh();
      $ticket->load('user', 'category', 'subCategory');

      $ticketOwner = $ticket->user;

      if (!$ticketOwner || !$ticketOwner->email) {
        Log::warning('Ticket owner has no email for status change notification', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog
        ]);
        return false;
      }

      $ticketOwner->notify(new PengaduStatusChangedNotification(
        $ticket,
        $oldStatus,
        $newStatus,
        $changedBy,
        $notes
      ));

      Log::info('Status change notification sent', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'recipient' => $ticketOwner->email
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send status change notification', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }
}
