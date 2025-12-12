<?php

/*
 * Function TicketAssignmentController.php:
 *  - Manage ticket assignments.
 *  - Handle assigning tickets to Pengadu and Pegawai Tugasan.
 *  - Provide assignment functionalities for Pengadu and Pegawai Tugasan.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketChronology;
use App\Models\Sla;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TicketAssignmentController extends Controller
{
  /**
   * Assign ticket to a user
   *
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function assignTicket(Request $request, Ticket $ticket)
  {
    $request->validate([
      'assignee_id' => 'required|exists:users,id',
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'level_pegtugas_id' => 'nullable|integer|min:1|max:5',
      'sla_duedate' => 'nullable|date'
    ]);

    try {
      // Create chronology entry with assignment
      $chronology = TicketChronology::create([
        'ticket_id' => $ticket->id,
        'frm_user_id' => Auth::id(),
        'level_pegtugas_id' => $request->level_pegtugas_id,
        'tick_status' => $request->tick_status,
        'escalate_to' => $request->assignee_id,
        'sla_duedate' => $request->sla_duedate ? \Carbon\Carbon::parse($request->sla_duedate) : null,
        'created_by' => Auth::id(),
      ]);

      // Update ticket's current status
      $ticket->update([
        'tick_status' => $request->tick_status,
        'updated_by' => Auth::id()
      ]);

      // Load the assigned user for response
      $assignee = User::find($request->assignee_id);

      return response()->json([
        'success' => true,
        'message' => 'Tiket berjaya diserahkan kepada ' . $assignee->nama,
        'chronology' => $chronology,
        'assignee' => $assignee
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa menyerahkan tiket: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get current assignees for a ticket
   *
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function getAssignees(Ticket $ticket)
  {
    try {
      // Get the latest chronology entries with escalations
      $assignments = $ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->with(['escalatedTo', 'status', 'createdBy'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($chronology) {
          return [
            'id' => $chronology->id,
            'assignee_id' => $chronology->escalate_to,
            'assignee_name' => $chronology->escalatedTo ? $chronology->escalatedTo->nama : 'Unknown',
            'assignee_email' => $chronology->escalatedTo ? $chronology->escalatedTo->email : '',
            'status' => $chronology->status ? $chronology->status->st_name : 'Unknown',
            'level_pegtugas' => $chronology->level_pegtugas_id,
            'assigned_by' => $chronology->createdBy ? $chronology->createdBy->nama : 'Unknown',
            'assigned_at' => $chronology->created_at->format('d/m/Y H:i:s'),
            'sla_duedate' => $chronology->sla_duedate ? $chronology->sla_duedate->format('d/m/Y H:i:s') : null
          ];
        });

      return response()->json([
        'success' => true,
        'assignments' => $assignments
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan senarai tugasan: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get assigned officer for the ticket
   */
  public static function getAssignedOfficer($ticket)
  {
    // Get the latest chronology entry with escalation
    $latestEscalation = $ticket->chronologies()
      ->whereNotNull('escalate_to')
      ->with('escalatedTo')
      ->latest()
      ->first();

    return $latestEscalation && $latestEscalation->escalatedTo
      ? e($latestEscalation->escalatedTo->nama)
      : '-';
  }

  /**
   * Get available users for assignment based on SLA criteria
   *
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getAvailableAssigneesBySLA(Request $request)
  {
    try {
      $categoryId = $request->get('cat_id');
      $subcategoryId = $request->get('subcat_id');
      $severity = $request->get('severity');

      // Base query for users with helpdesk roles
      $baseRoles = ['Pegawai Tugasan', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Administrator', 'Pentadbir Sistem'];

      $assigneesQuery = User::whereHas('roles', function ($query) use ($baseRoles) {
        $query->whereIn('name', $baseRoles);
      });

      // If SLA criteria provided, prioritize officers based on severity
      if ($categoryId && $subcategoryId && $severity) {
        $sla = Sla::where('cat_id', $categoryId)
          ->where('subcat_id', $subcategoryId)
          ->where('sla_severity', $severity)
          ->first();

        if ($sla) {
          // Adjust role priority based on SLA severity
          if ($severity === 'High') {
            // High priority: Administrators and senior staff first
            $assigneesQuery->whereHas('roles', function ($query) {
              $query->whereIn('name', ['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan']);
            });
          } elseif ($severity === 'Medium') {
            // Medium priority: Include supervisors and duty officers
            $assigneesQuery->whereHas('roles', function ($query) {
              $query->whereIn('name', ['Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan', 'Administrator', 'Pentadbir Sistem']);
            });
          } else {
            // Low priority: All available officers
            $assigneesQuery->whereHas('roles', function ($query) use ($baseRoles) {
              $query->whereIn('name', $baseRoles);
            });
          }
        }
      }

      $assignees = $assigneesQuery
        ->select('id', 'nama', 'email')
        ->orderByRaw("
                    CASE
                        WHEN EXISTS (
                            SELECT 1 FROM model_has_roles mhr
                            INNER JOIN roles r ON mhr.role_id = r.id
                            WHERE mhr.model_id = users.id
                            AND r.name IN ('Administrator', 'Pentadbir Sistem')
                        ) THEN 1
                        WHEN EXISTS (
                            SELECT 1 FROM model_has_roles mhr
                            INNER JOIN roles r ON mhr.role_id = r.id
                            WHERE mhr.model_id = users.id
                            AND r.name = 'Penyelia Helpdesk'
                        ) THEN 2
                        WHEN EXISTS (
                            SELECT 1 FROM model_has_roles mhr
                            INNER JOIN roles r ON mhr.role_id = r.id
                            WHERE mhr.model_id = users.id
                            AND r.name = 'Helpdesk On Duty'
                        ) THEN 3
                        ELSE 4
                    END
                ")
        ->orderBy('nama')
        ->get();

      return response()->json([
        'success' => true,
        'assignees' => $assignees,
        'sla_info' => $sla ?? null
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get available users for assignment (Pegawai Tugasan)
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getAvailableAssignees()
  {
    try {
      // Get users with role "Pegawai Tugasan" or admin roles that can handle tickets
      $assignees = User::whereHas('roles', function ($query) {
        $query->whereIn('name', [
          'Pegawai Tugasan',
          'Penyelia Helpdesk',
          'Helpdesk On Duty',
          'Administrator',
          'Pentadbir Sistem'
        ]);
      })
        ->select('id', 'nama', 'email')
        ->orderBy('nama')
        ->get();

      return response()->json([
        'success' => true,
        'assignees' => $assignees
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }
}
