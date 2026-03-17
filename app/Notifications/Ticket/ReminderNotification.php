<?php

/*
 * Function ReminderNotification.php:
 *  - Notification class to alert Pegawai Tugasan (Level 1 & 2) about reminder SLA tickets.
 *  - Sends email 1 working day BEFORE due date with ticket details.
 *  - Scheduled to be sent at 7:30 AM daily
 */

namespace App\Notifications\Ticket;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ReminderNotification extends Notification
{
  protected $ticket;

  /**
   * Create a new notification instance.
   *
   * @param Ticket $ticket
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
    // Determine the reminder message based on due date
    $dueDate = $this->ticket->latestSlaDueDate ? Carbon::parse($this->ticket->latestSlaDueDate) : null;
    $reminderMessage = 'Tiket ini akan **TAMAT TEMPOH ESOK** (1 hari bekerja lagi). Sila ambil tindakan segera.';

    // If due date is Monday and today is Friday, adjust message
    if ($dueDate && $dueDate->isMonday() && Carbon::today()->isFriday()) {
      $reminderMessage = 'Tiket ini akan **TAMAT TEMPOH PADA HARI ISNIN** (1 hari bekerja lagi). Sila ambil tindakan segera.';
    }

    return (new MailMessage)
      ->subject("[Peringatan] {$this->ticket->ticket_nolog}: SLA - Hampir Tamat Tempoh")
      ->markdown('emails.ticket.sla-reminder', [
        'ticket' => $this->ticket,
        'notifiable' => $notifiable,
        'reminderMessage' => $reminderMessage,
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
      'due_date' => $this->ticket->latestSlaDueDate,
      'status' => $this->ticket->tick_status,
      'type' => 'reminder',
    ];
  }
}
