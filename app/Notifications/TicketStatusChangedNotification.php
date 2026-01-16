<?php

/*
  * Function TicketStatusChangedNotification.php:
  *  - Notification: Ticket Status Changed to inform Pengadu and Pegawai Tugasan about ticket status changes.
  *  - Sends email with ticket details and status update information.
  * - run command: php test_notification_detail.php 14[ticket_id] to test email notification for ticket status "Aduan Baru"(1)
  */

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification
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
    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    // Skip email for "Aduan Diproses"
    if ($this->newStatus == 2) {
      return new MailMessage();
    }

    $statusName     = $this->getStatusName($this->newStatus);
    $oldStatusName  = $this->getStatusName($this->oldStatus);

    $message = (new MailMessage)
      ->subject("[{$statusName}] {$this->ticket->ticket_nolog} – {$this->ticket->ticket_title}")
      ->greeting('Assalamualaikum dan Salam Sejahtera,')
      ->line("**{$this->getGelaran($notifiable)}**")
      ->line("Dimaklumkan bahawa status tiket aduan anda adalah seperti berikut:");

    // Status-specific info
    $message->line($this->getStatusMessage($this->newStatus));

    // Ticket info (padat)
    $message->line("**No. Log Tiket:** {$this->ticket->ticket_nolog}")
      ->line("**Tajuk Aduan:** {$this->ticket->ticket_title}")
      //->line("**Status Terdahulu:** {$oldStatusName}")
      ->line("**Status Semasa:** {$statusName}")
      ->line("**Dilog Oleh:** {$this->changedBy->nama}");

    // Catatan
    if (!empty($this->notes)) {
      $message->line("**Catatan Pegawai:**")
        ->line($this->notes);
    }

    // Action button
    $message->action(
      'Lihat Tiket',
      url('/tickets/' . $this->ticket->id)
    );

    // Closing
    $message->line($this->getStatusClosingMessage($this->newStatus))
      ->salutation("Sekian, terima kasih.\n\nSistem eHelp");

    return $message;
  }

  /**
   * Get the gelaran (title) of the notifiable user
   */

  protected function getGelaran($notifiable)
{
    $gelaran = $notifiable->gelaran ?? 'Tuan / Puan';

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
   * Get status message in Malay
   */

  private function getStatusMessage($statusId)
  {
    return match ($statusId) {
      1 => 'Tiket aduan anda telah berjaya direkodkan dan akan diproses.',
      3 => 'Tiket aduan ini telah diserahkan kepada pegawai berkaitan untuk tindakan lanjut.',
      4 => 'Tiket aduan ini telah dirujuk kepada bahagian berkaitan untuk tindakan selanjutnya.',
      5 => 'Tiket aduan ini telah dibatalkan.',
      6 => 'Tiket aduan ini telah selesai diproses.',
      default => '',
    };
  }

  /**
   * Get closing message based on status
   */
  private function getStatusClosingMessage($statusId)
  {
    return match ($statusId) {
      1 => 'Kami akan menghubungi anda sekiranya memerlukan maklumat tambahan.',
      3, 4 => 'Makluman lanjut akan dimaklumkan dari semasa ke semasa.',
      5 => 'Sekiranya terdapat sebarang pertanyaan, sila hubungi helpdesk.',
      6 => 'Terima kasih atas kerjasama anda.',
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
    ];
  }
}
