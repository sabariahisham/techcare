<?php

/*
* Function PegawaiTugasanStatusChangedNotification.php:
*  - Notification: Ticket Status Changed to inform Pegawai Tugasan.
*  - Ticket status changes:
*     1. Level 1: Aduan Baru(1), Dirujuk(4), Dibatal(5)-after Level 2 submits, Selesai(6)-after Level 2 submits
*     2. Level 2: Serahan Kepada(3)
*  - Sends email with ticket details and status update information.
*  - run command: php test_notification_detail.php 14[ticket_id] to test email notification for ticket status "Aduan Baru"(1)
*/

namespace App\Notifications\Ticket;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PegawaiTugasanStatusChangedNotification extends Notification
{
  protected $ticket;
  protected $oldStatus;
  protected $newStatus;
  protected $changedBy;
  protected $notes;
  protected $level;

  /**
   * Create a new notification instance.
   */
  public function __construct(Ticket $ticket, $oldStatus, $newStatus, User $changedBy, $notes = null, $level = 1)
  {
    $this->ticket = $ticket;
    $this->oldStatus = (int) $oldStatus;
    $this->newStatus = (int) $newStatus;
    $this->changedBy = $changedBy;
    $this->notes = $notes;
    $this->level = (int) $level;
  }

  /**
   * Get the notification's delivery channels.
   *
   * @return array<int, string>
   */
  public function via(object $notifiable): array
  {
    // Allow all status notifications - filtering is done at service layer
    // This allows Level 1 to receive notifications when Level 2 cancels tickets
    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $statusName = $this->getStatusName($this->newStatus);

    // Log for debugging
    Log::info('Sending pegawai tugasan email', [
      'ticket_id' => $this->ticket->id,
      'new_status' => $this->newStatus,
      'status_name' => $statusName,
      'recipient' => $notifiable->email,
      'level' => $this->level
    ]);

    return (new MailMessage)
      ->subject("[{$statusName}] {$this->ticket->ticket_nolog}: {$this->ticket->ticket_title}")
      ->markdown('emails.ticket.pegawai-tugasan-status', [
        'ticket' => $this->ticket,
        'assignedUser' => $notifiable,
        'statusName' => $statusName,
        'newStatus' => (int) $this->newStatus,
        'oldStatus' => (int) $this->oldStatus,
        'changedBy' => $this->changedBy,
        'notes' => $this->notes,
        'level' => (int) $this->level,
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
   * Get status message in Malay based on level
   */
  private function getStatusMessage($statusId, $level)
  {
    if ($level == 1) {
      return match ($statusId) {
        1 => 'Tiket baru telah ditugaskan kepada anda. Sila ambil tindakan yang sewajarnya.',
        4 => 'Tiket ini telah dirujuk kepada bahagian lain. Sila semak dan ambil tindakan jika perlu.',
        5 => 'Tiket ini telah dibatalkan. Sila ambil maklum untuk rekod.',
        6 => 'Tiket ini telah diselesaikan. Terima kasih atas tindakan anda.',
        default => '',
      };
    } else {
      return match ($statusId) {
        3 => 'Tiket ini telah diserahkan kepada anda untuk tindakan lanjut.',
        5 => 'Tiket ini telah dibatalkan. Sila ambil maklum untuk rekod.',
        6 => 'Tiket ini telah diselesaikan. Terima kasih atas tindakan anda.',
        default => '',
      };
    }
  }

  /**
   * Get closing message based on status and level
   */
  private function getStatusClosingMessage($statusId, $level)
  {
    return match ($statusId) {
      1 => 'Sila ambil tindakan segera untuk memproses tiket ini.',
      3 => 'Sila semak dan ambil tindakan yang bersesuaian.',
      4 => 'Makluman lanjut akan dimaklumkan sekiranya diperlukan.',
      5 => 'Tiket ini telah dibatalkan dan tidak akan diproses lagi.',
      6 => 'Tiket ini telah ditutup.',
      default => '',
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
      'level' => $this->level,
    ];
  }
}
