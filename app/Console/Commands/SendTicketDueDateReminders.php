<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\TicketDueDateReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendTicketDueDateReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:send-duedate-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to level 1 officers for tickets due tomorrow (status: Aduan Baru)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ticket due date reminder process...');

        try {
            // Calculate tomorrow's date (1 day from now)
            $tomorrow = Carbon::tomorrow()->startOfDay();
            $tomorrowEnd = Carbon::tomorrow()->endOfDay();

            // Find tickets that meet the criteria:
            // 1. tick_status = 1 (Aduan Baru)
            // 2. tick_duedate is tomorrow
            $tickets = Ticket::where('tick_status', 1)
                ->whereBetween('tick_duedate', [$tomorrow, $tomorrowEnd])
                ->with(['user', 'category', 'subCategory'])
                ->get();

            if ($tickets->isEmpty()) {
                $this->info('No tickets found with due date tomorrow.');
                return Command::SUCCESS;
            }

            $this->info("Found {$tickets->count()} ticket(s) due tomorrow.");

            $sentCount = 0;
            $failedCount = 0;

            foreach ($tickets as $ticket) {
                try {
                    // Get the latest chronology entry with level_pegtugas_id = 1 and escalate_to
                    $chronology = TicketChronology::where('ticket_id', $ticket->id)
                        ->where('level_pegtugas_id', 1)
                        ->whereNotNull('escalate_to')
                        ->with('escalatedTo')
                        ->latest()
                        ->first();

                    if (!$chronology || !$chronology->escalatedTo) {
                        $this->warn("Ticket {$ticket->ticket_nolog}: No level 1 officer assigned. Skipping.");
                        continue;
                    }

                    $officer = $chronology->escalatedTo;

                    // Check if officer has email
                    if (!$officer->email) {
                        $this->warn("Ticket {$ticket->ticket_nolog}: Officer {$officer->nama} has no email. Skipping.");
                        continue;
                    }

                    // Send notification reminder
                    $officer->notify(new TicketDueDateReminderNotification($ticket));

                    $sentCount++;
                    $this->info("✓ Sent reminder for ticket {$ticket->ticket_nolog} to {$officer->nama} ({$officer->email})");

                    // Log the reminder
                    Log::info('Ticket due date reminder sent', [
                        'ticket_id' => $ticket->id,
                        'ticket_nolog' => $ticket->ticket_nolog,
                        'officer_id' => $officer->id,
                        'officer_name' => $officer->nama,
                        'officer_email' => $officer->email,
                        'due_date' => $ticket->tick_duedate->format('Y-m-d'),
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("✗ Failed to send reminder for ticket {$ticket->ticket_nolog}: {$e->getMessage()}");

                    Log::error('Failed to send ticket due date reminder', [
                        'ticket_id' => $ticket->id,
                        'ticket_nolog' => $ticket->ticket_nolog,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("\nSummary:");
            $this->info("Total tickets: {$tickets->count()}");
            $this->info("Reminders sent: {$sentCount}");
            $this->info("Failed: {$failedCount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error in reminder process: {$e->getMessage()}");
            Log::error('Error in ticket due date reminder command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
