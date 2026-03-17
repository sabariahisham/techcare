<?php

/**
 * Create SLA Overdue Test Data
 *
 * This script creates test data for OVERDUE (after due) notification:
 * - SLA due date = YESTERDAY (1 working day ago)
 * - Email should be sent 1 working day AFTER the SLA expired
 *
 * Usage: php create-overdue-test-data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║         CREATE OVERDUE TEST DATA - SETUP SCRIPT               ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Step 1: Look for active tickets
$activeTickets = Ticket::whereNotIn('tick_status', [5, 6])
  ->orderBy('id', 'desc')
  ->limit(5)
  ->get();

if ($activeTickets->count() == 0) {
  echo "❌ No active tickets found!\n";
  exit(1);
}

// Step 2: Look for users with email
$usersWithEmail = User::whereNotNull('email')
  ->where('email', '!=', '')
  ->orderBy('id', 'asc')
  ->limit(5)
  ->get();

if ($usersWithEmail->count() == 0) {
  echo "❌ No users with email found!\n";
  exit(1);
}

// Step 3: Calculate YESTERDAY (1 working day ago)
$today = Carbon::today();
$yesterday = Helpers::subtractWorkingDays($today, 1);

echo "📅 DATE CALCULATION (OVERDUE SCENARIO):\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Today:           {$today->format('l, d/m/Y')}\n";
echo "Yesterday:       {$yesterday->format('l, d/m/Y')} (1 working day ago)\n";
echo "SLA Due Date:    {$yesterday->format('Y-m-d 09:00:00')} ⚠️ OVERDUE!\n";
echo "\n";

if ($today->isMonday() && $yesterday->isFriday()) {
  echo "🎯 PERFECT! Today is Monday, SLA was due on Friday.\n";
  echo "   This tests the weekend overdue scenario!\n";
} elseif ($today->isSaturday() || $today->isSunday()) {
  echo "⚠️  WARNING: Today is weekend.\n";
  echo "   The system will skip execution today.\n";
} else {
  echo "ℹ️  Regular weekday overdue scenario.\n";
}
echo "\n";

// Step 4: Select ticket and user
$testTicket = $activeTickets->first();
$testUser = $usersWithEmail->first();

echo "🎯 SELECTED TEST DATA:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Ticket:      {$testTicket->ticket_nolog} (ID: {$testTicket->id})\n";
echo "Officer:     {$testUser->nama} (ID: {$testUser->id})\n";
echo "Email:       {$testUser->email}\n";
echo "Status:      {$testTicket->tick_status}\n";
echo "\n";

// Step 5: Check existing overdue data
$existingChrono = TicketChronology::where('ticket_id', $testTicket->id)
  ->whereBetween('sla_duedate', [
    $yesterday->copy()->startOfDay(),
    $yesterday->copy()->endOfDay()
  ])
  ->first();

if ($existingChrono) {
  echo "ℹ️  Overdue test data already exists!\n";
  echo "   Chronology ID: {$existingChrono->id}\n";
  echo "   SLA Due Date:  {$existingChrono->sla_duedate->format('Y-m-d H:i:s')} (OVERDUE)\n";
  echo "   Officer:       " . ($existingChrono->escalatedTo ? $existingChrono->escalatedTo->nama : 'N/A') . "\n";
  echo "\n";
  echo "Create NEW test data? (y/n): ";
  $handle = fopen("php://stdin", "r");
  $line = trim(fgets($handle));

  if (strtolower($line) !== 'y') {
    echo "Using existing data.\n";
    echo "\n";
    echo "✅ Run test: php artisan sla:test after-due\n";
    echo "\n";
    exit(0);
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
  $chronology->sla_duedate = $yesterday->setTime(9, 0, 0); // YESTERDAY = OVERDUE
  $chronology->sla_severity = 'high'; // High severity for overdue
  $chronology->sla_days = 1;
  $chronology->created_by = $testUser->id;
  $chronology->updated_by = $testUser->id;
  $chronology->save();

  DB::commit();

  echo "✅ SUCCESS! Overdue test chronology created:\n";
  echo "─────────────────────────────────────────────────────────────────\n";
  echo "Chronology ID:   {$chronology->id}\n";
  echo "Ticket:          {$overdueTicket->ticket_nolog}\n";
  echo "SLA Due Date:    {$chronology->sla_duedate->format('Y-m-d H:i:s')} ({$chronology->sla_duedate->format('l')}) ⚠️\n";
  echo "Status:          OVERDUE (1 working day past due)\n";
  echo "Officer:         {$testUser->nama}\n";
  echo "Officer Email:   {$testUser->email}\n";
  echo "\n";
} catch (\Exception $e) {
  DB::rollBack();
  echo "❌ ERROR creating chronology:\n";
  echo "   {$e->getMessage()}\n";
  echo "\n";
  exit(1);
}

// Step 7: Verify overdue data
echo "🔍 VERIFICATION:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$yesterdayStart = $yesterday->copy()->startOfDay();
$yesterdayEnd = $yesterday->copy()->endOfDay();

$overdueChronologies = TicketChronology::whereBetween('sla_duedate', [$yesterdayStart, $yesterdayEnd])
  ->whereNotNull('escalate_to')
  ->with(['ticket', 'escalatedTo'])
  ->get();

echo "Total OVERDUE chronologies: {$overdueChronologies->count()}\n";
echo "\n";

foreach ($overdueChronologies as $chrono) {
  $ticket = $chrono->ticket;
  $officer = $chrono->escalatedTo;

  $status = '✅ READY';
  $issues = [];

  if (!$ticket) {
    $status = '❌ ERROR';
    $issues[] = 'Ticket not found';
  } elseif (in_array($ticket->tick_status, [5, 6])) {
    $status = '⚠️  SKIP';
    $issues[] = 'Ticket is Batal/Selesai';
  }

  if (!$officer) {
    $status = '❌ ERROR';
    $issues[] = 'Officer not found';
  } elseif (empty($officer->email)) {
    $status = '⚠️  SKIP';
    $issues[] = 'No email';
  }

  $daysOverdue = $today->diffInDays($chrono->sla_duedate);

  echo "{$status} Chronology #{$chrono->id}\n";
  echo "   Ticket:   " . ($ticket ? $ticket->ticket_nolog : 'N/A') . "\n";
  echo "   Officer:  " . ($officer ? $officer->nama : 'N/A') . "\n";
  echo "   Email:    " . ($officer ? $officer->email : 'N/A') . "\n";
  echo "   Due Date: {$chrono->sla_duedate->format('d/m/Y H:i')} ({$chrono->sla_duedate->format('l')})\n";
  echo "   Status:   🔴 OVERDUE ({$daysOverdue} day" . ($daysOverdue > 1 ? 's' : '') . " late)\n";

  if (count($issues) > 0) {
    echo "   Issues:   " . implode(', ', $issues) . "\n";
  }

  echo "\n";
}

// Step 8: Show next steps
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST OVERDUE EMAIL                         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "1️⃣  Test OVERDUE notification:\n";
echo "   php artisan sla:test after-due\n";
echo "\n";
echo "2️⃣  Test BOTH (reminder + overdue):\n";
echo "   php artisan sla:test all\n";
echo "\n";
echo "3️⃣  Check Mailpit for email:\n";
echo "   http://localhost:8025\n";
echo "   Look for subject: '🚨 SLA Overdue' or similar\n";
echo "\n";
echo "4️⃣  Expected behavior:\n";
echo "   - Email subject: Overdue warning\n";
echo "   - Email sent to: {$testUser->email}\n";
echo "   - Message: Ticket is now overdue\n";
echo "\n";
echo "✅ Overdue test data ready!\n";
echo "\n";
