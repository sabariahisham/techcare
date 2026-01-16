<?php

/*
 * Function TicketDueDateReminderNotification.php:
 *  - Notification class to remind Pegawai Tugasan about tickets due tomorrow.
 *  - Sends email with ticket details and due date information.
 */

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketDueDateReminderNotification extends Notification
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
            ->subject("Peringatan: Tiket {$this->ticket->ticket_nolog} Akan Tamat Tempoh Esok")
            ->greeting("Assalamualaikum {$notifiable->nama},")
            ->line("Tiket yang ditugaskan kepada anda akan **tamat tempoh esok**.")
            ->line("**No. Log Tiket:** {$this->ticket->ticket_nolog}")
            ->line("**Tajuk:** {$this->ticket->ticket_title}")
            ->line("**Kategori:** " . ($this->ticket->category->cat_name ?? 'N/A'))
            ->line("**Sub Kategori:** " . ($this->ticket->subCategory->subcat_name ?? 'N/A'))
            ->line("**Nama Pengadu:** " . ($this->ticket->user->nama ?? 'N/A'))
            ->line("**Tarikh Akhir:** " . $this->ticket->tick_duedate->format('d/m/Y'))
            ->action('Lihat & Urus Tiket', url('/tickets/' . $this->ticket->id))
            ->line('Sila ambil tindakan segera untuk memastikan tiket diselesaikan sebelum tarikh akhir.')
            ->salutation('Terima kasih,
Sistem eHelp');
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
