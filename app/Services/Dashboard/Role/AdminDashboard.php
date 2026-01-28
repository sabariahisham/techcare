<?php

namespace App\Services\Dashboard\Role;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Admin Dashboard Service
 *
 * Provides dashboard data for administrator roles:
 * - Administrator
 * - Pentadbir Sistem
 * - Penyelia Helpdesk
 * - Helpdesk On Duty
 *
 * Returns data for blade partials:
 * - card-status-user (user's own tickets)
 * - cards-status (overall statistics)
 * - chart-status (ticket status distribution)
 * - chart-category (ticket category distribution)
 * - chart-trend (monthly ticket trends)
 * - chart-total (ticket types)
 * - chart-assigned (assigned officer performance)
 * - table-ticket-overdue (SLA overdue tickets)
 */
class AdminDashboard
{
  /**
   * Get dashboard data for admin roles
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
        'admin' => true,
        'pengurusan' => false,
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

      // Overall statistics (admin view)
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
