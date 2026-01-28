<?php

/**
 * Dashboard Service
 * Dispatcher: Pengarah lalu lintas
 * Handles various dashboard related functionalities
 */

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Dashboard\Role\AdminDashboard;
use App\Services\Dashboard\Role\PegawaiTugasanDashboard;
use App\Services\Dashboard\Role\PengurusanDashboard;
use App\Services\Dashboard\Role\HelpdeskDashboard;

/**
 * Dashboard Service Dispatcher
 *
 * This service acts as a traffic controller (pengarah lalu lintas) that routes
 * dashboard requests to the appropriate role-specific dashboard service.
 *
 * Architecture:
 * DashboardController → DashboardService (dispatcher) → Role Dashboard → Widgets → Blade
 *
 * Roles:
 * - Pegawai Tugasan → PegawaiTugasanDashboard
 * - Pengurusan MOF → PengurusanDashboard
 * - Helpdesk (Pentadbir Sistem, Penyelia Helpdesk, Helpdesk On Duty) → HelpdeskDashboard
 * - Administrator → AdminDashboard
 */
class DashboardService
{
  protected AdminDashboard $adminDashboard;
  protected PegawaiTugasanDashboard $pegawaiDashboard;
  protected PengurusanDashboard $pengurusanDashboard;
  protected HelpdeskDashboard $helpdeskDashboard;

  public function __construct(
    AdminDashboard $adminDashboard,
    PegawaiTugasanDashboard $pegawaiDashboard,
    PengurusanDashboard $pengurusanDashboard,
    HelpdeskDashboard $helpdeskDashboard
  ) {
    $this->adminDashboard = $adminDashboard;
    $this->pegawaiDashboard = $pegawaiDashboard;
    $this->pengurusanDashboard = $pengurusanDashboard;
    $this->helpdeskDashboard = $helpdeskDashboard;
  }

  /**
   * Get dashboard data based on user role
   *
   * @param User $user The authenticated user
   * @return array Dashboard data for the blade view
   */
  public function getDashboardData(User $user): array
  {
    // Initialize merged data structure
    $mergedData = [
      'user' => $user,
      'sections' => [
        'pegawai' => false,
        'admin' => false,
        'pengurusan' => false,
        'helpdesk' => false,
      ],
    ];

    // Initialize default ticket counts to prevent undefined variable errors
    $ticketData = [
      'newTickets' => 0,
      'inProgressTickets' => 0,
      'closedTickets' => 0,
      'referredTickets' => 0,
      'newTicketsOnBehalf' => 0,
      'inProgressTicketsOnBehalf' => 0,
      'closedTicketsOnBehalf' => 0,
      'referredTicketsOnBehalf' => 0,
    ];

    // Check Pegawai Tugasan role
    if ($user->hasRole('Pegawai Tugasan')) {
      $data = $this->pegawaiDashboard->getData($user);
      $mergedData['sections']['pegawai'] = true;
      // Remove 'user' and 'sections' keys to prevent overwriting
      unset($data['user'], $data['sections']);
      $ticketData = array_merge($ticketData, $data);
    }

    // Check Administrator role
    if ($user->hasRole('Administrator')) {
      $data = $this->adminDashboard->getData($user);
      $mergedData['sections']['admin'] = true;
      // Remove 'user' and 'sections' keys to prevent overwriting
      unset($data['user'], $data['sections']);
      $ticketData = array_merge($ticketData, $data);
    }

    // Check Pengurusan MOF role
    if ($user->hasRole('Pengurusan MOF')) {
      $data = $this->pengurusanDashboard->getData($user);
      $mergedData['sections']['pengurusan'] = true;
      // Remove 'user' and 'sections' keys to prevent overwriting
      unset($data['user'], $data['sections']);
      $ticketData = array_merge($ticketData, $data);
    }

    // Check Helpdesk roles
    if ($user->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      $data = $this->helpdeskDashboard->getData($user);
      $mergedData['sections']['helpdesk'] = true;
      // Remove 'user' and 'sections' keys to prevent overwriting
      unset($data['user'], $data['sections']);
      $ticketData = array_merge($ticketData, $data);
    }

    // Merge ticket counts and other data (user and sections are already set)
    return array_merge($mergedData, $ticketData);
  }
}
