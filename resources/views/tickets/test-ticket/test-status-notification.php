<?php

/**
 * Test script for status change notifications
 * Run: php test-status-notification.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Support\Str;
use Carbon\Carbon;

echo "=== Testing Status Change Notification ===\n\n";

try {
  // Find test user (you)
  $testUser = User::where('email', 'sabariah.hisham@treasury.gov.my')->first();

  if (!$testUser) {
    echo "✗ Test user not found. Creating...\n";
    $testUser = User::create([
      'no_kp' => '999999999999',
      'nama' => 'Sabariah Hisham (Test)',
      'email' => 'sabariah.hisham@treasury.gov.my',
      'email_verified_at' => now(),
      'password' => bcrypt('password123'),
      'user_status' => 1,
    ]);
    echo "✓ Test user created\n";
  } else {
    echo "✓ Test user found: {$testUser->nama}\n";
  }

  // Find an existing ticket or create test ticket
  $ticket = Ticket::where('user_id', $testUser->id)
    ->whereIn('tick_status', [1, 2, 3, 4]) // Active tickets
    ->with(['user', 'category', 'subCategory'])
    ->first();

  if (!$ticket) {
    echo "✗ No active ticket found for testing.\n";
    echo "Creating test ticket...\n\n";

    $category = \App\Models\RefCategory::first();
    $subCategory = \App\Models\RefSubCategory::where('cat_id', $category->id)->first();

    $ticketNumber = 'TEST-NOTIF-' . now()->format('YmdHis');

    $ticket = Ticket::create([
      'uuid' => Str::uuid(),
      'ticket_nolog' => $ticketNumber,
      'user_id' => $testUser->id,
      'cat_id' => $category->id,
      'subcat_id' => $subCategory->id,
      'ticket_title' => 'Test Ticket - Status Notification',
      'ticket_desc' => 'Tiket ujian untuk menguji sistem notifikasi status change.',
      'ticket_type' => 1,
      'tick_status' => 1, // Aduan Baru
      'tick_duedate' => Carbon::now()->addDays(3),
      'created_by' => $testUser->id,
    ]);

    $ticket->load(['user', 'category', 'subCategory']);
    echo "✓ Test ticket created: {$ticket->ticket_nolog}\n\n";
  } else {
    echo "✓ Using existing ticket: {$ticket->ticket_nolog}\n\n";
  }

  // Display ticket info
  echo "Current Ticket Status:\n";
  echo "  No. Log: {$ticket->ticket_nolog}\n";
  echo "  Title: {$ticket->ticket_title}\n";
  echo "  Current Status: " . getStatusName($ticket->tick_status) . "\n";
  echo "  Owner: {$ticket->user->nama} ({$ticket->user->email})\n\n";

  // Test all status changes
  echo "=== Testing Status Change Notifications ===\n\n";

  $statusTransitions = [
    ['from' => 1, 'to' => 2, 'name' => 'Aduan Baru → Aduan Diproses'],
    ['from' => 2, 'to' => 3, 'name' => 'Aduan Diproses → Serahan Kepada'],
    ['from' => 3, 'to' => 4, 'name' => 'Serahan Kepada → Aduan Dirujuk'],
    ['from' => 4, 'to' => 6, 'name' => 'Aduan Dirujuk → Aduan Selesai'],
  ];

  echo "Select status change to test:\n";
  foreach ($statusTransitions as $index => $transition) {
    echo "  " . ($index + 1) . ". {$transition['name']}\n";
  }
  echo "  5. Test Cancel (Status → Aduan Dibatal)\n";
  echo "  6. Send notification for current status\n\n";

  echo "Enter your choice (1-6) or press Enter for option 6: ";
  $handle = fopen("php://stdin", "r");
  $choice = trim(fgets($handle));
  fclose($handle);

  if (empty($choice)) {
    $choice = '6';
  }

  $oldStatus = $ticket->tick_status;
  $newStatus = $oldStatus;
  $notes = null;

  switch ($choice) {
    case '1':
      $newStatus = 2;
      $notes = 'Tiket sedang diproses oleh pegawai helpdesk.';
      break;
    case '2':
      $newStatus = 3;
      $notes = 'Tiket telah diserahkan kepada pegawai bertanggungjawab.';
      break;
    case '3':
      $newStatus = 4;
      $notes = 'Tiket dirujuk kepada jabatan berkenaan untuk tindakan lanjut.';
      break;
    case '4':
      $newStatus = 6;
      $notes = 'Tiket telah selesai diproses. Terima kasih.';
      break;
    case '5':
      $newStatus = 5;
      $notes = 'Tiket dibatalkan atas sebab tertentu.';
      break;
    case '6':
      // Keep current status
      $notes = 'Testing notification dengan status semasa.';
      break;
    default:
      echo "Invalid choice. Using current status.\n";
      break;
  }

  echo "\n--- Sending Notification ---\n";
  echo "From Status: " . getStatusName($oldStatus) . " ($oldStatus)\n";
  echo "To Status: " . getStatusName($newStatus) . " ($newStatus)\n";
  echo "Notes: " . ($notes ?: 'None') . "\n";
  echo "Recipient: {$testUser->nama} <{$testUser->email}>\n\n";

  // Send notification
  $changedBy = User::first(); // Get first user as the one who changed it

  try {
    $testUser->notify(new TicketStatusChangedNotification(
      $ticket,
      $oldStatus,
      $newStatus,
      $changedBy,
      $notes
    ));

    echo "✓ Notification sent successfully!\n\n";

    // Update ticket status if changed
    if ($newStatus != $oldStatus) {
      $ticket->update(['tick_status' => $newStatus]);
      echo "✓ Ticket status updated to: " . getStatusName($newStatus) . "\n\n";
    }

    echo "=== Email Preview ===\n";
    echo "Subject: Status Tiket {$ticket->ticket_nolog} Dikemaskini: " . getStatusName($newStatus) . "\n";
    echo "To: {$testUser->email}\n\n";
    echo "Body:\n";
    echo "  Assalamualaikum {$testUser->nama},\n";
    echo "  Status tiket anda telah dikemaskini.\n\n";
    echo "  " . getStatusIcon($newStatus) . " " . getStatusMessage($newStatus) . "\n\n";
    echo "  No. Log Tiket: {$ticket->ticket_nolog}\n";
    echo "  Tajuk: {$ticket->ticket_title}\n";
    echo "  Status Lama: " . getStatusName($oldStatus) . "\n";
    echo "  Status Baru: " . getStatusName($newStatus) . "\n";
    echo "  Dikemaskini Oleh: {$changedBy->nama}\n";
    if ($notes) {
      echo "  Catatan: {$notes}\n";
    }
    echo "\n  [Button: Lihat Tiket]\n\n";
    echo "  " . getClosingMessage($newStatus) . "\n\n";
    echo "  Terima kasih,\n";
    echo "  Sistem eHelp\n\n";

    echo "=== Check Your Email ===\n";
    echo "Please check: {$testUser->email}\n\n";

    echo "Note: Make sure MAIL settings in .env are configured:\n";
    echo "  MAIL_MAILER=smtp\n";
    echo "  MAIL_HOST=...\n";
    echo "  MAIL_FROM_ADDRESS=...\n\n";
  } catch (\Exception $e) {
    echo "✗ Failed to send notification: {$e->getMessage()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
  }
} catch (\Exception $e) {
  echo "✗ ERROR: {$e->getMessage()}\n";
  echo "\nStack trace:\n{$e->getTraceAsString()}\n";
  exit(1);
}

// Helper functions
function getStatusName($statusId)
{
  return match ($statusId) {
    1 => 'Aduan Baru',
    2 => 'Aduan Diproses',
    3 => 'Serahan Kepada',
    4 => 'Aduan Dirujuk',
    5 => 'Aduan Dibatal',
    6 => 'Aduan Selesai',
    default => 'Status Tidak Diketahui'
  };
}

function getStatusIcon($statusId)
{
  return match ($statusId) {
    1 => '🆕',
    2 => '⚙️',
    3 => '📤',
    4 => '📋',
    5 => '❌',
    6 => '✅',
    default => '📌'
  };
}

function getStatusMessage($statusId)
{
  return match ($statusId) {
    1 => 'Tiket anda telah dilog dan akan diproses dalam masa terdekat.',
    2 => 'Tiket anda sedang dalam proses penyelesaian oleh pegawai kami.',
    3 => 'Tiket anda telah diserahkan kepada pegawai lain untuk tindakan selanjutnya.',
    4 => 'Tiket anda telah dirujuk kepada pihak berkenaan untuk tindakan lanjut.',
    5 => 'Tiket anda telah dibatalkan. Sila hubungi helpdesk jika anda memerlukan penjelasan lanjut.',
    6 => 'Tiket anda telah selesai diproses. Terima kasih atas kesabaran anda.',
    default => 'Status tiket dikemaskini.'
  };
}

function getClosingMessage($statusId)
{
  return match ($statusId) {
    1 => 'Kami akan menghubungi anda sekiranya memerlukan maklumat tambahan.',
    2 => 'Kami akan memberitahu anda sebaik sahaja tiket selesai diproses.',
    3, 4 => 'Anda akan menerima kemaskini seterusnya tidak lama lagi.',
    5 => 'Sekiranya anda tidak bersetuju dengan pembatalan ini, sila hubungi helpdesk.',
    6 => 'Kami harap perkhidmatan kami memuaskan hati. Jika ada sebarang pertanyaan, sila hubungi kami.',
    default => 'Terima kasih atas maklum balas anda.'
  };
}
