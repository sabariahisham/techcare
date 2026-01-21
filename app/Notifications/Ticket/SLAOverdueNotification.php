<?php

/*
 * Function SLAOverdueNotification.php:
 *  - Notification class to alert Pegawai Tugasan about overdue tickets.
 *  - Sends email with ticket details and overdue information.
 */

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SLAOverdueNotification extends Notification
{
  use Queueable;

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
      ->subject("{$this->ticket->ticket_nolog}: ⛔ Tindakan Segera - SLA (OVERDUE)")
      ->markdown('emails.ticket.sla-overdue', [
        'ticket' => $this->ticket,
        'notifiable' => $notifiable,
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
    ];
  }
}
