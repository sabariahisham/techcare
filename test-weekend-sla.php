<?php

/**
 * Test Weekend SLA Reminder Logic
 *
 * This script helps test the weekend skip functionality:
 * - Scenario: SLA due date on Monday
 * - Email should be sent on Friday (1 working day before)
 * - NO emails on Saturday/Sunday
 *
 * Usage: php test-weekend-sla.php
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
echo "║          TEST WEEKEND SLA REMINDER - SCENARIO CHECKER         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$today = Carbon::today();

echo "📅 TODAY: " . $today->format('l, d/m/Y') . "\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Determine what day today is
if ($today->isWeekday()) {
  echo "✅ Today is a WORKING DAY\n";
} else {
  echo "⚠️  Today is a WEEKEND\n";
}

echo "\n";

// Calculate next working day
$nextWorkingDay = Helpers::addWorkingDays($today, 1);
echo "📅 Next Working Day: " . $nextWorkingDay->format('l, d/m/Y') . "\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Check if next working day is Monday (meaning today might be Friday)
if ($nextWorkingDay->isMonday() && $today->isFriday()) {
  echo "🎯 PERFECT TEST SCENARIO!\n";
  echo "   Today is Friday, next working day is Monday.\n";
  echo "   This is when weekend logic applies.\n";
} elseif ($today->isSaturday() || $today->isSunday()) {
  echo "🚫 Cannot send emails today (weekend)\n";
  echo "   The scheduler should SKIP execution.\n";
} else {
  echo "ℹ️  Regular weekday scenario\n";
  echo "   Next working day is " . $nextWorkingDay->format('l') . "\n";
}

echo "\n";

// Show test data requirements
echo "📋 TO CREATE TEST DATA:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "1. Find or create a ticket in the database\n";
echo "2. Create a chronology record with these details:\n";
echo "   - sla_duedate: " . $nextWorkingDay->format('Y-m-d 09:00:00') . " (Monday if today is Friday)\n";
echo "   - escalate_to: User ID of pegawai tugasan\n";
echo "   - level_pegtugas_id: 1 or 2\n";
echo "   - ticket_id: The ticket ID\n";
echo "\n";

// Check existing test candidates
echo "🔍 CHECKING EXISTING TICKETS DUE ON " . $nextWorkingDay->format('d/m/Y') . ":\n";
echo "─────────────────────────────────────────────────────────────────\n";

$nextWorkingDayStart = $nextWorkingDay->copy()->startOfDay();
$nextWorkingDayEnd = $nextWorkingDay->copy()->endOfDay();

$chronologies = TicketChronology::whereBetween('sla_duedate', [$nextWorkingDayStart, $nextWorkingDayEnd])
  ->whereNotNull('escalate_to')
  ->with(['ticket', 'escalatedTo'])
  ->get();

if ($chronologies->count() > 0) {
  echo "✅ Found {$chronologies->count()} candidate(s):\n\n";

  foreach ($chronologies as $chrono) {
    $ticket = $chrono->ticket;
    $officer = $chrono->escalatedTo;

    echo "  Ticket: " . ($ticket ? $ticket->ticket_nolog : 'N/A') . "\n";
    echo "  Status: " . ($ticket ? $ticket->tick_status : 'N/A') . "\n";
    echo "  SLA Due: " . $chrono->sla_duedate->format('d/m/Y H:i') . " (" . $chrono->sla_duedate->format('l') . ")\n";
    echo "  Officer: " . ($officer ? $officer->nama : 'N/A') . "\n";
    echo "  Email: " . ($officer ? $officer->email : 'N/A') . "\n";

    // Check if ticket status is valid
    if ($ticket && in_array($ticket->tick_status, [5, 6])) {
      echo "  ⚠️  WARNING: Ticket status is {$ticket->tick_status} (Batal/Selesai) - will be SKIPPED\n";
    }

    // Check if officer has email
    if ($officer && empty($officer->email)) {
      echo "  ⚠️  WARNING: Officer has no email - will be SKIPPED\n";
    }

    echo "\n";
  }
} else {
  echo "❌ No test candidates found!\n";
  echo "\n";
  echo "💡 QUICK FIX - Run this SQL to create test data:\n";
  echo "─────────────────────────────────────────────────────────────────\n";
  echo "\n";

  // Find a sample ticket and user
  $sampleTicket = Ticket::whereNotIn('tick_status', [5, 6])->first();
  $sampleUser = User::whereNotNull('email')->first();

  if ($sampleTicket && $sampleUser) {
    $sqlDate = $nextWorkingDay->format('Y-m-d 09:00:00');

    echo "INSERT INTO tickets_chronologies \n";
    echo "  (ticket_id, escalate_to, level_pegtugas_id, sla_duedate, created_at, updated_at)\n";
    echo "VALUES \n";
    echo "  ({$sampleTicket->id}, {$sampleUser->id}, 1, '{$sqlDate}', NOW(), NOW());\n";
    echo "\n";
    echo "This will create a test chronology for:\n";
    echo "  - Ticket: {$sampleTicket->ticket_nolog}\n";
    echo "  - Officer: {$sampleUser->nama} ({$sampleUser->email})\n";
    echo "  - Due Date: {$sqlDate} (" . $nextWorkingDay->format('l') . ")\n";
  } else {
    echo "⚠️  Could not find sample ticket or user in database.\n";
  }
}

echo "\n";
echo "🧪 TO RUN THE TEST:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "1. Ensure test data exists (see above)\n";
echo "2. Run the command:\n";
echo "   php artisan sla:test reminder\n";
echo "\n";
echo "3. Check the results:\n";
echo "   - On WEEKDAY: Should find and send emails\n";
echo "   - On WEEKEND: Should skip with log message\n";
echo "\n";
echo "4. Check email by looking at:\n";
echo "   - storage/logs/laravel.log\n";
echo "   - storage/logs/sla.log (if configured)\n";
echo "   - Your email inbox (if MAIL is configured)\n";
echo "\n";

// Show current day's behavior
echo "📊 EXPECTED BEHAVIOR TODAY (" . $today->format('l') . "):\n";
echo "─────────────────────────────────────────────────────────────────\n";

if ($today->isSaturday() || $today->isSunday()) {
  echo "🚫 SCHEDULER WILL SKIP\n";
  echo "   - Reason: Weekend day\n";
  echo "   - No emails will be sent\n";
  echo "   - Log message will show: 'SKIPPED execution on weekend'\n";
} elseif ($today->isFriday() && $nextWorkingDay->isMonday()) {
  echo "✅ SHOULD SEND EMAIL\n";
  echo "   - Today: Friday (working day)\n";
  echo "   - Next working day: Monday\n";
  echo "   - Emails will be sent for tickets due on Monday\n";
  echo "   - Email will mention: 'Tarikh akhir jatuh pada hari Isnin'\n";
} else {
  echo "✅ SHOULD SEND EMAIL (if data exists)\n";
  echo "   - Today: " . $today->format('l') . " (working day)\n";
  echo "   - Next working day: " . $nextWorkingDay->format('l') . "\n";
  echo "   - Emails will be sent for tickets due on " . $nextWorkingDay->format('l') . "\n";
}

echo "\n";
echo "✅ Test preparation complete!\n";
echo "\n";
