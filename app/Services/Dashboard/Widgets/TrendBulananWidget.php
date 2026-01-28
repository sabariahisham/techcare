<?php

namespace App\Services\Dashboard\Widgets;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Trend Bulanan Widget
 * 
 * Calculates monthly ticket trends.
 * Returns data suitable for line/bar chart visualization.
 * 
 * @include('dashboard/partials/chart-trend')
 * @include('dashboard/partials/chart-total')
 */
class TrendBulananWidget
{
    /**
     * Get monthly ticket trends for a specific year
     * 
     * @param int|null $year
     * @return array
     */
    public function getData(?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        
        // Get monthly ticket counts
        $monthlyData = DB::table('tickets_chronologies as tc')
            ->select(
                DB::raw('MONTH(tc.created_at) as month'),
                DB::raw('COUNT(tc.id) as count')
            )
            ->whereNull('tc.deleted_at')
            ->whereYear('tc.created_at', $year)
            ->groupBy(DB::raw('MONTH(tc.created_at)'))
            ->orderBy(DB::raw('MONTH(tc.created_at)'))
            ->pluck('count', 'month')
            ->toArray();
        
        // Month names
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        
        // Build complete data for all 12 months
        $result = [];
        foreach ($months as $monthNum => $monthName) {
            $result[] = [
                'month' => $monthNum,
                'month_name' => $monthName,
                'count' => $monthlyData[$monthNum] ?? 0
            ];
        }
        
        return [
            'year' => $year,
            'data' => $result,
            'total' => array_sum($monthlyData)
        ];
    }
    
    /**
     * Get available years from database
     * 
     * @return array
     */
    public function getAvailableYears(): array
    {
        $oldestYear = DB::table('tickets_chronologies')
            ->whereNull('deleted_at')
            ->min(DB::raw('YEAR(created_at)'));
        
        $currentYear = Carbon::now()->year;
        
        if (!$oldestYear) {
            return [$currentYear];
        }
        
        $years = [];
        for ($y = $oldestYear; $y <= $currentYear; $y++) {
            $years[] = $y;
        }
        
        return array_reverse($years);
    }
    
    /**
     * Get ticket type distribution (Self vs On Behalf)
     * 
     * @return array
     */
    public function getTicketTypeDistribution(): array
    {
        $typeCounts = DB::table('tickets as t')
            ->select(
                DB::raw('CASE WHEN t.ticket_type = 1 THEN "Tiket Sendiri" ELSE "Tiket Bagi Pihak" END as type_name'),
                DB::raw('COUNT(t.id) as count')
            )
            ->whereNull('t.deleted_at')
            ->groupBy('t.ticket_type')
            ->get()
            ->toArray();
        
        $result = [
            ['type' => 'Tiket Sendiri', 'count' => 0],
            ['type' => 'Tiket Bagi Pihak', 'count' => 0]
        ];
        
        foreach ($typeCounts as $item) {
            $index = $item->type_name === 'Tiket Sendiri' ? 0 : 1;
            $result[$index]['count'] = (int) $item->count;
        }
        
        return [
            'data' => $result,
            'total' => array_sum(array_column($result, 'count'))
        ];
    }
}
