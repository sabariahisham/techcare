<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\KnowledgeBase;
use OwenIt\Auditing\Models\Audit;



class TestAuditCommand extends Command
{
    //untuk menguji fungsi audit trail menggunakan command line dan model sedia ada
    // guna command ini : php artisan audit:test
    protected $signature = 'audit:test';
    protected $description = 'Test audit trail functionality';

    public function handle()
    {
        $this->info('Testing audit trail functionality...');

        // Check current audit count
        $initialCount = Audit::count();
        $this->info("Initial audit count: {$initialCount}");

        // Test Ticket auditing
        $ticket = Ticket::first();
        if ($ticket) {
            $this->info("Testing Ticket audit (ID: {$ticket->id})...");
            $oldTitle = $ticket->ticket_title;
            $newTitle = 'Test Audit ' . now()->format('H:i:s');
            
            $ticket->update(['ticket_title' => $newTitle]);
            
            $this->info("Updated ticket title from '{$oldTitle}' to '{$newTitle}'");
            
            $newCount = Audit::count();
            $this->info("Audit count after Ticket update: {$newCount}");
        } else {
            $this->warn('No tickets found for testing');
        }

        // Test TicketChronology auditing
        $chronology = TicketChronology::first();
        if ($chronology) {
            $this->info("Testing TicketChronology audit (ID: {$chronology->id})...");
            $chronology->update(['tick_status' => 2]);
            
            $newCount = Audit::count();
            $this->info("Audit count after TicketChronology update: {$newCount}");
        }

        // Test KnowledgeBase auditing
        $kb = KnowledgeBase::first();
        if ($kb) {
            $this->info("Testing KnowledgeBase audit (ID: {$kb->id})...");
            $kb->update(['kb_title' => 'Test KB Title ' . now()->format('H:i:s')]);
            
            $newCount = Audit::count();
            $this->info("Audit count after KnowledgeBase update: {$newCount}");
        }

        // Show final results
        $finalCount = Audit::count();
        $created = $finalCount - $initialCount;
        
        $this->info("Final audit count: {$finalCount}");
        $this->info("New audit records created: {$created}");
        
        if ($created > 0) {
            $this->info('✅ Audit trail is working correctly!');
            
            // Show latest audits
            $latestAudits = Audit::latest()->take(3)->get();
            $this->table(['ID', 'Model', 'Event', 'Created'], 
                $latestAudits->map(function($audit) {
                    return [
                        $audit->id,
                        class_basename($audit->auditable_type),
                        $audit->event,
                        $audit->created_at
                    ];
                })
            );
        } else {
            $this->error('❌ Audit trail is not working properly!');
        }

        return 0;
    }
}