<?php

namespace App\Services\Dashboard\Role;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Pengurusan MOF Dashboard Service
 *
 * Provides high-level KPI dashboard data for management.
 * Focus on summary, trends, and overall performance.
 *
 * Returns data for blade partials:
 * - card-status-user (user's own tickets)
 * - cards-status (overall statistics)
 * - chart-status (ticket status distribution)
 * - chart-category (ticket category distribution)
 * - chart-trend (monthly ticket trends)
 * - chart-total (ticket types)
 *
 * Note: Does NOT include chart-assigned (officer performance details)
 */
class PengurusanDashboard
{
  /**
   * Get dashboard data for Pengurusan MOF
   *
   * @param User $user The authenticated user
   * @return array Dashboard data for blade views
   */
  public function getData(User $user): array
  {
    $userId = $user->id;

    return [
      // Pass user object to blade
      'user' => $user,

      // Section visibility flags
      'sections' => [
        'pegawai' => false,
        'admin' => false,
        'pengurusan' => true,
        'helpdesk' => false,
      ],

      // User's own ticket status (shown to all users)
      'newTickets' => $this->getUserTicketsByStatus($userId, '1', 1),
      'inProgressTickets' => $this->getUserTicketsByStatus($userId, [2, 3], 1),
      'closedTickets' => $this->getUserTicketsByStatus($userId, '6', 1),
      'referredTickets' => $this->getUserTicketsByStatus($userId, '4', 1),

      // Tickets created on behalf (ticket_type = 0)
      'newTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '1', 0),
      'inProgressTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, [2, 3], 0),
      'closedTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '6', 0),
      'referredTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '4', 0),

      // Overall statistics (management view)
      'totalTickets' => Ticket::count(),
    ];
  }

  /**
   * Get user's tickets by status and type
   *
   * @param int $userId User ID
   * @param mixed $status Status ID(s) - can be single value or array
   * @param int $ticketType Ticket type (1 = self, 0 = on behalf)
   * @return int Ticket count
   */
  protected function getUserTicketsByStatus(int $userId, $status, int $ticketType): int
  {
    $query = Ticket::where('user_id', $userId)
      ->where('ticket_type', $ticketType);

    if (is_array($status)) {
      $query->whereIn('tick_status', $status);
    } else {
      $query->where('tick_status', $status);
    }

    return $query->count();
  }
}
