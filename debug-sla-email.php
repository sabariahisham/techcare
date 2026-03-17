<?php

/**
 * Debug SLA Email System
 * Helps troubleshoot why emails are not being sent
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
echo "║              DEBUG SLA EMAIL SYSTEM                           ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$today = Carbon::today();
$tomorrow = Helpers::addWorkingDays($today, 1);
$tomorrowStart = $tomorrow->copy()->startOfDay();
$tomorrowEnd = $tomorrow->copy()->endOfDay();

echo "📅 Date Info:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Today:    " . $today->format('l, d/m/Y') . "\n";
echo "Tomorrow: " . $tomorrow->format('l, d/m/Y') . "\n";
echo "\n";

// Check 1: Mail Configuration
echo "✓ 1. MAIL CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n";

if (config('mail.default') === 'smtp' && config('mail.mailers.smtp.host') === 'localhost') {
    echo "📧 Mailpit detected (localhost)\n";
    echo "   URL: http://localhost:8025\n";
} else {
    echo "⚠️  Mailpit configuration not detected\n";
}
echo "\n";

// Check 2: Test Data
echo "✓ 2. TEST DATA CHECK\n";
echo "─────────────────────────────────────────────────────────────────\n";

$chronologies = TicketChronology::whereBetween('sla_duedate', [$tomorrowStart, $tomorrowEnd])
    ->whereNotNull('escalate_to')
    ->with(['ticket', 'escalatedTo', 'ticket.status'])
    ->get();

echo "Total chronologies found: " . $chronologies->count() . "\n\n";

if ($chronologies->count() === 0) {
    echo "❌ NO TEST DATA FOUND!\n";
    echo "\n";
    echo "💡 Create test data with this SQL:\n";
    echo "─────────────────────────────────────────────────────────────────\n";

    $sampleTicket = Ticket::whereNotIn('tick_status', [5, 6])->first();
    $sampleUser = User::whereNotNull('email')->first();

    if ($sampleTicket && $sampleUser) {
        $sqlDate = $tomorrow->format('Y-m-d 09:00:00');
        echo "INSERT INTO tickets_chronologies \n";
        echo "  (ticket_id, escalate_to, level_pegtugas_id, sla_duedate, created_at, updated_at)\n";
        echo "VALUES \n";
        echo "  ({$sampleTicket->id}, {$sampleUser->id}, 1, '{$sqlDate}', NOW(), NOW());\n";
        echo "\n";
    }
} else {
    foreach ($chronologies as $index => $chrono) {
        echo "Record #" . ($index + 1) . ":\n";
        echo "  Chronology ID: " . $chrono->id . "\n";
        echo "  Ticket ID: " . ($chrono->ticket_id ?? 'NULL') . "\n";
        echo "  Ticket No: " . ($chrono->ticket ? $chrono->ticket->ticket_nolog : 'N/A') . "\n";
        echo "  SLA Due Date: " . $chrono->sla_duedate->format('Y-m-d H:i:s') . "\n";
        echo "  Escalate To: " . ($chrono->escalate_to ?? 'NULL') . "\n";
        echo "  Officer Name: " . ($chrono->escalatedTo ? $chrono->escalatedTo->nama : 'N/A') . "\n";
        echo "  Officer Email: " . ($chrono->escalatedTo ? $chrono->escalatedTo->email : 'N/A') . "\n";

        // Check ticket
        if (!$chrono->ticket) {
            echo "  ❌ ERROR: Ticket not found!\n";
        } else {
            echo "  Ticket Status: " . $chrono->ticket->tick_status . " (" . ($chrono->ticket->status ? $chrono->ticket->status->st_name : 'N/A') . ")\n";

            if (in_array($chrono->ticket->tick_status, [5, 6])) {
                echo "  ⚠️  WARNING: Ticket status is Batal(5) or Selesai(6) - WILL BE SKIPPED!\n";
            } else {
                echo "  ✅ Ticket status OK\n";
            }
        }

        // Check officer
        if (!$chrono->escalatedTo) {
            echo "  ❌ ERROR: Officer not found!\n";
        } else if (empty($chrono->escalatedTo->email)) {
            echo "  ❌ ERROR: Officer has NO EMAIL!\n";
        } else {
            echo "  ✅ Officer email OK\n";
        }

        echo "\n";
    }
}

// Check 3: Working Day Status
echo "✓ 3. WORKING DAY CHECK\n";
echo "─────────────────────────────────────────────────────────────────\n";
$isWorkingDay = Helpers::isWorkingDay($today);
echo "Is today a working day? " . ($isWorkingDay ? "✅ YES" : "❌ NO") . "\n";

if (!$isWorkingDay) {
    echo "⚠️  WARNING: Today is not a working day!\n";
    echo "   The service will skip execution and return early.\n";
    echo "   No emails will be sent.\n";
}
echo "\n";

// Check 4: Database Connection
echo "✓ 4. DATABASE CONNECTION\n";
echo "─────────────────────────────────────────────────────────────────\n";
try {
    $ticketCount = DB::table('tickets')->count();
    $chronologyCount = DB::table('tickets_chronologies')->count();
    echo "✅ Database connected\n";
    echo "   Total tickets: " . $ticketCount . "\n";
    echo "   Total chronologies: " . $chronologyCount . "\n";
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check 5: Latest Chronologies Query (same as service)
echo "✓ 5. LATEST CHRONOLOGY QUERY (Same as Service Logic)\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $latestChronologies = TicketChronology::select('tickets_chronologies.*')
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
        ->with(['ticket', 'ticket.user', 'ticket.status', 'escalatedTo'])
        ->get();

    echo "Latest chronologies found: " . $latestChronologies->count() . "\n";

    if ($latestChronologies->count() === 0) {
        echo "❌ No latest chronologies match the criteria\n";
        echo "\n";
        echo "Possible reasons:\n";
        echo "  - sla_duedate not in range: " . $tomorrowStart->format('Y-m-d H:i:s') . " to " . $tomorrowEnd->format('Y-m-d H:i:s') . "\n";
        echo "  - escalate_to is NULL\n";
        echo "  - Record is soft-deleted (deleted_at is not NULL)\n";
        echo "  - Not the latest chronology for that ticket\n";
    } else {
        echo "✅ Found matching records (same as service will find)\n";
    }
} catch (\Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n";

$issues = [];

if (config('mail.default') !== 'smtp' || config('mail.mailers.smtp.host') !== 'localhost') {
    $issues[] = "Mail not configured for Mailpit";
}

if ($chronologies->count() === 0) {
    $issues[] = "No test data found";
}

if (!$isWorkingDay) {
    $issues[] = "Today is not a working day (service will skip)";
}

foreach ($chronologies as $chrono) {
    if (!$chrono->ticket) {
        $issues[] = "Chronology #{$chrono->id}: Ticket not found";
    } else if (in_array($chrono->ticket->tick_status, [5, 6])) {
        $issues[] = "Chronology #{$chrono->id}: Ticket status is Batal/Selesai";
    }

    if (!$chrono->escalatedTo) {
        $issues[] = "Chronology #{$chrono->id}: Officer not found";
    } else if (empty($chrono->escalatedTo->email)) {
        $issues[] = "Chronology #{$chrono->id}: Officer has no email";
    }
}

if (empty($issues)) {
    echo "✅ All checks passed! System should work.\n";
    echo "\n";
    echo "📝 Next steps:\n";
    echo "1. Run: php artisan sla:test reminder\n";
    echo "2. Check Mailpit: http://localhost:8025\n";
    echo "3. Check logs: tail -50 storage/logs/laravel.log\n";
} else {
    echo "❌ Found " . count($issues) . " issue(s):\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". " . $issue . "\n";
    }
    echo "\n";
    echo "Fix these issues before testing.\n";
}

echo "\n";
