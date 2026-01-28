<?php

namespace App\Services\Dashboard\Role;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Pegawai Tugasan Dashboard Service
 *
 * Provides dashboard data specifically for Pegawai Tugasan (assigned officers).
 * All data is scoped to tickets assigned to this specific officer only.
 *
 * Returns data for blade partials:
 * - card-status-user (user's own tickets)
 * - my-tasks (assigned tasks section with cards and charts)
 *
 * Dashboard sections:
 * - Aduan Hari Ini (Today's logs)
 * - Selesai Minggu Ini (Completed this week)
 * - Belum Selesai (Pending tickets)
 * - Lewat SLA (Overdue SLA)
 * - Status Tiket (Ticket status chart)
 * - Kategori Tiket (Category chart)
 * - Senarai Tiket Melebihi Tempoh SLA (Overdue table)
 */
class PegawaiTugasanDashboard
{
  /**
   * Get dashboard data for Pegawai Tugasan
   *
   * @param User $user The authenticated user (Pegawai Tugasan)
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
        'pegawai' => true,
        'admin' => false,
        'pengurusan' => false,
        'helpdesk' => false,
      ],

      // User's own ticket status (tickets they created)
      'newTickets' => $this->getUserTicketsByStatus($userId, '1', 1),
      'inProgressTickets' => $this->getUserTicketsByStatus($userId, [2, 3], 1),
      'closedTickets' => $this->getUserTicketsByStatus($userId, '6', 1),
      'referredTickets' => $this->getUserTicketsByStatus($userId, '4', 1),

      // Tickets created on behalf (ticket_type = 0)
      'newTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '1', 0),
      'inProgressTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, [2, 3], 0),
      'closedTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '6', 0),
      'referredTicketsOnBehalf' => $this->getUserTicketsByStatus($userId, '4', 0),

      // Overall statistics - NOT needed for Pegawai Tugasan
      // but included for compatibility with blade
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
