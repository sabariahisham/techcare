<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SLA Widget
 * 
 * Calculates SLA compliance data for the dashboard.
 * Supports both overall system SLA and user-specific SLA.
 * 
 * @include('dashboard/partials/table-ticket-overdue')
 */
class SLAWidget
{
    /**
     * Get overall system SLA statistics
     * 
     * @return array
     */
    public function overall(): array
    {
        $now = Carbon::now();
        
        $overdueCount = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->whereNotIn('tc.tick_status', [5, 6])
            ->whereNotNull('tc.sla_duedate')
            ->where('tc.sla_duedate', '<', $now)
            ->count();
        
        $activeTickets = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->whereNotIn('tc.tick_status', [5, 6])
            ->count();
        
        $complianceRate = $activeTickets > 0 
            ? round((($activeTickets - $overdueCount) / $activeTickets) * 100, 1) 
            : 100;
        
        return [
            'overdue_count' => $overdueCount,
            'active_count' => $activeTickets,
            'compliance_rate' => $complianceRate
        ];
    }
    
    /**
     * Get SLA statistics for specific user
     * 
     * @param User $user
     * @return array
     */
    public function forUser(User $user): array
    {
        $now = Carbon::now();
        $userId = $user->id;
        
        $overdueCount = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) use ($userId) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->where('tc.escalate_to', $userId)
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.escalate_to = ' . $userId . '
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->whereNotIn('tc.tick_status', [5, 6])
            ->whereNotNull('tc.sla_duedate')
            ->where('tc.sla_duedate', '<', $now)
            ->count();
        
        $activeTickets = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) use ($userId) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->where('tc.escalate_to', $userId)
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.escalate_to = ' . $userId . '
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->whereNotIn('tc.tick_status', [5, 6])
            ->count();
        
        $complianceRate = $activeTickets > 0 
            ? round((($activeTickets - $overdueCount) / $activeTickets) * 100, 1) 
            : 100;
        
        return [
            'overdue_count' => $overdueCount,
            'active_count' => $activeTickets,
            'compliance_rate' => $complianceRate
        ];
    }
    
    /**
     * Get list of overdue tickets
     * 
     * @param User|null $user Optional user filter
     * @param int $limit Number of records to return
     * @return array
     */
    public function getOverdueList(?User $user = null, int $limit = 10): array
    {
        $now = Carbon::now();
        
        $query = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) use ($user) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
                
                if ($user) {
                    $join->where('tc.escalate_to', $user->id);
                }
            })
            ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
            ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
            ->leftJoin('users as u', 'tc.escalate_to', '=', 'u.id')
            ->select(
                't.id',
                't.ticket_nolog',
                't.ticket_title',
                'rc.cat_name',
                'rts.st_name as status_name',
                'tc.sla_duedate',
                'u.nama as assigned_to',
                DB::raw('DATEDIFF(NOW(), tc.sla_duedate) as days_overdue')
            )
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->whereNotIn('tc.tick_status', [5, 6])
            ->whereNotNull('tc.sla_duedate')
            ->where('tc.sla_duedate', '<', $now)
            ->orderBy('days_overdue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return array_map(function ($item) {
            return (array) $item;
        }, $query);
    }
}
