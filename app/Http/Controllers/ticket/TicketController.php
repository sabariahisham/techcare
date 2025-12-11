<?php

/*
 * Function TicketController.php:
 *  - Manage ticket listing and filtering for Pengadu and Pegawai Tugasan.
 *  - CRUD main ticket (create, read, update, delete)
 *  - status management (open, pending, closed).
 */

namespace App\Http\Controllers\Ticket;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Comment;
use App\Models\Document;
use App\Models\RefCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RefTicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Ticket\StoreTicketRequest;

class TicketController extends Controller
{
  public function index()
  {
    $categories = RefCategory::all();
    return view('tickets.index', compact('categories'));
  }

  public function store(StoreTicketRequest $request)
  {
    $validated = $request->validated();

    // Determine user_id based on ticket_type
    if ($validated['ticket_type'] == 1) {
      // Diri Sendiri - use authenticated user
      $user_id = Auth::id();
    } else {
      // Bagi Pihak - find user by email from search
      $other_email = $validated['other_email'] ?? null;
      if ($other_email) {
        $otherUser = User::where('email', $other_email)->first();
        if ($otherUser) {
          $user_id = $otherUser->id;
        } else {
          return response()->json([
            'success' => false,
            'message' => 'Pengguna tidak dijumpai. Sila pastikan email yang dicari adalah betul.',
            'errors' => ['other_email' => ['Pengguna dengan email ini tidak dijumpai dalam sistem.']]
          ], 422);
        }
      } else {
        return response()->json([
          'success' => false,
          'message' => 'Email pengguna diperlukan untuk aduan bagi pihak.',
          'errors' => ['other_email' => ['Sila cari dan pilih pengguna terlebih dahulu.']]
        ], 422);
      }
    }

    // Get default status (assuming first status is 'Open' or 'New')
    $defaultStatus = RefTicketStatus::first();

    $data = [
      'uuid' => Str::uuid(),
      'ticket_nolog' => $this->generateTicketNumber(),
      'user_id' => $user_id,
      'cat_id' => $validated['cat_id'],
      'subcat_id' => $validated['subcat_id'],
      'serial_number' => $validated['no_siri'] ?? null, // Map no_siri to serial_number column (optional)
      'ticket_title' => $validated['tick_title'], // Map tick_title to ticket_title
      'ticket_desc' => $validated['tick_desc'], // Map tick_desc to ticket_desc
      'ticket_type' => $validated['ticket_type'],
      'tick_status' => $defaultStatus ? $defaultStatus->id : 1,
      'tick_duedate' => null, // You can add logic for due date calculation
      'created_by' => Auth::id(),
    ];
    // Remove the other_person fields logic since we're using user_id directly

    $ticket = Ticket::create($data);

    // Handle file attachments if any
    if ($request->hasFile('attachments')) {
      TicketAttachmentController::handleFileAttachments($request->file('attachments'), $ticket->id);
    }

    return response()->json([
      'success' => true,
      'message' => 'Tiket berjaya dicipta! Tiket anda telah dilog dan akan diproses dalam masa terdekat.',
      'ticket' => [
        'id' => $ticket->id,
        'uuid' => $ticket->uuid,
        'ticket_nolog' => $ticket->ticket_nolog,
        'ticket_title' => $ticket->ticket_title,
        'ticket_type' => $ticket->ticket_type,
        'created_at' => $ticket->created_at->format('d/m/Y H:i'),
      ]
    ]);
  }

  public function show(Ticket $ticket)
  {
    // Load ticket with comprehensive relationships
    $ticket->load([
      'user.sektor',
      'user.bahagian',
      'user.seksyen',
      'user.unit',
      'category',
      'subCategory',
      'status',
      'createdBy',
      'updatedBy',
      'chronologies.status',
      'chronologies.createdBy',
      'chronologies.fromUser',
      'chronologies.escalatedTo',
      'comments.user'
    ]);

    // Force reload comments to ensure they are current
    $ticket->load('comments.user');

    // Force load attachments using direct query to ensure they are loaded
    $ticket->attachments = Document::where('doc_id', $ticket->id)
      ->where('doc_jenis', 'TICKET')
      ->get();

    // Get ticket history/audit trail if available
    $ticketHistory = TicketChronologyController::getTicketHistory($ticket);

    // Get other relevant data
    $allStatuses = RefTicketStatus::all();

    Log::info('Showing ticket with comments', [
      'ticket_id' => $ticket->id,
      'comments_count' => $ticket->comments->count()
    ]);

    return view('tickets.show', compact('ticket', 'ticketHistory', 'allStatuses'));
  }

  /**
   * Generate sequential ticket number in format HELP-YYYY-MM-XXXX
   */
  private function generateTicketNumber()
  {
    $currentDate = now();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    // Find the highest ticket number for the current month
    $prefix = "HELP-{$year}-{$month}-";
    $lastTicket = Ticket::where('ticket_nolog', 'LIKE', $prefix . '%')
      ->orderBy('ticket_nolog', 'desc')
      ->first();

    $nextNumber = 1;

    if ($lastTicket) {
      // Extract the last 4 digits and increment
      $lastNumber = (int) substr($lastTicket->ticket_nolog, -4);
      $nextNumber = $lastNumber + 1;
    }

    // Format as 4-digit number with leading zeros
    return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
  }


  /**
   * Update ticket status and create chronology entry
   */
  public function updateTicketStatus(Request $request, Ticket $ticket)
  {
    $request->validate([
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'escalate_to' => 'nullable|exists:users,id',
      'sla_duedate' => 'nullable|date',
      'level_pegtugas_id' => 'nullable|integer|min:1|max:5'
    ]);

    try {
      // Add chronology entry and update ticket status
      $chronology = TicketChronologyController::addChronologyEntry(
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
        'message' => 'Status tiket berjaya dikemaskini!',
        'ticket' => $ticket,
        'chronology' => $chronology
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mengemaskini status tiket: ' . $e->getMessage()
      ], 500);
    }
  }
}
