<?php

/*
 * Function TicketController.php:
 *  - Manage ticket listing and filtering for Pengadu and Pegawai Tugasan.
 *  - CRUD main ticket (create, read, update, delete)
 *  - status management (open, pending, closed).
 */

namespace App\Http\Controllers\Ticket;

use id;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Comment;
use App\Models\Document;
use App\Models\RefCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RefTicketStatus;
use App\Models\TicketChronology;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Ticket\StoreTicketRequest;

class TicketController extends Controller
{
  public function getData(Request $request)
  {
    $user = Auth::user();
    $type = $request->get('type', 'my'); // 'my' or 'manage'

    // Route to appropriate method based on user role and type
    if ($type === 'my') {
      return $this->getMyTicketsData($request);
    } else {
      return $this->getTicketsToManageData($request);
    }
  }

  /**
   * Get user's own tickets (all users can see their own tickets)
   */
  public function getMyTicketsData(Request $request)
  {
    $user = Auth::user();
    $ticketFilter = $request->get('ticket_filter', 'all'); // 'all', 'diri_sendiri', 'bagi_pihak', 'bagi_pihak_sendiri'

    $query = Ticket::with(['user', 'category', 'subCategory', 'status', 'createdBy']);

    // Apply filters based on ticket_filter parameter
    switch ($ticketFilter) {
      case 'diri_sendiri':
        // Show tickets I created for myself (created_by = user_id = current user)
        $query->where('user_id', $user->id)
          ->where('created_by', $user->id)
          ->where('ticket_type', 1);
        break;

      case 'bagi_pihak':
        // Show all "bagi pihak" tickets where I'm involved:
        // 1. Tickets I created on behalf of others (I'm Wakil)
        // 2. Tickets others created on behalf of me (I'm Pengadu)
        $query->where('ticket_type', 0)
          ->where(function ($q) use ($user) {
            $q->where('created_by', $user->id) // I created for others (Wakil)
              ->orWhere('user_id', $user->id); // Others created for me (Pengadu)
          });
        break;

      default: // 'all' or any other value
        // Show all tickets related to me:
        // 1. Tickets I created for myself (user_id = me AND created_by = me)
        // 2. Tickets others created for me (user_id = me AND created_by != me)
        // 3. Tickets I created for others (created_by = me AND user_id != me)
        $query->where(function ($q) use ($user) {
          $q->where('user_id', $user->id) // Tickets for me (regardless of who created them)
            ->orWhere('created_by', $user->id); // Tickets I created (regardless of for whom)
        });
        break;
    }

    $tickets = $query->latest();

    return DataTables::of($tickets)
      ->addIndexColumn()
      ->addColumn('no_log_tiket', function ($ticket) {
        return e($ticket->ticket_nolog);
      })
      ->addColumn('tarikh_aduan', function ($ticket) {
        return $ticket->created_at ? $ticket->created_at->format('d-m-Y') : '-';
      })
      ->addColumn('jenis_tiket', function ($ticket) {
        $currentUserId = Auth::id();
        if ($ticket->ticket_type == 1) {
          return '<span class="badge bg-info">Diri Sendiri</span>';
        } else {
          $userName = $ticket->user ? e($ticket->user->nama) : 'Tidak Diketahui';
          $creatorName = $ticket->createdBy ? e($ticket->createdBy->nama) : 'Tidak Diketahui';

          // Check if current user created this ticket for someone else
          if ($ticket->created_by == $currentUserId && $ticket->user_id != $currentUserId) {
            return '<span class="badge bg-warning">Bagi Pihak</span><i class="bx bx-info-circle ms-0" data-bs-toggle="tooltip" title="Aduan ini dibuat untuk pengguna lain."></i><br><small class="text-muted" style="font-style: italic;"> Pengadu: ' . $userName . '</small>';
          }
          // Check if someone else created this ticket for current user
          else if ($ticket->user_id == $currentUserId && $ticket->created_by != $currentUserId) {
            return '<span class="badge bg-warning">Bagi Pihak</span><i class="bx bx-info-circle ms-0" data-bs-toggle="tooltip" title="Aduan ini dibuat bagi pihak anda."></i><br><small class="text-muted" style="font-style: italic;">Wakil: ' . $creatorName . '</small>';
          }
          // Default case for other "bagi pihak" tickets
          else {
            return '<span class="badge bg-warning" >Bagi Pihak</span><br><small class="text-muted" style="font-style: italic;">Pengadu: ' . $userName . '</small>';
          }
        }
      })
      ->addColumn('kategori', function ($ticket) {
        return $ticket->category ? e($ticket->category->cat_name) : '-';
      })
      ->addColumn('sub_kategori', function ($ticket) {
        return $ticket->subCategory ? e($ticket->subCategory->subcat_name) : '-';
      })
      ->addColumn('status', function ($ticket) {
        return $this->formatStatusBadge($ticket);
      })
      ->addColumn('pegawai_tugasan', function ($ticket) {
        return $this->getAssignedOfficer($ticket);
      })
      ->addColumn('action', function ($ticket) {
        return $this->getActionButtons($ticket, 'my');
      })
      ->filterColumn('no_log_tiket', function ($query, $keyword) {
        $query->where('ticket_nolog', 'like', "%$keyword%");
      })
      ->filterColumn('kategori', function ($query, $keyword) {
        $query->whereHas('category', function ($q) use ($keyword) {
          $q->where('cat_name', 'like', "%$keyword%");
        });
      })
      ->filterColumn('sub_kategori', function ($query, $keyword) {
        $query->whereHas('subCategory', function ($q) use ($keyword) {
          $q->where('subcat_name', 'like', "%$keyword%");
        });
      })
      ->rawColumns(['action', 'status', 'jenis_tiket'])
      ->make(true);
  }

  /**
   * Get tickets to manage based on user role
   */
  public function getTicketsToManageData(Request $request)
  {
    $user = Auth::user();
    $query = Ticket::with(['user', 'category', 'subCategory', 'status']);

    // Apply role-based filtering
    if ($user->hasRole('Administrator')) {
      // Administrator can see all tickets
      $query->latest();
    } elseif ($user->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      // Helpdesk roles can see all tickets except specific statuses if needed
      $query->latest();
    } elseif ($user->hasRole('Pegawai Tugasan')) {
      // Pegawai Tugasan can see tickets assigned to them via escalation
      $query->whereHas('chronologies', function ($q) use ($user) {
        $q->where('escalate_to', $user->id);
      })->latest();
    } else {
      // Regular users can't manage tickets, return empty result
      $query->where('id', 0); // This will return no results
    }

    return DataTables::of($query)
      ->addIndexColumn()
      ->addColumn('no_log_tiket', function ($ticket) {
        return e($ticket->ticket_nolog);
      })
      ->addColumn('tarikh_aduan', function ($ticket) {
        return $ticket->created_at ? $ticket->created_at->format('d-m-Y') : '-';
      })
      ->addColumn('nama_pengadu', function ($ticket) {
        return $ticket->user ? e($ticket->user->nama) : '-';
      })
      ->addColumn('kategori', function ($ticket) {
        return $ticket->category ? e($ticket->category->cat_name) : '-';
      })
      ->addColumn('sub_kategori', function ($ticket) {
        return $ticket->subCategory ? e($ticket->subCategory->subcat_name) : '-';
      })
      ->addColumn('status', function ($ticket) {
        return $this->formatStatusBadge($ticket);
      })
      ->addColumn('pegawai_tugasan', function ($ticket) {
        return $this->getAssignedOfficer($ticket);
      })
      ->addColumn('action', function ($ticket) {
        return $this->getActionButtons($ticket, 'manage');
      })
      ->rawColumns(['action', 'status'])
      ->make(true);
  }

  /**
   * Format status badge for display
   */
  private function formatStatusBadge($ticket)
  {
    if (!$ticket->status) {
      return '<span class="badge bg-secondary">-</span>';
    }

    $statusName = $ticket->status->st_name;
    $badgeClass = '';

    switch ($statusName) {
      case 'Aduan Baru':
        $badgeClass = 'bg-primary';
        break;
      case 'Aduan Diproses':
        $badgeClass = 'bg-warning';
        break;
      case 'Serahan Kepada':
        $badgeClass = 'bg-info';
        break;
      case 'Aduan Dirujuk':
        $badgeClass = 'bg-danger';
        break;
      case 'Aduan DiBatal':
        $badgeClass = 'bg-secondary';
        break;
      case 'Aduan Selesai':
        $badgeClass = 'bg-success';
        break;
      default:
        $badgeClass = 'bg-secondary';
    }

    return '<span class="badge ' . $badgeClass . '">' . e($statusName) . '</span>';
  }

  /**
   * Get assigned officer for the ticket
   */
  private function getAssignedOfficer($ticket)
  {
    return TicketAssignmentController::getAssignedOfficer($ticket);
  }

  /**
   * Generate action buttons based on user role and context
   */
  private function getActionButtons($ticket, $context = 'my')
  {
    $user = Auth::user();
    $buttons = [];

    // View button for all tickets
    if ($context === 'my') {
      $buttons[] = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm view-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="View"><i class="bx bx-show"></i></button>';
    } else {
      // For tickets to manage
      if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
        $buttons[] = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm manage-ticket" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Manage Status"><i class="bx bx-cog"></i></button>';
      } elseif ($user->hasRole('Pegawai Tugasan')) {
        $buttons[] = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm manage-ticket" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Update Status"><i class="bx bx-task"></i></button>';
      }
    }

    return '<div class="d-inline-block text-nowrap">' . implode(' ', $buttons) . '</div>';
  }

  public function index()
  {
    $categories = RefCategory::all();
    return view('tickets.index', compact('categories'));
  }

  public function create()
  {
    //using index.blade.php
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

  // Edit functionality removed - complainants should only log and submit tickets

  // Update functionality removed - complainants should only log and submit tickets

  // Delete functionality removed - complainants should only log and submit tickets




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
