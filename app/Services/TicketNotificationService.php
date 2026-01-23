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
      $ticket->load('user', 'category', 'subCategory', 'chronologies.escalatedTo');

      Log::info('Starting to send new ticket notifications', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'queue_driver' => config('queue.default'),
        'mail_driver' => config('mail.default')
      ]);

      // Send to ticket owner
      $results['owner'] = $this->sendToOwner($ticket, $changedBy, $notes);

      // Send to assigned officer
      $results['officer'] = $this->sendToAssignedOfficer($ticket, $changedBy);

      Log::info('Finished sending new ticket notifications', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'owner_notified' => $results['owner'],
        'officer_notified' => $results['officer'],
        'queue_driver' => config('queue.default')
      ]);
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

      Log::info('Attempting to send notification to ticket owner', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'user_id' => $ticket->user_id,
        'owner_exists' => $ticketOwner ? 'yes' : 'no',
        'owner_email' => $ticketOwner->email ?? 'no-email',
        'owner_name' => $ticketOwner->nama ?? 'no-name'
      ]);

      if (!$ticketOwner || !$ticketOwner->email) {
        Log::warning('Ticket owner has no email address', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'user_id' => $ticket->user_id
        ]);
        return false;
      }

      $notificationMessage = $notes ?? 'Tiket anda telah berjaya dicipta dan akan diproses dalam masa terdekat.';

      Log::info('About to call notify() for Pengadu', [
        'ticket_id' => $ticket->id,
        'recipient' => $ticketOwner->email,
        'notification_class' => 'PengaduStatusChangedNotification',
        'status' => 1
      ]);

      $ticketOwner->notify(new PengaduStatusChangedNotification(
        $ticket,
        null, // No old status (new ticket)
        1, // Status "Aduan Baru"
        $changedBy,
        $notificationMessage
      ));

      Log::info('New ticket notification sent to owner SUCCESSFULLY', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'recipient' => $ticketOwner->email,
        'recipient_name' => $ticketOwner->nama,
        'mail_driver' => config('mail.default')
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send notification to ticket owner', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
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
      $ticket->load('user', 'category', 'subCategory', 'chronologies.escalatedTo');

      Log::info('Sending status change notification', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'old_status' => $oldStatus,
        'new_status' => $newStatus
      ]);

      $ticketOwner = $ticket->user;

      // Send to ticket owner (Pengadu)
      $ownerNotified = false;
      if ($ticketOwner && $ticketOwner->email) {
        $ticketOwner->notify(new PengaduStatusChangedNotification(
          $ticket,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes
        ));

        Log::info('Status change notification sent to owner', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'old_status' => $oldStatus,
          'new_status' => $newStatus,
          'recipient' => $ticketOwner->email
        ]);

        $ownerNotified = true;
      } else {
        Log::warning('Ticket owner has no email for status change notification', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog
        ]);
      }

      // Send to assigned officer (Pegawai Tugasan) for certain status changes
      // Status: 3 (Serahan Kepada), 4 (Dirujuk), 6 (Selesai)
      $officerNotified = false;
      if (in_array($newStatus, [3, 4, 6])) {
        $officerNotified = $this->sendStatusChangeToOfficer($ticket, $oldStatus, $newStatus, $changedBy, $notes);
      }

      Log::info('Status change notifications completed', [
        'ticket_id' => $ticket->id,
        'owner_notified' => $ownerNotified,
        'officer_notified' => $officerNotified
      ]);

      return $ownerNotified || $officerNotified;
    } catch (\Exception $e) {
      Log::error('Failed to send status change notification', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Send status change notification to assigned officer
   *
   * @param Ticket $ticket
   * @param int $oldStatus
   * @param int $newStatus
   * @param User $changedBy
   * @param string|null $notes
   * @return bool
   */
  private function sendStatusChangeToOfficer(
    Ticket $ticket,
    int $oldStatus,
    int $newStatus,
    User $changedBy,
    ?string $notes = null
  ): bool {
    try {
      // For Status 6 (Aduan Selesai), notify opposite level officers
      if ($newStatus == 6) {
        // First, determine the level of the person who submitted by checking officers table
        // Find the officer level for this user in the ticket's category and subcategory
        $submitterLevel = \Illuminate\Support\Facades\DB::table('officers')
          ->where('user_id', $changedBy->id)
          ->where('cat_id', $ticket->cat_id)
          ->where('subcat_id', $ticket->subcat_id)
          ->whereNull('deleted_at')
          ->value('level_tugasan');

        // If not found in officers table, try to get from latest chronology assignment
        if (!$submitterLevel) {
          $submitterLevel = TicketChronology::where('ticket_id', $ticket->id)
            ->whereNotNull('escalate_to')
            ->where('escalate_to', $changedBy->id)
            ->value('level_pegtugas_id');
        }

        // Default to level 2 if not found (assume Level 2 is completing)
        $submitterLevel = $submitterLevel ?? 2;

        // Notify opposite level: If Level 2 submitted, notify Level 1; if Level 1 submitted, notify Level 2
        $targetLevel = ($submitterLevel == 2) ? 1 : 2;

        Log::info('Determining completion notification recipients', [
          'ticket_id' => $ticket->id,
          'submitted_by' => $changedBy->id,
          'submitter_name' => $changedBy->nama,
          'submitter_level' => $submitterLevel,
          'target_level' => $targetLevel
        ]);

        // Get officers of target level (exclude the person who submitted)
        $targetOfficers = TicketChronology::where('ticket_id', $ticket->id)
          ->whereNotNull('escalate_to')
          ->where('level_pegtugas_id', $targetLevel)
          ->where('escalate_to', '!=', $changedBy->id)
          ->pluck('escalate_to')
          ->unique();

        if ($targetOfficers->isEmpty()) {
          Log::info('No Level ' . $targetLevel . ' officer assigned for completion notification', [
            'ticket_id' => $ticket->id,
            'new_status' => $newStatus,
            'submitted_by' => $changedBy->id,
            'target_level' => $targetLevel
          ]);
          return false;
        }

        $notificationSent = false;
        foreach ($targetOfficers as $officerId) {
          $assignedOfficer = User::find($officerId);

          if (!$assignedOfficer || !$assignedOfficer->email) {
            Log::warning('Level ' . $targetLevel . ' officer has no email for completion notification', [
              'ticket_id' => $ticket->id,
              'officer_id' => $officerId
            ]);
            continue;
          }

          $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
            $ticket,
            $oldStatus,
            $newStatus,
            $changedBy,
            $notes,
            $targetLevel
          ));

          Log::info('Completion notification sent to Level ' . $targetLevel . ' officer', [
            'ticket_id' => $ticket->id,
            'new_status' => $newStatus,
            'officer_id' => $assignedOfficer->id,
            'officer_email' => $assignedOfficer->email,
            'officer_name' => $assignedOfficer->nama,
            'level' => $targetLevel
          ]);

          $notificationSent = true;
        }

        return $notificationSent;
      }

      // For other statuses, notify latest assigned officer
      $latestChronology = TicketChronology::where('ticket_id', $ticket->id)
        ->whereNotNull('escalate_to')
        ->latest()
        ->first();

      if (!$latestChronology || !$latestChronology->escalate_to) {
        Log::info('No officer assigned for status change notification', [
          'ticket_id' => $ticket->id,
          'new_status' => $newStatus
        ]);
        return false;
      }

      $assignedOfficer = User::find($latestChronology->escalate_to);

      if (!$assignedOfficer || !$assignedOfficer->email) {
        Log::warning('Assigned officer has no email for status change', [
          'ticket_id' => $ticket->id,
          'officer_id' => $latestChronology->escalate_to
        ]);
        return false;
      }

      $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
        $ticket,
        $oldStatus,
        $newStatus,
        $changedBy,
        $notes,
        $latestChronology->level_pegtugas_id ?? 1
      ));

      Log::info('Status change notification sent to officer', [
        'ticket_id' => $ticket->id,
        'new_status' => $newStatus,
        'officer_id' => $assignedOfficer->id,
        'officer_email' => $assignedOfficer->email,
        'level' => $latestChronology->level_pegtugas_id
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send status change notification to officer', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }
}
