<?php

/*
 * Function TicketAssignmentController.php:
 *  - Manage ticket assignments.
 *  - CRUD ticket assignments (create, read, update)
 *  - Handle assigning tickets to Pengadu and Pegawai Tugasan.
 *  - Provide assignment functionalities for Pengadu and Pegawai Tugasan.
 *
 * IMPORTANT: SLA Logic for Update Ticket
 * When updating ticket (SITUASI 2):
 *  - sla_severity: User selects High/Medium/Low from dropdown
 *  - sla_days: Retrieved from slas table WHERE cat_id, subcat_id, sla_severity match
 *  - sla_duedate: current time + sla_days working days (skip weekends & public holidays)
 *  - Uses Helpers::addWorkingDays() to calculate working days properly
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\TicketNotificationService;
use App\Helpers\Helpers;
use Carbon\Carbon;

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
  public function updateTicketAssignment(Request $request, $uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    // First, get the status name to check if it's "Aduan Dirujuk"
    $selectedStatus = RefTicketStatus::find($request->tick_status);
    $isAduanDirujuk = $selectedStatus && $selectedStatus->st_name === 'Aduan Dirujuk';
    $isAduanBaru = $selectedStatus && $selectedStatus->st_name === 'Aduan Baru';

    // Dynamic validation rules based on status
    $validationRules = [
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'sla_severity' => 'nullable|string',
      'action_date' => 'nullable|date',
      'notes' => 'nullable|string|max:1000'
    ];

    // For status "Aduan Baru" (ID: 1), assigned_officers is optional (can be null)
    // For all other statuses (2, 3, 4, 5, 6), assigned_officers is required
    // BUT: If officers already exist for this ticket (from any status), allow optional selection
    $isStatusChanging = $ticket->tick_status != $request->tick_status;

    // Check if ticket has ANY officers assigned (from current or previous statuses)
    $hasAnyOfficersAssigned = $ticket->chronologies()
      ->whereNotNull('escalate_to')
      ->exists();

    // Check for officers in current status specifically
    $hasOfficersInCurrentStatus = $ticket->chronologies()
      ->whereNotNull('escalate_to')
      ->where('tick_status', $ticket->tick_status)
      ->exists();

    // Log validation decision
    \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Validation Check', [
      'ticket_id' => $ticket->id,
      'current_status' => $ticket->tick_status,
      'new_status' => $request->tick_status,
      'is_status_changing' => $isStatusChanging,
      'has_any_officers_assigned' => $hasAnyOfficersAssigned,
      'has_officers_in_current_status' => $hasOfficersInCurrentStatus,
      'is_aduan_baru' => $isAduanBaru,
      'assigned_officers_provided' => $request->has('assigned_officers') ? count($request->assigned_officers) : 0
    ]);

    if ($isAduanBaru) {
      $validationRules['assigned_officers'] = 'nullable|array';
      $validationRules['assigned_officers.*'] = 'exists:users,id';
    } else {
      // Only require assigned_officers if:
      // 1. No officers have ever been assigned to this ticket AND none provided
      // 2. Officers field can be optional if ticket already has officers assigned
      if (!$hasAnyOfficersAssigned) {
        // No officers exist at all - require selection
        $validationRules['assigned_officers'] = 'required|array|min:1';
        $validationRules['assigned_officers.*'] = 'exists:users,id';
      } else {
        // Officers already exist - allow optional (will use existing officers if none provided)
        $validationRules['assigned_officers'] = 'nullable|array';
        $validationRules['assigned_officers.*'] = 'exists:users,id';
      }
    }

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
      'assigned_officers.required' => 'Sila pilih sekurang-kurangnya satu pegawai tugasan untuk status ini.',
      'assigned_officers.min' => 'Sila pilih sekurang-kurangnya satu pegawai tugasan.',
      'assigned_officers.*.exists' => 'Pegawai tugasan yang dipilih tidak sah.',
      'action_date.date' => 'Format tarikh tindakan tidak sah.',
      'notes.max' => 'Catatan tidak boleh melebihi 1000 aksara.'
    ]);

    try {
      // Store old status before updating
      $oldStatus = $ticket->tick_status;

      // Log incoming request data for debugging
      \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Request Data', [
        'ticket_id' => $ticket->id,
        'tick_status' => $request->tick_status,
        'cat_id' => $request->cat_id,
        'subcat_id' => $request->subcat_id,
        'sla_severity' => $request->sla_severity,
        'assigned_officers' => $request->assigned_officers,
        'action_date' => $request->action_date,
        'notes' => $request->notes
      ]);

      // Situasi 2: Update ticket - Get SLA information based on category, subcategory, and severity
      $slaInfo = null;
      $slaDays = null;
      $slaDueDate = null;
      $levelPetugas = 1; // Default level
      $slaSeverity = $request->sla_severity;

      // Use existing ticket's cat_id and subcat_id if not provided in request
      $catId = $request->cat_id ?? $ticket->cat_id;
      $subcatId = $request->subcat_id ?? $ticket->subcat_id;

      // Get the first assigned officer to determine level_pegtugas
      $firstOfficerId = null;
      $assignedOfficerIds = [];

      // Only assign officers if status is NOT "Aduan Baru"
      if (!$isAduanBaru) {
        // If new officers are provided, use them
        if ($request->has('assigned_officers') && !empty($request->assigned_officers)) {
          $assignedOfficerIds = $request->assigned_officers;
          $firstOfficerId = $assignedOfficerIds[0];
        } else {
          // If no new officers provided, get existing officers from CURRENT status chronology
          $assignedOfficerIds = $ticket->chronologies()
            ->whereNotNull('escalate_to')
            ->where('tick_status', $ticket->tick_status) // Get from current status, not new status
            ->pluck('escalate_to')
            ->unique()
            ->toArray();

          // If no officers in current status, try to get from any previous status
          if (empty($assignedOfficerIds)) {
            $assignedOfficerIds = $ticket->chronologies()
              ->whereNotNull('escalate_to')
              ->orderBy('created_at', 'desc')
              ->pluck('escalate_to')
              ->unique()
              ->toArray();
          }

          $firstOfficerId = !empty($assignedOfficerIds) ? $assignedOfficerIds[0] : null;

          \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Using Existing Officers', [
            'ticket_id' => $ticket->id,
            'current_status' => $ticket->tick_status,
            'new_status' => $request->tick_status,
            'assigned_officer_ids' => $assignedOfficerIds,
            'first_officer_id' => $firstOfficerId
          ]);
        }

        // Get level_pegtugas from officers table if we have a first officer
        if ($firstOfficerId) {
          $officerData = \Illuminate\Support\Facades\DB::table('officers')
            ->where('user_id', $firstOfficerId)
            ->where('cat_id', $catId)
            ->where('subcat_id', $subcatId)
            ->whereNull('deleted_at')
            ->first();

          if ($officerData) {
            $levelPetugas = $officerData->level_tugasan ?? 1;
          }
        }
      }

      // Log incoming data for debugging
      \Illuminate\Support\Facades\Log::info('updateTicketAssignment - SLA Lookup Input', [
        'ticket_id' => $ticket->id,
        'request_cat_id' => $request->cat_id,
        'request_subcat_id' => $request->subcat_id,
        'used_cat_id' => $catId,
        'used_subcat_id' => $subcatId,
        'sla_severity' => $slaSeverity,
        'level_pegtugas' => $levelPetugas,
        'first_officer_id' => $firstOfficerId
      ]);

      // Try to get SLA info from database
      if ($catId && $subcatId) {
        // Build SLA query
        $slaQuery = Sla::where('cat_id', $catId)
          ->where('subcat_id', $subcatId)
          ->where('sla_level', $levelPetugas);

        // Add severity filter if provided
        if ($slaSeverity) {
          $slaQuery->where('sla_severity', $slaSeverity);
        }

        $slaInfo = $slaQuery->first();

        // If not found with severity, try without severity (fallback)
        if (!$slaInfo && $slaSeverity) {
          \Illuminate\Support\Facades\Log::warning('updateTicketAssignment - No SLA with severity, trying without', [
            'cat_id' => $catId,
            'subcat_id' => $subcatId,
            'sla_severity' => $slaSeverity,
            'level_pegtugas' => $levelPetugas
          ]);

          $slaInfo = Sla::where('cat_id', $catId)
            ->where('subcat_id', $subcatId)
            ->where('sla_level', $levelPetugas)
            ->first();
        }

        if ($slaInfo) {
          $slaDays = $slaInfo->sla_days;
          $slaSeverity = $slaSeverity ?? $slaInfo->sla_severity; // Use from DB if not provided
          $levelPetugas = $slaInfo->sla_level ?? $levelPetugas;

          // Calculate SLA due date by adding working days (skip weekends and public holidays)
          $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);

          // Log successful SLA calculation
          \Illuminate\Support\Facades\Log::info('updateTicketAssignment - SLA Calculation SUCCESS', [
            'ticket_id' => $ticket->id,
            'cat_id' => $catId,
            'subcat_id' => $subcatId,
            'sla_severity' => $slaSeverity,
            'sla_days' => $slaDays,
            'sla_duedate' => $slaDueDate ? $slaDueDate->format('Y-m-d H:i:s') : null,
            'level_pegtugas' => $levelPetugas
          ]);
        } else {
          // No SLA info found - use default values
          $slaDays = 3; // Default 3 working days
          $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);

          \Illuminate\Support\Facades\Log::warning('updateTicketAssignment - No SLA Info Found, using defaults', [
            'ticket_id' => $ticket->id,
            'cat_id' => $catId,
            'subcat_id' => $subcatId,
            'sla_severity' => $slaSeverity,
            'level_pegtugas' => $levelPetugas,
            'default_sla_days' => $slaDays,
            'default_sla_duedate' => $slaDueDate ? $slaDueDate->format('Y-m-d H:i:s') : null
          ]);
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

      // Log ticket update
      \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Ticket Updated', [
        'ticket_id' => $ticket->id,
        'update_data' => $updateData
      ]);

      // Note: firstOfficerId is already set in SLA calculation section above

      // Check if there's an existing chronology entry with the same status
      // Look for ANY chronology with this status (including those with escalate_to)
      $existingChronology = TicketChronology::where('ticket_id', $ticket->id)
        ->where('tick_status', $request->tick_status)
        ->orderBy('created_at', 'desc')
        ->first();

      // Log values before saving to chronology
      \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Before Saving to Chronology', [
        'ticket_id' => $ticket->id,
        'tick_status' => $request->tick_status,
        'sla_severity' => $slaSeverity,
        'sla_days' => $slaDays,
        'sla_duedate' => $slaDueDate ? $slaDueDate->format('Y-m-d H:i:s') : 'NULL',
        'level_pegtugas' => $levelPetugas,
        'escalate_to' => $firstOfficerId,
        'existing_chronology' => $existingChronology ? 'YES (ID: ' . $existingChronology->id . ')' : 'NO'
      ]);

      if ($existingChronology) {
        // Update existing chronology entry
        $chronology = $existingChronology;
        $chronology->update([
          'frm_user_id' => Auth::id(),
          'sla_severity' => $slaSeverity,
          'sla_days' => $slaDays,
          'sla_duedate' => $slaDueDate,
          'level_pegtugas_id' => $levelPetugas,
          'escalate_to' => $isAduanBaru ? null : $firstOfficerId, // NULL for Aduan Baru
          'updated_by' => Auth::id(),
          'updated_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
        ]);

        // Delete any other chronology entries for this ticket + status combination to avoid duplicates
        TicketChronology::where('ticket_id', $ticket->id)
          ->where('tick_status', $request->tick_status)
          ->where('id', '!=', $chronology->id)
          ->delete();

        \Illuminate\Support\Facades\Log::info('updateTicketAssignment - Existing Chronology Updated', [
          'chronology_id' => $chronology->id,
          'ticket_id' => $ticket->id,
          'tick_status' => $request->tick_status,
          'escalate_to' => $isAduanBaru ? 'NULL (Aduan Baru)' : $firstOfficerId,
          'sla_severity' => $slaSeverity,
          'sla_days' => $slaDays,
          'sla_duedate' => $slaDueDate ? $slaDueDate->format('Y-m-d H:i:s') : null
        ]);
      } else {
        // Create new chronology entry for status change
        $chronologyData = [
          'ticket_id' => $ticket->id,
          'frm_user_id' => Auth::id(),
          'tick_status' => $request->tick_status,
          'sla_severity' => $slaSeverity,
          'sla_days' => $slaDays,
          'sla_duedate' => $slaDueDate,
          'level_pegtugas_id' => $levelPetugas,
          'escalate_to' => $isAduanBaru ? null : $firstOfficerId, // NULL for Aduan Baru
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
          'created_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
          'updated_at' => now()
        ];

        // Create the chronology entry
        $chronology = TicketChronology::create($chronologyData);

        \Illuminate\Support\Facades\Log::info('updateTicketAssignment - New Chronology Created', [
          'chronology_id' => $chronology->id,
          'chronology_data' => $chronologyData
        ]);
      }

      // Handle additional officer assignments (2nd officer onwards) if provided
      // Only create additional assignments if status is NOT "Aduan Baru"
      if (!$isAduanBaru && !empty($assignedOfficerIds) && count($assignedOfficerIds) > 1) {
        // Skip the first officer as it's already stored in the main chronology entry
        $additionalOfficers = array_slice($assignedOfficerIds, 1);

        // First, delete existing additional officer chronology entries for this status
        TicketChronology::where('ticket_id', $ticket->id)
          ->where('tick_status', $request->tick_status)
          ->where('id', '!=', $chronology->id)
          ->delete();

        // Then create new entries for additional officers
        foreach ($additionalOfficers as $officerId) {
          // Create separate chronology entries for additional officers only
          TicketChronology::create([
            'ticket_id' => $ticket->id,
            'frm_user_id' => Auth::id(),
            'tick_status' => $request->tick_status,
            'escalate_to' => $officerId,
            'sla_severity' => $slaSeverity,
            'sla_days' => $slaDays,
            'sla_duedate' => $slaDueDate,
            'level_pegtugas_id' => $levelPetugas,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
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

      return redirect()->route('tickets.show', $ticket->uuid)
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
   * @param string $uuid
   * @return \Illuminate\Http\JsonResponse
   */
  public function assignTicket(Request $request, $uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

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
   * @param string $uuid
   * @return \Illuminate\Http\JsonResponse
   */
  public function getAssignees($uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

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
  public function getHistoryComment($ticketUuid)
  {
    try {
      $ticket = Ticket::where('uuid', $ticketUuid)
        ->with(['comments.user', 'comments.createdBy', 'status'])
        ->firstOrFail();

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
      // Sort by created_at descending (newest first)
      $comments = $ticket->comments->sortByDesc('created_at')->map(function ($comment) use ($ticket, $chronologies) {
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
      })->values();

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
   * Mark comments as viewed for a ticket
   * Store in database for reliability (session doesn't persist in impersonation mode)
   */
  public function markCommentsAsViewed($ticketUuid)
  {
    try {
      $ticket = Ticket::where('uuid', $ticketUuid)->firstOrFail();
      $userId = Auth::id();

      // Use cache with database fallback for reliability
      $cacheKey = "ticket_comments_viewed_{$ticket->id}_user_{$userId}";
      $viewedAt = now();

      // Store in cache (expires in 30 days)
      Cache::put($cacheKey, $viewedAt, now()->addDays(30));

      return response()->json([
        'success' => true,
        'message' => 'Catatan ditanda sebagai dibaca',
        'viewed_at' => $viewedAt->format('Y-m-d H:i:s')
      ]);
    } catch (\Exception $e) {
      Log::error('Error marking comments as viewed', [
        'error' => $e->getMessage(),
        'ticket_uuid' => $ticketUuid
      ]);
      return response()->json([
        'success' => false,
        'message' => 'Ralat: ' . $e->getMessage()
      ], 500);
    }
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
