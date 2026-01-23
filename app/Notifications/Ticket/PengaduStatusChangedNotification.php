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
use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengaduStatusChangedNotification extends Notification
// class PengaduStatusChangedNotification extends Notification implements ShouldQueue
{
  use Queueable;

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
        return []; // Return empty array = tiada channel = tiada email
    }

    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $statusName = $this->getStatusName($this->newStatus);

    // Get assigned user from latest chronology
    $latestChronology = $this->ticket->chronologies()->latest()->first();
    $assignedUser = $latestChronology && $latestChronology->escalate_to
      ? $latestChronology->escalatedTo
      : null;

    return (new MailMessage)
      ->subject("[{$statusName}] {$this->ticket->ticket_nolog}: {$this->ticket->ticket_title}")
      ->markdown('emails.ticket.pengadu-status', [
        'ticket' => $this->ticket,
        'assignedUser' => $assignedUser,
        'statusName' => $statusName,
        'changedBy' => $this->changedBy,
        'notes' => $this->notes,
      ]);
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
