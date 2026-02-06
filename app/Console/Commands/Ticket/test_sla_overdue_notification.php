<?php

/*
  * Function test_sla_overdue_notification.php:
  *  - Test script for SLA overdue notification system.
  *  - Check database status and send test emails.
  * Usage:
  *   - All overdue tickets: php app/Console/Commands/Ticket/test_sla-overdue_notification.php
  *   - Specific ticket: php app/Console/Commands/Ticket/test_sla-overdue_notification.php <ticket_id>
  */

namespace App\Console\Commands\Ticket;

use Illuminate\Console\Command;
use App\Services\TicketOverdueService;
use App\Models\Ticket;
use App\Models\TicketChronology;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestSLAOverdueNotification extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ticket:test-overdue-notification {--ticket= : Test with specific ticket ID}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Test SLA overdue notification system - check database and send test emails';

  protected $slaOverdueService;

  /**
   * Create a new command instance.
   */
  public function __construct(TicketOverdueService $slaOverdueService)
  {
    parent::__construct();
    $this->slaOverdueService = $slaOverdueService;
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('=================================================');
    $this->info('   Testing SLA Overdue Notification System');
    $this->info('=================================================');
    $this->newLine();

    $ticketId = $this->option('ticket');

    // If specific ticket ID provided, test with that ticket
    if ($ticketId) {
      return $this->testSpecificTicket($ticketId);
    }

    // Step 1: Check database for overdue tickets
    $this->info('📊 STEP 1: Checking database for overdue tickets...');
    $this->newLine();

    $this->checkDatabaseStatus();
    $this->newLine();

    // Step 2: Get overdue tickets
    $this->info('📋 STEP 2: Fetching overdue tickets from service...');
    $overdueTickets = $this->slaOverdueService->getOverdueTickets();

    if ($overdueTickets->isEmpty()) {
      $this->warn('⚠️  No overdue tickets found in the system.');
      $this->info('');
      $this->info('💡 Tips to create test data:');
      $this->info('   1. Create a ticket with chronology');
      $this->info('   2. Set sla_duedate in tickets_chronologies to past date');
      $this->info('   3. Make sure ticket status is not Selesai (5) or Tutup (6)');
      $this->newLine();
      return Command::SUCCESS;
    }

    $this->info("✅ Found {$overdueTickets->count()} overdue ticket(s)");
    $this->newLine();

    // Step 3: Display overdue tickets
    $this->info('📑 STEP 3: Overdue Tickets Details');
    $this->info('─────────────────────────────────────────────────');
    $this->displayOverdueTicketsTable($overdueTickets);
    $this->newLine();

    // Step 4: Ask for confirmation
    if (!$this->confirm('📧 Do you want to send overdue notification emails for these tickets?', true)) {
      $this->warn('❌ Email sending cancelled by user.');
      return Command::SUCCESS;
    }

    // Step 5: Send notifications
    $this->newLine();
    $this->info('📤 STEP 4: Sending overdue notifications...');
    $this->newLine();

    $bar = $this->output->createProgressBar($overdueTickets->count());
    $bar->start();

    $stats = $this->slaOverdueService->sendOverdueNotifications();

    $bar->finish();
    $this->newLine();
    $this->newLine();

    // Step 6: Display results
    $this->displayResults($stats);

    return Command::SUCCESS;
  }

  /**
   * Test with specific ticket ID
   */
  protected function testSpecificTicket($ticketId)
  {
    $this->info("🎯 Testing with specific Ticket ID: {$ticketId}");
    $this->newLine();

    $ticket = Ticket::with(['status', 'user', 'chronologies.escalatedTo'])->find($ticketId);

    if (!$ticket) {
      $this->error("❌ Ticket with ID {$ticketId} not found!");
      return Command::FAILURE;
    }

    // Display ticket info
    $this->info('📋 Ticket Information:');
    $this->table(
      ['Field', 'Value'],
      [
        ['Ticket No', $ticket->ticket_nolog],
        ['Title', $ticket->ticket_title],
        ['Status', $ticket->status->st_name ?? 'N/A'],
        ['Created By', $ticket->user->nama ?? 'N/A'],
        ['Due Date', $ticket->tick_duedate ? $ticket->tick_duedate->format('d/m/Y H:i') : 'Not set'],
      ]
    );
    $this->newLine();

    // Check chronologies
    $chronologies = $ticket->chronologies;

    if ($chronologies->isEmpty()) {
      $this->warn('⚠️  No chronologies found for this ticket.');
      return Command::SUCCESS;
    }

    $this->info("📊 Found {$chronologies->count()} chronology entries:");
    $this->table(
      ['ID', 'SLA Due Date', 'Status', 'Assigned To', 'Is Overdue?'],
      $chronologies->map(function ($chron) {
        $isOverdue = $chron->sla_duedate && $chron->sla_duedate->isPast();
        return [
          $chron->id,
          $chron->sla_duedate ? $chron->sla_duedate->format('d/m/Y H:i') : 'Not set',
          $chron->status->st_name ?? 'N/A',
          $chron->escalatedTo->nama ?? 'Not assigned',
          $isOverdue ? '✅ YES' : '❌ NO',
        ];
      })->toArray()
    );
    $this->newLine();

    $overdueChronologies = $chronologies->filter(function ($chron) {
      return $chron->sla_duedate && $chron->sla_duedate->isPast();
    });

    if ($overdueChronologies->isEmpty()) {
      $this->warn('⚠️  No overdue chronologies found for this ticket.');
      return Command::SUCCESS;
    }

    if (!$this->confirm('📧 Send test notification for this ticket?', true)) {
      $this->warn('❌ Email sending cancelled.');
      return Command::SUCCESS;
    }

    // Send notification
    $this->info('📤 Sending notification...');

    foreach ($overdueChronologies as $chronology) {
      if ($chronology->escalatedTo) {
        try {
          $chronology->escalatedTo->notify(new \App\Notifications\Ticket\OverdueNotification($ticket));
          $this->info("✅ Notification sent to: {$chronology->escalatedTo->email}");
        } catch (\Exception $e) {
          $this->error("❌ Failed: {$e->getMessage()}");
        }
      }
    }

    $this->newLine();
    $this->info('✅ Test completed!');

    return Command::SUCCESS;
  }

  /**
   * Check database status
   */
  protected function checkDatabaseStatus()
  {
    // Count total tickets
    $totalTickets = Ticket::count();
    $this->info("📝 Total tickets in database: {$totalTickets}");

    // Count tickets with due date
    $ticketsWithDueDate = Ticket::whereNotNull('tick_duedate')->count();
    $this->info("📅 Tickets with due date: {$ticketsWithDueDate}");

    // Count chronologies with SLA due date
    $chronologiesWithSLA = TicketChronology::whereNotNull('sla_duedate')->count();
    $this->info("⏰ Chronologies with SLA due date: {$chronologiesWithSLA}");

    // Count overdue chronologies
    $overdueCount = TicketChronology::whereNotNull('sla_duedate')
      ->where('sla_duedate', '<', Carbon::now())
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->count();

    $this->info("🔴 Overdue chronologies (active tickets): {$overdueCount}");
  }

  /**
   * Display overdue tickets in table format
   */
  protected function displayOverdueTicketsTable($overdueTickets)
  {
    $this->table(
      ['Ticket No', 'Title', 'SLA Due Date', 'Days Overdue', 'Assigned To', 'Status'],
      $overdueTickets->map(function ($item) {
        return [
          $item['ticket']->ticket_nolog,
          \Illuminate\Support\Str::limit($item['ticket']->ticket_title, 30),
          $item['sla_duedate']->format('d/m/Y H:i'),
          abs(round($item['days_overdue'])) . ' days',
          $item['assigned_to'],
          $item['ticket']->status->st_name ?? 'N/A'
        ];
      })->toArray()
    );
  }

  /**
   * Display results statistics
   */
  protected function displayResults($stats)
  {
    $this->info('=================================================');
    $this->info('          📊 NOTIFICATION RESULTS');
    $this->info('=================================================');

    $this->table(
      ['Metric', 'Count'],
      [
        ['Total Overdue Tickets', $stats['total_overdue']],
        ['✅ Successfully Sent', $stats['sent']],
        ['❌ Failed', $stats['failed']],
      ]
    );

    if ($stats['failed'] > 0 && !empty($stats['errors'])) {
      $this->newLine();
      $this->error('🚨 Errors encountered:');
      foreach ($stats['errors'] as $index => $error) {
        $this->error(($index + 1) . '. ' . $error);
      }
    }

    $this->newLine();

    if ($stats['sent'] > 0) {
      $this->info('✅ Test completed successfully!');
      $this->info('💡 Check your email inbox or Mailpit (http://localhost:8025) to view sent emails.');
    } else {
      $this->warn('⚠️  No emails were sent. Please check the errors above.');
    }
  }
}
