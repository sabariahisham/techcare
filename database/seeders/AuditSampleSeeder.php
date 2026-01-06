<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\KnowledgeBase;
use App\Models\User;
use Carbon\Carbon;

class AuditSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=AuditSampleSeeder
     */
    public function run(): void
    {
        $this->command->info('Creating manual audit sample data...');

        // Get sample user for testing
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        // Create manual audit records for testing
        $this->createManualAuditRecords($user);

        // Try automatic auditing
        $this->testAutomaticAuditing($user);

        $this->command->info('Audit sample data creation completed!');
    }

    private function createManualAuditRecords($user)
    {
        $this->command->info('Creating manual audit records...');
        
        // Manual Ticket audit record
        DB::table('audits')->insert([
            'user_type' => 'App\Models\User',
            'user_id' => $user->id,
            'event' => 'updated',
            'auditable_type' => 'App\Models\Ticket',
            'auditable_id' => 1,
            'old_values' => json_encode(['ticket_title' => 'Old Title', 'ticket_desc' => 'Old Description']),
            'new_values' => json_encode(['ticket_title' => 'New Title', 'ticket_desc' => 'New Description']),
            'url' => '/tickets/1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel Seeder',
            'tags' => 'ticket',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        // Manual TicketChronology audit record
        DB::table('audits')->insert([
            'user_type' => 'App\Models\User',
            'user_id' => $user->id,
            'event' => 'created',
            'auditable_type' => 'App\Models\TicketChronology',
            'auditable_id' => 1,
            'old_values' => json_encode([]),
            'new_values' => json_encode(['tick_status' => 2, 'escalate_to' => $user->id]),
            'url' => '/tickets/chronology',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel Seeder',
            'tags' => 'ticket-chronology',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        // Manual KnowledgeBase audit record
        DB::table('audits')->insert([
            'user_type' => 'App\Models\User',
            'user_id' => $user->id,
            'event' => 'updated',
            'auditable_type' => 'App\Models\KnowledgeBase',
            'auditable_id' => 1,
            'old_values' => json_encode(['kb_title' => 'Old KB Title', 'kb_status' => 0]),
            'new_values' => json_encode(['kb_title' => 'Updated KB Title', 'kb_status' => 1]),
            'url' => '/knowledgebase/1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel Seeder',
            'tags' => 'knowledge-base',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $this->command->info('Manual audit records created successfully!');
    }

    private function testAutomaticAuditing($user)
    {
        $this->command->info('Testing automatic auditing...');
        
        $initialCount = \OwenIt\Auditing\Models\Audit::count();
        $this->command->info("Initial audit count: {$initialCount}");

        // Test with existing records
        $ticket = Ticket::first();
        if ($ticket) {
            $this->command->info('Updating ticket to test automatic auditing...');
            $ticket->ticket_title = 'Auto Audit Test - ' . now()->format('H:i:s');
            $ticket->save();
            
            $newCount = \OwenIt\Auditing\Models\Audit::count();
            $this->command->info("Audit count after ticket update: {$newCount}");
            
            if ($newCount > $initialCount) {
                $this->command->info('✅ Automatic auditing is working!');
            } else {
                $this->command->warn('⚠️  Automatic auditing may not be configured correctly');
            }
        }
    }
}