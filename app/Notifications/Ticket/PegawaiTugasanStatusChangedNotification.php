<?php

/*
* Function PegawaiTugasanStatusChangedNotification.php:
*  - Notification: Ticket Status Changed to inform Pegawai Tugasan.
*  - Ticket status changes:
*     1. Level 1: Aduan Baru(1), Dirujuk(4), Selesai(6)-after Level 2 submits
*     2. Level 2: Serahan Kepada(3)
*  - Sends email with ticket details and status update information.
*  - run command: php test_notification_detail.php 14[ticket_id] to test email notification for ticket status "Aduan Baru"(1)
*/

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PegawaiTugasanStatusChangedNotification extends Notification
{
  use Queueable;

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
    $this->oldStatus = $oldStatus;
    $this->newStatus = $newStatus;
    $this->changedBy = $changedBy;
    $this->notes = $notes;
    $this->level = $level;
  }

  /**
   * Get the notification's delivery channels.
   *
   * @return array<int, string>
   */
  public function via(object $notifiable): array
  {
    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    // Skip email for "Aduan Diproses" (status 2) and "Aduan Dibatal" (status 5)
    if (in_array($this->newStatus, [2, 5])) {
      return new MailMessage();
    }

    $statusName     = $this->getStatusName($this->newStatus);
    $oldStatusName  = $this->getStatusName($this->oldStatus);

    $message = (new MailMessage)
      ->subject("[{$statusName}] {$this->ticket->ticket_nolog} – {$this->ticket->ticket_title}")
      ->greeting('Assalamualaikum dan Salam Sejahtera,')
      ->line("**{$this->getGelaran($notifiable)}**")
      ->line("Dimaklumkan bahawa tiket berikut memerlukan tindakan anda:");

    // Status-specific info
    $message->line($this->getStatusMessage($this->newStatus, $this->level));

    // Ticket info
    $message->line("**No. Log Tiket:** {$this->ticket->ticket_nolog}")
      ->line("**Tajuk Aduan:** {$this->ticket->ticket_title}")
      ->line("**Kategori:** " . ($this->ticket->category->cat_name ?? 'N/A'))
      ->line("**Sub Kategori:** " . ($this->ticket->subCategory->subcat_name ?? 'N/A'))
      ->line("**Nama Pengadu:** " . ($this->ticket->user->nama ?? 'N/A'))
      ->line("**Status Semasa:** {$statusName}")
      ->line("**Dilog Oleh:** {$this->changedBy->nama}");

    // Catatan
    if (!empty($this->notes)) {
      $message->line("**Catatan:**")
        ->line($this->notes);
    }

    // Action button
    $message->action(
      'Lihat & Urus Tiket',
      url('/tickets/' . $this->ticket->id)
    );

    // Closing
    $message->line($this->getStatusClosingMessage($this->newStatus, $this->level))
      ->salutation("Sekian, terima kasih.\n\nSistem eHelp");

    return $message;
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
        6 => 'Tiket ini telah diselesaikan. Terima kasih atas tindakan anda.',
        default => '',
      };
    } else {
      return match ($statusId) {
        3 => 'Tiket ini telah diserahkan kepada anda untuk tindakan lanjut.',
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
