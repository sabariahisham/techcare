<?php

/*
 * Test Script: Due Date Notification
 * Purpose: Test due date notification email for a specific ticket
 * Usage: php app/Console/Commands/Ticket/test_duedate_notification.php <ticket_id>
 * Example: php app/Console/Commands/Ticket/test_duedate_notification.php 14
 */

// Guard: Only execute if this file is run directly
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
  return;
}

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Notifications\Ticket\DueDateNotification;
use Illuminate\Support\Facades\Notification;

require __DIR__ . '/../../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get ticket ID from command line
$ticketId = $argv[1] ?? null;

if (!$ticketId) {
  echo "❌ ERROR: Ticket ID is required.\n";
  echo "Usage: php test_duedate_notification.php <ticket_id>\n";
  echo "Example: php test_duedate_notification.php 14\n";
  exit(1);
}

echo "🔍 Testing Due Date Notification for Ticket ID: {$ticketId}\n";
echo str_repeat('-', 60) . "\n";

try {
  // Find the ticket
  $ticket = Ticket::with(['user', 'category', 'subCategory', 'status'])
    ->findOrFail($ticketId);

  echo "✓ Ticket found: {$ticket->ticket_nolog}\n";
  echo "  Title: {$ticket->ticket_title}\n";
  echo "  Status: {$ticket->status->st_name}\n";
  echo "  Due Date: " . ($ticket->tick_duedate ? $ticket->tick_duedate->format('d/m/Y') : 'No due date') . "\n\n";

  // Find the Level 1 officer assigned to this ticket
  $chronology = TicketChronology::where('ticket_id', $ticket->id)
    ->where('level_pegtugas_id', 1)
    ->whereNotNull('escalate_to')
    ->with('escalatedTo')
    ->latest()
    ->first();

  if (!$chronology || !$chronology->escalatedTo) {
    echo "❌ ERROR: No Level 1 officer assigned to this ticket.\n";
    echo "Please assign a Level 1 officer first in the system.\n";
    exit(1);
  }

  $officer = $chronology->escalatedTo;

  echo "👤 Level 1 Officer Found:\n";
  echo "  Name: {$officer->nama}\n";
  echo "  Email: {$officer->email}\n";
  echo "  Role: Level 1 Pegawai Tugasan\n\n";

  // Check if officer has email
  if (!$officer->email) {
    echo "❌ ERROR: Officer has no email address.\n";
    exit(1);
  }

  // Send the notification
  echo "📧 Sending due date notification...\n";
  $officer->notify(new DueDateNotification($ticket));

  echo "✅ SENT: Due date notification queued successfully!\n\n";

  echo "📋 Notification Details:\n";
  echo "  Type: Due Date Alert (Peringatan Tarikh Tamat)\n";
  echo "  To: {$officer->nama} ({$officer->email})\n";
  echo "  Ticket: {$ticket->ticket_nolog}\n";
  echo "  Due Date: " . ($ticket->tick_duedate ? $ticket->tick_duedate->format('d/m/Y') : 'N/A') . "\n\n";

  echo "🔄 Next Steps:\n";
  echo "1. Process the queue: php artisan queue:work --stop-when-empty\n";
  echo "2. Check email in Mailpit: http://127.0.0.1:8025\n";
  echo "3. Look for subject: ⚠️ Peringatan: Tiket {$ticket->ticket_nolog} Akan Tamat Tempoh\n\n";

  echo str_repeat('-', 60) . "\n";
  echo "✅ Test completed successfully!\n";
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
  echo "❌ ERROR: Ticket ID {$ticketId} not found in database.\n";
  exit(1);
} catch (\Exception $e) {
  echo "❌ ERROR: {$e->getMessage()}\n";
  echo "Stack trace:\n{$e->getTraceAsString()}\n";
  exit(1);
}
