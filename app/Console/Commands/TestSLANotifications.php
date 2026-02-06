<?php

/*
  Command to test SLA notifications manually.
  Can test reminder (1 day before due) and after due (1 day after due) notifications.
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TicketReminderService;
use App\Services\TicketOverdueService;

class TestSLANotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:test {type? : Type of notification to test (reminder/after-due/all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SLA notifications manually (Reminder & After Due)';

    protected $reminderService;
    protected $overdueService;

    /**
     * Create a new command instance.
     */
    public function __construct(TicketReminderService $reminderService, TicketOverdueService $overdueService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
        $this->overdueService = $overdueService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';

        $this->info('🚀 Testing SLA Notifications...');
        $this->newLine();

        if ($type === 'reminder' || $type === 'all') {
            $this->testReminder();
        }

        if ($type === 'after-due' || $type === 'all') {
            $this->testAfterDue();
        }

        $this->newLine();
        $this->info('✅ Test completed! Check storage/logs/laravel.log for details.');

        return 0;
    }

    protected function testReminder()
    {
        $this->line('📧 Testing REMINDER notifications (1 day before due)...');

        try {
            $stats = $this->reminderService->sendBeforeDueReminders();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Type', $stats['type']],
                    ['Total Found', $stats['total_found']],
                    ['Sent', $stats['sent']],
                    ['Failed', $stats['failed']],
                    ['Skipped', $stats['skipped']],
                ]
            );

            if (!empty($stats['errors'])) {
                $this->error('Errors:');
                foreach ($stats['errors'] as $error) {
                    $this->line('  - ' . $error);
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    protected function testAfterDue()
    {
        $this->line('📧 Testing AFTER DUE notifications (1 day after due)...');

        try {
            $stats = $this->overdueService->sendAfterDueNotifications();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Type', $stats['type']],
                    ['Total Found', $stats['total_found']],
                    ['Sent', $stats['sent']],
                    ['Failed', $stats['failed']],
                    ['Skipped', $stats['skipped']],
                ]
            );

            if (!empty($stats['errors'])) {
                $this->error('Errors:');
                foreach ($stats['errors'] as $error) {
                    $this->line('  - ' . $error);
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }

        $this->newLine();
    }
}
