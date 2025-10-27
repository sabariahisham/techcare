<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\RefTicketStatus;
use App\Models\Category;
use Carbon\Carbon;
use DB;
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
}
