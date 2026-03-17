<?php

/**
 * Create SLA Test Data
 *
 * This script creates complete test data for SLA reminder testing:
 * 1. Checks existing tickets and users
 * 2. Creates test chronology with proper sla_duedate
 * 3. Validates data is ready for testing
 *
 * Usage: php create-sla-test-data.php
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
echo "║              CREATE SLA TEST DATA - SETUP SCRIPT              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Step 1: Check for existing active tickets
echo "📋 STEP 1: Looking for active tickets...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$activeTickets = Ticket::whereNotIn('tick_status', [5, 6])
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($activeTickets->count() == 0) {
    echo "❌ No active tickets found!\n";
    echo "   You need to create a ticket first in the system.\n";
    echo "\n";
    exit(1);
}

echo "✅ Found {$activeTickets->count()} active ticket(s):\n";
foreach ($activeTickets as $ticket) {
    echo "   - #{$ticket->id}: {$ticket->ticket_nolog} (Status: {$ticket->tick_status})\n";
}
echo "\n";

// Step 2: Check for users with email
echo "👥 STEP 2: Looking for users with email...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$usersWithEmail = User::whereNotNull('email')
    ->where('email', '!=', '')
    ->orderBy('id', 'asc')
    ->limit(5)
    ->get();

if ($usersWithEmail->count() == 0) {
    echo "❌ No users with email found!\n";
    echo "   You need to add email addresses to users.\n";
    echo "\n";
    exit(1);
}

echo "✅ Found {$usersWithEmail->count()} user(s) with email:\n";
foreach ($usersWithEmail as $user) {
    echo "   - #{$user->id}: {$user->nama} ({$user->email})\n";
}
echo "\n";

// Step 3: Calculate target date
$today = Carbon::today();
$tomorrow = Helpers::addWorkingDays($today, 1);

echo "📅 STEP 3: Calculate SLA due date...\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Today:           {$today->format('l, d/m/Y')}\n";
echo "Next Working Day: {$tomorrow->format('l, d/m/Y')}\n";
echo "SLA Due Date:    {$tomorrow->format('Y-m-d 09:00:00')}\n";
echo "\n";

if ($tomorrow->isMonday() && $today->isFriday()) {
    echo "🎯 PERFECT! Today is Friday, due date is Monday.\n";
    echo "   This will test the weekend scenario!\n";
} elseif ($today->isSaturday() || $today->isSunday()) {
    echo "⚠️  WARNING: Today is weekend.\n";
    echo "   The test command will skip execution today.\n";
    echo "   But you can still create data for testing tomorrow.\n";
} else {
    echo "ℹ️  Regular weekday scenario.\n";
}
echo "\n";

// Step 4: Select ticket and user for test
$testTicket = $activeTickets->first();
$testUser = $usersWithEmail->first();

echo "🎯 STEP 4: Creating test data...\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Selected Ticket: {$testTicket->ticket_nolog} (ID: {$testTicket->id})\n";
echo "Selected User:   {$testUser->nama} (ID: {$testUser->id})\n";
echo "User Email:      {$testUser->email}\n";
echo "\n";

// Step 5: Check if test data already exists
$existingChrono = TicketChronology::where('ticket_id', $testTicket->id)
    ->whereBetween('sla_duedate', [
        $tomorrow->copy()->startOfDay(),
        $tomorrow->copy()->endOfDay()
    ])
    ->first();

if ($existingChrono) {
    echo "ℹ️  Test data already exists!\n";
    echo "   Chronology ID: {$existingChrono->id}\n";
    echo "   SLA Due Date:  {$existingChrono->sla_duedate->format('Y-m-d H:i:s')}\n";
    echo "   Officer:       " . ($existingChrono->escalatedTo ? $existingChrono->escalatedTo->nama : 'N/A') . "\n";
    echo "\n";
    echo "Do you want to create NEW test data? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));

    if (strtolower($line) !== 'y') {
        echo "Skipping creation. Using existing data.\n";
        echo "\n";
        echo "✅ Test data ready! Run: php artisan sla:test reminder\n";
        echo "\n";
        exit(0);
    }
}

// Step 6: Create test chronology
try {
    DB::beginTransaction();

    $chronology = new TicketChronology();
    $chronology->ticket_id = $testTicket->id;
    $chronology->frm_user_id = $testUser->id; // User who created the chronology
    $chronology->escalate_to = $testUser->id; // Officer assigned
    $chronology->level_pegtugas_id = 1; // Level 1
    $chronology->tick_status = $testTicket->tick_status; // Same as ticket status
    $chronology->sla_duedate = $tomorrow->setTime(9, 0, 0);
    $chronology->sla_severity = 'medium';
    $chronology->sla_days = 1; // 1 working day
    $chronology->created_by = $testUser->id;
    $chronology->updated_by = $testUser->id;
    $chronology->save();

    DB::commit();

    echo "✅ SUCCESS! Test chronology created:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    echo "Chronology ID:   {$chronology->id}\n";
    echo "Ticket:          {$testTicket->ticket_nolog}\n";
    echo "SLA Due Date:    {$chronology->sla_duedate->format('Y-m-d H:i:s')} ({$chronology->sla_duedate->format('l')})\n";
    echo "Officer:         {$testUser->nama}\n";
    echo "Officer Email:   {$testUser->email}\n";
    echo "Level:           {$chronology->level_pegtugas_id}\n";
    echo "\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR creating chronology:\n";
    echo "   {$e->getMessage()}\n";
    echo "\n";
    exit(1);
}

// Step 7: Verify data
echo "🔍 STEP 5: Verifying test data...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$tomorrowStart = $tomorrow->copy()->startOfDay();
$tomorrowEnd = $tomorrow->copy()->endOfDay();

$verifyChronologies = TicketChronology::whereBetween('sla_duedate', [$tomorrowStart, $tomorrowEnd])
    ->whereNotNull('escalate_to')
    ->with(['ticket', 'escalatedTo'])
    ->get();

echo "Total chronologies due tomorrow: {$verifyChronologies->count()}\n";
echo "\n";

foreach ($verifyChronologies as $chrono) {
    $ticket = $chrono->ticket;
    $officer = $chrono->escalatedTo;

    $status = '✅ READY';
    $issues = [];

    if (!$ticket) {
        $status = '❌ ERROR';
        $issues[] = 'Ticket not found';
    } elseif (in_array($ticket->tick_status, [5, 6])) {
        $status = '⚠️  SKIP';
        $issues[] = 'Ticket status is Batal/Selesai';
    }

    if (!$officer) {
        $status = '❌ ERROR';
        $issues[] = 'Officer not found';
    } elseif (empty($officer->email)) {
        $status = '⚠️  SKIP';
        $issues[] = 'Officer has no email';
    }

    echo "{$status} Chronology #{$chrono->id}\n";
    echo "   Ticket:  " . ($ticket ? $ticket->ticket_nolog : 'N/A') . "\n";
    echo "   Officer: " . ($officer ? $officer->nama : 'N/A') . "\n";
    echo "   Email:   " . ($officer ? $officer->email : 'N/A') . "\n";
    echo "   Due:     {$chrono->sla_duedate->format('d/m/Y H:i')} ({$chrono->sla_duedate->format('l')})\n";

    if (count($issues) > 0) {
        echo "   Issues:  " . implode(', ', $issues) . "\n";
    }

    echo "\n";
}

// Step 8: Show next steps
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                        NEXT STEPS                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "1️⃣  Test the reminder system:\n";
echo "   php artisan sla:test reminder\n";
echo "\n";
echo "2️⃣  Check if email appears in Mailpit:\n";
echo "   Open: http://localhost:8025\n";
echo "\n";
echo "3️⃣  Check application logs:\n";
echo "   tail -f storage/logs/laravel.log\n";
echo "\n";
echo "4️⃣  If today is WEEKEND, the test will show:\n";
echo "   'Not a working day' - This is CORRECT behavior!\n";
echo "   Try again on Monday.\n";
echo "\n";
echo "📧 Expected email recipient: {$testUser->email}\n";
echo "\n";
echo "✅ Setup complete!\n";
echo "\n";
