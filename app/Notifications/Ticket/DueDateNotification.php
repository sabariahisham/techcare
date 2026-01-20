<?php

/*
 * Function DueDateNotification.php:
 *  - Notification class to remind Pegawai Tugasan about tickets due tomorrow.
 *  - Sends email with ticket details and due date information.
 */

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DueDateNotification extends Notification
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
      ->subject("{$this->ticket->ticket_nolog}: Peringatan Tarikh Tamat Tindakan")
      ->markdown('emails.ticket.due-date-alert', [
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
    ];
  }
}
