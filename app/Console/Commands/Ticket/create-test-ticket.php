<?php

/**
 * Create test ticket for reminder testing
 * Run: php app/Console/Commands/Ticket/create-test-ticket.php
 */

// Guard: Only execute if this file is run directly
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
  return;
}

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Support\Str;
use Carbon\Carbon;

require __DIR__ . '/../../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Creating Test Ticket for Reminder ===\n\n";

try {
  // Get user ID from command line or use a default
  $userId = $argv[1] ?? null;

  if (!$userId) {
    echo "❌ ERROR: User ID is required.\n";
    echo "Usage: php app/Console/Commands/Ticket/create-test-ticket.php <user_id>\n";
    echo "Example: php app/Console/Commands/Ticket/create-test-ticket.php 1\n";
    exit(1);
  }

  // Find the user
  $testUser = User::find($userId);

  if (!$testUser) {
    echo "❌ ERROR: User ID {$userId} not found in database.\n";
    exit(1);
  }

  echo "✓ Found user: {$testUser->nama}\n";
  echo "✓ Email: {$testUser->email}\n";

  // Use same user as complainant and officer
  $complainant = $testUser;
  echo "✓ Complainant: {$complainant->nama}\n";

  // Get first category and subcategory
  $category = RefCategory::first();
  $subCategory = RefSubCategory::where('cat_id', $category->id)->first();

  if (!$category || !$subCategory) {
    echo "✗ ERROR: No categories/subcategories found in database.\n";
    echo "Please ensure ref_categories and ref_sub_categories tables have data.\n";
    exit(1);
  }

  echo "✓ Category: {$category->cat_name}\n";
  echo "✓ SubCategory: {$subCategory->subcat_name}\n";

  // Calculate tomorrow's due date
  $tomorrow = Carbon::tomorrow()->setTime(17, 0, 0); // Set to 5 PM tomorrow
  echo "✓ Due Date: {$tomorrow->format('d/m/Y H:i')}\n\n";

  // Get the next ticket counter
  $lastTicket = Ticket::whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->orderBy('id', 'desc')
    ->first();

  $counter = 1;
  if ($lastTicket) {
    // Extract counter from last ticket number
    preg_match('/HELP-\d{4}-\d{2}-(\d{4})/', $lastTicket->ticket_nolog, $matches);
    if (isset($matches[1])) {
      $counter = intval($matches[1]) + 1;
    }
  }

  // Create test ticket number in format HELP-YYYY-MM-XXXX-Test
  $ticketNumber = sprintf('HELP-%s-%04d-Test', now()->format('Y-m'), $counter);

  $ticket = Ticket::create([
    'uuid' => Str::uuid(),
    'ticket_nolog' => $ticketNumber,
    'user_id' => $complainant->id,
    'cat_id' => $category->id,
    'subcat_id' => $subCategory->id,
    'ticket_title' => 'Test Ticket - Email Reminder',
    'ticket_desc' => 'Ini adalah tiket ujian untuk menguji sistem email reminder. Tiket ini akan tamat tempoh esok.',
    'ticket_type' => 1, // Diri sendiri
    'tick_status' => 1, // Aduan Baru
    'tick_duedate' => $tomorrow,
    'created_by' => $complainant->id,
    'created_at' => now(),
  ]);

  echo "✓ Ticket created: {$ticket->ticket_nolog}\n";

  // Create chronology entry with level 1 officer assignment
  $chronology = TicketChronology::create([
    'ticket_id' => $ticket->id,
    'frm_user_id' => $complainant->id,
    'level_pegtugas_id' => 1, // Level 1 - IMPORTANT for reminder
    'tick_status' => 1,
    'escalate_to' => $testUser->id, // Assigned to you
    'sla_duedate' => $tomorrow,
    'sla_days' => 3,
    'sla_severity' => 'Medium',
    'created_by' => $complainant->id,
    'updated_by' => $complainant->id,
  ]);

  echo "✓ Chronology created with level 1 officer assignment\n";
  echo "✓ Assigned to: {$testUser->nama} ({$testUser->email})\n\n";

  echo "=== Test Ticket Created Successfully ===\n\n";
  echo "Ticket Details:\n";
  echo "  Ticket ID: {$ticket->id}\n";
  echo "  No. Log: {$ticket->ticket_nolog}\n";
  echo "  Title: {$ticket->ticket_title}\n";
  echo "  Status: Aduan Baru (1)\n";
  echo "  Due Date: {$tomorrow->format('d/m/Y H:i')}\n";
  echo "  Assigned to: {$testUser->nama}\n";
  echo "  Email: {$testUser->email}\n\n";

  echo "📊 Database Records:\n";
  echo "  ✓ Ticket ID {$ticket->id} created in 'tickets' table\n";
  echo "  ✓ Chronology ID {$chronology->id} created in 'ticket_chronologies' table\n\n";

  echo "Next steps to test email:\n";
  echo "1. Run due date notification test:\n";
  echo "   php app/Console/Commands/Ticket/test_duedate_notification.php {$ticket->id}\n\n";
  echo "2. Process the queue:\n";
  echo "   php artisan queue:work --stop-when-empty\n\n";
  echo "3. Check email in Mailpit: http://127.0.0.1:8025\n\n";
} catch (\Exception $e) {
  echo "✗ ERROR: {$e->getMessage()}\n";
  echo "\nStack trace:\n{$e->getTraceAsString()}\n";
  exit(1);
}
