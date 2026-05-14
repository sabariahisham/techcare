<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RefTicketStatus;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PegawaiTugasanController
 *
 * This controller handles all dashboard-related functionality specifically for Pegawai Tugasan role.
 * All data queries are filtered by assigned tasks (escalate_to = current user).
 *
 * Created: March 4, 2026
 * Separated from DashboardController to improve code organization and maintainability.
 */
class PegawaiTugasanController extends Controller
{
  /**
   * Get today's logs count for Pegawai Tugasan (assigned tasks only)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTodayLogsCount()
  {
    try {
      $userId = Auth::id();

      $todayCount = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('t.tick_status', [5, 6])
        ->count(DB::raw('DISTINCT t.id'));

      return response()->json([
        'success' => true,
        'count' => $todayCount
      ]);
    } catch (\Exception $e) {
      Log::error('Error in PegawaiTugasanController@getTodayLogsCount', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

      return response()->json([
        'success' => true,
        'count' => 0
      ]);
    }
  }

  /**
   * Get weekly completed tickets for Pegawai Tugasan (assigned tasks only)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getWeeklyCompleted()
  {
    try {
      $startOfWeek = Carbon::now()->startOfWeek(); // Monday
      $endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Friday
      $userId = Auth::id();

      $weeklyData = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->where('tc.tick_status', 6);
        })
        ->select(
          DB::raw('DAYOFWEEK(t.updated_at) as day_of_week'),
          DB::raw('DATE(t.updated_at) as date'),
          DB::raw('COUNT(DISTINCT t.id) as count')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->where('t.tick_status', 6)
        ->whereBetween('t.updated_at', [$startOfWeek, $endOfWeek])
        ->groupBy(DB::raw('DATE(t.updated_at)'), DB::raw('DAYOFWEEK(t.updated_at)'))
        ->orderBy(DB::raw('DATE(t.updated_at)'))
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
      Log::error('Error in PegawaiTugasanController@getWeeklyCompleted', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

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
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getPendingStats()
  {
    try {
      $userId = Auth::id();

      $totalTickets = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereYear('t.created_at', Carbon::now()->year)
        ->count(DB::raw('DISTINCT t.id'));

      $pendingTickets = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('t.tick_status', [5, 6])
        ->whereYear('t.created_at', Carbon::now()->year)
        ->count(DB::raw('DISTINCT t.id'));

      // Calculate percentage
      $percentage = $totalTickets > 0 ? round(($pendingTickets / $totalTickets) * 100, 1) : 0;

      return response()->json([
        'success' => true,
        'pending_count' => $pendingTickets,
        'total_count' => $totalTickets,
        'percentage' => $percentage
      ]);
    } catch (\Exception $e) {
      Log::error('Error in PegawaiTugasanController@getPendingStats', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

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
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getOverdueSLACount()
  {
    try {
      $now = Carbon::now();
      $userId = Auth::id();

      $overdueCount = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('t.tick_status', [5, 6])
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now)
        ->count(DB::raw('DISTINCT t.id'));

      return response()->json([
        'success' => true,
        'count' => $overdueCount
      ]);
    } catch (\Exception $e) {
      Log::error('Error in PegawaiTugasanController@getOverdueSLACount', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

      return response()->json([
        'success' => true,
        'count' => 0
      ]);
    }
  }

  /**
   * Get status tickets for Pegawai Tugasan (assigned tasks only)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getStatusTickets()
  {
    $userId = Auth::id();

    try {
      $statusQuery = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->join('ref_ticket_statuses as rts', 't.tick_status', '=', 'rts.id')
        ->select('rts.id', 'rts.st_name', DB::raw('COUNT(DISTINCT t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNull('rts.deleted_at')
        ->whereYear('t.created_at', Carbon::now()->year);

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
          'st_name' => $status->st_name === 'Aduan Baru' ? 'Aduan Baharu' : $status->st_name,
          'count' => $count ? $count->count : 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $statusData
      ]);
    } catch (\Exception $e) {
      Log::error('Error in PegawaiTugasanController@getStatusTickets', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

      // Fallback with empty data
      $sampleData = [
        ['id' => 1, 'st_name' => 'Aduan Baharu', 'count' => 0],
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
   *
   * CLOSED AFTER UAT (04032026) - KEEPING METHOD FOR FUTURE USE
   * Currently not being called from frontend, but kept for potential future requirements
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCategoryTickets()
  {
    try {
      $userId = Auth::id();

      // Get all categories first
      $allCategories = RefCategory::select('id', 'cat_name')->orderBy('id')->get();

      $categoryQuery = DB::table('tickets as t')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->select('rc.id', DB::raw('COUNT(DISTINCT t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('rc.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereYear('t.created_at', Carbon::now()->year)
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
      Log::error('Error in PegawaiTugasanController@getCategoryTickets', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

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
   * Get subcategory tickets for Pegawai Tugasan (assigned tasks only)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getSubcategoryTickets()
  {
    try {
      $userId = Auth::id();

      // Get subcategories assigned to this user from officers table
      $assignedSubcategories = DB::table('officers as o')
        ->join('ref_sub_categories as rsc', 'o.subcat_id', '=', 'rsc.id')
        ->select('rsc.id', 'rsc.subcat_name')
        ->where('o.user_id', $userId)
        ->whereNull('o.deleted_at')
        ->whereNull('rsc.deleted_at')
        ->groupBy('rsc.id', 'rsc.subcat_name')
        ->orderBy('rsc.id')
        ->get();

      $subcategoryQuery = DB::table('tickets as t')
        ->join('ref_sub_categories as rsc', 't.subcat_id', '=', 'rsc.id')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->select('rsc.id', DB::raw('COUNT(DISTINCT t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('rsc.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereYear('t.created_at', Carbon::now()->year)
        ->groupBy('rsc.id');

      $subcategoryCounts = $subcategoryQuery->pluck('count', 'id');

      // Build data only for subcategories assigned to this user from officers table
      $subcategoryData = [];
      foreach ($assignedSubcategories as $subcategory) {
        $subcategoryData[] = [
          'id' => $subcategory->id,
          'subcat_name' => $subcategory->subcat_name,
          'count' => $subcategoryCounts[$subcategory->id] ?? 0
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $subcategoryData
      ]);
    } catch (\Exception $e) {
      Log::error('Error in PegawaiTugasanController@getSubcategoryTickets', [
        'error' => $e->getMessage(),
        'user_id' => Auth::id()
      ]);

      // Fallback with empty data
      return response()->json([
        'success' => true,
        'data' => []
      ]);
    }
  }

  /**
   * Get list of overdue tickets for Pegawai Tugasan (assigned tasks only)
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getListOverdue(Request $request)
  {
    try {
      $now = Carbon::now();
      $userId = Auth::id();

      $overdueQuery = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) use ($userId) {
          $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereColumn('tc.tick_status', 't.tick_status');
        })
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_ticket_statuses as rts', 't.tick_status', '=', 'rts.id')
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
        ->whereNotIn('t.tick_status', [5, 6])
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now);

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
            $badgeClass = 'badge-warning'; // Default
            switch ($row['status_id']) {
              case 1:
                $badgeClass = 'badge-primary';
                break; // Aduan Baru
              case 2:
                $badgeClass = 'badge-warning';
                break; // Aduan Diproses
              case 3:
                $badgeClass = 'badge-info';
                break; // Serahan Kepada
              case 4:
                $badgeClass = 'badge-danger';
                break; // Aduan Dirujuk
              case 5:
                $badgeClass = 'badge-secondary';
                break; // Aduan DiBatal
              case 6:
                $badgeClass = 'badge-success';
                break; // Aduan Selesai
              default:
                $badgeClass = 'badge-secondary';
                break;
            }

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
              $class = 'text-danger';
              $icon = '<i class="ti ti-bell text-danger me-1"></i>';
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
      Log::error('Error in PegawaiTugasanController@getListOverdue', [
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
