<?php

// Guard: Only execute if this file is run directly
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
  return;
}

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Services\TicketNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: Email Notification to Ticket Owner & Level 1 Officer      ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Get ticket
$ticketId = $argv[1] ?? 14;
$ticket = Ticket::with(['user', 'category', 'subCategory'])->find($ticketId);

if (!$ticket) {
  echo "❌ Ticket #{$ticketId} not found.\n";
  exit(1);
}

echo "📋 Ticket Information:\n";
echo "   ID: {$ticket->id}\n";
echo "   Nolog: {$ticket->ticket_nolog}\n";
echo "   Title: {$ticket->ticket_title}\n";
echo "   Status: {$ticket->tick_status} (Aduan Baru)\n";
echo "\n";

// Check ticket owner
echo "👤 Ticket Owner (Pengadu):\n";
if ($ticket->user) {
  echo "   Name: {$ticket->user->nama}\n";
  echo "   Email: {$ticket->user->email}\n";
  echo "   ✅ Will receive notification\n";
} else {
  echo "   ❌ No owner found\n";
}
echo "\n";

// Check Level 1 officer
echo "👮 Level 1 Officer Assignment:\n";
$chronology = TicketChronology::where('ticket_id', $ticket->id)
  ->where('level_pegtugas_id', 1)
  ->whereNotNull('escalate_to')
  ->first();

if ($chronology && $chronology->escalate_to) {
  $officer = User::find($chronology->escalate_to);
  if ($officer) {
    echo "   Officer ID: {$officer->id}\n";
    echo "   Name: {$officer->nama}\n";
    echo "   Email: {$officer->email}\n";
    echo "   Level: {$chronology->level_pegtugas_id}\n";
    echo "   ✅ Will receive notification\n";
  } else {
    echo "   ❌ Officer user not found (ID: {$chronology->escalate_to})\n";
  }
} else {
  echo "   ❌ No Level 1 officer assigned\n";
}
echo "\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Send notifications
echo "📤 Sending Notifications...\n\n";

try {
  $notificationService = app(TicketNotificationService::class);
  $changedBy = $ticket->user; // Use ticket owner as changed by

  $results = $notificationService->sendNewTicketNotifications($ticket, $changedBy);

  echo "📊 Results:\n";
  echo "   Owner notification: " . ($results['owner'] ? "✅ SENT" : "❌ FAILED") . "\n";
  echo "   Officer notification: " . ($results['officer'] ? "✅ SENT" : "❌ FAILED") . "\n";

  if (!empty($results['errors'])) {
    echo "\n⚠️  Errors:\n";
    foreach ($results['errors'] as $error) {
      echo "   - {$error}\n";
    }
  }
} catch (\Exception $e) {
  echo "❌ Exception: {$e->getMessage()}\n";
  echo "\nStack trace:\n{$e->getTraceAsString()}\n";
  exit(1);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check recent logs
echo "📝 Recent Log Entries:\n\n";

$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
  $logContent = file_get_contents($logPath);
  $lines = explode("\n", $logContent);
  $recentLines = array_slice($lines, -100); // Last 100 lines

  $relevantLogs = [];
  foreach ($recentLines as $line) {
    if (
      stripos($line, 'ticket notification') !== false ||
      stripos($line, 'Level 1 officer') !== false ||
      stripos($line, "ticket_id.*{$ticket->id}") !== false
    ) {
      $relevantLogs[] = $line;
    }
  }

  if (!empty($relevantLogs)) {
    foreach (array_slice($relevantLogs, -10) as $log) {
      echo "   " . substr($log, 0, 150) . "\n";
    }
  } else {
    echo "   No relevant logs found in last 100 lines\n";
  }
} else {
  echo "   Log file not found: {$logPath}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Summary
echo "📧 Email Summary:\n";
echo "   Total emails that should be sent: 2\n";
echo "   1. To: {$ticket->user->email} (Ticket Owner)\n";

if ($chronology && $chronology->escalate_to) {
  $officer = User::find($chronology->escalate_to);
  if ($officer) {
    echo "   2. To: {$officer->email} (Level 1 Officer)\n";
  }
}

echo "\n";
echo "🔍 To verify in Mailpit (http://localhost:8025):\n";
echo "   - Check inbox for 2 emails\n";
echo "   - Both should have subject: 'Status Tiket {$ticket->ticket_nolog} Dikemaskini: Aduan Baru'\n";
echo "   - Email 1: Recipient = {$ticket->user->email}\n";
if ($chronology && $chronology->escalate_to) {
  $officer = User::find($chronology->escalate_to);
  if ($officer) {
    echo "   - Email 2: Recipient = {$officer->email}\n";
  }
}

echo "\n";
echo "✅ Test completed!\n\n";
