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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
      $assignedOfficerIds = TicketChronology::query()
        ->where('ticket_id', $ticket->id)
        ->where('level_pegtugas_id', 1)
        ->where('tick_status', 1)
        ->whereNotNull('escalate_to')
        ->pluck('escalate_to')
        ->unique()
        ->values();

      if ($assignedOfficerIds->isEmpty()) {
        Log::info('No Level 1 officer assigned to ticket yet', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog
        ]);
        return false;
      }

      $assignedOfficers = User::query()
        ->whereIn('id', $assignedOfficerIds->all(), 'and', false)
        ->whereNotNull('email')
        ->get(['id', 'nama', 'email', 'nama_gred']);

      if ($assignedOfficers->isEmpty()) {
        Log::warning('Assigned Level 1 officers have no valid email addresses', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'officer_ids' => $assignedOfficerIds->all(),
          'level_pegtugas_id' => 1
        ]);
        return false;
      }

      $assignedOfficers = $this->sortOfficersByGrade($assignedOfficers);

      return $this->sendGroupedOfficerNotification(
        $ticket,
        $assignedOfficers,
        null,
        1,
        $changedBy,
        'Tiket baru telah ditugaskan kepada anda. Sila ambil tindakan yang sewajarnya.',
        1,
        'TICKET_ASSIGNED'
      );
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

      // Send to ticket owner (Pengadu) for status 4 (Dirujuk), 5 (Dibatal), and 6 (Selesai).
      // Status 1 (Aduan Baru) is handled separately when the ticket is created.
      $ownerNotified = false;
      if ($ticketOwner && $ticketOwner->email && in_array($newStatus, [4, 5, 6])) {
        $notificationType = in_array($newStatus, [5, 6]) ? 'TICKET_CLOSED' : 'STATUS_CHANGE';

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

        Log::info('Status change notification sent to owner', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'old_status' => $oldStatus,
          'new_status' => $newStatus,
          'recipient' => $ticketOwner->email
        ]);

        $ownerNotified = true;
      } elseif ($ticketOwner && in_array($newStatus, [4, 5, 6])) {
        Log::warning('Ticket owner has no email for status change notification', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'new_status' => $newStatus
        ]);
      }

      // Send to assigned officer (Pegawai Tugasan) for certain status changes
      // Status: 3 (Serahan Kepada), 4 (Dirujuk) -> Officer Assignment (TICKET_ASSIGNED)
      // Status: 5 (Aduan Dibatal), 6 (Selesai) -> Ticket Closure (TICKET_CLOSED)
      $officerNotified = false;
      $notificationType = 'STATUS_CHANGE';
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
      $submitterLevel = $this->resolveOfficerLevel($ticket, $changedBy);

      // If Level 2 performs referral/cancellation/completion,
      // notify all assigned Level 1 officers as a grouped notice.
      if ($submitterLevel === 2 && in_array($newStatus, [4, 5, 6], true)) {
        $levelOneOfficers = $this->getAssignedOfficersByLevel($ticket, 1);

        if ($levelOneOfficers->isEmpty()) {
          Log::info('No Level 1 officers found for grouped notification from Level 2 action', [
            'ticket_id' => $ticket->id,
            'submitted_by' => $changedBy->id,
            'submitter_name' => $changedBy->nama,
            'submitter_level' => $submitterLevel,
            'new_status' => $newStatus
          ]);
          return false;
        }

        return $this->sendGroupedOfficerNotification(
          $ticket,
          $levelOneOfficers,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes,
          1,
          $notificationType
        );
      }

      // For Status 5 (Aduan Dibatal) and 6 (Aduan Selesai),
      // notify only the officer who performed the final action for non-Level-2 flows.
      if (in_array($newStatus, [5, 6], true)) {
        $actionType = ($newStatus == 5) ? 'cancellation' : 'completion';
        Log::info("Determining {$actionType} notification recipient", [
          'ticket_id' => $ticket->id,
          'submitted_by' => $changedBy->id,
          'submitter_name' => $changedBy->nama,
          'submitter_level' => $submitterLevel,
          'recipient_id' => $changedBy->id,
          'new_status' => $newStatus
        ]);

        if (!$changedBy->email) {
          Log::warning("Final {$actionType} notification skipped because actioning officer has no email", [
            'ticket_id' => $ticket->id,
            'ticket_nolog' => $ticket->ticket_nolog,
            'new_status' => $newStatus,
            'submitted_by' => $changedBy->id
          ]);
          return false;
        }

        return $this->sendGroupedOfficerNotification(
          $ticket,
          collect([$changedBy]),
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes,
          $submitterLevel,
          $notificationType
        );
      }

      // For other statuses, notify ALL assigned officers for this status
      $chronologies = TicketChronology::query()
        ->where('ticket_id', $ticket->id)
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

      // Status 3 (Serahan Kepada) & 4 (Dirujuk): satu e-mel berkumpulan kepada SEMUA
      // escalate_to bagi status tersebut — bukan satu e-mel per level_pegtugas_id.
      if (in_array($newStatus, [3, 4], true)) {
        $officerIds = $chronologies
          ->pluck('escalate_to')
          ->filter()
          ->map(fn ($id) => (int) $id)
          ->unique()
          ->values();

        if ($officerIds->isEmpty()) {
          return false;
        }

        $assignedOfficers = User::query()
          ->whereIn('id', $officerIds->all(), 'and', false)
          ->whereNotNull('email')
          ->get(['id', 'nama', 'email', 'nama_gred']);

        if ($assignedOfficers->isEmpty()) {
          Log::warning('Assigned officers have no valid email for grouped status change', [
            'ticket_id' => $ticket->id,
            'ticket_nolog' => $ticket->ticket_nolog,
            'new_status' => $newStatus,
            'officer_ids' => $officerIds->all()
          ]);

          return false;
        }

        $assignedOfficers = $this->sortOfficersByGrade($assignedOfficers);

        $levelForNotification = (int) $chronologies->max(
          fn ($c) => (int) ($c->level_pegtugas_id ?? 1)
        );

        Log::channel('mail')->info('[MAIL][STATUS_CHANGE] Single grouped email for all assignees at status', [
          'ticket' => $ticket->ticket_nolog,
          'new_status' => $newStatus,
          'officer_ids' => $officerIds->all(),
          'level_passed_to_notification' => $levelForNotification,
        ]);

        return $this->sendGroupedOfficerNotification(
          $ticket,
          $assignedOfficers,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes,
          $levelForNotification,
          $notificationType
        );
      }

      $notificationSent = false;
      foreach ($chronologies->groupBy(fn($chronology) => (int) ($chronology->level_pegtugas_id ?? 1)) as $level => $levelChronologies) {
        $officerIds = $levelChronologies
          ->pluck('escalate_to')
          ->filter()
          ->unique()
          ->values();

        if ($officerIds->isEmpty()) {
          continue;
        }

        $assignedOfficers = User::query()
          ->whereIn('id', $officerIds->all(), 'and', false)
          ->whereNotNull('email')
          ->get(['id', 'nama', 'email', 'nama_gred']);

        if ($assignedOfficers->isEmpty()) {
          Log::warning('Assigned officers have no valid email for grouped status change', [
            'ticket_id' => $ticket->id,
            'ticket_nolog' => $ticket->ticket_nolog,
            'new_status' => $newStatus,
            'level' => (int) $level,
            'officer_ids' => $officerIds->all()
          ]);
          continue;
        }

        $assignedOfficers = $this->sortOfficersByGrade($assignedOfficers);

        $notificationSent = $this->sendGroupedOfficerNotification(
          $ticket,
          $assignedOfficers,
          $oldStatus,
          $newStatus,
          $changedBy,
          $notes,
          (int) $level,
          $notificationType
        ) || $notificationSent;
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

  private function resolveOfficerLevel(Ticket $ticket, User $changedBy): int
  {
    $submitterLevel = \Illuminate\Support\Facades\DB::table('officers')
      ->where('user_id', $changedBy->id)
      ->where('cat_id', $ticket->cat_id)
      ->where('subcat_id', $ticket->subcat_id)
      ->whereNull('deleted_at')
      ->value('level_tugasan');

    if (!$submitterLevel) {
      $submitterLevel = TicketChronology::query()
        ->where('ticket_id', $ticket->id)
        ->whereNotNull('escalate_to')
        ->where('escalate_to', $changedBy->id)
        ->value('level_pegtugas_id');
    }

    return (int) ($submitterLevel ?? 2);
  }

  private function getAssignedOfficersByLevel(Ticket $ticket, int $level): Collection
  {
    $officerIds = TicketChronology::query()
      ->where('ticket_id', $ticket->id)
      ->whereNotNull('escalate_to')
      ->where('level_pegtugas_id', $level)
      ->pluck('escalate_to')
      ->filter()
      ->unique()
      ->values();

    if ($officerIds->isEmpty()) {
      return collect();
    }

    $officers = User::query()
      ->whereIn('id', $officerIds->all(), 'and', false)
      ->whereNotNull('email')
      ->get(['id', 'nama', 'email', 'nama_gred']);

    return $this->sortOfficersByGrade($officers);
  }

  /**
   * Send one grouped email notification to multiple officers.
   */
  private function sendGroupedOfficerNotification(
    Ticket $ticket,
    Collection $assignedOfficers,
    ?int $oldStatus,
    int $newStatus,
    User $changedBy,
    ?string $notes,
    int $level,
    string $notificationType
  ): bool {
    $recipientMap = $assignedOfficers
      ->filter(fn($officer) => filled($officer->email ?? null))
      ->unique('email')
      ->mapWithKeys(function ($officer) {
        $email = trim((string) $officer->email);
        $name = trim((string) ($officer->nama ?? ''));

        return [$email => $name !== '' ? $name : $email];
      })
      ->all();

    if (empty($recipientMap)) {
      Log::warning('Grouped officer notification skipped because no valid recipient email exists', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'new_status' => $newStatus,
        'level' => $level
      ]);
      return false;
    }

    $recipientEmails = array_keys($recipientMap);
    $recipientNames = array_values($recipientMap);

    Log::channel('mail')->info("[MAIL][{$notificationType}][SENDING] Preparing grouped email to assigned officers", [
      'ticket' => $ticket->ticket_nolog,
      'recipient_emails' => array_map(fn($email) => $this->maskEmail($email), $recipientEmails),
      'recipient_names' => $recipientNames,
      'recipient_count' => count($recipientEmails),
      'old_status' => $oldStatus,
      'new_status' => $newStatus,
      'level' => $level
    ]);

    Notification::route('mail', $recipientMap)
      ->notify(new PegawaiTugasanStatusChangedNotification(
        $ticket,
        $oldStatus ?? 0,
        $newStatus,
        $changedBy,
        $notes,
        $level,
        $assignedOfficers
      ));

    Log::channel('mail')->info("[MAIL][{$notificationType}][SUCCESS] Grouped email sent to assigned officers", [
      'ticket' => $ticket->ticket_nolog,
      'recipient_emails' => array_map(fn($email) => $this->maskEmail($email), $recipientEmails),
      'recipient_names' => $recipientNames,
      'recipient_count' => count($recipientEmails),
      'old_status' => $oldStatus,
      'new_status' => $newStatus,
      'level' => $level
    ]);

    Log::info('Grouped status change notification sent to officers', [
      'ticket_id' => $ticket->id,
      'ticket_nolog' => $ticket->ticket_nolog,
      'officer_ids' => $assignedOfficers->pluck('id')->values()->all(),
      'officer_emails' => $recipientEmails,
      'officer_names' => $recipientNames,
      'old_status' => $oldStatus,
      'new_status' => $newStatus,
      'level' => $level,
      'notification_type' => $notificationType,
      'recipient_count' => count($recipientEmails)
    ]);

    return true;
  }

  /**
   * Sort officers collection by nama_gred DESC then id ASC.
   */
  private function sortOfficersByGrade(Collection $officers): Collection
  {
    if ($officers->count() <= 1) {
      return $officers;
    }

    return $officers
      ->sort(function ($a, $b) {
        $gradeA = (int) ($a->nama_gred ?? 0);
        $gradeB = (int) ($b->nama_gred ?? 0);
        if ($gradeA !== $gradeB) {
          return $gradeB - $gradeA;
        }
        return $a->id - $b->id;
      })
      ->values();
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
