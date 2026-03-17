<?php

/**
 * Test Weekend SLA Reminder Logic
 *
 * This command helps test the weekend skip functionality:
 * - Scenario: SLA due date on Monday
 * - Email should be sent on Friday (1 working day before)
 * - NO emails on Saturday/Sunday
 *
 * Usage: php artisan sla:test-weekend
 */

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TestWeekendSLA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:test-weekend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test weekend SLA reminder scenario and check test data readiness';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║          TEST WEEKEND SLA REMINDER - SCENARIO CHECKER         ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $today = Carbon::today();

        $this->info("📅 TODAY: " . $today->format('l, d/m/Y'));
        $this->line('─────────────────────────────────────────────────────────────────');

        // Determine what day today is
        if ($today->isWeekday()) {
            $this->info("✅ Today is a WORKING DAY");
        } else {
            $this->warn("⚠️  Today is a WEEKEND");
        }

        $this->newLine();

        // Calculate next working day
        $nextWorkingDay = Helpers::addWorkingDays($today, 1);
        $this->info("📅 Next Working Day: " . $nextWorkingDay->format('l, d/m/Y'));
        $this->line('─────────────────────────────────────────────────────────────────');

        // Check if next working day is Monday (meaning today might be Friday)
        if ($nextWorkingDay->isMonday() && $today->isFriday()) {
            $this->info("🎯 PERFECT TEST SCENARIO!");
            $this->line("   Today is Friday, next working day is Monday.");
            $this->line("   This is when weekend logic applies.");
        } elseif ($today->isSaturday() || $today->isSunday()) {
            $this->error("🚫 Cannot send emails today (weekend)");
            $this->line("   The scheduler should SKIP execution.");
        } else {
            $this->info("ℹ️  Regular weekday scenario");
            $this->line("   Next working day is " . $nextWorkingDay->format('l'));
        }

        $this->newLine();

        // Show test data requirements
        $this->info("📋 TO CREATE TEST DATA:");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("1. Find or create a ticket in the database");
        $this->line("2. Create a chronology record with these details:");
        $this->line("   - sla_duedate: " . $nextWorkingDay->format('Y-m-d 09:00:00') . " (Monday if today is Friday)");
        $this->line("   - escalate_to: User ID of pegawai tugasan");
        $this->line("   - level_pegtugas_id: 1 or 2");
        $this->line("   - ticket_id: The ticket ID");
        $this->newLine();

        // Check existing test candidates
        $this->info("🔍 CHECKING EXISTING TICKETS DUE ON " . $nextWorkingDay->format('d/m/Y') . ":");
        $this->line('─────────────────────────────────────────────────────────────────');

        $nextWorkingDayStart = $nextWorkingDay->copy()->startOfDay();
        $nextWorkingDayEnd = $nextWorkingDay->copy()->endOfDay();

        $chronologies = TicketChronology::whereBetween('sla_duedate', [$nextWorkingDayStart, $nextWorkingDayEnd])
            ->whereNotNull('escalate_to')
            ->with(['ticket', 'escalatedTo'])
            ->get();

        if ($chronologies->count() > 0) {
            $this->info("✅ Found {$chronologies->count()} candidate(s):");
            $this->newLine();

            $tableData = [];
            foreach ($chronologies as $chrono) {
                $ticket = $chrono->ticket;
                $officer = $chrono->escalatedTo;

                $status = '✅ Ready';
                $warnings = [];

                if ($ticket && in_array($ticket->tick_status, [5, 6])) {
                    $status = '⚠️  Skip';
                    $warnings[] = 'Batal/Selesai';
                }

                if ($officer && empty($officer->email)) {
                    $status = '⚠️  Skip';
                    $warnings[] = 'No email';
                }

                $tableData[] = [
                    $ticket ? $ticket->ticket_nolog : 'N/A',
                    $officer ? $officer->nama : 'N/A',
                    $officer ? $officer->email : 'N/A',
                    $chrono->sla_duedate->format('d/m/Y H:i'),
                    $status . (count($warnings) > 0 ? ' (' . implode(', ', $warnings) . ')' : '')
                ];
            }

            $this->table(['Ticket', 'Officer', 'Email', 'Due Date', 'Status'], $tableData);
        } else {
            $this->error("❌ No test candidates found!");
            $this->newLine();
            $this->info("💡 QUICK FIX - Use this command to create test data:");
            $this->line("   php artisan sla:create-test-data");
        }

        $this->newLine();
        $this->info("🧪 TO RUN THE TEST:");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("1. Ensure test data exists (see above)");
        $this->line("2. Run the command:");
        $this->line("   php artisan sla:test reminder");
        $this->newLine();
        $this->line("3. Check the results:");
        $this->line("   - On WEEKDAY: Should find and send emails");
        $this->line("   - On WEEKEND: Should skip with log message");
        $this->newLine();

        // Show current day's behavior
        $this->info("📊 EXPECTED BEHAVIOR TODAY (" . $today->format('l') . "):");
        $this->line('─────────────────────────────────────────────────────────────────');

        if ($today->isSaturday() || $today->isSunday()) {
            $this->warn("🚫 SCHEDULER WILL SKIP");
            $this->line("   - Reason: Weekend day");
            $this->line("   - No emails will be sent");
            $this->line("   - Log message will show: 'SKIPPED execution on weekend'");
        } elseif ($today->isFriday() && $nextWorkingDay->isMonday()) {
            $this->info("✅ SHOULD SEND EMAIL");
            $this->line("   - Today: Friday (working day)");
            $this->line("   - Next working day: Monday");
            $this->line("   - Emails will be sent for tickets due on Monday");
            $this->line("   - Email will mention: 'Tarikh akhir jatuh pada hari Isnin'");
        } else {
            $this->info("✅ SHOULD SEND EMAIL (if data exists)");
            $this->line("   - Today: " . $today->format('l') . " (working day)");
            $this->line("   - Next working day: " . $nextWorkingDay->format('l'));
            $this->line("   - Emails will be sent for tickets due on " . $nextWorkingDay->format('l'));
        }

        $this->newLine();
        $this->info("✅ Test preparation complete!");
        $this->newLine();

        return Command::SUCCESS;
    }
}
