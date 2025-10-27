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
use Auth;

class DashboardController extends Controller
{
  public function index()
  {
    $totalTickets = Ticket::count();
    $statuses = RefTicketStatus::all();

    $newTickets = Ticket::where('tick_status', '1')->count();
    $inProgressTickets = Ticket::where('tick_status', '2')->count();
    $closedTickets = Ticket::where('tick_status', '6')->count();
    $pendingTickets = Ticket::whereNotIn('tick_status', [5, 6])->count();

    return view('dashboard.dashboard', compact('totalTickets', 'newTickets', 'inProgressTickets', 'closedTickets', 'pendingTickets'));
  }
}
