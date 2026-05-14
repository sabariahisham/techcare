<?php

/*
* Function PengaduStatusChangedNotification.php:
*  - Notification: Ticket Status Changed to inform Pengadu.
*  - Ticket status changes to Aduan Baru(1), Dirujuk(4), Aduan Dibatal(5), Selesai(6)
*  - Sends email with ticket details and status update information.
*  - run command: php test_notification_detail.php 14[ticket_id] to test email notification for ticket status "Aduan Baru"(1)
*/

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PengaduStatusChangedNotification extends Notification
{
  protected $ticket;
  protected $oldStatus;
  protected $newStatus;
  protected $changedBy;
  protected $notes;

  /**
   * Create a new notification instance.
   */
  public function __construct(Ticket $ticket, $oldStatus, $newStatus, User $changedBy, $notes = null)
  {
    $this->ticket = $ticket;
    $this->oldStatus = $oldStatus;
    $this->newStatus = $newStatus;
    $this->changedBy = $changedBy;
    $this->notes = $notes;
  }

  /**
   * Get the notification's delivery channels.
   *
   * @return array<int, string>
   */
  public function via(object $notifiable): array
  {
    // Jangan hantar email untuk status 2 (Aduan Diproses) dan 3 (Serahan Kepada)
    // Pengadu tidak perlu tahu pasal proses dalaman pegawai
    if (in_array($this->newStatus, [2, 3])) {
      Log::info('PengaduStatusChangedNotification: Skipping email for internal status', [
        'new_status' => $this->newStatus,
        'ticket_id' => $this->ticket->id,
        'notifiable_email' => $notifiable->email ?? 'no-email'
      ]);
      return []; // Return empty array = tiada channel = tiada email
    }

    Log::info('PengaduStatusChangedNotification: Sending email to Pengadu', [
      'new_status' => $this->newStatus,
      'old_status' => $this->oldStatus,
      'ticket_id' => $this->ticket->id,
      'ticket_nolog' => $this->ticket->ticket_nolog,
      'notifiable_email' => $notifiable->email ?? 'no-email',
      'notifiable_id' => $notifiable->id ?? 'no-id'
    ]);

    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $statusName = $this->getStatusName($this->newStatus);
    $assignedUsers = $this->getAssignedUsers();

    return (new MailMessage)
      ->subject("[{$statusName}] {$this->ticket->ticket_nolog}: {$this->ticket->ticket_title}")
      ->markdown('emails.ticket.pengadu-status', [
        'ticket' => $this->ticket,
        'assignedUser' => $assignedUsers->count() === 1 ? $assignedUsers->first() : $assignedUsers,
        'assignedUsers' => $assignedUsers,
        'newStatus' => $this->newStatus,
        'statusName' => $statusName,
        'changedBy' => $this->changedBy,
        'notes' => $this->notes,
      ]);
  }

  /**
   * Get all assigned officers for the notification status.
   */
  private function getAssignedUsers(): Collection
  {
    if (in_array((int) $this->newStatus, [5, 6], true)) {
      $beforeClose = $this->ticket->getActiveAssigneeUsersBeforeLatestTerminalChronology();
      if ($beforeClose->isNotEmpty()) {
        return self::sortUsersByGrade($beforeClose);
      }
    }

    $assignedUsers = $this->ticket->chronologies()
      ->whereNotNull('escalate_to')
      ->where('tick_status', $this->newStatus)
      ->with('escalatedTo:id,nama,nama_gred')
      ->get()
      ->pluck('escalatedTo')
      ->filter(fn($user) => $user && !blank($user->nama))
      ->unique('id')
      ->values();

    if ($assignedUsers->isNotEmpty()) {
      return self::sortUsersByGrade($assignedUsers);
    }

    return self::sortUsersByGrade(
      $this->ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->with('escalatedTo:id,nama,nama_gred')
        ->latest()
        ->get()
        ->pluck('escalatedTo')
        ->filter(fn($user) => $user && !blank($user->nama))
        ->unique('id')
        ->values()
    );
  }

  /**
   * Sort user collection by nama_gred DESC then id ASC.
   */
  private static function sortUsersByGrade(Collection $users): Collection
  {
    if ($users->isEmpty()) {
      return $users;
    }

    $needsGrade = $users->contains(fn($u) => !isset($u->nama_gred));
    if ($needsGrade) {
      $ids = $users->pluck('id')->filter()->unique()->values();
      if ($ids->isNotEmpty()) {
        $gradeMap = User::query()->whereIn('id', $ids->all(), 'and', false)->pluck('nama_gred', 'id');
        $users = $users->map(function ($u) use ($gradeMap) {
          if (!isset($u->nama_gred) && isset($gradeMap[$u->id])) {
            $u->nama_gred = $gradeMap[$u->id];
          }
          return $u;
        });
      }
    }

    return $users
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
   * Get the gelaran (title) of the notifiable user
   */
  protected function getGelaran($notifiable)
  {
    $gelaran = $notifiable->gelaran ?? 'Tuan/Puan';

    return "Y.Bhg. {$gelaran} {$notifiable->nama}";
  }

  /**
   * Get status name in Malay
   */
  private function getStatusName($statusId)
  {
    return match ($statusId) {
      1 => 'Aduan Baru',
      2 => 'Aduan Diproses',
      3 => 'Serahan Kepada',
      4 => 'Aduan Dirujuk',
      5 => 'Aduan Dibatal',
      6 => 'Aduan Selesai',
      default => ''
    };
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toArray(object $notifiable): array
  {
    return [
      'ticket_id' => $this->ticket->id,
      'ticket_nolog' => $this->ticket->ticket_nolog,
      'old_status' => $this->oldStatus,
      'new_status' => $this->newStatus,
      'changed_by' => $this->changedBy->id,
    ];
  }
}
