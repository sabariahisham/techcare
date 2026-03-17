<?php

/**
 * Create Complete SLA Test Data Set
 *
 * This command creates a comprehensive test data set for all SLA scenarios:
 * 1. REMINDER: SLA due tomorrow (1 working day ahead)
 * 2. OVERDUE: SLA due yesterday (1 working day past)
 * 3. WEEKEND: SLA due on Monday (for Friday testing)
 * 4. MULTIPLE RECIPIENTS: Different officers for testing
 *
 * Usage: php artisan sla:create-complete-test-data
 */

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateCompleteTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:create-complete-test-data
                            {--clean : Delete existing test data first}
                            {--count=3 : Number of test records per scenario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create complete test data set for reminder, overdue, and weekend scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║        CREATE COMPLETE SLA TEST DATA - ALL SCENARIOS          ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $count = (int) $this->option('count');

        // Clean existing test data if requested
        if ($this->option('clean')) {
            $this->cleanTestData();
        }

        // Get active tickets and users
        $activeTickets = Ticket::whereNotIn('tick_status', [5, 6])
            ->orderBy('id', 'desc')
            ->limit($count * 3)
            ->get();

        if ($activeTickets->count() < $count) {
            $this->error("❌ Not enough active tickets! Found: {$activeTickets->count()}, Need: {$count}");
            return Command::FAILURE;
        }

        $usersWithEmail = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id', 'asc')
            ->limit($count * 3)
            ->get();

        if ($usersWithEmail->count() < $count) {
            $this->error("❌ Not enough users with email! Found: {$usersWithEmail->count()}, Need: {$count}");
            return Command::FAILURE;
        }

        $today = Carbon::today();
        $created = [
            'reminder' => [],
            'overdue' => [],
            'weekend' => []
        ];

        try {
            DB::beginTransaction();

            // 1. Create REMINDER test data (due TOMORROW)
            $this->info("📧 SCENARIO 1: REMINDER (Due Tomorrow - 1 Working Day Ahead)");
            $this->line('─────────────────────────────────────────────────────────────────');

            $tomorrow = Helpers::addWorkingDays($today, 1);

            for ($i = 0; $i < $count; $i++) {
                $ticket = $activeTickets[$i];
                $user = $usersWithEmail[$i];

                $chrono = $this->createChronology(
                    $ticket,
                    $user,
                    $tomorrow->copy()->setTime(9, 0, 0),
                    'medium',
                    1
                );

                $created['reminder'][] = [
                    'id' => $chrono->id,
                    'ticket' => $ticket->ticket_nolog,
                    'officer' => $user->nama,
                    'email' => $user->email,
                    'due_date' => $chrono->sla_duedate->format('d/m/Y H:i (l)')
                ];

                $this->line("  ✅ #{$chrono->id} - {$ticket->ticket_nolog} → {$user->nama}");
            }

            $this->newLine();

            // 2. Create OVERDUE test data (due YESTERDAY)
            $this->info("⚠️  SCENARIO 2: OVERDUE (Due Yesterday - 1 Working Day Past)");
            $this->line('─────────────────────────────────────────────────────────────────');

            $yesterday = Helpers::subtractWorkingDays($today, 1);

            for ($i = 0; $i < $count; $i++) {
                $ticket = $activeTickets[$count + $i];
                $user = $usersWithEmail[$count + $i];

                $chrono = $this->createChronology(
                    $ticket,
                    $user,
                    $yesterday->copy()->setTime(9, 0, 0),
                    'high',
                    2
                );

                $created['overdue'][] = [
                    'id' => $chrono->id,
                    'ticket' => $ticket->ticket_nolog,
                    'officer' => $user->nama,
                    'email' => $user->email,
                    'due_date' => $chrono->sla_duedate->format('d/m/Y H:i (l)')
                ];

                $this->line("  ✅ #{$chrono->id} - {$ticket->ticket_nolog} → {$user->nama}");
            }

            $this->newLine();

            // 3. Create WEEKEND test data (due NEXT MONDAY if today is Friday, or next Friday→Monday)
            $this->info("🎯 SCENARIO 3: WEEKEND (Friday → Monday Scenario)");
            $this->line('─────────────────────────────────────────────────────────────────');

            // Calculate next Monday
            $nextMonday = $today->copy();
            while (!$nextMonday->isMonday()) {
                $nextMonday->addDay();
            }

            // If Monday is more than 7 days away, use the one after next Friday
            if ($nextMonday->diffInDays($today) > 7) {
                $nextMonday->addWeek();
            }

            $fridayBeforeMonday = $nextMonday->copy()->subDays(3); // Friday = Monday - 3 days

            $this->line("  📅 Target: Due on Monday {$nextMonday->format('d/m/Y')}");
            $this->line("  📅 Email should be sent on Friday {$fridayBeforeMonday->format('d/m/Y')}");
            $this->newLine();

            for ($i = 0; $i < $count; $i++) {
                $ticket = $activeTickets[($count * 2) + $i];
                $user = $usersWithEmail[($count * 2) + $i];

                $chrono = $this->createChronology(
                    $ticket,
                    $user,
                    $nextMonday->copy()->setTime(9, 0, 0),
                    'medium',
                    1
                );

                $created['weekend'][] = [
                    'id' => $chrono->id,
                    'ticket' => $ticket->ticket_nolog,
                    'officer' => $user->nama,
                    'email' => $user->email,
                    'due_date' => $chrono->sla_duedate->format('d/m/Y H:i (l)')
                ];

                $this->line("  ✅ #{$chrono->id} - {$ticket->ticket_nolog} → {$user->nama}");
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ SUCCESS! Complete test data set created.");
            $this->newLine();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ ERROR: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // Display summary
        $this->displaySummary($created, $today, $tomorrow, $yesterday, $nextMonday, $fridayBeforeMonday);

        return Command::SUCCESS;
    }

    /**
     * Create a chronology record
     */
    private function createChronology($ticket, $user, $dueDate, $severity, $level)
    {
        $chronology = new TicketChronology();
        $chronology->ticket_id = $ticket->id;
        $chronology->frm_user_id = $user->id;
        $chronology->escalate_to = $user->id;
        $chronology->level_pegtugas_id = $level;
        $chronology->tick_status = $ticket->tick_status;
        $chronology->sla_duedate = $dueDate;
        $chronology->sla_severity = $severity;
        $chronology->sla_days = 1;
        $chronology->created_by = $user->id;
        $chronology->updated_by = $user->id;
        $chronology->save();

        return $chronology;
    }

    /**
     * Clean existing test data
     */
    private function cleanTestData()
    {
        $this->warn("🧹 Cleaning existing test data...");

        // You can add logic here to delete test chronologies
        // For safety, we'll just notify

        $this->line("   Note: Manual cleanup may be required for old test data");
        $this->newLine();
    }

    /**
     * Display summary table
     */
    private function displaySummary($created, $today, $tomorrow, $yesterday, $nextMonday, $fridayBeforeMonday)
    {
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('📊 SUMMARY');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Scenario 1: REMINDER
        $this->info("1️⃣  REMINDER SCENARIO ({$tomorrow->format('l, d/m/Y')})");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->table(
            ['ID', 'Ticket', 'Officer', 'Email'],
            array_map(function ($item) {
                return [$item['id'], $item['ticket'], $item['officer'], $item['email']];
            }, $created['reminder'])
        );
        $this->line("📧 Test command: php artisan sla:test reminder");
        $this->line("✅ Expected: {$tomorrow->format('l')} emails should be sent TODAY ({$today->format('l')})");
        $this->newLine();

        // Scenario 2: OVERDUE
        $this->warn("2️⃣  OVERDUE SCENARIO ({$yesterday->format('l, d/m/Y')}) - ALREADY LATE!");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->table(
            ['ID', 'Ticket', 'Officer', 'Email'],
            array_map(function ($item) {
                return [$item['id'], $item['ticket'], $item['officer'], $item['email']];
            }, $created['overdue'])
        );
        $this->line("📧 Test command: php artisan sla:test after-due");
        $this->line("⚠️  Expected: Overdue emails should be sent TODAY ({$today->format('l')})");
        $this->newLine();

        // Scenario 3: WEEKEND
        $this->info("3️⃣  WEEKEND SCENARIO (Due: {$nextMonday->format('l, d/m/Y')})");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->table(
            ['ID', 'Ticket', 'Officer', 'Email'],
            array_map(function ($item) {
                return [$item['id'], $item['ticket'], $item['officer'], $item['email']];
            }, $created['weekend'])
        );
        $this->line("📧 Test command: php artisan sla:test reminder");
        $this->line("📅 Email should be sent on: {$fridayBeforeMonday->format('l, d/m/Y')} (Friday before Monday)");
        $this->line("🚫 Should NOT send on: {$fridayBeforeMonday->copy()->addDay()->format('l, d/m/Y')} (Saturday)");
        $this->line("🚫 Should NOT send on: {$fridayBeforeMonday->copy()->addDays(2)->format('l, d/m/Y')} (Sunday)");
        $this->newLine();

        // Test commands guide
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('🧪 HOW TO TEST');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $this->info("TODAY ({$today->format('l, d/m/Y')}) - Run these commands:");
        $this->line("  1. php artisan sla:test reminder    # Should send " . count($created['reminder']) . " emails");
        $this->line("  2. php artisan sla:test after-due   # Should send " . count($created['overdue']) . " emails");
        $this->line("  3. Check Mailpit: http://localhost:8025");
        $this->newLine();

        if ($today->isFriday() && $nextMonday->diffInDays($today) == 3) {
            $this->info("🎯 BONUS: Today is FRIDAY! Perfect for weekend testing!");
            $this->line("  - Weekend scenario emails should be sent NOW");
            $this->line("  - Check special message: 'Tarikh akhir jatuh pada hari Isnin'");
        } else {
            $this->line("⏰ WEEKEND TEST: Wait until {$fridayBeforeMonday->format('l, d/m/Y')} (Friday)");
            $this->line("  Then run: php artisan sla:test reminder");
            $this->line("  Should send " . count($created['weekend']) . " emails with Monday message");
        }

        $this->newLine();
        $this->line("📧 Total unique recipients: " . $this->countUniqueEmails($created));
        $this->line("📬 Expected emails in Mailpit: " . (count($created['reminder']) + count($created['overdue'])));
        $this->newLine();

        $this->info("✅ Test data ready! Start testing now!");
        $this->newLine();
    }

    /**
     * Count unique email addresses
     */
    private function countUniqueEmails($created)
    {
        $emails = [];
        foreach ($created as $scenario => $items) {
            foreach ($items as $item) {
                $emails[$item['email']] = true;
            }
        }
        return count($emails);
    }
}
