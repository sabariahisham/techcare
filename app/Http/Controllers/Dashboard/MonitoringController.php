<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * MonitoringController
 *
 * Handles real-time monitoring dashboard for Helpdesk On Duty
 * Designed for TV display with auto-refresh every 1 minute
 */
class MonitoringController extends Controller
{
  /**
   * Display the monitoring dashboard
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $user = Auth::user();

    // Check if user has helpdesk role
    $sections = [
      'helpdesk' => $user->hasAnyRole(['Helpdesk On Duty', 'Penyelia Helpdesk', 'Administrator', 'Pentadbir Sistem'])
    ];

    return view('dashboard.dashboard-monitoring', compact('user', 'sections'));
  }

  /**
   * Get cards data (Aduan Hari Ini, Belum Selesai, Lewat SLA, Selesai Hari Ini)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCardsData()
  {
    try {
      $today = Carbon::today();
      $now = Carbon::now();

      // Aduan Hari Ini - tickets created today
      $aduanHariIni = DB::table('tickets')
        ->whereNull('deleted_at')
        ->whereDate('created_at', $today)
        ->count();

      // Belum Selesai - tickets not completed (status != 6 and != 5)
      $belumSelesai = DB::table('tickets as t')
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
        ->whereNotIn('tc.tick_status', [5, 6]) // not cancelled or completed
        ->count();

      // Total tickets - keseluruhan (all-time, for percentage calculation)
      $totalTickets = DB::table('tickets')
        ->whereNull('deleted_at')
        ->count();

      // Pending percentage - Keseluruhan (belum selesai berbanding jumlah tiket sepanjang masa)
      $pendingPercentageOverall = $totalTickets > 0
        ? round(($belumSelesai / $totalTickets) * 100, 1)
        : 0;

      // Bulan Semasa - julat tarikh bulan ini
      $startOfMonth = Carbon::now()->startOfMonth();
      $endOfMonth = Carbon::now()->endOfMonth();

      // Belum Selesai - Bulan Semasa (tiket dicipta bulan ini & belum selesai/dibatal)
      $belumSelesaiBulanSemasa = DB::table('tickets as t')
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
        ->whereNotIn('tc.tick_status', [5, 6]) // not cancelled or completed
        ->whereBetween('t.created_at', [$startOfMonth, $endOfMonth])
        ->count();

      // Total tickets - Bulan Semasa (semua tiket dicipta bulan ini)
      $totalTicketsBulanSemasa = DB::table('tickets')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->count();

      // Pending percentage - Bulan Semasa
      $pendingPercentageBulanSemasa = $totalTicketsBulanSemasa > 0
        ? round(($belumSelesaiBulanSemasa / $totalTicketsBulanSemasa) * 100, 1)
        : 0;

      // Lewat SLA - tickets past due date
      $lewatSLA = DB::table('tickets as t')
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
        ->whereNotIn('tc.tick_status', [5, 6]) // not cancelled or completed
        ->where('t.tick_duedate', '<', $now)
        ->count();

      // Selesai Hari Ini - tickets completed today
      $selesaiHariIni = DB::table('tickets as t')
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
        ->where('tc.tick_status', 6) // completed
        ->whereDate('t.updated_at', $today)
        ->count();

      // Weekly completed for chart
      $startOfWeek = Carbon::now()->startOfWeek();
      $endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Monday to Friday

      $weeklyData = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                            SELECT MAX(tc2.id)
                            FROM tickets_chronologies tc2
                            WHERE tc2.ticket_id = t.id
                            AND tc2.deleted_at IS NULL
                        )');
        })
        ->select(
          DB::raw('DAYOFWEEK(t.updated_at) as day_of_week'),
          DB::raw('COUNT(DISTINCT t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->where('tc.tick_status', 6)
        ->whereBetween('t.updated_at', [$startOfWeek, $endOfWeek])
        ->groupBy(DB::raw('DAYOFWEEK(t.updated_at)'))
        ->get()
        ->keyBy('day_of_week');

      // Prepare weekly data for Monday to Friday
      $weekDays = [
        2 => 'Isnin',
        3 => 'Selasa',
        4 => 'Rabu',
        5 => 'Khamis',
        6 => 'Jumaat'
      ];

      $chartData = [];
      foreach ($weekDays as $dayNum => $dayName) {
        $count = $weeklyData->get($dayNum)->count ?? 0;
        $chartData[] = [
          'day' => $dayName,
          'count' => $count
        ];
      }

      return response()->json([
        'success' => true,
        'data' => [
          'aduan_hari_ini' => $aduanHariIni,
          'belum_selesai' => $belumSelesai,
          'total_tickets' => $totalTickets,
          'pending_percentage_overall' => $pendingPercentageOverall,
          'belum_selesai_bulan_semasa' => $belumSelesaiBulanSemasa,
          'total_tickets_bulan_semasa' => $totalTicketsBulanSemasa,
          'pending_percentage_bulan_semasa' => $pendingPercentageBulanSemasa,
          'lewat_sla' => $lewatSLA,
          'selesai_hari_ini' => $selesaiHariIni,
          'weekly_chart' => $chartData
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching cards data: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }

  /**
   * Get status chart data (Bar chart showing ticket counts by status)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getStatusChartData()
  {
    try {
      $statusData = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                            SELECT MAX(tc2.id)
                            FROM tickets_chronologies tc2
                            WHERE tc2.ticket_id = t.id
                            AND tc2.deleted_at IS NULL
                        )');
        })
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->select(
          'rts.id as status_id',
          'rts.st_name as status',
          DB::raw('COUNT(DISTINCT t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNull('rts.deleted_at')
        ->groupBy('rts.id', 'rts.st_name')
        ->orderBy('rts.id')
        ->get();

      $completedCount = $statusData
        ->filter(function ($row) {
          return (int) $row->status_id === 6 || strcasecmp(trim($row->status), 'Aduan Selesai') === 0;
        })
        ->sum('count');

      $filteredStatusData = $statusData
        ->reject(function ($row) {
          return (int) $row->status_id === 6 || strcasecmp(trim($row->status), 'Aduan Selesai') === 0;
        });

      $labels = $filteredStatusData->pluck('status')->toArray();
      $counts = $filteredStatusData->pluck('count')->toArray();

      return response()->json([
        'success' => true,
        'data' => [
          'labels' => $labels,
          'counts' => $counts,
          'completed_count' => $completedCount
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching status chart data: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }

  public function getCategoryChartData()
  {
    try {
      $categoryData = DB::table('tickets as t')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->select(
          'rc.cat_name as category',
          DB::raw('COUNT(t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('rc.deleted_at')
        ->groupBy('rc.id', 'rc.cat_name')
        ->orderBy('count', 'desc')
        ->get();

      $labels = $categoryData->pluck('category')->toArray();
      $counts = $categoryData->pluck('count')->toArray();

      return response()->json([
        'success' => true,
        'data' => [
          'labels' => $labels,
          'counts' => $counts
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching category chart data: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }

  /**
   * Get sub-category chart data (Pie chart showing ticket counts by sub-category)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getSubCategoryChartData()
  {
    try {
      $subCategoryData = DB::table('tickets as t')
        ->join('ref_sub_categories as rsc', 't.subcat_id', '=', 'rsc.id')
        ->select(
          'rsc.subcat_name as subcategory',
          DB::raw('COUNT(t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('rsc.deleted_at')
        ->groupBy('rsc.id', 'rsc.subcat_name')
        ->orderBy('count', 'desc')
        ->limit(10) // Top 10 subcategories
        ->get();

      $labels = $subCategoryData->pluck('subcategory')->toArray();
      $counts = $subCategoryData->pluck('count')->toArray();

      return response()->json([
        'success' => true,
        'data' => [
          'labels' => $labels,
          'counts' => $counts
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching subcategory chart data: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }

  /**
   * Get overdue tickets (tickets past SLA)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getOverdueTickets()
  {
    try {
      $now = Carbon::now();

      $overdueTickets = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                            SELECT MAX(tc2.id)
                            FROM tickets_chronologies tc2
                            WHERE tc2.ticket_id = t.id
                            AND tc2.deleted_at IS NULL
                        )');
        })
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_sub_categories as rsc', 't.subcat_id', '=', 'rsc.id')
        ->join('users as u', 't.user_id', '=', 'u.id')
        ->leftJoin('users as assigned', 'tc.escalate_to', '=', 'assigned.id')
        ->select(
          't.ticket_nolog',
          't.ticket_title',
          'rc.cat_name as category',
          'rsc.subcat_name as subcategory',
          'rts.st_name as status',
          'u.name as requester',
          'assigned.name as assigned_to',
          't.tick_duedate',
          't.created_at',
          DB::raw('TIMESTAMPDIFF(HOUR, t.tick_duedate, NOW()) as hours_overdue')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('tc.tick_status', [5, 6]) // not cancelled or completed
        ->where('t.tick_duedate', '<', $now)
        ->orderBy('hours_overdue', 'desc')
        ->limit(10)
        ->get();

      return response()->json([
        'success' => true,
        'data' => $overdueTickets
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching overdue tickets: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }

  /**
   * Get action tickets (tickets requiring action)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getActionTickets()
  {
    try {
      $actionTickets = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                            SELECT MAX(tc2.id)
                            FROM tickets_chronologies tc2
                            WHERE tc2.ticket_id = t.id
                            AND tc2.deleted_at IS NULL
                        )');
        })
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_sub_categories as rsc', 't.subcat_id', '=', 'rsc.id')
        ->join('users as u', 't.user_id', '=', 'u.id')
        ->leftJoin('users as assigned', 'tc.escalate_to', '=', 'assigned.id')
        ->select(
          't.ticket_nolog',
          't.ticket_title',
          'rc.cat_name as category',
          'rsc.subcat_name as subcategory',
          'rts.st_name as status',
          'u.name as requester',
          'assigned.name as assigned_to',
          't.tick_duedate',
          't.created_at',
          DB::raw('TIMESTAMPDIFF(HOUR, NOW(), t.tick_duedate) as hours_remaining')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereIn('tc.tick_status', [1, 2, 3, 4]) // New, In Progress, Handed Over, Referred
        ->orderBy('t.tick_duedate', 'asc')
        ->limit(10)
        ->get();

      return response()->json([
        'success' => true,
        'data' => $actionTickets
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching action tickets: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error fetching data'
      ], 500);
    }
  }
}
