<?php

/**
 * Create SLA Test Data
 *
 * This command creates complete test data for SLA reminder testing:
 * 1. Checks existing tickets and users
 * 2. Creates test chronology with proper sla_duedate
 * 3. Validates data is ready for testing
 *
 * Usage: php artisan sla:create-test-data
 */

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateSLATestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:create-test-data {--force : Force creation even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test data for SLA reminder notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║              CREATE SLA TEST DATA - SETUP SCRIPT              ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Step 1: Check for existing active tickets
        $this->info("📋 STEP 1: Looking for active tickets...");
        $this->line('─────────────────────────────────────────────────────────────────');

        $activeTickets = Ticket::whereNotIn('tick_status', [5, 6])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        if ($activeTickets->count() == 0) {
            $this->error("❌ No active tickets found!");
            $this->line("   You need to create a ticket first in the system.");
            return Command::FAILURE;
        }

        $this->info("✅ Found {$activeTickets->count()} active ticket(s)");
        $this->newLine();

        // Step 2: Check for users with email
        $this->info("👥 STEP 2: Looking for users with email...");
        $this->line('─────────────────────────────────────────────────────────────────');

        $usersWithEmail = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        if ($usersWithEmail->count() == 0) {
            $this->error("❌ No users with email found!");
            $this->line("   You need to add email addresses to users.");
            return Command::FAILURE;
        }

        $this->info("✅ Found {$usersWithEmail->count()} user(s) with email");
        $this->newLine();

        // Step 3: Calculate target date
        $today = Carbon::today();
        $tomorrow = Helpers::addWorkingDays($today, 1);

        $this->info("📅 STEP 3: Calculate SLA due date...");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("Today:            {$today->format('l, d/m/Y')}");
        $this->line("Next Working Day: {$tomorrow->format('l, d/m/Y')}");
        $this->line("SLA Due Date:     {$tomorrow->format('Y-m-d 09:00:00')}");
        $this->newLine();

        if ($tomorrow->isMonday() && $today->isFriday()) {
            $this->info("🎯 PERFECT! Today is Friday, due date is Monday.");
            $this->line("   This will test the weekend scenario!");
        } elseif ($today->isSaturday() || $today->isSunday()) {
            $this->warn("⚠️  WARNING: Today is weekend.");
            $this->line("   The test command will skip execution today.");
            $this->line("   But you can still create data for testing tomorrow.");
        } else {
            $this->info("ℹ️  Regular weekday scenario.");
        }
        $this->newLine();

        // Step 4: Select ticket and user for test
        $testTicket = $activeTickets->first();
        $testUser = $usersWithEmail->first();

        $this->info("🎯 STEP 4: Creating test data...");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("Selected Ticket: {$testTicket->ticket_nolog} (ID: {$testTicket->id})");
        $this->line("Selected User:   {$testUser->nama} (ID: {$testUser->id})");
        $this->line("User Email:      {$testUser->email}");
        $this->newLine();

        // Step 5: Check if test data already exists
        $existingChrono = TicketChronology::where('ticket_id', $testTicket->id)
            ->whereBetween('sla_duedate', [
                $tomorrow->copy()->startOfDay(),
                $tomorrow->copy()->endOfDay()
            ])
            ->first();

        if ($existingChrono && !$this->option('force')) {
            $this->info("ℹ️  Test data already exists!");
            $this->line("   Chronology ID: {$existingChrono->id}");
            $this->line("   SLA Due Date:  {$existingChrono->sla_duedate->format('Y-m-d H:i:s')}");
            $this->line("   Officer:       " . ($existingChrono->escalatedTo ? $existingChrono->escalatedTo->nama : 'N/A'));
            $this->newLine();

            if (!$this->confirm('Create NEW test data?', false)) {
                $this->info("Using existing data.");
                $this->newLine();
                $this->info("✅ Test data ready! Run: php artisan sla:test reminder");
                $this->newLine();
                return Command::SUCCESS;
            }
        }

        // Step 6: Create test chronology
        try {
            DB::beginTransaction();

            $chronology = new TicketChronology();
            $chronology->ticket_id = $testTicket->id;
            $chronology->frm_user_id = $testUser->id;
            $chronology->escalate_to = $testUser->id;
            $chronology->level_pegtugas_id = 1;
            $chronology->tick_status = $testTicket->tick_status;
            $chronology->sla_duedate = $tomorrow->setTime(9, 0, 0);
            $chronology->sla_severity = 'medium';
            $chronology->sla_days = 1;
            $chronology->created_by = $testUser->id;
            $chronology->updated_by = $testUser->id;
            $chronology->save();

            DB::commit();

            $this->info("✅ SUCCESS! Test chronology created:");
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->line("Chronology ID:   {$chronology->id}");
            $this->line("Ticket:          {$testTicket->ticket_nolog}");
            $this->line("SLA Due Date:    {$chronology->sla_duedate->format('Y-m-d H:i:s')} ({$chronology->sla_duedate->format('l')})");
            $this->line("Officer:         {$testUser->nama}");
            $this->line("Officer Email:   {$testUser->email}");
            $this->line("Level:           {$chronology->level_pegtugas_id}");
            $this->newLine();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ ERROR creating chronology:");
            $this->line("   {$e->getMessage()}");
            $this->newLine();
            return Command::FAILURE;
        }

        // Step 7: Show next steps
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║                        NEXT STEPS                             ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info("1️⃣  Test the reminder system:");
        $this->line("   php artisan sla:test reminder");
        $this->newLine();
        $this->info("2️⃣  Check if email appears in Mailpit:");
        $this->line("   Open: http://localhost:8025");
        $this->newLine();
        $this->info("3️⃣  Check application logs:");
        $this->line("   tail -f storage/logs/laravel.log");
        $this->newLine();
        $this->info("📧 Expected email recipient: {$testUser->email}");
        $this->newLine();
        $this->info("✅ Setup complete!");
        $this->newLine();

        return Command::SUCCESS;
    }
}
