<?php

/**
 * Test SLA Notification System
 *
 * This script tests:
 * 1. REMINDER: Tickets with sla_duedate = tomorrow (1 working day from today)
 * 2. OVERDUE: Tickets with sla_duedate = yesterday (1 working day ago)
 *
 * Usage: php test-sla-notifications.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           SLA NOTIFICATION SYSTEM - TEST SCRIPT            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Calculate dates
$today = Carbon::today();
$tomorrow = Helpers::addWorkingDays($today, 1);
$yesterday = Helpers::subtractWorkingDays($today, 1);

echo "📅 Date Information:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "Today:     {$today->format('Y-m-d (l)')}\n";
echo "Tomorrow:  {$tomorrow->format('Y-m-d (l)')} (working day)\n";
echo "Yesterday: {$yesterday->format('Y-m-d (l)')} (working day)\n";
echo "\n";

// Test 1: Check REMINDER candidates (due tomorrow)
echo "🔍 TEST 1: REMINDER Candidates (sla_duedate = tomorrow)\n";
echo "─────────────────────────────────────────────────────────────\n";

$tomorrowStart = $tomorrow->copy()->startOfDay();
$tomorrowEnd = $tomorrow->copy()->endOfDay();

$reminderCandidates = TicketChronology::select('tickets_chronologies.*')
  ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                    FROM tickets_chronologies
                    WHERE deleted_at IS NULL
                    GROUP BY ticket_id) latest'), function ($join) {
    $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
      ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
  })
  ->whereBetween('tickets_chronologies.sla_duedate', [$tomorrowStart, $tomorrowEnd])
  ->whereNotNull('tickets_chronologies.escalate_to')
  ->whereNull('tickets_chronologies.deleted_at')
  ->with(['ticket', 'ticket.status', 'escalatedTo'])
  ->get();

echo "Found: {$reminderCandidates->count()} ticket(s)\n\n";

if ($reminderCandidates->count() > 0) {
  echo "Details:\n";
  foreach ($reminderCandidates as $chronology) {
    $ticket = $chronology->ticket;
    $officer = $chronology->escalatedTo;

    echo "├─ Ticket: {$ticket->ticket_nolog}\n";
    echo "│  Status: {$ticket->tick_status} (" . ($ticket->status->st_name ?? 'N/A') . ")\n";
    echo "│  SLA Due: {$chronology->sla_duedate}\n";
    echo "│  Officer: " . ($officer ? $officer->nama : 'N/A') . "\n";
    echo "│  Email: " . ($officer ? $officer->email : 'N/A') . "\n";

    // Check if will be sent
    $willSend = true;
    $reason = '';

    if (in_array($ticket->tick_status, [5, 6])) {
      $willSend = false;
      $reason = 'Status is Batal (5) or Selesai (6)';
    } elseif (!$chronology->escalate_to || !$officer) {
      $willSend = false;
      $reason = 'No assigned officer';
    } elseif (empty($officer->email)) {
      $willSend = false;
      $reason = 'Officer has no email';
    }

    if ($willSend) {
      echo "│  ✅ WILL SEND REMINDER\n";
    } else {
      echo "│  ⊘ WILL SKIP: {$reason}\n";
    }
    echo "│\n";
  }
} else {
  echo "ℹ️  No tickets found with sla_duedate = {$tomorrow->format('Y-m-d')}\n";
  echo "\n";
  echo "💡 To test REMINDER notifications:\n";
  echo "   1. Create/update a ticket chronology with:\n";
  echo "      - sla_duedate = '{$tomorrow->format('Y-m-d')}'\n";
  echo "      - escalate_to = [valid user_id]\n";
  echo "      - tick_status != 5 (Batal) or 6 (Selesai)\n";
  echo "   2. Run: php artisan sla:send-notifications --type=reminder\n";
}

echo "\n";

// Test 2: Check OVERDUE candidates (due yesterday)
echo "🔍 TEST 2: OVERDUE Candidates (sla_duedate = yesterday)\n";
echo "─────────────────────────────────────────────────────────────\n";

$yesterdayStart = $yesterday->copy()->startOfDay();
$yesterdayEnd = $yesterday->copy()->endOfDay();

$overdueCandidates = TicketChronology::select('tickets_chronologies.*')
  ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                    FROM tickets_chronologies
                    WHERE deleted_at IS NULL
                    GROUP BY ticket_id) latest'), function ($join) {
    $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
      ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
  })
  ->whereBetween('tickets_chronologies.sla_duedate', [$yesterdayStart, $yesterdayEnd])
  ->whereNotNull('tickets_chronologies.escalate_to')
  ->whereNull('tickets_chronologies.deleted_at')
  ->with(['ticket', 'ticket.status', 'escalatedTo'])
  ->get();

echo "Found: {$overdueCandidates->count()} ticket(s)\n\n";

if ($overdueCandidates->count() > 0) {
  echo "Details:\n";
  foreach ($overdueCandidates as $chronology) {
    $ticket = $chronology->ticket;
    $officer = $chronology->escalatedTo;

    echo "├─ Ticket: {$ticket->ticket_nolog}\n";
    echo "│  Status: {$ticket->tick_status} (" . ($ticket->status->st_name ?? 'N/A') . ")\n";
    echo "│  SLA Due: {$chronology->sla_duedate}\n";
    echo "│  Officer: " . ($officer ? $officer->nama : 'N/A') . "\n";
    echo "│  Email: " . ($officer ? $officer->email : 'N/A') . "\n";

    // Check if will be sent
    $willSend = true;
    $reason = '';

    if (in_array($ticket->tick_status, [5, 6])) {
      $willSend = false;
      $reason = 'Status is Batal (5) or Selesai (6)';
    } elseif (!$chronology->escalate_to || !$officer) {
      $willSend = false;
      $reason = 'No assigned officer';
    } elseif (empty($officer->email)) {
      $willSend = false;
      $reason = 'Officer has no email';
    }

    if ($willSend) {
      echo "│  ✅ WILL SEND OVERDUE\n";
    } else {
      echo "│  ⊘ WILL SKIP: {$reason}\n";
    }
    echo "│\n";
  }
} else {
  echo "ℹ️  No tickets found with sla_duedate = {$yesterday->format('Y-m-d')}\n";
  echo "\n";
  echo "💡 To test OVERDUE notifications:\n";
  echo "   1. Create/update a ticket chronology with:\n";
  echo "      - sla_duedate = '{$yesterday->format('Y-m-d')}'\n";
  echo "      - escalate_to = [valid user_id]\n";
  echo "      - tick_status != 5 (Batal) or 6 (Selesai)\n";
  echo "   2. Run: php artisan sla:send-notifications --type=overdue\n";
}

echo "\n";

// Test 3: Check all tickets with SLA due dates
echo "📊 STATISTICS: All Tickets with SLA Due Dates\n";
echo "─────────────────────────────────────────────────────────────\n";

$allWithSLA = TicketChronology::select('tickets_chronologies.*')
  ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                    FROM tickets_chronologies
                    WHERE deleted_at IS NULL
                    GROUP BY ticket_id) latest'), function ($join) {
    $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
      ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
  })
  ->whereNotNull('tickets_chronologies.sla_duedate')
  ->whereNull('tickets_chronologies.deleted_at')
  ->with(['ticket', 'ticket.status'])
  ->get();

echo "Total tickets with sla_duedate: {$allWithSLA->count()}\n";

// Group by status
$byStatus = $allWithSLA->groupBy(function ($item) {
  return $item->ticket->tick_status;
});

echo "\nBreakdown by Status:\n";
foreach ($byStatus as $status => $tickets) {
  $statusName = ($tickets->first()->ticket->status->st_name ?? "Status {$status}");
  echo "  Status {$status} ({$statusName}): {$tickets->count()} ticket(s)\n";
}

// Check upcoming due dates
echo "\n";
echo "📅 Upcoming SLA Due Dates (Next 7 days):\n";
$next7Days = $allWithSLA->filter(function ($chronology) {
  $dueDate = Carbon::parse($chronology->sla_duedate);
  return $dueDate->between(Carbon::today(), Carbon::today()->addDays(7));
})->sortBy('sla_duedate');

if ($next7Days->count() > 0) {
  foreach ($next7Days as $chronology) {
    $ticket = $chronology->ticket;
    $dueDate = Carbon::parse($chronology->sla_duedate);
    $daysUntil = Carbon::today()->diffInDays($dueDate, false);

    echo "  {$dueDate->format('Y-m-d')} ({$daysUntil} days) - {$ticket->ticket_nolog} (Status: {$ticket->tick_status})\n";
  }
} else {
  echo "  No tickets due in next 7 days\n";
}

// Check overdue tickets
echo "\n";
echo "⚠️  Overdue Tickets (Already Past Due Date):\n";
$overdueTickets = $allWithSLA->filter(function ($chronology) {
  $dueDate = Carbon::parse($chronology->sla_duedate);
  return $dueDate->lt(Carbon::today()) && !in_array($chronology->ticket->tick_status, [5, 6]);
})->sortBy('sla_duedate');

if ($overdueTickets->count() > 0) {
  foreach ($overdueTickets as $chronology) {
    $ticket = $chronology->ticket;
    $dueDate = Carbon::parse($chronology->sla_duedate);
    $daysOverdue = $dueDate->diffInDays(Carbon::today());

    echo "  {$dueDate->format('Y-m-d')} ({$daysOverdue} days overdue) - {$ticket->ticket_nolog} (Status: {$ticket->tick_status})\n";
  }
} else {
  echo "  No overdue tickets\n";
}

echo "\n";

// Test 4: Run the actual notification commands
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST EXECUTION                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "💡 To run actual notifications:\n";
echo "\n";
echo "1️⃣  Test REMINDER (1 day before):\n";
echo "   php artisan sla:send-notifications --type=reminder\n";
echo "\n";
echo "2️⃣  Test OVERDUE (1 day after):\n";
echo "   php artisan sla:send-notifications --type=overdue\n";
echo "\n";
echo "3️⃣  Test BOTH:\n";
echo "   php artisan sla:send-notifications --type=all\n";
echo "\n";
echo "4️⃣  View logs:\n";
echo "   Get-Content storage\\logs\\laravel-" . date('Y-m-d') . ".log -Tail 50\n";
echo "\n";

// Sample SQL queries
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              USEFUL SQL QUERIES FOR TESTING                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "🔍 Check REMINDER candidates (manual query):\n";
echo "───────────────────────────────────────────────────────────\n";
echo "SELECT t.ticket_nolog, t.tick_status, tc.sla_duedate, \n";
echo "       u.nama, u.email\n";
echo "FROM tickets t\n";
echo "INNER JOIN (\n";
echo "    SELECT ticket_id, MAX(created_at) as max_created\n";
echo "    FROM tickets_chronologies\n";
echo "    WHERE deleted_at IS NULL\n";
echo "    GROUP BY ticket_id\n";
echo ") latest ON t.id = latest.ticket_id\n";
echo "INNER JOIN tickets_chronologies tc \n";
echo "    ON tc.ticket_id = latest.ticket_id \n";
echo "    AND tc.created_at = latest.max_created\n";
echo "LEFT JOIN users u ON u.id = tc.escalate_to\n";
echo "WHERE DATE(tc.sla_duedate) = '{$tomorrow->format('Y-m-d')}'\n";
echo "    AND tc.escalate_to IS NOT NULL\n";
echo "    AND tc.deleted_at IS NULL\n";
echo "    AND t.tick_status NOT IN (5, 6); -- 5=Batal, 6=Selesai\n";
echo "\n";

echo "🔍 Check OVERDUE candidates (manual query):\n";
echo "───────────────────────────────────────────────────────────\n";
echo "SELECT t.ticket_nolog, t.tick_status, tc.sla_duedate, \n";
echo "       u.nama, u.email\n";
echo "FROM tickets t\n";
echo "INNER JOIN (\n";
echo "    SELECT ticket_id, MAX(created_at) as max_created\n";
echo "    FROM tickets_chronologies\n";
echo "    WHERE deleted_at IS NULL\n";
echo "    GROUP BY ticket_id\n";
echo ") latest ON t.id = latest.ticket_id\n";
echo "INNER JOIN tickets_chronologies tc \n";
echo "    ON tc.ticket_id = latest.ticket_id \n";
echo "    AND tc.created_at = latest.max_created\n";
echo "LEFT JOIN users u ON u.id = tc.escalate_to\n";
echo "WHERE DATE(tc.sla_duedate) = '{$yesterday->format('Y-m-d')}'\n";
echo "    AND tc.escalate_to IS NOT NULL\n";
echo "    AND tc.deleted_at IS NULL\n";
echo "    AND t.tick_status NOT IN (5, 6); -- 5=Batal, 6=Selesai\n";
echo "\n";

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                     TEST COMPLETE                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
