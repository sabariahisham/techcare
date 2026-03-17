<?php

/**
 * Create SLA Overdue Test Data
 *
 * This command creates test data for OVERDUE (after due) notification:
 * - SLA due date = YESTERDAY (1 working day ago)
 * - Email should be sent 1 working day AFTER the SLA expired
 *
 * Usage: php artisan sla:create-overdue-data
 */

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateOverdueTestData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'sla:create-overdue-data {--force : Force creation even if data exists}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create test data for SLA overdue (after due) notifications';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('');
    $this->line('╔═══════════════════════════════════════════════════════════════╗');
    $this->line('║         CREATE OVERDUE TEST DATA - SETUP SCRIPT               ║');
    $this->line('╚═══════════════════════════════════════════════════════════════╝');
    $this->info('');

    // Step 1: Look for active tickets
    $activeTickets = Ticket::whereNotIn('tick_status', [5, 6])
      ->orderBy('id', 'desc')
      ->limit(5)
      ->get();

    if ($activeTickets->count() == 0) {
      $this->error("❌ No active tickets found!");
      return Command::FAILURE;
    }

    // Step 2: Look for users with email
    $usersWithEmail = User::whereNotNull('email')
      ->where('email', '!=', '')
      ->orderBy('id', 'asc')
      ->limit(5)
      ->get();

    if ($usersWithEmail->count() == 0) {
      $this->error("❌ No users with email found!");
      return Command::FAILURE;
    }

    // Step 3: Calculate YESTERDAY (1 working day ago)
    $today = Carbon::today();
    $yesterday = Helpers::subtractWorkingDays($today, 1);

    $this->info("📅 DATE CALCULATION (OVERDUE SCENARIO):");
    $this->line('─────────────────────────────────────────────────────────────────');
    $this->line("Today:           {$today->format('l, d/m/Y')}");
    $this->line("Yesterday:       {$yesterday->format('l, d/m/Y')} (1 working day ago)");
    $this->line("SLA Due Date:    {$yesterday->format('Y-m-d 09:00:00')} ⚠️ OVERDUE!");
    $this->newLine();

    if ($today->isMonday() && $yesterday->isFriday()) {
      $this->info("🎯 PERFECT! Today is Monday, SLA was due on Friday.");
      $this->line("   This tests the weekend overdue scenario!");
    } elseif ($today->isSaturday() || $today->isSunday()) {
      $this->warn("⚠️  WARNING: Today is weekend.");
      $this->line("   The system will skip execution today.");
    } else {
      $this->info("ℹ️  Regular weekday overdue scenario.");
    }
    $this->newLine();

    // Step 4: Select ticket and user
    $testTicket = $activeTickets->first();
    $testUser = $usersWithEmail->first();

    $this->info("🎯 SELECTED TEST DATA:");
    $this->line('─────────────────────────────────────────────────────────────────');
    $this->line("Ticket:      {$testTicket->ticket_nolog} (ID: {$testTicket->id})");
    $this->line("Officer:     {$testUser->nama} (ID: {$testUser->id})");
    $this->line("Email:       {$testUser->email}");
    $this->line("Status:      {$testTicket->tick_status}");
    $this->newLine();

    // Step 5: Check existing overdue data
    $existingChrono = TicketChronology::where('ticket_id', $testTicket->id)
      ->whereBetween('sla_duedate', [
        $yesterday->copy()->startOfDay(),
        $yesterday->copy()->endOfDay()
      ])
      ->first();

    if ($existingChrono && !$this->option('force')) {
      $this->info("ℹ️  Overdue test data already exists!");
      $this->line("   Chronology ID: {$existingChrono->id}");
      $this->line("   SLA Due Date:  {$existingChrono->sla_duedate->format('Y-m-d H:i:s')} (OVERDUE)");
      $this->line("   Officer:       " . ($existingChrono->escalatedTo ? $existingChrono->escalatedTo->nama : 'N/A'));
      $this->newLine();

      if (!$this->confirm('Create NEW test data?', false)) {
        $this->info("Using existing data.");
        $this->newLine();
        $this->info("✅ Run test: php artisan sla:test after-due");
        $this->newLine();
        return Command::SUCCESS;
      }
    }

    // Step 6: Create overdue chronology
    try {
      DB::beginTransaction();

      // Use a different ticket to avoid conflicts
      $overdueTicket = $activeTickets->count() > 1 ? $activeTickets[1] : $activeTickets->first();

      $chronology = new TicketChronology();
      $chronology->ticket_id = $overdueTicket->id;
      $chronology->frm_user_id = $testUser->id;
      $chronology->escalate_to = $testUser->id;
      $chronology->level_pegtugas_id = 1;
      $chronology->tick_status = $overdueTicket->tick_status;
      $chronology->sla_duedate = $yesterday->setTime(9, 0, 0);
      $chronology->sla_severity = 'high';
      $chronology->sla_days = 1;
      $chronology->created_by = $testUser->id;
      $chronology->updated_by = $testUser->id;
      $chronology->save();

      DB::commit();

      $this->info("✅ SUCCESS! Overdue test chronology created:");
      $this->line('─────────────────────────────────────────────────────────────────');
      $this->line("Chronology ID:   {$chronology->id}");
      $this->line("Ticket:          {$overdueTicket->ticket_nolog}");
      $this->line("SLA Due Date:    {$chronology->sla_duedate->format('Y-m-d H:i:s')} ({$chronology->sla_duedate->format('l')}) ⚠️");
      $this->line("Status:          OVERDUE (1 working day past due)");
      $this->line("Officer:         {$testUser->nama}");
      $this->line("Officer Email:   {$testUser->email}");
      $this->newLine();
    } catch (\Exception $e) {
      DB::rollBack();
      $this->error("❌ ERROR creating chronology:");
      $this->line("   {$e->getMessage()}");
      $this->newLine();
      return Command::FAILURE;
    }

    // Step 7: Verify overdue data
    $this->info("🔍 VERIFICATION:");
    $this->line('─────────────────────────────────────────────────────────────────');

    $yesterdayStart = $yesterday->copy()->startOfDay();
    $yesterdayEnd = $yesterday->copy()->endOfDay();

    $overdueChronologies = TicketChronology::whereBetween('sla_duedate', [$yesterdayStart, $yesterdayEnd])
      ->whereNotNull('escalate_to')
      ->with(['ticket', 'escalatedTo'])
      ->get();

    $this->line("Total OVERDUE chronologies: {$overdueChronologies->count()}");
    $this->newLine();

    $tableData = [];
    foreach ($overdueChronologies as $chrono) {
      $ticket = $chrono->ticket;
      $officer = $chrono->escalatedTo;

      $status = '✅ Ready';
      $issues = [];

      if (!$ticket) {
        $status = '❌ ERROR';
        $issues[] = 'Ticket not found';
      } elseif (in_array($ticket->tick_status, [5, 6])) {
        $status = '⚠️  Skip';
        $issues[] = 'Batal/Selesai';
      }

      if (!$officer) {
        $status = '❌ ERROR';
        $issues[] = 'Officer not found';
      } elseif (empty($officer->email)) {
        $status = '⚠️  Skip';
        $issues[] = 'No email';
      }

      $tableData[] = [
        $chrono->id,
        $ticket ? $ticket->ticket_nolog : 'N/A',
        $officer ? $officer->nama : 'N/A',
        $officer ? $officer->email : 'N/A',
        $chrono->sla_duedate->format('d/m/Y H:i'),
        $status . (count($issues) > 0 ? ' (' . implode(', ', $issues) . ')' : '')
      ];
    }

    $this->table(['ID', 'Ticket', 'Officer', 'Email', 'Due Date', 'Status'], $tableData);
    $this->newLine();

    // Step 8: Show next steps
    $this->line('╔═══════════════════════════════════════════════════════════════╗');
    $this->line('║                    TEST OVERDUE EMAIL                         ║');
    $this->line('╚═══════════════════════════════════════════════════════════════╝');
    $this->newLine();
    $this->info("1️⃣  Test OVERDUE notification:");
    $this->line("   php artisan sla:test after-due");
    $this->newLine();
    $this->info("2️⃣  Test BOTH (reminder + overdue):");
    $this->line("   php artisan sla:test all");
    $this->newLine();
    $this->info("3️⃣  Check Mailpit for email:");
    $this->line("   http://localhost:8025");
    $this->line("   Look for subject: '🚨 SLA Overdue' or similar");
    $this->newLine();
    $this->info("📧 Expected email recipient: {$testUser->email}");
    $this->newLine();
    $this->info("✅ Overdue test data ready!");
    $this->newLine();

    return Command::SUCCESS;
  }
}
