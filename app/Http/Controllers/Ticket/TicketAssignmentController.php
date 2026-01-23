<?php

/*
 * Function TicketAssignmentController.php:
 *  - Manage ticket assignments.
 *  - CRUD ticket assignments (create, read, update)
 *  - Handle assigning tickets to Pengadu and Pegawai Tugasan.
 *  - Provide assignment functionalities for Pengadu and Pegawai Tugasan.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketChronology;
use App\Models\RefTicketStatus;
use App\Models\TicketComment;
use App\Models\Sla;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Ticket\TicketChronologyController;
use Illuminate\Support\Facades\Auth;
use App\Services\TicketNotificationService;

class TicketAssignmentController extends Controller
{
  protected $notificationService;

  public function __construct(TicketNotificationService $notificationService)
  {
    $this->notificationService = $notificationService;
  }

  /**
   * Update ticket details and assignment
   */
  public function updateTicketAssignment(Request $request, Ticket $ticket)
  {
    // First, get the status name to check if it's "Aduan Dirujuk"
    $selectedStatus = RefTicketStatus::find($request->tick_status);
    $isAduanDirujuk = $selectedStatus && $selectedStatus->st_name === 'Aduan Dirujuk';

    // Dynamic validation rules based on status
    $validationRules = [
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'sla_severity' => 'nullable|string',
      'assigned_officers' => 'nullable|array',
      'assigned_officers.*' => 'exists:users,id',
      'action_date' => 'nullable|date',
      'notes' => 'nullable|string|max:1000'
    ];

    // Add kategori and sub kategori validation only for "Aduan Dirujuk" status
    if ($isAduanDirujuk) {
      $validationRules['cat_id'] = 'required|exists:ref_categories,id';
      $validationRules['subcat_id'] = 'required|exists:ref_sub_categories,id';
    } else {
      $validationRules['cat_id'] = 'nullable|exists:ref_categories,id';
      $validationRules['subcat_id'] = 'nullable|exists:ref_sub_categories,id';
    }

    $request->validate($validationRules, [
      'tick_status.required' => 'Sila pilih status tiket.',
      'tick_status.exists' => 'Status tiket yang dipilih tidak sah.',
      'cat_id.required' => 'Sila pilih jenis aduan untuk status Aduan Dirujuk.',
      'cat_id.exists' => 'Kategori aduan yang dipilih tidak sah.',
      'subcat_id.required' => 'Sila pilih sub kategori aduan untuk status Aduan Dirujuk.',
      'subcat_id.exists' => 'Sub kategori aduan yang dipilih tidak sah.',
      'assigned_officers.*.exists' => 'Pegawai tugasan yang dipilih tidak sah.',
      'action_date.date' => 'Format tarikh tindakan tidak sah.',
      'notes.max' => 'Catatan tidak boleh melebihi 1000 aksara.'
    ]);

    try {
      // Store old status before updating
      $oldStatus = $ticket->tick_status;

      // Get SLA information based on category, subcategory, and severity
      $slaInfo = null;
      $slaDays = null;
      $levelPetugas = 1; // Default level

      if ($request->sla_severity && $request->cat_id && $request->subcat_id) {
        $slaInfo = Sla::where('cat_id', $request->cat_id)
          ->where('subcat_id', $request->subcat_id)
          ->where('sla_severity', $request->sla_severity)
          ->first();

        if ($slaInfo) {
          $slaDays = $slaInfo->sla_days;
          $levelPetugas = $slaInfo->level_petugas_id ?? 1;
        }
      }

      // Update ticket basic information (without sla_severity since it's in chronology)
      $updateData = [
        'tick_status' => $request->tick_status,
        'updated_by' => Auth::id()
      ];

      // Only update category and subcategory for "Aduan Dirujuk" status
      if ($isAduanDirujuk && $request->cat_id && $request->subcat_id) {
        $updateData['cat_id'] = $request->cat_id;
        $updateData['subcat_id'] = $request->subcat_id;
      }

      $ticket->update($updateData);

      // Get the first assigned officer (if any)
      $firstOfficer = null;
      if ($request->has('assigned_officers') && !empty($request->assigned_officers)) {
        $firstOfficer = $request->assigned_officers[0];
      }

      // Check if there's an existing chronology entry with the same status
      // Look for ANY chronology with this status (including those with escalate_to)
      $existingChronology = TicketChronology::where('ticket_id', $ticket->id)
        ->where('tick_status', $request->tick_status)
        ->orderBy('created_at', 'desc')
        ->first();

      if ($existingChronology) {
        // Update existing chronology entry
        $chronology = $existingChronology;
        $chronology->update([
          'frm_user_id' => Auth::id(),
          'sla_severity' => $request->sla_severity,
          'sla_days' => $slaDays,
          'level_pegtugas_id' => $levelPetugas,
          'escalate_to' => $firstOfficer, // Store first officer here
          'updated_by' => Auth::id(),
          'updated_at' => $request->action_date ? \Carbon\Carbon::parse($request->action_date) : now(),
        ]);

        // Delete any other chronology entries for this ticket + status combination to avoid duplicates
        TicketChronology::where('ticket_id', $ticket->id)
          ->where('tick_status', $request->tick_status)
          ->where('id', '!=', $chronology->id)
          ->delete();
      } else {
        // Create new chronology entry for status change
        $chronologyData = [
          'ticket_id' => $ticket->id,
          'frm_user_id' => Auth::id(),
          'tick_status' => $request->tick_status,
          'sla_severity' => $request->sla_severity,
          'sla_days' => $slaDays,
          'level_pegtugas_id' => $levelPetugas,
          'escalate_to' => $firstOfficer, // Store first officer in main entry
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
          'created_at' => $request->action_date ? \Carbon\Carbon::parse($request->action_date) : now(),
          'updated_at' => now()
        ];

        // Create the chronology entry
        $chronology = TicketChronology::create($chronologyData);
      }

      // Handle additional officer assignments (2nd officer onwards) if provided
      if ($request->has('assigned_officers') && count($request->assigned_officers) > 1) {
        // Skip the first officer as it's already stored in the main chronology entry
        $additionalOfficers = array_slice($request->assigned_officers, 1);

        foreach ($additionalOfficers as $officerId) {
          // Create separate chronology entries for additional officers only
          TicketChronology::create([
            'ticket_id' => $ticket->id,
            'frm_user_id' => Auth::id(),
            'tick_status' => $request->tick_status,
            'escalate_to' => $officerId,
            'sla_severity' => $request->sla_severity,
            'sla_days' => $slaDays,
            'level_pegtugas_id' => $levelPetugas,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => $request->action_date ? \Carbon\Carbon::parse($request->action_date) : now(),
            'updated_at' => now()
          ]);
        }
      }

      // Add notes to chronology if provided
      if ($request->notes) {
        $chronology->update([
          'notes' => $request->notes,
          'updated_by' => Auth::id()
        ]);

        // Also create a comment entry for "Catatan Tindakan" display
        TicketComment::create([
          'user_id' => Auth::id(),
          'ticket_id' => $ticket->id,
          'comment_text' => $request->notes,
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
        ]);
      } else {
        // Add default notes
        $chronology->update([
          'notes' => 'Tiket dikemaskini oleh ' . Auth::user()->nama .
            ($request->sla_severity ? '. Tahap Severity: ' . $request->sla_severity : ''),
          'updated_by' => Auth::id()
        ]);
      }

      // Reload ticket with latest data
      $ticket->refresh();
      $ticket->load(['status', 'category', 'subCategory', 'chronologies.status', 'chronologies.createdBy', 'chronologies.fromUser', 'chronologies.escalatedTo']);

      // Send status change notification
      \Illuminate\Support\Facades\Log::info('Sending status change notification from TicketAssignmentController', [
        'ticket_id' => $ticket->id,
        'old_status' => $oldStatus,
        'new_status' => $request->tick_status,
        'changed_by' => Auth::id(),
      ]);

      $this->notificationService->sendStatusChangeNotification(
        $ticket,
        $oldStatus,
        $request->tick_status,
        Auth::user(),
        $request->notes
      );

      return redirect()->route('tickets.show', $ticket)
        ->with('success', 'Tiket berjaya dikemaskini!');
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'Ralat semasa mengemaskini tiket: ' . $e->getMessage())
        ->withInput();
    }
  }

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

  /**
   * Get history comment from Pengadu and Pegawai Tugasan
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getHistoryComment($ticketId)
  {
    try {
      $ticket = Ticket::with(['comments.user', 'comments.createdBy', 'status'])->findOrFail($ticketId);

      // Get all chronologies to determine the status at the time each comment was made
      // Group by status and get the earliest chronology for each status
      $chronologies = $ticket->chronologies()
        ->with('status')
        ->orderBy('created_at', 'asc')
        ->get()
        ->unique(function ($item) {
          return $item->tick_status;
        });

      // Get all comments from pengadu (ticket creator) and pegawai tugasan (officers)
      $comments = $ticket->comments->map(function ($comment) use ($ticket, $chronologies) {
        // Find the status that was active when this comment was created
        $commentStatus = $this->getStatusAtTime($comment->created_at, $chronologies);

        return [
          'id' => $comment->id,
          'created_at' => $comment->created_at->format('d/m/Y H:i'),
          'user_name' => $comment->user ? $comment->user->nama : 'Tidak diketahui',
          'user_unit' => $comment->user ? ($comment->user->unit->nama_unit ?? $comment->user->kod_unit ?? '-') : '-',
          'user_type' => $this->getUserType($comment->user, $ticket),
          'comment' => $comment->comment_text,
          'created_by' => $comment->createdBy ? $comment->createdBy->nama : 'Tidak diketahui',
          'ticket_status' => $commentStatus['status_name'],
          'status_class' => $commentStatus['status_class']
        ];
      });

      return response()->json([
        'success' => true,
        'comments' => $comments
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan catatan tindakan: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Determine the ticket status that was active at a specific time
   */
  private function getStatusAtTime($commentTime, $chronologies)
  {
    // Default to "Aduan Baru" if no chronology found
    $defaultStatus = [
      'status_name' => 'Aduan Baru',
      'status_class' => $this->getStatusBadgeClass('Aduan Baru')
    ];

    // If no chronologies exist, return default
    if ($chronologies->isEmpty()) {
      return $defaultStatus;
    }

    // Find the chronology that was active at the comment time
    $activeChronology = null;

    foreach ($chronologies as $chronology) {
      // If chronology was created before or at the comment time, it was active
      if ($chronology->created_at <= $commentTime) {
        $activeChronology = $chronology;
      } else {
        // Once we find a chronology created after the comment time, stop
        break;
      }
    }

    // If we found an active chronology, use its status
    if ($activeChronology && $activeChronology->status) {
      return [
        'status_name' => $activeChronology->status->st_name,
        'status_class' => $this->getStatusBadgeClass($activeChronology->status->st_name)
      ];
    }

    // Return default status if no matching chronology found
    return $defaultStatus;
  }

  /**
   * Determine user type (Pengadu or Pegawai Tugasan)
   */
  private function getUserType($user, $ticket)
  {
    if (!$user) {
      return 'Tidak diketahui';
    }

    // Check if user is the ticket creator (Pengadu)
    if ($user->id === $ticket->user_id) {
      return 'Pengadu';
    }

    // Check if user has officer roles (Pegawai Tugasan)
    if ($user->hasAnyRole(['Pegawai Tugasan', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Administrator', 'Pentadbir Sistem'])) {
      return 'Pegawai Tugasan';
    }

    return 'Pengguna';
  }

  /**
   * Get Bootstrap badge class for ticket status
   */
  private function getStatusBadgeClass($statusName)
  {
    switch ($statusName) {
      case 'Aduan Baru':
        return 'bg-primary';
      case 'Aduan Diproses':
        return 'bg-warning';
      case 'Serahan Kepada':
        return 'bg-info';
      case 'Aduan Dirujuk':
        return 'bg-danger';
      case 'Aduan DiBatal':
        return 'bg-secondary';
      case 'Aduan Selesai':
        return 'bg-success';
      default:
        return 'bg-secondary';
    }
  }
}
