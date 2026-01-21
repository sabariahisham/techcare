<?php

/*
  * Function TicketSLAOverdueController.php (NOTIFICATION CONTROLLER):
  *  - Manage OVERDUE ticket email notifications for Pegawai Tugasan (Level 1 or 2).
  *  - Configure email settings and templates.
  *  - Send alerts for ticket OVERDUE(data).
  */

namespace App\Http\Controllers\Notifications;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Services\TicketSLAOverdueService;

class TicketSLAOverdueController extends Controller
{
  protected $slaOverdueService;

  public function __construct(TicketSLAOverdueService $slaOverdueService)
  {
    $this->slaOverdueService = $slaOverdueService;
  }

  /**
   * Display list of overdue tickets
   */
  public function index()
  {
    $overdueTickets = $this->slaOverdueService->getOverdueTickets();

    return response()->json([
      'success' => true,
      'count' => $overdueTickets->count(),
      'data' => $overdueTickets
    ]);
  }

  /**
   * Trigger overdue notifications manually
   */
  public function sendNotifications()
  {
    try {
      $stats = $this->slaOverdueService->sendOverdueNotifications();

      return response()->json([
        'success' => true,
        'message' => 'Overdue notifications processed',
        'stats' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error('Error sending overdue notifications: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send overdue notifications',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
