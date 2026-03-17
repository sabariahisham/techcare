<?php

/**
 * Debug SLA Email System
 *
 * This command helps troubleshoot why emails are not being sent.
 * It checks:
 * - Mail configuration
 * - Test data availability
 * - Working day status
 * - Database connection
 * - Query results
 *
 * Usage: php artisan sla:debug-email
 */

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DebugSLAEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:debug-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug SLA email system - Check configuration, data, and readiness';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║              DEBUG SLA EMAIL SYSTEM                           ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $today = Carbon::today();
        $tomorrow = Helpers::addWorkingDays($today, 1);
        $tomorrowStart = $tomorrow->copy()->startOfDay();
        $tomorrowEnd = $tomorrow->copy()->endOfDay();

        $this->info("📅 Date Info:");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("Today:    " . $today->format('l, d/m/Y'));
        $this->line("Tomorrow: " . $tomorrow->format('l, d/m/Y'));
        $this->newLine();

        // Check 1: Mail Configuration
        $this->info("✓ 1. MAIL CONFIGURATION");
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->line("MAIL_MAILER: " . config('mail.default'));
        $this->line("MAIL_HOST: " . config('mail.mailers.smtp.host'));
        $this->line("MAIL_PORT: " . config('mail.mailers.smtp.port'));
        $this->line("MAIL_FROM_ADDRESS: " . config('mail.from.address'));
        $this->line("MAIL_FROM_NAME: " . config('mail.from.name'));

        if (config('mail.default') === 'smtp' && config('mail.mailers.smtp.host') === 'localhost') {
            $this->info("📧 Mailpit detected (localhost)");
            $this->line("   URL: http://localhost:8025");
        } else {
            $this->warn("⚠️  Mailpit configuration not detected");
        }
        $this->newLine();

        // Check 2: Test Data
        $this->info("✓ 2. TEST DATA CHECK");
        $this->line('─────────────────────────────────────────────────────────────────');

        $chronologies = TicketChronology::whereBetween('sla_duedate', [$tomorrowStart, $tomorrowEnd])
            ->whereNotNull('escalate_to')
            ->with(['ticket', 'escalatedTo', 'ticket.status'])
            ->get();

        $this->line("Total chronologies found: " . $chronologies->count());
        $this->newLine();

        if ($chronologies->count() === 0) {
            $this->error("❌ NO TEST DATA FOUND!");
            $this->newLine();
            $this->info("💡 Create test data with this command:");
            $this->line("   php artisan sla:create-test-data");
            $this->newLine();
        } else {
            $tableData = [];
            foreach ($chronologies as $chrono) {
                $ticket = $chrono->ticket;
                $officer = $chrono->escalatedTo;

                $status = '✅ OK';
                $issues = [];

                if (!$ticket) {
                    $status = '❌ ERROR';
                    $issues[] = 'Ticket not found';
                } elseif (in_array($ticket->tick_status, [5, 6])) {
                    $status = '⚠️  SKIP';
                    $issues[] = 'Batal/Selesai';
                }

                if (!$officer) {
                    $status = '❌ ERROR';
                    $issues[] = 'Officer not found';
                } elseif (empty($officer->email)) {
                    $status = '⚠️  SKIP';
                    $issues[] = 'No email';
                }

                $tableData[] = [
                    $chrono->id,
                    $ticket ? $ticket->ticket_nolog : 'N/A',
                    $officer ? $officer->nama : 'N/A',
                    $officer ? $officer->email : 'N/A',
                    $status . (count($issues) > 0 ? ' (' . implode(', ', $issues) . ')' : '')
                ];
            }

            $this->table(['ID', 'Ticket', 'Officer', 'Email', 'Status'], $tableData);
        }

        // Check 3: Working Day Status
        $this->info("✓ 3. WORKING DAY CHECK");
        $this->line('─────────────────────────────────────────────────────────────────');
        $isWorkingDay = Helpers::isWorkingDay($today);

        if ($isWorkingDay) {
            $this->info("Is today a working day? ✅ YES");
        } else {
            $this->warn("Is today a working day? ❌ NO");
            $this->warn("⚠️  WARNING: Today is not a working day!");
            $this->line("   The service will skip execution and return early.");
            $this->line("   No emails will be sent.");
        }
        $this->newLine();

        // Check 4: Database Connection
        $this->info("✓ 4. DATABASE CONNECTION");
        $this->line('─────────────────────────────────────────────────────────────────');
        try {
            $ticketCount = DB::table('tickets')->count();
            $chronologyCount = DB::table('tickets_chronologies')->count();
            $this->info("✅ Database connected");
            $this->line("   Total tickets: " . $ticketCount);
            $this->line("   Total chronologies: " . $chronologyCount);
        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
        }
        $this->newLine();

        // Check 5: Latest Chronologies Query
        $this->info("✓ 5. LATEST CHRONOLOGY QUERY (Same as Service Logic)");
        $this->line('─────────────────────────────────────────────────────────────────');

        try {
            $latestChronologies = TicketChronology::select('tickets_chronologies.*')
                ->join(DB::raw('(SELECT ticket_id, MAX(created_at) as max_created
                                FROM tickets_chronologies
                                WHERE deleted_at IS NULL
                                GROUP BY ticket_id) latest'), function ($join) {
                    $join->on('tickets_chronologies.ticket_id', '=', 'latest.ticket_id')
                        ->on('tickets_chronologies.created_at', '=', 'latest.max_created');
                })
                ->whereBetween('tickets_chronologies.sla_duedate', [$tomorrowStart, $tomorrowEnd])
                ->whereNotNull('tickets_chronologies.escalate_to')
                ->whereNull('tickets_chronologies.deleted_at')
                ->with(['ticket', 'ticket.user', 'ticket.status', 'escalatedTo'])
                ->get();

            $this->line("Latest chronologies found: " . $latestChronologies->count());

            if ($latestChronologies->count() === 0) {
                $this->error("❌ No latest chronologies match the criteria");
                $this->newLine();
                $this->line("Possible reasons:");
                $this->line("  - sla_duedate not in range: " . $tomorrowStart->format('Y-m-d H:i:s') . " to " . $tomorrowEnd->format('Y-m-d H:i:s'));
                $this->line("  - escalate_to is NULL");
                $this->line("  - Record is soft-deleted (deleted_at is not NULL)");
                $this->line("  - Not the latest chronology for that ticket");
            } else {
                $this->info("✅ Found matching records (same as service will find)");
            }
        } catch (\Exception $e) {
            $this->error("❌ Query error: " . $e->getMessage());
        }
        $this->newLine();

        // Summary
        $this->line('════════════════════════════════════════════════════════════════');
        $this->info("📊 SUMMARY");
        $this->line('════════════════════════════════════════════════════════════════');

        $issues = [];

        if (config('mail.default') !== 'smtp' || config('mail.mailers.smtp.host') !== 'localhost') {
            $issues[] = "Mail not configured for Mailpit";
        }

        if ($chronologies->count() === 0) {
            $issues[] = "No test data found";
        }

        if (!$isWorkingDay) {
            $issues[] = "Today is not a working day (service will skip)";
        }

        foreach ($chronologies as $chrono) {
            if (!$chrono->ticket) {
                $issues[] = "Chronology #{$chrono->id}: Ticket not found";
            } else if (in_array($chrono->ticket->tick_status, [5, 6])) {
                $issues[] = "Chronology #{$chrono->id}: Ticket status is Batal/Selesai";
            }

            if (!$chrono->escalatedTo) {
                $issues[] = "Chronology #{$chrono->id}: Officer not found";
            } else if (empty($chrono->escalatedTo->email)) {
                $issues[] = "Chronology #{$chrono->id}: Officer has no email";
            }
        }

        if (empty($issues)) {
            $this->info("✅ All checks passed! System should work.");
            $this->newLine();
            $this->info("📝 Next steps:");
            $this->line("1. Run: php artisan sla:test reminder");
            $this->line("2. Check Mailpit: http://localhost:8025");
            $this->line("3. Check logs: tail -50 storage/logs/laravel.log");
        } else {
            $this->error("❌ Found " . count($issues) . " issue(s):");
            foreach ($issues as $i => $issue) {
                $this->line("   " . ($i + 1) . ". " . $issue);
            }
            $this->newLine();
            $this->warn("Fix these issues before testing.");
        }

        $this->newLine();

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }
}
