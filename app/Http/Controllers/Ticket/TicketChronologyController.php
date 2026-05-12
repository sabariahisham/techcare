<?php

/*
 * Function TicketChronologyController.php:
 *  - Manage ticket chronology (status history-use badges and timeline).
 *  - Track status changes and escalations.
 *  - Provide chronology data for ticket history display.
 *  - Comments will update according to current status in chronological view
 *  - Response time calculation: count for total number of days for ticket complaints to be completed
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TicketChronologyController extends Controller
{
  /**
   * Get chronology data for a ticket
   *
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function getChronology(Ticket $ticket)
  {
    try {
      // Load ticket with chronologies and related data
      $ticket->load([
        'chronologies.status',
        'chronologies.createdBy',
        'chronologies.fromUser',
        'chronologies.escalatedTo',
        'category',
        'subCategory',
        'user',
        'createdBy'
      ]);

      $history = $this->getTicketHistory($ticket);

      return response()->json([
        'success' => true,
        'chronology' => $history,
        'ticket_info' => [
          'id' => $ticket->id,
          'ticket_nolog' => $ticket->ticket_nolog,
          'title' => $ticket->ticket_title,
          'type' => $ticket->ticket_type == 1 ? 'Diri Sendiri' : 'Bagi Pihak'
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan kronologi tiket: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Normalize assignee names from single, comma-separated, JSON, or array values.
   */
  private static function normalizeAssigneeNames($value): array
  {
    if (blank($value)) {
      return [];
    }

    if ($value instanceof \Illuminate\Support\Collection) {
      $value = $value->all();
    }

    if (is_string($value)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $value = $decoded;
      } else {
        $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
      }
    }

    if (is_object($value) && method_exists($value, 'toArray')) {
      $value = $value->toArray();
    }

    if (!is_array($value)) {
      $value = [$value];
    }

    $normalizedValues = collect($value)
      ->flatMap(function ($item) {
        if (blank($item)) {
          return [];
        }

        if ($item instanceof User) {
          return [$item->nama];
        }

        if (is_object($item)) {
          if (isset($item->nama)) {
            return [$item->nama];
          }

          return method_exists($item, 'toArray')
            ? self::normalizeAssigneeNames($item->toArray())
            : [trim((string) $item)];
        }

        if (is_array($item)) {
          if (isset($item['nama'])) {
            return [$item['nama']];
          }

          return self::normalizeAssigneeNames($item);
        }

        return [trim((string) $item)];
      })
      ->filter()
      ->values();

    $numericIds = $normalizedValues
      ->filter(fn($item) => is_numeric($item))
      ->map(fn($item) => (int) $item)
      ->unique()
      ->values();

    $resolvedNames = $numericIds->isNotEmpty()
      ? User::query()->whereIn('id', $numericIds->all(), 'and', false)->pluck('nama', 'id')
      : collect();

    return $normalizedValues
      ->map(function ($item) use ($resolvedNames) {
        if (is_numeric($item) && $resolvedNames->has((int) $item)) {
          return $resolvedNames[(int) $item];
        }

        return trim((string) $item);
      })
      ->filter()
      ->unique()
      ->values()
      ->all();
  }

  /**
   * Resolve assignee names for chronology entries even when stored as arrays.
   */
  private static function getChronologyAssigneeNames(TicketChronology $chronology): array
  {
    $values = [];

    if ($chronology->escalatedTo) {
      $values[] = $chronology->escalatedTo->nama;
    }

    if (!blank($chronology->escalate_to)) {
      $values[] = $chronology->escalate_to;
    }

    return self::normalizeAssigneeNames($values);
  }

  /**
   * Format assignee names for timeline display.
   */
  private static function formatAssigneeNamesForDisplay(array $names): string
  {
    if (empty($names)) {
      return '';
    }

    $names = collect($names)
      ->map(fn($name) => trim((string) $name))
      ->filter()
      ->unique()
      ->values();

    if ($names->isEmpty()) {
      return '';
    }

    $primaryName = $names->first();
    $remainingCount = $names->count() - 1;

    return $remainingCount > 0
      ? $primaryName . ' (+' . $remainingCount . ')'
      : $primaryName;
  }

  /**
   * Add chronology entry when ticket status changes
   * Also updates the ticket's current status to reflect the latest status
   */
  public static function addChronologyEntry($ticketId, $newStatusId, $escalateTo = null, $slaDueDate = null, $levelPegtugasId = null, $notes = null)
  {
    // Create chronology entry
    $chronology = TicketChronology::create([
      'ticket_id' => $ticketId,
      'frm_user_id' => Auth::id(),
      'level_pegtugas_id' => $levelPegtugasId,
      'tick_status' => $newStatusId,
      'escalate_to' => $escalateTo,
      'sla_duedate' => $slaDueDate,
      'created_by' => Auth::id(),
    ]);

    // If notes are provided, add them as a comment
    if ($notes) {
      TicketComment::create([
        'ticket_id' => $ticketId,
        'user_id' => Auth::id(),
        'comment_text' => $notes,
        'created_by' => Auth::id(),
      ]);
    }

    // Update ticket's current status to reflect the latest status change
    $ticket = Ticket::query()->find($ticketId);
    if ($ticket) {
      $ticket->update([
        'tick_status' => $newStatusId,
        'updated_by' => Auth::id()
      ]);
    }

    return $chronology;
  }

  /**
   * Get ticket history/kronologi for display from tickets_chronologies table and comments
   */
  public static function getTicketHistory($ticket)
  {
    $history = collect();

    // Get chronology data from tickets_chronologies table only
    if ($ticket->chronologies && $ticket->chronologies->count() > 0) {
      foreach ($ticket->chronologies as $chronology) {
        $statusName = $chronology->status ? $chronology->status->st_name : 'Status Tidak Diketahui';
        $assigneeNames = self::getChronologyAssigneeNames($chronology);

        // Build details array with available chronology information
        $details = [];

        // Add escalation information
        if (!empty($assigneeNames)) {
          $details['Diserahkan kepada'] = self::formatAssigneeNamesForDisplay($assigneeNames);
        }

        // Add SLA information
        if ($chronology->sla_duedate) {
          $details['SLA Due Date'] = $chronology->sla_duedate->format('d/m/Y H:i:s');
        }

        // Add level pegtugas information
        if ($chronology->level_pegtugas_id) {
          $details['Level Pegtugas'] = "Level {$chronology->level_pegtugas_id}";
        }

        // Add processed by information
        if ($chronology->fromUser) {
          $details['Diproses oleh'] = $chronology->fromUser->nama;
        }

        // For "Aduan Baru" status, add ticket creation details
        if ($statusName === 'Aduan Baru') {
          $details['Kategori'] = $ticket->category ? $ticket->category->cat_name : '-';
          $details['Sub Kategori'] = $ticket->subCategory ? $ticket->subCategory->subcat_name : '-';
          $details['Jenis Tiket'] = $ticket->ticket_type == 1 ? 'Diri Sendiri' : 'Bagi Pihak';
        }

        $history->push([
          'action' => $statusName,
          'status' => $statusName,
          'description' => $chronology->getTimelineDescription(),
          'user' => $chronology->createdBy ? $chronology->createdBy->nama : 'Sistem',
          'timestamp' => $chronology->created_at,
          'details' => $details,
          'notes' => null, // No notes for status changes
          'type' => 'chronology'
        ]);
      }
    } else {
      // If no chronologies exist, show only current ticket status (fallback)
      $currentStatus = $ticket->status ? $ticket->status->st_name : 'Aduan Baru';
      $history->push([
        'action' => $currentStatus,
        'status' => $currentStatus,
        'description' => $ticket->status ? $ticket->status->st_desc : 'Tiket telah dicipta dalam sistem.',
        'user' => $ticket->createdBy ? $ticket->createdBy->nama : ($ticket->user ? $ticket->user->nama : 'Sistem'),
        'timestamp' => $ticket->created_at,
        'details' => [
          'Kategori' => $ticket->category ? $ticket->category->cat_name : '-',
          'Sub Kategori' => $ticket->subCategory ? $ticket->subCategory->subcat_name : '-',
          'Jenis Tiket' => $ticket->ticket_type == 1 ? 'Diri Sendiri' : 'Bagi Pihak'
        ],
        'notes' => null,
        'type' => 'chronology'
      ]);
    }

    // Get comments and add them to the timeline
    if ($ticket->comments && $ticket->comments->count() > 0) {
      foreach ($ticket->comments as $comment) {
        // Get the current status at the time of comment
        $currentStatus = $ticket->status ? $ticket->status->st_name : 'Status Tidak Diketahui';

        $history->push([
          'action' => $currentStatus,
          'status' => $currentStatus,
          'description' => 'Catatan ditambah untuk tiket ini.',
          'user' => $comment->user ? $comment->user->nama : 'Pengguna Tidak Diketahui',
          'timestamp' => $comment->created_at,
          'details' => [],
          'notes' => $comment->comment_text, // Use comment_text field
          'type' => 'comment'
        ]);
      }
    }

    // Sort chronologically by timestamp
    return $history->sortBy('timestamp');
  }

  /**
   * Create a new chronology entry (for API use)
   *
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(Request $request, Ticket $ticket)
  {
    $request->validate([
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'escalate_to' => 'nullable|exists:users,id',
      'sla_duedate' => 'nullable|date',
      'level_pegtugas_id' => 'nullable|integer|min:1|max:5',
      'remarks' => 'nullable|string|max:1000'
    ]);

    try {
      // Create chronology entry
      $chronology = self::addChronologyEntry(
        $ticket->id,
        $request->tick_status,
        $request->escalate_to,
        $request->sla_duedate ? \Carbon\Carbon::parse($request->sla_duedate) : null,
        $request->level_pegtugas_id
      );

      // Reload ticket with latest data
      $ticket->refresh();
      $ticket->load(['status', 'chronologies.status', 'chronologies.createdBy', 'chronologies.fromUser', 'chronologies.escalatedTo']);

      return response()->json([
        'success' => true,
        'message' => 'Kronologi tiket berjaya ditambah!',
        'chronology' => $chronology,
        'ticket' => $ticket
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa menambah kronologi: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get chronology statistics for a ticket
   *
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function getStatistics(Ticket $ticket)
  {
    try {
      $chronologies = $ticket->chronologies()->with(['status', 'escalatedTo'])->get();

      $stats = [
        'total_chronologies' => $chronologies->count(),
        'status_changes' => $chronologies->pluck('tick_status')->unique()->count(),
        'escalations' => $chronologies->whereNotNull('escalate_to')->count(),
        'sla_entries' => $chronologies->whereNotNull('sla_duedate')->count(),
        'response_time' => $this->calculateResponseTime($ticket),
        'resolution_time' => $this->calculateResolutionTime($ticket)
      ];

      return response()->json([
        'success' => true,
        'statistics' => $stats
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan statistik kronologi: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Calculate response time for a ticket (first status change after creation)
   */
  private function calculateResponseTime($ticket)
  {
    $firstResponse = $ticket->chronologies()
      ->where('tick_status', '!=', 1) // Exclude initial "Aduan Baru" status
      ->oldest()
      ->first();

    if ($firstResponse) {
      return $ticket->created_at->diffInHours($firstResponse->created_at);
    }

    return null;
  }

  /**
   * Calculate resolution time for a ticket (time to resolve)
   */
  private function calculateResolutionTime($ticket)
  {
    $resolution = $ticket->chronologies()
      ->where('tick_status', 6) // Assuming 6 is "Aduan Selesai"
      ->latest()
      ->first();

    if ($resolution) {
      return $ticket->created_at->diffInHours($resolution->created_at);
    }

    return null;
  }
}
