<?php

/* Task
Implement the DashboardController index() method.

Rules:
- Use DashboardService.
- Do NOT put any business logic here.
- Pass all data returned from the service directly to the dashboard blade.
- Assume authentication is already handled.

Only return the final controller code.
*/

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Models\RefTicketStatus;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
  protected DashboardService $dashboardService;

  public function __construct(DashboardService $dashboardService)
  {
    $this->dashboardService = $dashboardService;
  }

  /**
   * Display the dashboard
   *
   * This method delegates to DashboardService which routes to the appropriate
   * role-specific dashboard service based on the authenticated user's role.
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $user = Auth::user();
    $data = $this->dashboardService->getDashboardData($user);

    return view('dashboard.dashboard', $data);
  }

  // New methods for the 4 dashboard cards //
  // ----------------------------------------
  /* Log Hari Ini */
  public function getTodayLogsCount()
  {
    try {
      $today = Carbon::today();
      $userId = Auth::id();

      // Count tickets created today from tickets_chronologies
      $query = DB::table('tickets_chronologies as tc')
        ->whereNull('tc.deleted_at')
        ->whereDate('tc.created_at', $today);

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $query->where('tc.escalate_to', $userId);
      }

      $todayCount = $query->count();

      return response()->json([
        'success' => true,
        'count' => $todayCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'count' => 15 // Fallback sample data
      ]);
    }
  }

  /* Selesai Minggu Ini */
  public function getWeeklyCompletedTickets()
  {
    try {
      $startOfWeek = Carbon::now()->startOfWeek(); // Monday
      $endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Friday (Sunday minus 2 days)
      $userId = Auth::id();

      // Get completed tickets (status = 6) for this week, grouped by day
      $query = DB::table('tickets_chronologies as tc')
        ->select(
          DB::raw('DAYOFWEEK(tc.updated_at) as day_of_week'),
          DB::raw('DATE(tc.updated_at) as date'),
          DB::raw('COUNT(*) as count')
        )
        ->whereNull('tc.deleted_at')
        ->where('tc.tick_status', 6) // Completed status
        ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek]);

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $query->where('tc.escalate_to', $userId);
      }

      $weeklyData = $query->groupBy(DB::raw('DATE(tc.updated_at)'), DB::raw('DAYOFWEEK(tc.updated_at)'))
        ->orderBy(DB::raw('DATE(tc.updated_at)'))
        ->get();

      // Prepare data for Monday to Friday
      $weekDays = [
        2 => 'Isnin',    // Monday
        3 => 'Selasa',   // Tuesday
        4 => 'Rabu',     // Wednesday
        5 => 'Khamis',   // Thursday
        6 => 'Jumaat'    // Friday
      ];

      $chartData = [];
      $totalCompleted = 0;

      foreach ($weekDays as $dayNum => $dayName) {
        $dayData = $weeklyData->where('day_of_week', $dayNum)->first();
        $count = $dayData ? $dayData->count : 0;
        $totalCompleted += $count;

        $chartData[] = [
          'day' => $dayName,
          'count' => $count
        ];
      }

      return response()->json([
        'success' => true,
        'total' => $totalCompleted,
        'daily_data' => $chartData
      ]);
    } catch (\Exception $e) {
      // Fallback sample data
      $sampleData = [
        ['day' => 'Isnin', 'count' => 8],
        ['day' => 'Selasa', 'count' => 12],
        ['day' => 'Rabu', 'count' => 6],
        ['day' => 'Khamis', 'count' => 9],
        ['day' => 'Jumaat', 'count' => 5]
      ];

      return response()->json([
        'success' => true,
        'total' => 40,
        'daily_data' => $sampleData
      ]);
    }
  }

  /* Belum Selesai */
  public function getPendingTicketsStats()
  {
    try {
      $userId = Auth::id();

      // Base query for total tickets
      $totalTicketsQuery = DB::table('tickets as t')
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
        ->whereNull('tc.deleted_at');

      // Base query for pending tickets
      $pendingTicketsQuery = DB::table('tickets as t')
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
        ->whereNotIn('tc.tick_status', [5, 6]); // Not cancelled or completed

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $totalTicketsQuery->where('tc.escalate_to', $userId);
        $pendingTicketsQuery->where('tc.escalate_to', $userId);
      }

      $totalTickets = $totalTicketsQuery->count();
      $pendingTickets = $pendingTicketsQuery->count();

      // Calculate percentage
      $percentage = $totalTickets > 0 ? round(($pendingTickets / $totalTickets) * 100, 1) : 0;

      return response()->json([
        'success' => true,
        'pending_count' => $pendingTickets,
        'total_count' => $totalTickets,
        'percentage' => $percentage
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'pending_count' => 25,
        'total_count' => 100,
        'percentage' => 25.0
      ]);
    }
  }

  /* Lewat SLA */
  public function getOverdueSLACount()
  {
    try {
      $now = Carbon::now();
      $userId = Auth::id();

      // Count tickets that are overdue (past SLA due date and not completed)
      $overdueQuery = DB::table('tickets as t')
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
        ->where('tc.tick_status', '!=', 6) // Not completed
        ->where('tc.tick_status', '!=', 5) // Not cancelled
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now);

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $overdueQuery->where('tc.escalate_to', $userId);
      }

      $overdueCount = $overdueQuery->count();

      return response()->json([
        'success' => true,
        'count' => $overdueCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'count' => 7 // Fallback sample data
      ]);
    }
  }

  // Status Tiket
  // -------------
  public function getStatusTickets()
  {
    $userId = Auth::id();

    try {
      // Get status counts from ticket_chronologies table
      $statusQuery = DB::table('tickets_chronologies as tc')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->select('rts.id', 'rts.st_name', DB::raw('COUNT(tc.id) as count'))
        ->whereNull('tc.deleted_at')
        ->whereNull('rts.deleted_at');

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $statusQuery->where('tc.escalate_to', $userId);
      }

      $statusCounts = $statusQuery->groupBy('rts.id', 'rts.st_name')
        ->orderBy('rts.id')
        ->get();

      // Get all statuses with zero count for those not in chronologies
      $allStatuses = RefTicketStatus::all();
      $statusData = [];

      foreach ($allStatuses as $status) {
        $count = $statusCounts->where('id', $status->id)->first();
        $statusData[] = [
          'id' => $status->id,
          'st_name' => $status->st_name,
          'count' => $count ? $count->count : 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $statusData
      ]);
    } catch (\Exception $e) {
      // Fallback with sample data if database queries fail
      $sampleData = [
        ['id' => 1, 'st_name' => 'Aduan Baru', 'count' => 15],
        ['id' => 2, 'st_name' => 'Aduan Diproses', 'count' => 8],
        ['id' => 3, 'st_name' => 'Serahan Kepada', 'count' => 5],
        ['id' => 4, 'st_name' => 'Aduan Dirujuk', 'count' => 3],
        ['id' => 5, 'st_name' => 'Aduan DiBatal', 'count' => 2],
        ['id' => 6, 'st_name' => 'Aduan Selesai', 'count' => 25]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData
      ]);
    }
  }

  // Kategori Tiket
  // --------------
  public function getCategoryTickets()
  {
    try {
      $userId = Auth::id();

      // Get all categories first
      $allCategories = RefCategory::select('id', 'cat_name')->orderBy('id')->get();

      // Get ticket counts per category
      $categoryQuery = DB::table('tickets as t')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->select('rc.id', DB::raw('COUNT(t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('rc.deleted_at');

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      if (
        !Auth::user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']) &&
        Auth::user()->hasRole('Pegawai Tugasan')
      ) {
        $categoryQuery->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereRaw('tc.id = (
              SELECT MAX(tc2.id)
              FROM tickets_chronologies tc2
              WHERE tc2.ticket_id = t.id
              AND tc2.deleted_at IS NULL
            )');
        })
          ->whereNull('tc.deleted_at');
      }

      $categoryCounts = $categoryQuery->groupBy('rc.id')
        ->pluck('count', 'id');

      // Build complete data with zero counts for categories with no tickets
      $categoryData = [];
      foreach ($allCategories as $category) {
        $categoryData[] = [
          'id' => $category->id,
          'cat_name' => $category->cat_name,
          'count' => $categoryCounts[$category->id] ?? 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $categoryData
      ]);
    } catch (\Exception $e) {
      // Fallback with sample data including all 7 categories
      $sampleData = [
        ['id' => 1, 'cat_name' => 'Aplikasi', 'count' => 8],
        ['id' => 2, 'cat_name' => 'Perkakasan & Perkhidmatan ICT', 'count' => 15],
        ['id' => 3, 'cat_name' => 'Keselamatan ICT', 'count' => 3],
        ['id' => 4, 'cat_name' => 'Pangkalan Data & Sysadmin', 'count' => 2],
        ['id' => 5, 'cat_name' => 'Pusat Data', 'count' => 20],
        ['id' => 6, 'cat_name' => 'Rangkaian', 'count' => 5],
        ['id' => 7, 'cat_name' => 'Emel/SSO', 'count' => 0]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData
      ]);
    }
  }

  // Tiket Aduan Mengikut Jenis (Tiket Sendiri vs Tiket Bagi Pihak)
  // ---------------------------------------------------------------
  public function getTotalTickets()
  {
    try {
      // Get total tickets count from tickets_chronologies table
      $totalCount = DB::table('tickets_chronologies')
        ->whereNull('deleted_at')
        ->count();

      // Get tickets count by type (Self vs On Behalf)
      $ticketTypes = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
        ->select(
          DB::raw('CASE
            WHEN t.ticket_type = 1 THEN "Tiket Sendiri"
            ELSE "Tiket Bagi Pihak"
          END as type_name'),
          DB::raw('COUNT(DISTINCT t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->groupBy('t.ticket_type')
        ->get();

      // If no data, provide fallback
      if ($ticketTypes->isEmpty()) {
        $ticketTypes = collect([
          ['type_name' => 'Tiket Sendiri', 'count' => 0],
          ['type_name' => 'Tiket Bagi Pihak', 'count' => 0]
        ]);
        $totalCount = 0;
      }

      return response()->json([
        'success' => true,
        'data' => $ticketTypes->toArray(),
        'total' => $totalCount
      ]);
    } catch (\Exception $e) {
      // Fallback with sample data
      $sampleData = [
        ['type_name' => 'Tiket Sendiri', 'count' => 45],
        ['type_name' => 'Tiket Bagi Pihak', 'count' => 13]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData,
        'total' => 58
      ]);
    }
  }

  // Trend Bulanan Tiket mengikut Tahun
  // ----------------------------------
  public function getMonthlyTrends(Request $request)
  {
    $year = $request->get('year', Carbon::now()->year);

    try {
      // Get monthly ticket counts from tickets_chronologies table
      $monthlyData = DB::table('tickets_chronologies as tc')
        ->select(
          DB::raw('MONTH(tc.created_at) as month'),
          DB::raw('COUNT(tc.id) as count')
        )
        ->whereNull('tc.deleted_at')
        ->whereYear('tc.created_at', $year)
        ->groupBy(DB::raw('MONTH(tc.created_at)'))
        ->orderBy(DB::raw('MONTH(tc.created_at)'))
        ->get();

      // Create array for all 12 months with zero count as default
      $months = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
      ];

      $trendData = [];
      $totalForYear = 0;

      foreach ($months as $monthNum => $monthName) {
        $monthData = $monthlyData->where('month', $monthNum)->first();
        $count = $monthData ? $monthData->count : 0;
        $totalForYear += $count;

        $trendData[] = [
          'month' => $monthNum,
          'month_name' => $monthName,
          'count' => $count
        ];
      }

      // Get the oldest and newest years from database
      $oldestYear = DB::table('tickets_chronologies')
        ->whereNull('deleted_at')
        ->min(DB::raw('YEAR(created_at)'));

      $currentYear = Carbon::now()->year;

      // Generate all years from oldest to current
      $availableYears = [];
      if ($oldestYear) {
        for ($y = $oldestYear; $y <= $currentYear; $y++) {
          $availableYears[] = $y;
        }
        // Reverse to show newest first
        $availableYears = array_reverse($availableYears);
      } else {
        // If no data, just show current year
        $availableYears = [$currentYear];
      }

      return response()->json([
        'success' => true,
        'data' => $trendData,
        'year' => (int)$year,
        'total' => $totalForYear,
        'available_years' => $availableYears
      ]);
    } catch (\Exception $e) {
      // Fallback with sample data
      $sampleData = [
        ['month' => 1, 'month_name' => 'Jan', 'count' => 12],
        ['month' => 2, 'month_name' => 'Feb', 'count' => 18],
        ['month' => 3, 'month_name' => 'Mar', 'count' => 15],
        ['month' => 4, 'month_name' => 'Apr', 'count' => 22],
        ['month' => 5, 'month_name' => 'May', 'count' => 28],
        ['month' => 6, 'month_name' => 'Jun', 'count' => 25],
        ['month' => 7, 'month_name' => 'Jul', 'count' => 30],
        ['month' => 8, 'month_name' => 'Aug', 'count' => 20],
        ['month' => 9, 'month_name' => 'Sep', 'count' => 16],
        ['month' => 10, 'month_name' => 'Oct', 'count' => 24],
        ['month' => 11, 'month_name' => 'Nov', 'count' => 19],
        ['month' => 12, 'month_name' => 'Dec', 'count' => 14]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData,
        'year' => (int)$year,
        'total' => 243,
        'available_years' => [2024, 2023, 2022]
      ]);
    }
  }

  // Jumlah Tiket Mengikut Pegawai Tugasan dengan pecahan siap vs belum siap
  // -----------------------------------------------------------------------
  public function getTotalAssigned(Request $request)
  {
    $month = $request->get('month', Carbon::now()->month);
    $year = $request->get('year', Carbon::now()->year);
    $categoryId = $request->get('category_id');
    $subcategoryId = $request->get('subcategory_id');
    $isDebug = config('app.debug', false);

    try {
      // Step 1: Get tickets with their latest chronology status
      // This ensures we get the current status for each ticket
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
          't.id as ticket_id',
          't.ticket_nolog',
          't.cat_id',
          't.subcat_id',
          'u.id as user_id',
          'u.nama as officer_name',
          'r.name as role_name',
          'tc_latest.tick_status as current_status',
          'tc_latest.id as latest_chronology_id',
          'tc_latest.created_at as assigned_date',
          'tc_latest.escalate_to'
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc_latest.deleted_at')
        ->whereNull('u.deleted_at')
        ->whereNotNull('tc_latest.escalate_to')
        ->where('r.name', 'Pegawai Tugasan') // Filter for Pegawai Tugasan role only
        ->whereMonth('tc_latest.created_at', $month)
        ->whereYear('tc_latest.created_at', $year);

      // Add category filtering if specified
      if ($categoryId) {
        $query->where('t.cat_id', $categoryId);
      }

      // Add subcategory filtering if specified
      if ($subcategoryId) {
        $query->where('t.subcat_id', $subcategoryId);
      }

      $ticketsWithLatestStatus = $query->get();

      // Step 2: Group and aggregate data by officer
      $officerGroups = $ticketsWithLatestStatus->groupBy('user_id');

      $assignedData = collect();

      foreach ($officerGroups as $userId => $tickets) {
        $firstTicket = $tickets->first();

        // Separate completed and pending tickets
        $completedTickets = $tickets->where('current_status', 6); // Status 6 = Aduan Selesai
        $pendingTickets = $tickets->whereNotIn('current_status', [5, 6]); // Not cancelled or completed

        $totalCount = $tickets->count();
        $completedCount = $completedTickets->count();
        $pendingCount = $pendingTickets->count();

        $officerData = [
          'user_id' => $userId,
          'officer_name' => $firstTicket->officer_name,
          'role_name' => $firstTicket->role_name,
          'total_tickets' => $totalCount,
          'completed_tickets' => $completedCount,
          'pending_tickets' => $pendingCount
        ];

        // Add detailed debug information for development
        if ($isDebug) {
          $completedTicketIds = $completedTickets->pluck('ticket_id')->toArray();
          $pendingTicketIds = $pendingTickets->pluck('ticket_id')->toArray();
          $allTicketIds = $tickets->pluck('ticket_id')->toArray();
          $chronologyIds = $tickets->pluck('latest_chronology_id')->toArray();

          $officerData['debug'] = [
            'user_id' => $userId,
            'all_ticket_ids' => $allTicketIds,
            'completed_ticket_ids' => $completedTicketIds,
            'pending_ticket_ids' => $pendingTicketIds,
            'chronology_ids' => $chronologyIds,
            'completed_tickets_detail' => $completedTickets->map(function ($ticket) {
              return [
                'ticket_id' => $ticket->ticket_id,
                'ticket_nolog' => $ticket->ticket_nolog,
                'status' => $ticket->current_status,
                'chronology_id' => $ticket->latest_chronology_id
              ];
            })->values()->toArray(),
            'pending_tickets_detail' => $pendingTickets->map(function ($ticket) {
              return [
                'ticket_id' => $ticket->ticket_id,
                'ticket_nolog' => $ticket->ticket_nolog,
                'status' => $ticket->current_status,
                'chronology_id' => $ticket->latest_chronology_id
              ];
            })->values()->toArray(),
            'tooltip' => sprintf(
              "User: %s (ID: %d) | Total: %d | Selesai: %d (IDs: %s) | Belum: %d (IDs: %s)",
              $firstTicket->officer_name,
              $userId,
              $totalCount,
              $completedCount,
              implode(',', $completedTicketIds),
              $pendingCount,
              implode(',', $pendingTicketIds)
            )
          ];
        }

        $assignedData->push($officerData);
      }

      // Step 3: Sort by total tickets and limit to top 10
      $assignedData = $assignedData->sortByDesc('total_tickets')->take(10)->values();

      // Calculate totals for the period
      $totalAssignedTickets = $assignedData->sum('total_tickets');
      $totalCompletedTickets = $assignedData->sum('completed_tickets');
      $totalPendingTickets = $assignedData->sum('pending_tickets');

      // Step 4: Get available months for dropdown (months that have assigned tickets)
      $availableMonths = DB::table('tickets_chronologies')
        ->select(
          DB::raw('MONTH(created_at) as month'),
          DB::raw('YEAR(created_at) as year')
        )
        ->whereNull('deleted_at')
        ->whereNotNull('escalate_to')
        ->groupBy(DB::raw('MONTH(created_at)'), DB::raw('YEAR(created_at)'))
        ->orderBy(DB::raw('YEAR(created_at)'), 'desc')
        ->orderBy(DB::raw('MONTH(created_at)'), 'desc')
        ->get()
        ->map(function ($item) {
          $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
          ];
          return [
            'month' => $item->month,
            'year' => $item->year,
            'month_name' => $monthNames[$item->month],
            'display_name' => $monthNames[$item->month] . ' ' . $item->year
          ];
        })
        ->unique(function ($item) {
          return $item['month'] . '-' . $item['year'];
        })
        ->values();

      return response()->json([
        'success' => true,
        'data' => $assignedData->toArray(),
        'month' => (int)$month,
        'year' => (int)$year,
        'total' => $totalAssignedTickets,
        'total_completed' => $totalCompletedTickets,
        'total_pending' => $totalPendingTickets,
        'available_months' => $availableMonths->toArray(),
        'debug_mode' => $isDebug
      ]);
    } catch (\Exception $e) {
      // Return error with no fallback sample data
      return response()->json([
        'success' => false,
        'message' => 'Error retrieving assigned tickets data: ' . $e->getMessage(),
        'data' => [],
        'month' => (int)$month,
        'year' => (int)$year,
        'total' => 0,
        'total_completed' => 0,
        'total_pending' => 0,
        'available_months' => [],
        'debug_mode' => $isDebug
      ]);
    }
  }

  // Senarai Tiket Melebihi Tempoh SLA oleh Pegawai Tugasan
  // -------------------------------------------------------
  public function getListOverdue(Request $request)
  {
    try {
      // Define overdue criteria:
      // Tickets that are not completed (status != 6 'Aduan Selesai') and have passed their SLA due date
      $now = Carbon::now();
      $userId = Auth::id();
      $user = Auth::user();

      // Get overdue tickets from the latest chronology for each ticket
      $overdueQuery = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
              SELECT MAX(tc2.id)
              FROM tickets_chronologies tc2
              WHERE tc2.ticket_id = t.id
              AND tc2.deleted_at IS NULL
            )');
        })
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->leftJoin('users as u', 'tc.escalate_to', '=', 'u.id')
        ->select(
          't.id',
          't.uuid',
          't.ticket_nolog',
          't.ticket_title',
          'rc.cat_name',
          'rts.st_name as status_name',
          'rts.id as status_id',
          'tc.sla_duedate',
          'tc.escalate_to',
          'u.nama as assigned_to',
          't.created_at as ticket_created',
          DB::raw('DATEDIFF(NOW(), tc.sla_duedate) as days_overdue')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->where('tc.tick_status', '!=', 6) // Not completed
        ->where('tc.tick_status', '!=', 5) // Not cancelled
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now);

      // Filter logic:
      // - If user has admin roles → show ALL data (overall)
      // - If user ONLY has Pegawai Tugasan → show filtered data (assigned to them only)
      $hasAdminRoles = $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pengurusan MOF']);
      $hasPegawaiRole = $user->hasRole('Pegawai Tugasan');

      // Debug user roles
      Log::info('Overdue Query - User Roles', [
        'user_id' => $userId,
        'user_name' => $user->nama,
        'all_roles' => $user->getRoleNames()->toArray(),
        'has_admin_roles' => $hasAdminRoles,
        'has_pegawai_role' => $hasPegawaiRole,
        'will_filter' => (!$hasAdminRoles && $hasPegawaiRole)
      ]);

      if (!$hasAdminRoles && $hasPegawaiRole) {
        $overdueQuery->where('tc.escalate_to', $userId);
        Log::info('Overdue Query - Filter Applied', [
          'escalate_to' => $userId
        ]);
      }

      // Log the SQL query before execution
      $sql = $overdueQuery->toSql();
      $bindings = $overdueQuery->getBindings();
      Log::info('Overdue Query - SQL', [
        'sql' => $sql,
        'bindings' => $bindings
      ]);

      $overdueTickets = $overdueQuery->orderBy('days_overdue', 'desc')
        ->get();

      // Debug logging
      Log::info('Overdue Tickets Query Results', [
        'user_id' => $userId,
        'has_admin_roles' => $hasAdminRoles,
        'has_pegawai_role' => $hasPegawaiRole,
        'filtered_for_pegawai' => (!$hasAdminRoles && $hasPegawaiRole),
        'total_tickets' => $overdueTickets->count(),
        'tickets' => $overdueTickets->pluck('ticket_nolog')->toArray()
      ]);

      // Process data for DataTable
      $processedData = $overdueTickets->map(function ($ticket, $index) {
        return [
          'ticket_id' => $ticket->id,
          'ticket_uuid' => $ticket->uuid,
          'ticket_nolog' => $ticket->ticket_nolog,
          'kategori' => $ticket->cat_name,
          'tajuk' => $ticket->ticket_title,
          'status' => $ticket->status_name,
          'status_id' => $ticket->status_id,
          'assigned_to' => $ticket->assigned_to ?? 'Belum Ditugaskan',
          'sla_duedate' => $ticket->sla_duedate ? Carbon::parse($ticket->sla_duedate)->format('d/m/Y H:i') : '-',
          'days_overdue' => max(0, $ticket->days_overdue),
          'ticket_created' => Carbon::parse($ticket->ticket_created)->format('d/m/Y H:i')
        ];
      });

      if ($request->ajax()) {
        $response = datatables($processedData)
          ->addIndexColumn()
          ->addColumn('action', function ($row) {
            return '<button type="button" class="btn btn-sm view-record" data-uuid="' . $row['ticket_uuid'] . '" title="Lihat Tiket"><i class="bx bx-show"></i></button>';
          })
          ->editColumn('status', function ($row) {
            $badgeClass = 'badge-warning'; // Default for overdue
            if ($row['status_id'] == 1) $badgeClass = 'badge-info';
            else if ($row['status_id'] == 2) $badgeClass = 'badge-warning';
            else if ($row['status_id'] == 3) $badgeClass = 'badge-primary';
            else if ($row['status_id'] == 4) $badgeClass = 'badge-secondary';

            return '<span class="badge ' . $badgeClass . '">' . $row['status'] . '</span>';
          })
          ->editColumn('days_overdue', function ($row) {
            $days = $row['days_overdue'];
            if ($days > 7) {
              $class = 'text-danger fw-bold';
              $icon = '<i class="ti ti-bell text-danger blink-icon me-1"></i>';
            } else if ($days > 3) {
              $class = 'text-warning fw-bold';
              $icon = '<i class="ti ti-bell text-warning me-1"></i>';
            } else {
              $class = 'text-info';
              $icon = '<i class="ti ti-bell text-info me-1"></i>';
            }
            return $icon . '<span class="' . $class . '">' . $days . ' hari</span>';
          })
          ->rawColumns(['action', 'status', 'days_overdue']);

        // Add debug info if debug mode is enabled
        if (config('app.debug')) {
          $response->with('debug', [
            'user_id' => $userId,
            'user_name' => $user->nama,
            'all_roles' => $user->getRoleNames()->toArray(),
            'has_admin_roles' => $hasAdminRoles,
            'has_pegawai_role' => $hasPegawaiRole,
            'filtered_for_pegawai' => (!$hasAdminRoles && $hasPegawaiRole),
            'total_tickets_fetched' => $overdueTickets->count(),
            'ticket_nologs' => $overdueTickets->pluck('ticket_nolog')->toArray()
          ]);
        }

        return $response->make(true);
      }

      return response()->json([
        'success' => true,
        'data' => $processedData->toArray(),
        'total' => $processedData->count()
      ]);
    } catch (\Exception $e) {
      \Log::error('Error in getListOverdue', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => Auth::id()
      ]);

      if ($request->ajax()) {
        return response()->json([
          'draw' => intval($request->input('draw')),
          'recordsTotal' => 0,
          'recordsFiltered' => 0,
          'data' => [],
          'error' => config('app.debug') ? $e->getMessage() : 'Gagal memuat data'
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => 'Gagal memuat data tiket overdue',
        'error' => config('app.debug') ? $e->getMessage() : null
      ], 500);
    }
  }

  // Get subcategories for a specific category
  // ----------------------------------------
  public function getSubcategories($categoryId)
  {
    try {
      $subcategories = \App\Models\RefSubCategory::where('cat_id', $categoryId)
        ->whereNull('deleted_at')
        ->orderBy('subcat_name')
        ->get(['id', 'subcat_name']);

      return response()->json([
        'success' => true,
        'data' => $subcategories
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error retrieving subcategories: ' . $e->getMessage(),
        'data' => []
      ]);
    }
  }

  // =============================================================================
  // PEGAWAI TUGASAN SPECIFIC ENDPOINTS
  // These methods ALWAYS filter by escalate_to (assigned tasks only)
  // Used by cards-status-pegawai.blade.php in the Pegawai Tugasan section
  // =============================================================================

  /**
   * Get today's logs count for Pegawai Tugasan (assigned tasks only)
   */
  public function getTodayLogsCountPegawai()
  {
    try {
      $today = Carbon::today();
      $userId = Auth::id();

      // Count tickets created today from tickets_chronologies
      // ALWAYS filter by escalate_to (assigned to this user)
      $todayCount = DB::table('tickets_chronologies as tc')
        ->whereNull('tc.deleted_at')
        ->whereDate('tc.created_at', $today)
        ->where('tc.escalate_to', $userId)
        ->count();

      return response()->json([
        'success' => true,
        'count' => $todayCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'count' => 0
      ]);
    }
  }

  /**
   * Get weekly completed tickets for Pegawai Tugasan (assigned tasks only)
   */
  public function getWeeklyCompletedTicketsPegawai()
  {
    try {
      $startOfWeek = Carbon::now()->startOfWeek(); // Monday
      $endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Friday
      $userId = Auth::id();

      // Get completed tickets (status = 6) for this week, grouped by day
      // ALWAYS filter by escalate_to (assigned to this user)
      $weeklyData = DB::table('tickets_chronologies as tc')
        ->select(
          DB::raw('DAYOFWEEK(tc.updated_at) as day_of_week'),
          DB::raw('DATE(tc.updated_at) as date'),
          DB::raw('COUNT(*) as count')
        )
        ->whereNull('tc.deleted_at')
        ->where('tc.tick_status', 6) // Completed status
        ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek])
        ->where('tc.escalate_to', $userId)
        ->groupBy(DB::raw('DATE(tc.updated_at)'), DB::raw('DAYOFWEEK(tc.updated_at)'))
        ->orderBy(DB::raw('DATE(tc.updated_at)'))
        ->get();

      // Prepare data for Monday to Friday
      $weekDays = [
        2 => 'Isnin',    // Monday
        3 => 'Selasa',   // Tuesday
        4 => 'Rabu',     // Wednesday
        5 => 'Khamis',   // Thursday
        6 => 'Jumaat'    // Friday
      ];

      $chartData = [];
      $totalCompleted = 0;

      foreach ($weekDays as $dayNum => $dayName) {
        $dayData = $weeklyData->where('day_of_week', $dayNum)->first();
        $count = $dayData ? $dayData->count : 0;
        $totalCompleted += $count;

        $chartData[] = [
          'day' => $dayName,
          'count' => $count
        ];
      }

      return response()->json([
        'success' => true,
        'total' => $totalCompleted,
        'daily_data' => $chartData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'total' => 0,
        'daily_data' => [
          ['day' => 'Isnin', 'count' => 0],
          ['day' => 'Selasa', 'count' => 0],
          ['day' => 'Rabu', 'count' => 0],
          ['day' => 'Khamis', 'count' => 0],
          ['day' => 'Jumaat', 'count' => 0]
        ]
      ]);
    }
  }

  /**
   * Get pending tickets stats for Pegawai Tugasan (assigned tasks only)
   */
  public function getPendingTicketsStatsPegawai()
  {
    try {
      $userId = Auth::id();

      // Base query for total tickets assigned to this user
      $totalTickets = DB::table('tickets as t')
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
        ->where('tc.escalate_to', $userId)
        ->count();

      // Base query for pending tickets assigned to this user
      $pendingTickets = DB::table('tickets as t')
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
        ->whereNotIn('tc.tick_status', [5, 6]) // Not cancelled or completed
        ->where('tc.escalate_to', $userId)
        ->count();

      // Calculate percentage
      $percentage = $totalTickets > 0 ? round(($pendingTickets / $totalTickets) * 100, 1) : 0;

      return response()->json([
        'success' => true,
        'pending_count' => $pendingTickets,
        'total_count' => $totalTickets,
        'percentage' => $percentage
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'pending_count' => 0,
        'total_count' => 0,
        'percentage' => 0
      ]);
    }
  }

  /**
   * Get overdue SLA count for Pegawai Tugasan (assigned tasks only)
   */
  public function getOverdueSLACountPegawai()
  {
    try {
      $now = Carbon::now();
      $userId = Auth::id();

      // Count tickets that are overdue (past SLA due date and not completed)
      // ALWAYS filter by escalate_to (assigned to this user)
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
        ->where('tc.tick_status', '!=', 6) // Not completed
        ->where('tc.tick_status', '!=', 5) // Not cancelled
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now)
        ->where('tc.escalate_to', $userId)
        ->count();

      return response()->json([
        'success' => true,
        'count' => $overdueCount
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => true,
        'count' => 0
      ]);
    }
  }

  /**
   * Get status tickets for Pegawai Tugasan (assigned tasks only)
   */
  public function getStatusTicketsPegawai()
  {
    $userId = Auth::id();

    try {
      // Get status counts from ticket_chronologies table
      // ALWAYS filter by escalate_to (assigned to this user)
      $statusQuery = DB::table('tickets_chronologies as tc')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->select('rts.id', 'rts.st_name', DB::raw('COUNT(tc.id) as count'))
        ->whereNull('tc.deleted_at')
        ->whereNull('rts.deleted_at')
        ->where('tc.escalate_to', $userId);

      $statusCounts = $statusQuery->groupBy('rts.id', 'rts.st_name')
        ->orderBy('rts.id')
        ->get();

      // Get all statuses with zero count for those not in chronologies
      $allStatuses = RefTicketStatus::all();
      $statusData = [];

      foreach ($allStatuses as $status) {
        $count = $statusCounts->where('id', $status->id)->first();
        $statusData[] = [
          'id' => $status->id,
          'st_name' => $status->st_name,
          'count' => $count ? $count->count : 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $statusData
      ]);
    } catch (\Exception $e) {
      // Fallback with empty data
      $sampleData = [
        ['id' => 1, 'st_name' => 'Aduan Baru', 'count' => 0],
        ['id' => 2, 'st_name' => 'Aduan Diproses', 'count' => 0],
        ['id' => 3, 'st_name' => 'Serahan Kepada', 'count' => 0],
        ['id' => 4, 'st_name' => 'Aduan Dirujuk', 'count' => 0],
        ['id' => 5, 'st_name' => 'Aduan DiBatal', 'count' => 0],
        ['id' => 6, 'st_name' => 'Aduan Selesai', 'count' => 0]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData
      ]);
    }
  }

  /**
   * Get category tickets for Pegawai Tugasan (assigned tasks only)
   */
  public function getCategoryTicketsPegawai()
  {
    try {
      $userId = Auth::id();

      // Get all categories first
      $allCategories = RefCategory::select('id', 'cat_name')->orderBy('id')->get();

      // Get ticket counts per category
      // ALWAYS filter by escalate_to (assigned to this user)
      $categoryQuery = DB::table('tickets as t')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereRaw('tc.id = (
              SELECT MAX(tc2.id)
              FROM tickets_chronologies tc2
              WHERE tc2.ticket_id = t.id
              AND tc2.deleted_at IS NULL
            )');
        })
        ->select('rc.id', DB::raw('COUNT(t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('rc.deleted_at')
        ->whereNull('tc.deleted_at')
        ->groupBy('rc.id');

      $categoryCounts = $categoryQuery->pluck('count', 'id');

      // Build complete data with zero counts for categories with no tickets
      $categoryData = [];
      foreach ($allCategories as $category) {
        $categoryData[] = [
          'id' => $category->id,
          'cat_name' => $category->cat_name,
          'count' => $categoryCounts[$category->id] ?? 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $categoryData
      ]);
    } catch (\Exception $e) {
      // Fallback with empty data
      $sampleData = [
        ['id' => 1, 'cat_name' => 'Aplikasi', 'count' => 0],
        ['id' => 2, 'cat_name' => 'Perkakasan & Perkhidmatan ICT', 'count' => 0],
        ['id' => 3, 'cat_name' => 'Keselamatan ICT', 'count' => 0],
        ['id' => 4, 'cat_name' => 'Pangkalan Data & Sysadmin', 'count' => 0],
        ['id' => 5, 'cat_name' => 'Pusat Data', 'count' => 0],
        ['id' => 6, 'cat_name' => 'Rangkaian', 'count' => 0],
        ['id' => 7, 'cat_name' => 'Emel/SSO', 'count' => 0]
      ];

      return response()->json([
        'success' => true,
        'data' => $sampleData
      ]);
    }
  }

  /**
   * Get list of overdue tickets for Pegawai Tugasan (assigned tasks only)
   */
  public function getListOverduePegawai(Request $request)
  {
    try {
      $now = Carbon::now();
      $userId = Auth::id();

      // Get overdue tickets from the latest chronology for each ticket
      // ALWAYS filter by escalate_to (assigned to this user)
      $overdueQuery = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
              SELECT MAX(tc2.id)
              FROM tickets_chronologies tc2
              WHERE tc2.ticket_id = t.id
              AND tc2.deleted_at IS NULL
            )');
        })
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->leftJoin('users as u', 'tc.escalate_to', '=', 'u.id')
        ->select(
          't.id',
          't.uuid',
          't.ticket_nolog',
          't.ticket_title',
          'rc.cat_name',
          'rts.st_name as status_name',
          'rts.id as status_id',
          'tc.sla_duedate',
          'tc.escalate_to',
          'u.nama as assigned_to',
          't.created_at as ticket_created',
          DB::raw('DATEDIFF(NOW(), tc.sla_duedate) as days_overdue')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->where('tc.tick_status', '!=', 6) // Not completed
        ->where('tc.tick_status', '!=', 5) // Not cancelled
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now)
        ->where('tc.escalate_to', $userId); // ALWAYS filter by assigned user

      $overdueTickets = $overdueQuery->orderBy('days_overdue', 'desc')
        ->get();

      // Process data for DataTable
      $processedData = $overdueTickets->map(function ($ticket, $index) {
        return [
          'ticket_id' => $ticket->id,
          'ticket_uuid' => $ticket->uuid,
          'ticket_nolog' => $ticket->ticket_nolog,
          'kategori' => $ticket->cat_name,
          'tajuk' => $ticket->ticket_title,
          'status' => $ticket->status_name,
          'status_id' => $ticket->status_id,
          'assigned_to' => $ticket->assigned_to ?? 'Belum Ditugaskan',
          'sla_duedate' => $ticket->sla_duedate ? Carbon::parse($ticket->sla_duedate)->format('d/m/Y H:i') : '-',
          'days_overdue' => max(0, $ticket->days_overdue),
          'ticket_created' => Carbon::parse($ticket->ticket_created)->format('d/m/Y H:i')
        ];
      });

      if ($request->ajax()) {
        return datatables($processedData)
          ->addIndexColumn()
          ->addColumn('action', function ($row) {
            return '<button type="button" class="btn btn-sm view-record" data-uuid="' . $row['ticket_uuid'] . '" title="Lihat Tiket"><i class="bx bx-show"></i></button>';
          })
          ->editColumn('status', function ($row) {
            $badgeClass = 'badge-warning'; // Default for overdue
            if ($row['status_id'] == 1) $badgeClass = 'badge-info';
            else if ($row['status_id'] == 2) $badgeClass = 'badge-warning';
            else if ($row['status_id'] == 3) $badgeClass = 'badge-primary';
            else if ($row['status_id'] == 4) $badgeClass = 'badge-secondary';

            return '<span class="badge ' . $badgeClass . '">' . $row['status'] . '</span>';
          })
          ->editColumn('days_overdue', function ($row) {
            $days = $row['days_overdue'];
            if ($days > 7) {
              $class = 'text-danger fw-bold';
              $icon = '<i class="ti ti-bell text-danger blink-icon me-1"></i>';
            } else if ($days > 3) {
              $class = 'text-warning fw-bold';
              $icon = '<i class="ti ti-bell text-warning me-1"></i>';
            } else {
              $class = 'text-info';
              $icon = '<i class="ti ti-bell text-info me-1"></i>';
            }
            return $icon . '<span class="' . $class . '">' . $days . ' hari</span>';
          })
          ->rawColumns(['action', 'status', 'days_overdue'])
          ->make(true);
      }

      return response()->json([
        'success' => true,
        'data' => $processedData->toArray(),
        'total' => $processedData->count()
      ]);
    } catch (\Exception $e) {
      \Log::error('Error in getListOverduePegawai', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => Auth::id()
      ]);

      if ($request->ajax()) {
        return response()->json([
          'success' => false,
          'message' => 'Gagal memuat data tiket overdue',
          'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
        ], 500);
      }

      return response()->json([
        'success' => false,
        'message' => 'Gagal memuat data tiket overdue',
        'data' => [],
        'total' => 0
      ]);
    }
  }
}
