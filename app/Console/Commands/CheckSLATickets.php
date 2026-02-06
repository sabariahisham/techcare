<?php

/*
  Command to check tickets in the database with SLA due dates.
  This is for debugging and verification purposes.
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TicketChronology;
use Carbon\Carbon;
use App\Helpers\Helpers;

class CheckSLATickets extends Command
{
  protected $signature = 'sla:check-tickets';
  protected $description = 'Check tickets in database with SLA due dates';

  public function handle()
  {
    $this->info('🔍 Checking SLA Tickets in Database...');
    $this->newLine();

    // Current date/time
    $now = Carbon::now();
    $today = Carbon::today();

    $this->line("📅 Current Time: {$now->format('Y-m-d H:i:s')}");
    $this->line("📅 Today: {$today->format('Y-m-d')}");
    $this->newLine();

    // Calculate working day dates
    $tomorrow = Helpers::addWorkingDays($today, 1);
    $yesterday = Helpers::subtractWorkingDays($today, 1);

    $this->line("📅 Tomorrow (working day): {$tomorrow->format('Y-m-d')}");
    $this->line("📅 Yesterday (working day): {$yesterday->format('Y-m-d')}");
    $this->newLine();

    // Check REMINDER tickets (due tomorrow)
    $this->info('🔔 REMINDER Tickets (Due Tomorrow)');
    $reminderTickets = TicketChronology::whereNotNull('sla_duedate')
      ->whereBetween('sla_duedate', [$tomorrow->startOfDay(), $tomorrow->copy()->endOfDay()])
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'escalatedTo'])
      ->get();

    $this->line("Found: {$reminderTickets->count()} tickets");

    if ($reminderTickets->count() > 0) {
      $data = [];
      foreach ($reminderTickets as $chrono) {
        $data[] = [
          $chrono->ticket->ticket_nolog ?? 'N/A',
          $chrono->sla_duedate->format('Y-m-d H:i'),
          $chrono->ticket->status->st_name ?? 'N/A',
          $chrono->escalatedTo->email ?? 'N/A',
        ];
      }
      $this->table(['Ticket No', 'SLA Due Date', 'Status', 'Recipient Email'], $data);
    }
    $this->newLine();

    // Check AFTER DUE tickets (due yesterday)
    $this->info('⚠️ AFTER DUE Tickets (Due Yesterday)');
    $afterDueTickets = TicketChronology::whereNotNull('sla_duedate')
      ->whereBetween('sla_duedate', [$yesterday->startOfDay(), $yesterday->copy()->endOfDay()])
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'escalatedTo'])
      ->get();

    $this->line("Found: {$afterDueTickets->count()} tickets");

    if ($afterDueTickets->count() > 0) {
      $data = [];
      foreach ($afterDueTickets as $chrono) {
        $data[] = [
          $chrono->ticket->ticket_nolog ?? 'N/A',
          $chrono->sla_duedate->format('Y-m-d H:i'),
          $chrono->ticket->status->st_name ?? 'N/A',
          $chrono->escalatedTo->email ?? 'N/A',
        ];
      }
      $this->table(['Ticket No', 'SLA Due Date', 'Status', 'Recipient Email'], $data);
    }
    $this->newLine();

    // Check ALL active tickets with SLA
    $this->info('📊 All Active Tickets with SLA Due Dates');
    $allTickets = TicketChronology::whereNotNull('sla_duedate')
      ->whereHas('ticket', function ($query) {
        $query->whereNotIn('tick_status', [5, 6]);
      })
      ->with(['ticket.status', 'escalatedTo'])
      ->orderBy('sla_duedate', 'asc')
      ->take(10)
      ->get();

    $this->line("Found: {$allTickets->count()} active tickets (showing top 10)");

    if ($allTickets->count() > 0) {
      $data = [];
      foreach ($allTickets as $chrono) {
        $daysUntilDue = $now->diffInDays($chrono->sla_duedate, false);
        $status = $daysUntilDue > 0 ? "In {$daysUntilDue} days" : ($daysUntilDue == 0 ? 'Today' : abs($daysUntilDue) . ' days overdue');

        $data[] = [
          $chrono->ticket->ticket_nolog ?? 'N/A',
          $chrono->sla_duedate->format('Y-m-d H:i'),
          $status,
          $chrono->ticket->status->st_name ?? 'N/A',
          $chrono->escalatedTo->email ?? 'N/A',
        ];
      }
      $this->table(['Ticket No', 'SLA Due Date', 'Days Until/Overdue', 'Status', 'Recipient Email'], $data);
    } else {
      $this->warn('⚠️ No active tickets with SLA due dates found in database!');
    }

    $this->newLine();
    $this->info('✅ Database check completed!');

    return 0;
  }
}
