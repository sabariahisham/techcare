<?php

/*
 * Function TicketSLAController.php:
 *  - Manage Service Level Agreements (SLA) for tickets.
 *  - Track and calculate response and resolution times.
 *  - Provide SLA compliance reports for Pengadu and Pegawai Tugasan.
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
     * Get overdue tickets based on SLA
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverdueTickets()
    {
        try {
            $currentTime = Carbon::now();

            // Get tickets that are overdue based on SLA due dates from chronologies
            $overdueTickets = Ticket::with(['user', 'category', 'subCategory', 'status'])
                ->whereHas('chronologies', function ($query) use ($currentTime) {
                    $query->whereNotNull('sla_duedate')
                        ->where('sla_duedate', '<', $currentTime);
                })
                ->whereNotIn('tick_status', [6]) // Exclude resolved tickets (status 6 = Selesai)
                ->get()
                ->map(function ($ticket) use ($currentTime) {
                    $latestSLA = $ticket->chronologies()
                        ->whereNotNull('sla_duedate')
                        ->latest()
                        ->first();

                    $overdueHours = $latestSLA && $latestSLA->sla_duedate 
                        ? $currentTime->diffInHours($latestSLA->sla_duedate) 
                        : 0;

                    return [
                        'id' => $ticket->id,
                        'ticket_nolog' => $ticket->ticket_nolog,
                        'title' => $ticket->ticket_title,
                        'user_name' => $ticket->user ? $ticket->user->nama : 'Unknown',
                        'category' => $ticket->category ? $ticket->category->cat_name : 'Unknown',
                        'subcategory' => $ticket->subCategory ? $ticket->subCategory->subcat_name : 'Unknown',
                        'status' => $ticket->status ? $ticket->status->st_name : 'Unknown',
                        'sla_due_date' => $latestSLA ? $latestSLA->sla_duedate->format('d/m/Y H:i:s') : null,
                        'overdue_hours' => $overdueHours,
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
     * Get count of overdue tickets
     */
    private function getOverdueTicketsCount()
    {
        $currentTime = Carbon::now();

        return Ticket::whereHas('chronologies', function ($query) use ($currentTime) {
            $query->whereNotNull('sla_duedate')
                ->where('sla_duedate', '<', $currentTime);
        })
        ->whereNotIn('tick_status', [6]) // Exclude resolved tickets
        ->count();
    }

    /**
     * Get count of tickets that met SLA
     */
    private function getMetSlaTicketsCount()
    {
        // Count tickets that were resolved within SLA timeframe
        return Ticket::where('tick_status', 6) // Resolved status
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
        $resolvedTickets = Ticket::where('tick_status', 6)
            ->get()
            ->map(function ($ticket) {
                $resolutionChronology = $ticket->chronologies()
                    ->where('tick_status', 6)
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
