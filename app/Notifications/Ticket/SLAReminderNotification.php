<?php

/*
 * Function SLAReminderNotification.php:
 *  - Notification class untuk reminder SLA sebelum overdue
 *  - Sends email dengan ticket details dan reminder information
 *  - Untuk reminder: SLA-1 day, due today, dan due date+1
 */

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class SLAReminderNotification extends Notification implements ShouldQueue
{
  use Queueable;

  protected $ticket;
  protected $reminderType; // 'before_due', 'due_today', 'after_due'

  /**
   * Create a new notification instance.
   *
   * @param Ticket $ticket
   * @param string $reminderType Type of reminder: 'before_due', 'due_today', 'after_due'
   */
  public function __construct(Ticket $ticket, string $reminderType = 'before_due')
  {
    $this->ticket = $ticket;
    $this->reminderType = $reminderType;
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
    $subject = $this->getSubject();

    return (new MailMessage)
      ->subject("{$this->ticket->ticket_nolog}: {$subject}")
      ->markdown('emails.ticket.sla-reminder', [
        'ticket' => $this->ticket,
        'notifiable' => $notifiable,
        'reminderType' => $this->reminderType,
        'reminderMessage' => $this->getReminderMessage(),
      ]);
  }

  /**
   * Get email subject based on reminder type
   */
  protected function getSubject(): string
  {
    switch ($this->reminderType) {
      case 'before_due':
        return '⚠️ Peringatan SLA - Hampir Tamat Tempoh';
      case 'due_today':
        return '🔔 Peringatan SLA - Tamat Tempoh HARI INI';
      case 'after_due':
        return '⚠️ Peringatan SLA - Tempoh Tamat Semalam';
      default:
        return '⚠️ Peringatan SLA';
    }
  }

  /**
   * Get reminder message based on type
   */
  protected function getReminderMessage(): string
  {
    switch ($this->reminderType) {
      case 'before_due':
        return 'Tiket ini akan **TAMAT TEMPOH ESOK**. Sila ambil tindakan segera.';
      case 'due_today':
        return 'Tiket ini akan **TAMAT TEMPOH HARI INI**. Sila selesaikan dengan segera.';
      case 'after_due':
        return 'Tiket ini telah **MELEPASI TARIKH AKHIR SEMALAM**. Tindakan segera diperlukan.';
      default:
        return 'Sila ambil tindakan terhadap tiket ini.';
    }
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
      'due_date' => $this->ticket->latestSlaDueDate,
      'status' => $this->ticket->tick_status,
      'reminder_type' => $this->reminderType,
    ];
  }
}
