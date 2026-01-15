<?php

/**
 * Create test ticket for reminder testing
 * Run: php create-test-ticket.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Support\Str;
use Carbon\Carbon;

echo "=== Creating Test Ticket for Reminder ===\n\n";

try {
  // Find or get test user (you)
  $testUser = User::where('email', 'sabariah.hisham@treasury.gov.my')->first();

  if (!$testUser) {
    echo "Creating test officer user...\n";
    $testUser = User::create([
      'no_kp' => '999999999999',
      'nama' => 'Sabariah Hisham (Test)',
      'email' => 'sabariah.hisham@treasury.gov.my',
      'email_verified_at' => now(),
      'password' => bcrypt('password123'),
      'user_status' => 1,
    ]);
    echo "✓ Test officer user created: {$testUser->nama}\n";
  } else {
    echo "✓ Found existing user: {$testUser->nama}\n";
  }

  // Find complainant user (use authenticated user or first user)
  $complainant = User::where('id', '!=', $testUser->id)->first();
  if (!$complainant) {
    $complainant = $testUser; // Use same user if no other users exist
  }
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

  // Create test ticket
  $ticketNumber = 'TEST-' . now()->format('Ymd-His');

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
  echo "  No. Log: {$ticket->ticket_nolog}\n";
  echo "  Title: {$ticket->ticket_title}\n";
  echo "  Status: Aduan Baru (1)\n";
  echo "  Due Date: {$tomorrow->format('d/m/Y H:i')}\n";
  echo "  Assigned to: {$testUser->nama}\n";
  echo "  Email: {$testUser->email}\n\n";

  echo "Next steps:\n";
  echo "1. Run the reminder command:\n";
  echo "   php artisan tickets:send-duedate-reminders\n\n";
  echo "2. Check your email: {$testUser->email}\n\n";
  echo "3. Verify email configuration in .env:\n";
  echo "   MAIL_MAILER=smtp\n";
  echo "   MAIL_HOST=...\n";
  echo "   MAIL_PORT=...\n";
  echo "   MAIL_USERNAME=...\n";
  echo "   MAIL_PASSWORD=...\n\n";
} catch (\Exception $e) {
  echo "✗ ERROR: {$e->getMessage()}\n";
  echo "\nStack trace:\n{$e->getTraceAsString()}\n";
  exit(1);
}
