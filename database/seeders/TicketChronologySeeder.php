<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\RefTicketStatus;
use App\Models\TicketChronology;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TicketChronologySeeder extends Seeder
{
  public function run(): void
  {
    // Get sample tickets and users
    $tickets = Ticket::with(['user', 'status'])->limit(3)->get();
    $users = User::limit(10)->get();
    $statuses = RefTicketStatus::orderBy('id')->get();

    if ($tickets->isEmpty() || $users->isEmpty() || $statuses->isEmpty()) {
      $this->command->info('Skipping chronology seeder - missing required data (tickets, users, or statuses)');
      return;
    }

    $this->command->info('Creating chronology entries for ' . $tickets->count() . ' tickets...');

    foreach ($tickets as $ticketIndex => $ticket) {
      // Define chronology progression based on TicketChronology model structure
      $chronologyProgression = [
        [
          'status_name' => 'Aduan Diproses',
          'hours_ago' => 72,  // 3 days ago
          'level_pegtugas_id' => 1,
          'sla_hours' => 48
        ],
        [
          'status_name' => 'Serahan Kepada',
          'hours_ago' => 48,  // 2 days ago
          'level_pegtugas_id' => 2,
          'escalate_to_different_user' => true,
          'sla_hours' => 24
        ],
        [
          'status_name' => 'Aduan Dirujuk',
          'hours_ago' => 24,  // 1 day ago
          'level_pegtugas_id' => 3,
          'sla_hours' => 12
        ],
        [
          'status_name' => 'Aduan Selesai',
          'hours_ago' => 6,   // 6 hours ago
          'level_pegtugas_id' => 2,
          'sla_hours' => 0    // No SLA for completed
        ]
      ];

      // Create chronology entries for each ticket
      foreach ($chronologyProgression as $index => $progression) {
        $status = $statuses->where('st_name', $progression['status_name'])->first();

        if (!$status) {
          $this->command->warn("Status '{$progression['status_name']}' not found, skipping...");
          continue;
        }

        // Select users for this chronology entry
        $fromUserIndex = ($ticketIndex * 4 + $index) % $users->count();
        $fromUser = $users[$fromUserIndex];

        $escalateToUser = null;
        if (!empty($progression['escalate_to_different_user'])) {
          $escalateToIndex = ($fromUserIndex + 1) % $users->count();
          $escalateToUser = $users[$escalateToIndex];
        }

        // Calculate timestamps
        $createdAt = Carbon::now()->subHours($progression['hours_ago']);
        $slaDueDate = $progression['sla_hours'] > 0
          ? $createdAt->copy()->addHours($progression['sla_hours'])
          : null;

        // Create chronology entry following the model structure
        try {
          TicketChronology::create([
            'ticket_id' => $ticket->id,
            'frm_user_id' => $fromUser->id,
            'level_pegtugas_id' => $progression['level_pegtugas_id'],
            'tick_status' => $status->id,
            'escalate_to' => $escalateToUser?->id,
            'sla_duedate' => $slaDueDate,
            'created_by' => $fromUser->id,
            'updated_by' => $fromUser->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
          ]);

          $this->command->info("✓ Created chronology: {$status->st_name} for ticket {$ticket->ticket_nolog}");
        } catch (\Exception $e) {
          $this->command->error("✗ Failed to create chronology for ticket {$ticket->ticket_nolog}: " . $e->getMessage());
        }

        // Stop creating more entries for some tickets (simulate different completion levels)
        if ($ticketIndex === 1 && $index === 1) break; // Second ticket stops at "Serahan Kepada"
        if ($ticketIndex === 2 && $index === 2) break; // Third ticket stops at "Aduan Dirujuk"
      }
    }

    // Display summary
    $totalChronologies = TicketChronology::count();
    $this->command->info("✅ Chronology seeder completed successfully!");
    $this->command->info("📊 Total chronology entries in database: {$totalChronologies}");

    // Show sample data
    $this->command->info("\n📋 Sample chronology entries created:");
    $sampleEntries = TicketChronology::with(['ticket', 'status', 'fromUser', 'escalatedTo'])
      ->latest()
      ->limit(5)
      ->get();

    foreach ($sampleEntries as $entry) {
      $escalateInfo = $entry->escalatedTo ? " → {$entry->escalatedTo->nama}" : "";
      $this->command->info("   • {$entry->ticket->ticket_nolog}: {$entry->status->st_name} by {$entry->fromUser->nama}{$escalateInfo}");
    }
  }
}
