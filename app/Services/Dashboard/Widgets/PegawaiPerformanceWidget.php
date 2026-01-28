<?php

namespace App\Services\Dashboard\Widgets;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Pegawai Performance Widget
 * 
 * Calculates ticket performance per Pegawai Tugasan.
 * Returns data suitable for bar chart visualization.
 * 
 * @include('dashboard/partials/chart-assigned')
 */
class PegawaiPerformanceWidget
{
    /**
     * Get performance data for all Pegawai Tugasan
     * 
     * @param int|null $month Optional month filter
     * @param int|null $year Optional year filter
     * @param int|null $categoryId Optional category filter
     * @param int|null $subcategoryId Optional subcategory filter
     * @param int $limit Number of top performers to return
     * @return array
     */
    public function getData(
        ?int $month = null,
        ?int $year = null,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        int $limit = 10
    ): array {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;
        
        // Get tickets with their latest chronology
        $query = DB::table('tickets as t')
            ->join('tickets_chronologies as tc_latest', function ($join) {
                $join->on('t.id', '=', 'tc_latest.ticket_id')
                    ->whereRaw('tc_latest.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->join('users as u', 'tc_latest.escalate_to', '=', 'u.id')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->select(
                'u.id as user_id',
                'u.nama as pegawai',
                DB::raw('COUNT(t.id) as jumlah'),
                DB::raw('SUM(CASE WHEN tc_latest.tick_status = 6 THEN 1 ELSE 0 END) as selesai'),
                DB::raw('SUM(CASE WHEN tc_latest.tick_status NOT IN (5, 6) THEN 1 ELSE 0 END) as belum_selesai')
            )
            ->whereNull('t.deleted_at')
            ->whereNull('tc_latest.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereNotNull('tc_latest.escalate_to')
            ->where('r.name', 'Pegawai Tugasan')
            ->whereMonth('tc_latest.created_at', $month)
            ->whereYear('tc_latest.created_at', $year);
        
        // Apply category filter if provided
        if ($categoryId) {
            $query->where('t.cat_id', $categoryId);
        }
        
        // Apply subcategory filter if provided
        if ($subcategoryId) {
            $query->where('t.subcat_id', $subcategoryId);
        }
        
        $results = $query->groupBy('u.id', 'u.nama')
            ->orderByDesc('jumlah')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return array_map(function ($item) {
            return [
                'user_id' => $item->user_id,
                'pegawai' => $item->pegawai,
                'selesai' => (int) $item->selesai,
                'belum_selesai' => (int) $item->belum_selesai,
                'jumlah' => (int) $item->jumlah
            ];
        }, $results);
    }
    
    /**
     * Get performance summary
     * 
     * @param int|null $month
     * @param int|null $year
     * @return array
     */
    public function getSummary(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;
        
        $summary = DB::table('tickets as t')
            ->join('tickets_chronologies as tc_latest', function ($join) {
                $join->on('t.id', '=', 'tc_latest.ticket_id')
                    ->whereRaw('tc_latest.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->join('users as u', 'tc_latest.escalate_to', '=', 'u.id')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->select(
                DB::raw('COUNT(DISTINCT u.id) as total_pegawai'),
                DB::raw('COUNT(t.id) as total_tickets'),
                DB::raw('SUM(CASE WHEN tc_latest.tick_status = 6 THEN 1 ELSE 0 END) as total_completed'),
                DB::raw('SUM(CASE WHEN tc_latest.tick_status NOT IN (5, 6) THEN 1 ELSE 0 END) as total_pending')
            )
            ->whereNull('t.deleted_at')
            ->whereNull('tc_latest.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereNotNull('tc_latest.escalate_to')
            ->where('r.name', 'Pegawai Tugasan')
            ->whereMonth('tc_latest.created_at', $month)
            ->whereYear('tc_latest.created_at', $year)
            ->first();
        
        if (!$summary) {
            return [
                'total_pegawai' => 0,
                'total_tickets' => 0,
                'total_completed' => 0,
                'total_pending' => 0,
                'completion_rate' => 0
            ];
        }
        
        $completionRate = $summary->total_tickets > 0
            ? round(($summary->total_completed / $summary->total_tickets) * 100, 1)
            : 0;
        
        return [
            'total_pegawai' => (int) $summary->total_pegawai,
            'total_tickets' => (int) $summary->total_tickets,
            'total_completed' => (int) $summary->total_completed,
            'total_pending' => (int) $summary->total_pending,
            'completion_rate' => $completionRate
        ];
    }
}
