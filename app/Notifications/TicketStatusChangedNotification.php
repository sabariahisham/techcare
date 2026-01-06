<?php

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
    $statusName = $this->getStatusName($this->newStatus);
    $oldStatusName = $this->getStatusName($this->oldStatus);
    $statusColor = $this->getStatusColor($this->newStatus);

    $message = (new MailMessage)
      ->subject("Status Tiket {$this->ticket->ticket_nolog} Dikemaskini: {$statusName}")
      ->greeting("Assalamualaikum {$notifiable->nama},")
      ->line("Status tiket anda telah dikemaskini.");

    // Add status-specific messages
    $message = $this->addStatusSpecificMessage($message, $this->newStatus);

    $message->line("**No. Log Tiket:** {$this->ticket->ticket_nolog}")
      ->line("**Tajuk:** {$this->ticket->ticket_title}")
      ->line("**Status Lama:** {$oldStatusName}")
      ->line("**Status Baru:** {$statusName}")
      ->line("**Dikemaskini Oleh:** {$this->changedBy->nama}");

    // Add notes if provided
    if ($this->notes) {
      $message->line("**Catatan:** {$this->notes}");
    }

    // Add action button based on status
    if (in_array($this->newStatus, [1, 2, 3, 4])) {
      $message->action('Lihat Tiket', url('/tickets/' . $this->ticket->id));
    } else {
      $message->line("Anda boleh melihat tiket untuk maklumat lanjut.");
      $message->action('Lihat Tiket', url('/tickets/' . $this->ticket->id));
    }

    $message->line($this->getStatusClosingMessage($this->newStatus))
      ->salutation('Terima kasih,  
Sistem eHelp');

    return $message;
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
      default => 'Status Tidak Diketahui'
    };
  }

  /**
   * Get status color for email styling
   */
  private function getStatusColor($statusId)
  {
    return match ($statusId) {
      1 => 'primary',
      2 => 'warning',
      3 => 'info',
      4 => 'secondary',
      5 => 'danger',
      6 => 'success',
      default => 'secondary'
    };
  }

  /**
   * Add status-specific message
   */
  private function addStatusSpecificMessage(MailMessage $message, $statusId)
  {
    switch ($statusId) {
      case 1: // Aduan Baru
        $message->line("🆕 Tiket anda telah dilog dan akan diproses dalam masa terdekat.");
        break;

      case 2: // Aduan Diproses
        $message->line("⚙️ Tiket anda sedang dalam proses penyelesaian oleh pegawai kami.");
        break;

      case 3: // Serahan Kepada
        $message->line("📤 Tiket anda telah diserahkan kepada pegawai lain untuk tindakan selanjutnya.");
        break;

      case 4: // Aduan Dirujuk
        $message->line("📋 Tiket anda telah dirujuk kepada pihak berkenaan untuk tindakan lanjut.");
        break;

      case 5: // Aduan Dibatal
        $message->line("❌ Tiket anda telah dibatalkan. Sila hubungi helpdesk jika anda memerlukan penjelasan lanjut.");
        break;

      case 6: // Aduan Selesai
        $message->line("✅ Tiket anda telah selesai diproses. Terima kasih atas kesabaran anda.");
        break;
    }

    return $message;
  }

  /**
   * Get closing message based on status
   */
  private function getStatusClosingMessage($statusId)
  {
    return match ($statusId) {
      1 => 'Kami akan menghubungi anda sekiranya memerlukan maklumat tambahan.',
      2 => 'Kami akan memberitahu anda sebaik sahaja tiket selesai diproses.',
      3, 4 => 'Anda akan menerima kemaskini seterusnya tidak lama lagi.',
      5 => 'Sekiranya anda tidak bersetuju dengan pembatalan ini, sila hubungi helpdesk.',
      6 => 'Kami harap perkhidmatan kami memuaskan hati. Jika ada sebarang pertanyaan, sila hubungi kami.',
      default => 'Terima kasih atas maklum balas anda.'
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
