<?php

/**
 * Command to send SLA notifications for tickets.
 * Handles both reminder (-1 working day) and overdue (+1 working day) notifications.
 */

namespace App\Console\Commands;

use App\Services\TicketSLAReminderService;
use Illuminate\Console\Command;

class SendSLANotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:send-notifications {--type=all : Type of notification (reminder|overdue|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SLA notifications for tickets (reminder and overdue only)';

    /**
     * Execute the console command.
     */
    public function handle(TicketSLAReminderService $service)
    {
        $type = $this->option('type');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║         SLA NOTIFICATION SYSTEM (SIMPLIFIED)               ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("Type: {$type}");
        $this->info("Time: " . now()->format('Y-m-d H:i:s'));
        $this->info("Timezone: Asia/Kuala_Lumpur");
        $this->newLine();

        try {
            $results = [];

            // Send reminder notifications (-1 working day)
            if (in_array($type, ['reminder', 'all'])) {
                $this->info("📧 Sending REMINDER notifications (-1 working day before sla_duedate)...");
                $this->line("─────────────────────────────────────────────────────────────");

                $reminderResults = $service->sendBeforeDueReminders();
                $results['reminder'] = $reminderResults;

                $this->info("✓ Sent: {$reminderResults['sent']}");
                $this->info("✗ Failed: {$reminderResults['failed']}");
                $this->info("⊘ Skipped: {$reminderResults['skipped']}");

                // Show details if any
                if (!empty($reminderResults['details']) && $reminderResults['sent'] > 0) {
                    $this->newLine();
                    $this->table(
                        ['Ticket', 'Officer', 'Email', 'Status'],
                        array_map(function ($detail) {
                            return [
                                $detail['ticket'] ?? 'N/A',
                                $detail['officer'] ?? 'N/A',
                                $detail['email'] ?? 'N/A',
                                $detail['status'] ?? 'N/A'
                            ];
                        }, array_slice($reminderResults['details'], 0, 10)) // Show max 10
                    );

                    if (count($reminderResults['details']) > 10) {
                        $this->info("... and " . (count($reminderResults['details']) - 10) . " more");
                    }
                }

                $this->newLine();
            }

            // Send overdue notifications (+1 working day)
            if (in_array($type, ['overdue', 'all'])) {
                $this->info("⚠️  Sending OVERDUE notifications (+1 working day after sla_duedate)...");
                $this->line("─────────────────────────────────────────────────────────────");

                $overdueResults = $service->sendAfterDueReminders();
                $results['overdue'] = $overdueResults;

                $this->info("✓ Sent: {$overdueResults['sent']}");
                $this->info("✗ Failed: {$overdueResults['failed']}");
                $this->info("⊘ Skipped: {$overdueResults['skipped']}");

                // Show details if any
                if (!empty($overdueResults['details']) && $overdueResults['sent'] > 0) {
                    $this->newLine();
                    $this->table(
                        ['Ticket', 'Officer', 'Email', 'Status'],
                        array_map(function ($detail) {
                            return [
                                $detail['ticket'] ?? 'N/A',
                                $detail['officer'] ?? 'N/A',
                                $detail['email'] ?? 'N/A',
                                $detail['status'] ?? 'N/A'
                            ];
                        }, array_slice($overdueResults['details'], 0, 10)) // Show max 10
                    );

                    if (count($overdueResults['details']) > 10) {
                        $this->info("... and " . (count($overdueResults['details']) - 10) . " more");
                    }
                }

                $this->newLine();
            }

            // Summary
            $this->info("╔════════════════════════════════════════════════════════════╗");
            $this->info("║                       SUMMARY                              ║");
            $this->info("╚════════════════════════════════════════════════════════════╝");

            $totalSent = ($results['reminder']['sent'] ?? 0) + ($results['overdue']['sent'] ?? 0);
            $totalFailed = ($results['reminder']['failed'] ?? 0) + ($results['overdue']['failed'] ?? 0);
            $totalSkipped = ($results['reminder']['skipped'] ?? 0) + ($results['overdue']['skipped'] ?? 0);

            $this->info("Total Sent: {$totalSent}");
            $this->info("Total Failed: {$totalFailed}");
            $this->info("Total Skipped: {$totalSkipped}");
            $this->newLine();

            if ($totalSent > 0) {
                $this->info("✅ Notifications sent successfully!");
            } else {
                $this->warn("⚠️  No notifications were sent. Check logs for details.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("╔════════════════════════════════════════════════════════════╗");
            $this->error("║                         ERROR                              ║");
            $this->error("╚════════════════════════════════════════════════════════════╝");
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
            $this->newLine();

            return Command::FAILURE;
        }
    }
}
