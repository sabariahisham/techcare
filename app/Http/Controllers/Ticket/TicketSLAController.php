<?php

/*
 * Function TicketSLAController.php (REPORTING):
  * - Manage SLA related functionalities for tickets.
  * - Report overdue tickets based on SLA due dates.
  * - Provide SLA statistics and compliance reports.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TicketSLAController extends Controller
{
    /**
     * Get overdue tickets based on SLA (past due date more than 1 day)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverdueTickets()
    {
        try {
            $currentTime = Carbon::now();
            $yesterdayStart = Carbon::yesterday()->startOfDay();

            // Get tickets with overdue SLA (past yesterday)
            $overdueTickets = Ticket::with(['user', 'category', 'subCategory', 'status'])
                ->whereHas('chronologies', function ($query) use ($yesterdayStart) {
                    $query->whereNotNull('sla_duedate')
                        ->where('sla_duedate', '<', $yesterdayStart);
                })
                ->whereNotIn('tick_status', [5, 6]) // Exclude Selesai & Tutup
                ->get()
                ->map(function ($ticket) use ($currentTime) {
                    $latestSLA = $ticket->chronologies()
                        ->whereNotNull('sla_duedate')
                        ->latest()
                        ->first();

                    $overdueHours = $latestSLA && $latestSLA->sla_duedate
                        ? $currentTime->diffInHours($latestSLA->sla_duedate)
                        : 0;

                    $overdueDays = $latestSLA && $latestSLA->sla_duedate
                        ? $currentTime->diffInDays($latestSLA->sla_duedate)
                        : 0;

                    return [
                        'id' => $ticket->id,
                        'uuid' => $ticket->uuid,
                        'ticket_nolog' => $ticket->ticket_nolog,
                        'title' => $ticket->ticket_title,
                        'user_name' => $ticket->user ? $ticket->user->nama : 'Unknown',
                        'category' => $ticket->category ? $ticket->category->cat_name : 'Unknown',
                        'subcategory' => $ticket->subCategory ? $ticket->subCategory->subcat_name : 'Unknown',
                        'status' => $ticket->status ? $ticket->status->st_name : 'Unknown',
                        'sla_due_date' => $latestSLA ? $latestSLA->sla_duedate->format('d/m/Y H:i:s') : null,
                        'overdue_hours' => $overdueHours,
                        'overdue_days' => $overdueDays,
                        'level_pegtugas' => $latestSLA ? $latestSLA->level_pegtugas_id : null,
                        'assigned_to' => $latestSLA && $latestSLA->escalatedTo ? $latestSLA->escalatedTo->nama : 'N/A',
                        'created_at' => $ticket->created_at->format('d/m/Y H:i:s')
                    ];
                });

            return response()->json([
                'success' => true,
                'overdue_tickets' => $overdueTickets,
                'total_count' => $overdueTickets->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa mendapatkan tiket lampau masa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tickets with SLA due tomorrow (SLA-1)
     */
    public function getDueTomorrowTickets()
    {
        try {
            $tomorrow = Carbon::tomorrow()->startOfDay();
            $tomorrowEnd = Carbon::tomorrow()->endOfDay();

            return $this->getTicketsByDateRange($tomorrow, $tomorrowEnd, 'Due Tomorrow (SLA-1)');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa mendapatkan tiket due tomorrow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tickets with SLA due today
     */
    public function getDueTodayTickets()
    {
        try {
            $today = Carbon::today()->startOfDay();
            $todayEnd = Carbon::today()->endOfDay();

            return $this->getTicketsByDateRange($today, $todayEnd, 'Due Today');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa mendapatkan tiket due today: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tickets that passed due date yesterday (due date+1)
     */
    public function getDueYesterdayTickets()
    {
        try {
            $yesterday = Carbon::yesterday()->startOfDay();
            $yesterdayEnd = Carbon::yesterday()->endOfDay();

            return $this->getTicketsByDateRange($yesterday, $yesterdayEnd, 'Due Yesterday (Due Date+1)');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa mendapatkan tiket due yesterday: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get tickets by date range
     */
    private function getTicketsByDateRange(Carbon $startDate, Carbon $endDate, string $label)
    {
        $tickets = Ticket::with(['user', 'category', 'subCategory', 'status'])
            ->whereHas('chronologies', function ($query) use ($startDate, $endDate) {
                $query->whereNotNull('sla_duedate')
                    ->whereBetween('sla_duedate', [$startDate, $endDate]);
            })
            ->whereNotIn('tick_status', [5, 6])
            ->get()
            ->map(function ($ticket) {
                $latestSLA = $ticket->chronologies()
                    ->whereNotNull('sla_duedate')
                    ->latest()
                    ->first();

                return [
                    'id' => $ticket->id,
                    'uuid' => $ticket->uuid,
                    'ticket_nolog' => $ticket->ticket_nolog,
                    'title' => $ticket->ticket_title,
                    'user_name' => $ticket->user ? $ticket->user->nama : 'Unknown',
                    'category' => $ticket->category ? $ticket->category->cat_name : 'Unknown',
                    'subcategory' => $ticket->subCategory ? $ticket->subCategory->subcat_name : 'Unknown',
                    'status' => $ticket->status ? $ticket->status->st_name : 'Unknown',
                    'sla_due_date' => $latestSLA ? $latestSLA->sla_duedate->format('d/m/Y H:i:s') : null,
                    'level_pegtugas' => $latestSLA ? $latestSLA->level_pegtugas_id : null,
                    'assigned_to' => $latestSLA && $latestSLA->escalatedTo ? $latestSLA->escalatedTo->nama : 'N/A',
                    'sla_severity' => $latestSLA ? $latestSLA->sla_severity : null,
                    'created_at' => $ticket->created_at->format('d/m/Y H:i:s')
                ];
            });

        return response()->json([
            'success' => true,
            'label' => $label,
            'tickets' => $tickets,
            'total_count' => $tickets->count()
        ]);
    }

    /**
     * Get SLA statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlaStatistics()
    {
        try {
            $currentTime = Carbon::now();
            $startOfMonth = Carbon::now()->startOfMonth();

            // Get overall SLA statistics
            $stats = [
                'total_tickets_this_month' => Ticket::whereBetween('created_at', [$startOfMonth, $currentTime])->count(),
                'overdue_tickets' => $this->getOverdueTicketsCount(),
                'met_sla_tickets' => $this->getMetSlaTicketsCount(),
                'average_resolution_time' => $this->getAverageResolutionTime(),
                'sla_compliance_rate' => $this->getSlaComplianceRate()
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat semasa mendapatkan statistik SLA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count of overdue tickets (past due date more than 1 day)
     */
    private function getOverdueTicketsCount()
    {
        $yesterdayStart = Carbon::yesterday()->startOfDay();

        return Ticket::whereHas('chronologies', function ($query) use ($yesterdayStart) {
            $query->whereNotNull('sla_duedate')
                ->where('sla_duedate', '<', $yesterdayStart);
        })
        ->whereNotIn('tick_status', [5, 6]) // Exclude Selesai & Tutup
        ->count();
    }

    /**
     * Get count of tickets that met SLA
     */
    private function getMetSlaTicketsCount()
    {
        // Count tickets that were resolved within SLA timeframe
        // Status 5 = Selesai, 6 = Tutup
        return Ticket::whereIn('tick_status', [5, 6])
            ->whereHas('chronologies', function ($query) {
                $query->whereNotNull('sla_duedate')
                    ->whereColumn('created_at', '<=', 'sla_duedate');
            })
            ->count();
    }

    /**
     * Get average resolution time in hours
     */
    private function getAverageResolutionTime()
    {
        // Status 5 = Selesai, 6 = Tutup
        $resolvedTickets = Ticket::whereIn('tick_status', [5, 6])
            ->get()
            ->map(function ($ticket) {
                $resolutionChronology = $ticket->chronologies()
                    ->whereIn('tick_status', [5, 6])
                    ->latest()
                    ->first();

                if ($resolutionChronology) {
                    return $ticket->created_at->diffInHours($resolutionChronology->created_at);
                }
                return null;
            })
            ->filter()
            ->values();

        return $resolvedTickets->count() > 0 ? round($resolvedTickets->average(), 2) : 0;
    }

    /**
     * Calculate SLA compliance rate as percentage
     */
    private function getSlaComplianceRate()
    {
        $totalTicketsWithSla = Ticket::whereHas('chronologies', function ($query) {
            $query->whereNotNull('sla_duedate');
        })->count();

        if ($totalTicketsWithSla == 0) {
            return 100; // No SLA tickets means 100% compliance
        }

        $metSlaCount = $this->getMetSlaTicketsCount();
        $overdueCount = $this->getOverdueTicketsCount();

        $compliantTickets = $totalTicketsWithSla - $overdueCount;

        return round(($compliantTickets / $totalTicketsWithSla) * 100, 2);
    }
}
