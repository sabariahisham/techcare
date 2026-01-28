<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Ticket;
use App\Models\RefTicketStatus;
use Illuminate\Support\Facades\DB;

/**
 * Ticket Summary Widget
 * 
 * Calculates overall ticket summary for the system.
 * Returns counts for each status for dashboard cards.
 * 
 * @include('dashboard/partials/cards-status')
 */
class TicketSummaryWidget
{
    /**
     * Get overall ticket summary by status
     * 
     * @return array
     */
    public function getData(): array
    {
        // Get counts grouped by status from the latest chronology
        $statusCounts = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->select('tc.tick_status', DB::raw('COUNT(*) as count'))
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->groupBy('tc.tick_status')
            ->pluck('count', 'tick_status')
            ->toArray();

        return [
            'baru' => $statusCounts[1] ?? 0,           // Status 1: Aduan Baru
            'diproses' => ($statusCounts[2] ?? 0) + ($statusCounts[3] ?? 0), // Status 2,3: Diproses, Serahan
            'dirujuk' => $statusCounts[4] ?? 0,        // Status 4: Aduan Dirujuk
            'selesai' => $statusCounts[6] ?? 0,        // Status 6: Aduan Selesai
            'dibatal' => $statusCounts[5] ?? 0,        // Status 5: Aduan Dibatal
            'total' => array_sum($statusCounts)
        ];
    }
    
    /**
     * Get ticket summary with status names
     * 
     * @return array
     */
    public function getDetailedData(): array
    {
        $statuses = RefTicketStatus::whereNull('deleted_at')->get();
        
        $statusCounts = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->select('tc.tick_status', DB::raw('COUNT(*) as count'))
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->groupBy('tc.tick_status')
            ->pluck('count', 'tick_status')
            ->toArray();
        
        $result = [];
        foreach ($statuses as $status) {
            $result[] = [
                'status_id' => $status->id,
                'status_name' => $status->st_name,
                'count' => $statusCounts[$status->id] ?? 0
            ];
        }
        
        return $result;
    }
}
