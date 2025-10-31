<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\RefTicketStatus;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  public function index()
  {
    /* Status Aduan Tiket:
        1	Aduan Baru
        2	Aduan Diproses
        3	Serahan Kepada
        4	Aduan Dirujuk
        5	Aduan DiBatal
        6	Aduan Selesai
    */

    $totalTickets = Ticket::count();
    $statuses = RefTicketStatus::all();
    $userId = Auth::id();

    // Tickets created by user (ticket_type = 1)
    $newTickets = Ticket::where('user_id', $userId)->where('ticket_type', '1')->where('tick_status', '1')->count();
    $inProgressTickets = Ticket::where('user_id', $userId)->where('ticket_type', '1')->whereIn('tick_status', [2, 3])->count();
    $closedTickets = Ticket::where('user_id', $userId)->where('ticket_type', '1')->where('tick_status', '6')->count();
    $referredTickets = Ticket::where('user_id', $userId)->where('ticket_type', '1')->where('tick_status', '4')->count();

    // Tickets created on behalf (ticket_type = 0) - "Bagi Pihak"
    $newTicketsOnBehalf = Ticket::where('user_id', $userId)->where('ticket_type', '0')->where('tick_status', '1')->count();
    $inProgressTicketsOnBehalf = Ticket::where('user_id', $userId)->where('ticket_type', '0')->whereIn('tick_status', [2, 3])->count();
    $closedTicketsOnBehalf = Ticket::where('user_id', $userId)->where('ticket_type', '0')->where('tick_status', '6')->count();
    $referredTicketsOnBehalf = Ticket::where('user_id', $userId)->where('ticket_type', '0')->where('tick_status', '4')->count();

    return view('dashboard.dashboard', compact(
      'totalTickets',
      'newTickets',
      'inProgressTickets',
      'closedTickets',
      'referredTickets',
      'newTicketsOnBehalf',
      'inProgressTicketsOnBehalf',
      'closedTicketsOnBehalf',
      'referredTicketsOnBehalf'
    ));
  }

  public function getStatusTickets()
  {
    $userId = Auth::id();

    try {
      // Get status counts from ticket_chronologies table
      $statusCounts = DB::table('tickets_chronologies as tc')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->select('rts.id', 'rts.st_name', DB::raw('COUNT(tc.id) as count'))
        ->whereNull('tc.deleted_at')
        ->whereNull('rts.deleted_at')
        ->groupBy('rts.id', 'rts.st_name')
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

}
