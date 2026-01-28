<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Tugasan Anda Widget
 * 
 * Calculates ticket counts for a specific Pegawai Tugasan.
 * Filters tickets by escalate_to (assigned officer).
 * 
 * @include('dashboard/partials/my-tasks')
 */
class TugasanAndaWidget
{
    /**
     * Get assigned tickets data for specific user
     * 
     * @param User $user
     * @return array
     */
    public function getData(User $user): array
    {
        $userId = $user->id;
        
        // Get counts from latest chronology where user is assigned
        $statusCounts = DB::table('tickets as t')
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
            ->select('tc.tick_status', DB::raw('COUNT(*) as count'))
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at')
            ->groupBy('tc.tick_status')
            ->pluck('count', 'tick_status')
            ->toArray();
        
        return [
            'baru' => $statusCounts[1] ?? 0,
            'diproses' => ($statusCounts[2] ?? 0) + ($statusCounts[3] ?? 0),
            'dirujuk' => $statusCounts[4] ?? 0,
            'selesai' => $statusCounts[6] ?? 0,
            'dibatal' => $statusCounts[5] ?? 0,
            'total' => array_sum($statusCounts)
        ];
    }
    
    /**
     * Get today's ticket count for user
     * 
     * @param User $user
     * @return int
     */
    public function getTodayCount(User $user): int
    {
        $today = Carbon::today();
        
        return DB::table('tickets_chronologies as tc')
            ->whereNull('tc.deleted_at')
            ->where('tc.escalate_to', $user->id)
            ->whereDate('tc.created_at', $today)
            ->count();
    }
    
    /**
     * Get weekly completed tickets for user
     * 
     * @param User $user
     * @return array
     */
    public function getWeeklyCompleted(User $user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Friday
        
        $weeklyData = DB::table('tickets_chronologies as tc')
            ->select(
                DB::raw('DAYOFWEEK(tc.updated_at) as day_of_week'),
                DB::raw('COUNT(*) as count')
            )
            ->whereNull('tc.deleted_at')
            ->where('tc.escalate_to', $user->id)
            ->where('tc.tick_status', 6)
            ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek])
            ->groupBy(DB::raw('DAYOFWEEK(tc.updated_at)'))
            ->pluck('count', 'day_of_week')
            ->toArray();
        
        $weekDays = [
            2 => 'Isnin',
            3 => 'Selasa',
            4 => 'Rabu',
            5 => 'Khamis',
            6 => 'Jumaat'
        ];
        
        $result = [];
        foreach ($weekDays as $dayNum => $dayName) {
            $result[] = [
                'day' => $dayName,
                'count' => $weeklyData[$dayNum] ?? 0
            ];
        }
        
        return [
            'total' => array_sum($weeklyData),
            'daily_data' => $result
        ];
    }
    
    /**
     * Get pending tickets percentage for user
     * 
     * @param User $user
     * @return array
     */
    public function getPendingStats(User $user): array
    {
        $totalQuery = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) use ($user) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->where('tc.escalate_to', $user->id)
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                        AND tc2.escalate_to = ' . $user->id . '
                        AND tc2.deleted_at IS NULL
                    )');
            })
            ->whereNull('t.deleted_at')
            ->whereNull('tc.deleted_at');
        
        $total = $totalQuery->count();
        
        $pending = (clone $totalQuery)
            ->whereNotIn('tc.tick_status', [5, 6])
            ->count();
        
        $percentage = $total > 0 ? round(($pending / $total) * 100, 1) : 0;
        
        return [
            'pending_count' => $pending,
            'total_count' => $total,
            'percentage' => $percentage
        ];
    }
}
