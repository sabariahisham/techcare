<?php

/*
 * Function AfterDueNotification.php:
 *  - Notification class to alert Pegawai Tugasan (Level 1 & 2) about after due tickets.
 *  - Sends email 1 working day AFTER due date with ticket details.
 *  - Scheduled to be sent at 7:30 AM daily
 */

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AfterDueNotification extends Notification
{
  protected $ticket;

  /**
   * Create a new notification instance.
   */
  public function __construct(Ticket $ticket)
  {
    $this->ticket = $ticket;
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
    return (new MailMessage)
      ->subject("{$this->ticket->ticket_nolog}: ⛔ Tindakan Segera - SLA Tamat Tempoh (1 Hari Lepas)")
      ->markdown('emails.ticket.sla-after-due', [
        'ticket' => $this->ticket,
        'notifiable' => $notifiable,
        'message' => 'Tiket ini telah **MELEPASI TARIKH AKHIR SEMALAM**. Tindakan segera diperlukan.',
      ]);
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
      'due_date' => $this->ticket->tick_duedate,
      'status' => $this->ticket->tick_status,
      'type' => 'after_due',
    ];
  }
}
