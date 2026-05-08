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

      // Log to mail channel - SENDING
      Log::channel('mail')->info('[MAIL][TICKET_CREATED][SENDING] Preparing email to ticket owner', [
        'ticket' => $ticket->ticket_nolog,
        'recipient_email' => $this->maskEmail($ticketOwner->email),
        'recipient_name' => $ticketOwner->nama,
        'ticket_status' => 'Aduan Baru'
      ]);

      $ticketOwner->notify(new PengaduStatusChangedNotification(
        $ticket,
        null, // No old status (new ticket)
        1, // Status "Aduan Baru"
        $changedBy,
        $notificationMessage
      ));

      // Log to mail channel - SUCCESS
      Log::channel('mail')->info('[MAIL][TICKET_CREATED][SUCCESS] Email sent successfully to ticket owner', [
        'ticket' => $ticket->ticket_nolog,
        'recipient_email' => $this->maskEmail($ticketOwner->email),
        'recipient_name' => $ticketOwner->nama,
        'ticket_status' => 'Aduan Baru'
      ]);

      Log::info('New ticket notification sent to owner SUCCESSFULLY', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'recipient' => $ticketOwner->email,
        'recipient_name' => $ticketOwner->nama,
        'mail_driver' => config('mail.default')
      ]);

      return true;
    } catch (\Exception $e) {
      // Log to mail channel - FAILED
      Log::channel('mail')->error('[MAIL][TICKET_CREATED][FAILED] Email sending failed to ticket owner', [
        'ticket' => $ticket->ticket_nolog,
        'error_message' => $e->getMessage()
      ]);

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

      // Log to mail channel - SENDING
      Log::channel('mail')->info('[MAIL][TICKET_ASSIGNED][SENDING] Preparing email to assigned officer', [
        'ticket' => $ticket->ticket_nolog,
        'recipient_email' => $this->maskEmail($assignedOfficer->email),
        'recipient_name' => $assignedOfficer->nama,
        'level' => 1,
        'new_status' => 1, // Aduan Baru
        'tick_status' => 1
      ]);

      $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
        $ticket,
        null,
        1,
        $changedBy,
        'Tiket baru telah ditugaskan kepada anda. Sila ambil tindakan yang sewajarnya.',
        1 // Level 1
      ));

      // Log to mail channel - SUCCESS
      Log::channel('mail')->info('[MAIL][TICKET_ASSIGNED][SUCCESS] Email sent successfully to assigned officer', [
        'ticket' => $ticket->ticket_nolog,
        'recipient_email' => $this->maskEmail($assignedOfficer->email),
        'recipient_name' => $assignedOfficer->nama,
        'level' => 1,
        'new_status' => 1, // Aduan Baru
        'tick_status' => 1
      ]);

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
      // Log to mail channel - FAILED
      Log::channel('mail')->error('[MAIL][TICKET_ASSIGNED][FAILED] Email sending failed to assigned officer', [
        'ticket' => $ticket->ticket_nolog,
        'error_message' => $e->getMessage()
      ]);

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

      // Send to ticket owner (Pengadu) - ONLY for Status 5 (Dibatal) & 6 (Selesai)
      $ownerNotified = false;
      if ($ticketOwner && $ticketOwner->email && in_array($newStatus, [5, 6])) {
        $notificationType = 'TICKET_CLOSED';

        // Log to mail channel - SENDING
        Log::channel('mail')->info("[MAIL][{$notificationType}][SENDING] Preparing email to ticket owner", [
          'ticket' => $ticket->ticket_nolog,
          'recipient_email' => $this->maskEmail($ticketOwner->email),
          'recipient_name' => $ticketOwner->nama,
          'old_status' => $oldStatus,
          'new_status' => $newStatus
        ]);

        $ticketOwner->notify(new PengaduStatusChangedNotification(
          $ticket,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes
        ));

        // Log to mail channel - SUCCESS
        Log::channel('mail')->info("[MAIL][{$notificationType}][SUCCESS] Email sent to ticket owner", [
          'ticket' => $ticket->ticket_nolog,
          'recipient_email' => $this->maskEmail($ticketOwner->email),
          'recipient_name' => $ticketOwner->nama,
          'old_status' => $oldStatus,
          'new_status' => $newStatus
        ]);

        Log::info('Closure notification sent to owner', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'old_status' => $oldStatus,
          'new_status' => $newStatus,
          'recipient' => $ticketOwner->email
        ]);

        $ownerNotified = true;
      } elseif ($ticketOwner && in_array($newStatus, [5, 6])) {
        Log::warning('Ticket owner has no email for closure notification', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'new_status' => $newStatus
        ]);
      }

      // Send to assigned officer (Pegawai Tugasan) for certain status changes
      // Status: 3 (Serahan Kepada), 4 (Dirujuk) -> Officer Assignment (TICKET_ASSIGNED)
      // Status: 5 (Aduan Dibatal), 6 (Selesai) -> Ticket Closure (TICKET_CLOSED)
      $officerNotified = false;
      if (in_array($newStatus, [3, 4, 5, 6])) {
        // Determine notification type based on status
        if (in_array($newStatus, [5, 6])) {
          $notificationType = 'TICKET_CLOSED';
        } elseif (in_array($newStatus, [3, 4])) {
          $notificationType = 'TICKET_ASSIGNED';
        }
        $officerNotified = $this->sendStatusChangeToOfficer($ticket, $oldStatus, $newStatus, $changedBy, $notes, $notificationType);
      }

      Log::info('Status change notifications completed', [
        'ticket_id' => $ticket->id,
        'owner_notified' => $ownerNotified,
        'officer_notified' => $officerNotified
      ]);

      return $ownerNotified || $officerNotified;
    } catch (\Exception $e) {
      // Log to mail channel - FAILED
      Log::channel('mail')->error('[MAIL][STATUS_CHANGE][FAILED] Status change notification process failed', [
        'ticket' => $ticket->ticket_nolog ?? 'unknown',
        'error_message' => $e->getMessage()
      ]);

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
   * @param string $notificationType (TICKET_ASSIGNED, TICKET_CLOSED)
   * @return bool
   */
  private function sendStatusChangeToOfficer(
    Ticket $ticket,
    int $oldStatus,
    int $newStatus,
    User $changedBy,
    ?string $notes = null,
    string $notificationType = 'TICKET_ASSIGNED'
  ): bool {
    try {
      // For Status 5 (Aduan Dibatal) and 6 (Aduan Selesai), notify opposite level officers
      if (in_array($newStatus, [5, 6])) {
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

        $actionType = ($newStatus == 5) ? 'cancellation' : 'completion';
        Log::info("Determining {$actionType} notification recipients", [
          'ticket_id' => $ticket->id,
          'submitted_by' => $changedBy->id,
          'submitter_name' => $changedBy->nama,
          'submitter_level' => $submitterLevel,
          'target_level' => $targetLevel,
          'new_status' => $newStatus
        ]);

        // Get officers of target level (exclude the person who submitted)
        $targetOfficers = TicketChronology::where('ticket_id', $ticket->id)
          ->whereNotNull('escalate_to')
          ->where('level_pegtugas_id', $targetLevel)
          ->where('escalate_to', '!=', $changedBy->id)
          ->pluck('escalate_to')
          ->unique();

        if ($targetOfficers->isEmpty()) {
          $actionType = ($newStatus == 5) ? 'cancellation' : 'completion';
          Log::info("No Level {$targetLevel} officer assigned for {$actionType} notification", [
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

          // Log to mail channel - SENDING
          Log::channel('mail')->info("[MAIL][{$notificationType}][SENDING] Preparing email to Level {$targetLevel} officer", [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($assignedOfficer->email),
            'recipient_name' => $assignedOfficer->nama,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'level' => $targetLevel
          ]);

          $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
            $ticket,
            $oldStatus,
            $newStatus,
            $changedBy,
            $notes,
            $targetLevel
          ));

          // Log to mail channel - SUCCESS
          Log::channel('mail')->info("[MAIL][{$notificationType}][SUCCESS] Email sent to Level {$targetLevel} officer", [
            'ticket' => $ticket->ticket_nolog,
            'recipient_email' => $this->maskEmail($assignedOfficer->email),
            'recipient_name' => $assignedOfficer->nama,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'level' => $targetLevel
          ]);

          $actionType = ($newStatus == 5) ? 'Cancellation' : 'Completion';
          Log::info("{$actionType} notification sent to Level {$targetLevel} officer", [
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

      // For other statuses, notify ALL assigned officers for this status
      $chronologies = TicketChronology::where('ticket_id', $ticket->id)
        ->whereNotNull('escalate_to')
        ->where('tick_status', $newStatus)
        ->get();

      if ($chronologies->isEmpty()) {
        Log::info('No officers assigned for status change notification', [
          'ticket_id' => $ticket->id,
          'new_status' => $newStatus
        ]);
        return false;
      }

      $notificationSent = false;
      foreach ($chronologies as $chronology) {
        $assignedOfficer = User::find($chronology->escalate_to);
        if (!$assignedOfficer || !$assignedOfficer->email) {
          Log::warning('Assigned officer has no email for status change', [
            'ticket_id' => $ticket->id,
            'officer_id' => $chronology->escalate_to
          ]);
          continue;
        }

        // Log to mail channel - SENDING
        Log::channel('mail')->info("[MAIL][{$notificationType}][SENDING] Preparing email to assigned officer", [
          'ticket' => $ticket->ticket_nolog,
          'recipient_email' => $this->maskEmail($assignedOfficer->email),
          'recipient_name' => $assignedOfficer->nama,
          'old_status' => $oldStatus,
          'new_status' => $newStatus,
          'level' => $chronology->level_pegtugas_id ?? 1
        ]);

        $assignedOfficer->notify(new PegawaiTugasanStatusChangedNotification(
          $ticket,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes,
          $chronology->level_pegtugas_id ?? 1
        ));

        // Log to mail channel - SUCCESS
        Log::channel('mail')->info("[MAIL][{$notificationType}][SUCCESS] Email sent to assigned officer", [
          'ticket' => $ticket->ticket_nolog,
          'recipient_email' => $this->maskEmail($assignedOfficer->email),
          'recipient_name' => $assignedOfficer->nama,
          'old_status' => $oldStatus,
          'new_status' => $newStatus,
          'level' => $chronology->level_pegtugas_id ?? 1
        ]);

        Log::info('Status change notification sent to officer', [
          'ticket_id' => $ticket->id,
          'new_status' => $newStatus,
          'officer_id' => $assignedOfficer->id,
          'officer_email' => $assignedOfficer->email,
          'level' => $chronology->level_pegtugas_id
        ]);

        $notificationSent = true;
      }
      return $notificationSent;
    } catch (\Exception $e) {      // Log to mail channel - FAILED
      Log::channel('mail')->error('[MAIL][STATUS_CHANGE][FAILED] Status change email failed for ticket owner', [
        'ticket' => $ticket->ticket_nolog,
        'error_message' => $e->getMessage()
      ]);
      Log::error('Failed to send status change notification to officer', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Mask email address for privacy in logs
   * Example: john.doe@example.com => j***e@example.com
   *
   * @param string $email
   * @return string
   */
  private function maskEmail(string $email): string
  {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
      return $email; // Invalid email, return as is
    }

    $username = $parts[0];
    $domain = $parts[1];

    if (strlen($username) <= 2) {
      return $email; // Too short to mask meaningfully
    }

    $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . $username[strlen($username) - 1];
    return $maskedUsername . '@' . $domain;
  }
}
