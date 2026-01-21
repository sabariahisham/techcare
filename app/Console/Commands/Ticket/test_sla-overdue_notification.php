<?php

/*
 * Test Script: SLA Overdue Notification
 * Purpose: Test SLA overdue notification email for tickets that have exceeded their due date
 * Usage:
 *   - All overdue tickets: php app/Console/Commands/Ticket/test_sla-overdue_notification.php
 *   - Specific ticket: php app/Console/Commands/Ticket/test_sla-overdue_notification.php <ticket_id>
 * Example: php app/Console/Commands/Ticket/test_sla-overdue_notification.php 14
 */

// Guard: Only execute if this file is run directly
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
  return;
}

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Services\TicketSLAOverdueService;
use App\Notifications\Ticket\SLAOverdueNotification;
use Carbon\Carbon;

require __DIR__ . '/../../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║           SLA OVERDUE NOTIFICATION TEST SCRIPT                   ║\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "\n";

// Get ticket ID from command line (optional)
$ticketId = $argv[1] ?? null;

try {
  if ($ticketId) {
    // Test specific ticket
    echo "🎯 Testing with specific Ticket ID: {$ticketId}\n\n";

    $ticket = Ticket::with(['status', 'user', 'chronologies.escalatedTo'])->find($ticketId);

    if (!$ticket) {
      echo "❌ ERROR: Ticket with ID {$ticketId} not found!\n\n";
      exit(1);
    }

    // Display ticket info
    echo "📋 TICKET INFORMATION:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Ticket No     : {$ticket->ticket_nolog}\n";
    echo "Title         : {$ticket->ticket_title}\n";
    echo "Status        : " . ($ticket->status->st_name ?? 'N/A') . "\n";
    echo "Created By    : " . ($ticket->user->nama ?? 'N/A') . "\n";
    echo "Due Date      : " . ($ticket->tick_duedate ? $ticket->tick_duedate->format('d/m/Y H:i') : 'Not set') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Check chronologies
    $chronologies = $ticket->chronologies;

    if ($chronologies->isEmpty()) {
      echo "⚠️  WARNING: No chronologies found for this ticket.\n\n";
      exit(0);
    }

    echo "📊 CHRONOLOGY ENTRIES ({$chronologies->count()}):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($chronologies as $chron) {
      $isOverdue = $chron->sla_duedate && $chron->sla_duedate->isPast();
      echo "ID            : {$chron->id}\n";
      echo "SLA Due Date  : " . ($chron->sla_duedate ? $chron->sla_duedate->format('d/m/Y H:i') : 'Not set') . "\n";
      echo "Status        : " . ($chron->status->st_name ?? 'N/A') . "\n";
      echo "Assigned To   : " . ($chron->escalatedTo->nama ?? 'Not assigned') . "\n";
      echo "Is Overdue?   : " . ($isOverdue ? '✅ YES' : '❌ NO') . "\n";
      echo "───────────────────────────────────────────────────────────────────\n";
    }
    echo "\n";

    $overdueChronologies = $chronologies->filter(function ($chron) {
      return $chron->sla_duedate && $chron->sla_duedate->isPast();
    });

    if ($overdueChronologies->isEmpty()) {
      echo "⚠️  WARNING: No overdue chronologies found for this ticket.\n\n";
      exit(0);
    }

    echo "📤 SENDING NOTIFICATION...\n\n";

    $sentCount = 0;
    $failedCount = 0;

    foreach ($overdueChronologies as $chronology) {
      if ($chronology->escalatedTo) {
        try {
          $chronology->escalatedTo->notify(new SLAOverdueNotification($ticket));
          echo "✅ Notification sent to: {$chronology->escalatedTo->email}\n";
          $sentCount++;
        } catch (\Exception $e) {
          echo "❌ Failed to send to {$chronology->escalatedTo->email}: {$e->getMessage()}\n";
          $failedCount++;
        }
      }
    }

    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════════╗\n";
    echo "║                          SUMMARY                                  ║\n";
    echo "╠═══════════════════════════════════════════════════════════════════╣\n";
    echo "║ Successfully Sent: {$sentCount}                                          ║\n";
    echo "║ Failed: {$failedCount}                                                   ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════╝\n";
  } else {
    // Test all overdue tickets
    echo "📊 CHECKING DATABASE FOR OVERDUE TICKETS...\n\n";

    // Count statistics
    $totalTickets = Ticket::count();
    $ticketsWithDueDate = Ticket::whereNotNull('tick_duedate')->count();
    $chronologiesWithSLA = TicketChronology::whereNotNull('sla_duedate')->count();
    $overdueCount = TicketChronology::whereNotNull('sla_duedate')
      ->where('sla_duedate', '<', Carbon::now())
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->count();

    echo "📝 Total tickets in database: {$totalTickets}\n";
    echo "📅 Tickets with due date: {$ticketsWithDueDate}\n";
    echo "⏰ Chronologies with SLA due date: {$chronologiesWithSLA}\n";
    echo "🔴 Overdue chronologies (active tickets): {$overdueCount}\n\n";

    // Get overdue tickets
    $service = new TicketSLAOverdueService();
    $overdueTickets = $service->getOverdueTickets();

    if ($overdueTickets->isEmpty()) {
      echo "⚠️  No overdue tickets found in the system.\n\n";
      echo "💡 TIPS TO CREATE TEST DATA:\n";
      echo "   1. Create a ticket with chronology\n";
      echo "   2. Set sla_duedate in tickets_chronologies to past date\n";
      echo "   3. Make sure ticket status is not Selesai (5) or Tutup (6)\n\n";
      exit(0);
    }

    echo "✅ Found {$overdueTickets->count()} overdue ticket(s)\n\n";

    // Display overdue tickets
    echo "📑 OVERDUE TICKETS DETAILS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    printf(
      "%-22s %-30s %-18s %-12s %-25s %-15s\n",
      "Ticket No",
      "Title",
      "SLA Due Date",
      "Days Overdue",
      "Assigned To",
      "Status"
    );
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($overdueTickets as $item) {
      $title = strlen($item['ticket']->ticket_title) > 28
        ? substr($item['ticket']->ticket_title, 0, 25) . '...'
        : $item['ticket']->ticket_title;

      printf(
        "%-22s %-30s %-18s %-12s %-25s %-15s\n",
        $item['ticket']->ticket_nolog,
        $title,
        $item['sla_duedate']->format('d/m/Y H:i'),
        abs(round($item['days_overdue'])) . ' days',
        strlen($item['assigned_to']) > 23 ? substr($item['assigned_to'], 0, 20) . '...' : $item['assigned_to'],
        $item['ticket']->status->st_name ?? 'N/A'
      );
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Ask for confirmation
    echo "📧 Do you want to send overdue notification emails for these tickets? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirm = trim(strtolower($line));
    fclose($handle);

    if ($confirm !== 'y' && $confirm !== 'yes') {
      echo "\n❌ Email sending cancelled by user.\n\n";
      exit(0);
    }

    echo "\n📤 SENDING OVERDUE NOTIFICATIONS...\n\n";

    $stats = $service->sendOverdueNotifications();

    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════════╗\n";
    echo "║                    NOTIFICATION RESULTS                           ║\n";
    echo "╠═══════════════════════════════════════════════════════════════════╣\n";
    echo "║ Total Overdue Tickets: {$stats['total_overdue']}                            ║\n";
    echo "║ Successfully Sent: {$stats['sent']}                                     ║\n";
    echo "║ Failed: {$stats['failed']}                                              ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════╝\n";

    if ($stats['failed'] > 0 && !empty($stats['errors'])) {
      echo "\n🚨 ERRORS ENCOUNTERED:\n";
      foreach ($stats['errors'] as $index => $error) {
        echo ($index + 1) . ". {$error}\n";
      }
    }

    if ($stats['sent'] > 0) {
      echo "\n✅ Test completed successfully!\n";
      echo "💡 Check your email inbox or Mailpit (http://localhost:8025) to view sent emails.\n";
    } else {
      echo "\n⚠️  No emails were sent. Please check the errors above.\n";
    }
  }

  echo "\n";
} catch (\Exception $e) {
  echo "\n❌ ERROR: " . $e->getMessage() . "\n";
  echo "Stack trace:\n";
  echo $e->getTraceAsString() . "\n\n";
  exit(1);
}
