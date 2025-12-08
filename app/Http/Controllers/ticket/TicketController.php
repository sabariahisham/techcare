<?php

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
use Illuminate\Support\Facades\DB;
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
        // Show tickets I created on behalf of others (created_by = current user, but user_id != current user)
        $query->where('created_by', $user->id)
          ->where('user_id', '!=', $user->id)
          ->where('ticket_type', 0);
        break;

      case 'bagi_pihak_sendiri':
        // Show tickets others created on behalf of me (user_id = current user, but created_by != current user)
        $query->where('user_id', $user->id)
          ->where('created_by', '!=', $user->id)
          ->where('ticket_type', 0);
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
            return '<span class="badge bg-warning">Bagi Pihak (Pengadu)</span><br><small class="text-muted" style="font-style: italic;">Nama Pengadu: ' . $userName . '</small>';
          }
          // Check if someone else created this ticket for current user
          else if ($ticket->user_id == $currentUserId && $ticket->created_by != $currentUserId) {
            return '<span class="badge bg-secondary">Bagi Pihak (Sendiri)</span><br><small class="text-muted" style="font-style: italic;">Dibuat oleh: ' . $creatorName . '</small>';
          }
          // Default case for other "bagi pihak" tickets
          else {
            return '<span class="badge bg-secondary">Bagi Pihak</span><br><small class="text-muted" style="font-style: italic;">Nama Pengadu: ' . $userName . '</small>';
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
      $this->handleFileAttachments($request->file('attachments'), $ticket->id);
    }

    return response()->json([
      'success' => true,
      'message' => 'Tiket berjaya dicipta!',
      'ticket' => $ticket
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
    $ticketHistory = $this->getTicketHistory($ticket);

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

  public function searchUserByName(Request $request)
  {
    $request->validate([
      'name' => 'required|string|min:2'
    ]);

    $name = $request->input('name');

    // Search for user by name with relationships
    $user = User::with(['sektor', 'bahagian', 'seksyen', 'unit', 'jawatan', 'skim'])
      ->where('nama', 'like', '%' . $name . '%')
      ->where('id', '!=', Auth::id()) // Exclude current user
      ->first();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Pengguna tidak ditemui dengan nama tersebut.'
      ], 404);
    }

    // Prepare user data with related information
    $userData = [
      'id' => $user->id,
      'nama' => $user->nama,
      'name' => $user->nama, // fallback
      'email' => $user->email,
      'kod_sektor' => $user->kod_sektor,
      'kod_bah' => $user->kod_bah,
      'kod_sec' => $user->kod_sec,
      'kod_unit' => $user->kod_unit,
      'kod_skim' => $user->kod_skim,
      'nama_gred' => $user->nama_gred,
      'tel_no_p' => $user->tel_no_p,
      'tel_no_hp' => $user->tel_no_hp,
    ];

    // Get related names if relationships exist
    if ($user->sektor) {
      $userData['nama_sektor'] = $user->sektor->nama_sektor;
    }

    if ($user->bahagian) {
      $userData['nama_bah'] = $user->bahagian->nama_bah;
    }

    if ($user->seksyen) {
      $userData['nama_sec'] = $user->seksyen->nama_sec;
    }

    if ($user->unit) {
      $userData['nama_unit'] = $user->unit->nama_unit;
    }

    if ($user->jawatan) {
      $userData['nama_jawatan'] = $user->jawatan->nama_jawatan;
    }

    if ($user->skim) {
      $userData['nama_skim'] = $user->skim->nama_skim;
    }

    return response()->json([
      'success' => true,
      'message' => 'Pengguna berjaya ditemui.',
      'user' => $userData
    ]);
  }

  public function searchUserByEmail(Request $request)
  {
    $request->validate([
      'email' => 'required|email'
    ]);

    $email = $request->input('email');

    // Search for user by exact email with relationships
    $user = User::with(['sektor', 'bahagian', 'seksyen', 'unit', 'jawatan', 'skim'])
      ->where('email', $email)
      ->where('id', '!=', Auth::id()) // Exclude current user
      ->first();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Pengguna tidak ditemui dengan email tersebut.'
      ], 404);
    }

    // Prepare user data with related information
    $userData = [
      'id' => $user->id,
      'nama' => $user->nama,
      'name' => $user->nama, // fallback
      'email' => $user->email,
      'kod_sektor' => $user->kod_sektor,
      'kod_bah' => $user->kod_bah,
      'kod_sec' => $user->kod_sec,
      'kod_unit' => $user->kod_unit,
      'kod_skim' => $user->kod_skim,
      'nama_gred' => $user->nama_gred,
      'tel_no_p' => $user->tel_no_p,
      'tel_no_hp' => $user->tel_no_hp,
    ];

    // Get related names if relationships exist
    if ($user->sektor) {
      $userData['nama_sektor'] = $user->sektor->nama_sektor;
    }

    if ($user->bahagian) {
      $userData['nama_bah'] = $user->bahagian->nama_bah;
    }

    if ($user->seksyen) {
      $userData['nama_sec'] = $user->seksyen->nama_sec;
    }

    if ($user->unit) {
      $userData['nama_unit'] = $user->unit->nama_unit;
    }

    if ($user->jawatan) {
      $userData['nama_jawatan'] = $user->jawatan->nama_jawatan;
    }

    if ($user->skim) {
      $userData['nama_skim'] = $user->skim->nama_skim;
    }

    return response()->json([
      'success' => true,
      'message' => 'Pengguna berjaya ditemui.',
      'user' => $userData
    ]);
  }

  public function searchUserAutocomplete(Request $request)
  {
    $request->validate([
      'query' => 'required|string|min:2'
    ]);

    $query = $request->input('query');

    // Search for users primarily by name (with some email support for edge cases)
    $users = User::select('id', 'nama', 'email')
      ->where(function ($q) use ($query) {
        // Primary search: by name (case insensitive)
        $q->where('nama', 'like', '%' . $query . '%');
        // Secondary search: by email username part (before @)
        $q->orWhere('email', 'like', $query . '%@%');
      })
      ->where('id', '!=', Auth::id()) // Exclude current user
      ->whereNotNull('nama') // Ensure nama is not null
      ->where('nama', '!=', '') // Ensure nama is not empty
      ->where('nama', 'not like', '%@%') // Exclude email-like names
      ->orderBy('nama', 'asc') // Order by name alphabetically
      ->limit(8) // Limit results to prevent too many options
      ->get()
      ->filter(function ($user) {
        // Additional filtering: exclude names that are just initials or codes
        $name = trim($user->nama);

        // Skip if name is just 1-2 characters (likely initials)
        if (strlen($name) <= 2) {
          return false;
        }

        // Skip if name contains only uppercase letters and spaces (likely codes)
        if (preg_match('/^[A-Z\s]+$/', $name) && strlen($name) <= 5) {
          return false;
        }

        return true;
      })
      ->values() // Reset array keys
      ->map(function ($user) {
        return [
          'id' => $user->id,
          'email' => $user->email,
          'nama' => $user->nama,
          'display' => $user->nama // Only show name in display
        ];
      });

    return response()->json([
      'success' => true,
      'users' => $users
    ]);
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
   * Get ticket history/kronologi for display from tickets_chronologies table only
   */
  private function getTicketHistory($ticket)
  {
    $history = collect();

    // Get chronology data from tickets_chronologies table only
    if ($ticket->chronologies && $ticket->chronologies->count() > 0) {
      foreach ($ticket->chronologies as $chronology) {
        $statusName = $chronology->status ? $chronology->status->st_name : 'Status Tidak Diketahui';

        // Build details array with available chronology information
        $details = [];

        // Add escalation information
        if ($chronology->escalate_to && $chronology->escalatedTo) {
          $details['Diserahkan kepada'] = $chronology->escalatedTo->nama;
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
          'details' => $details
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
        ]
      ]);
    }

    // Sort chronologically by timestamp
    return $history->sortBy('timestamp');
  }

  /**
   * Add chronology entry when ticket status changes
   * Also updates the ticket's current status to reflect the latest status
   */
  public function addChronologyEntry($ticketId, $newStatusId, $escalateTo = null, $slaDueDate = null, $levelPegtugasId = null)
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

    // Update ticket's current status to reflect the latest status change
    $ticket = Ticket::find($ticketId);
    if ($ticket) {
      $ticket->update([
        'tick_status' => $newStatusId,
        'updated_by' => Auth::id()
      ]);
    }

    return $chronology;
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
      $chronology = $this->addChronologyEntry(
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

  /**
   * Handle file attachments for ticket
   *
   * Stores uploaded files and creates Document records with:
   * - Files saved to storage/app/public/ticket_attachments/
   * - doc_jenis = 'TICKET'
   * - doc_id = ticket ID
   *
   * @param array|\Illuminate\Http\UploadedFile $files
   * @param int $ticketId
   * @return void
   */
  private function handleFileAttachments($files, $ticketId)
  {
    if (!is_array($files)) {
      $files = [$files];
    }

    foreach ($files as $file) {
      if ($file && $file->isValid()) {
        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;

        // Store file
        $path = $file->storeAs('ticket_attachments', $filename, 'public');

        // Create document record
        Document::create([
          'doc_id' => $ticketId,
          'uuid' => Str::uuid(),
          'doc_mime' => $file->getMimeType(),
          'doc_name' => $originalName,
          'doc_storage_nm' => $filename,
          'doc_jenis' => 'TICKET',
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
        ]);
      }
    }
  }

  /**
   * Download ticket attachment file
   *
   * Downloads a specific attachment file by document ID.
   * Only allows downloads of documents with doc_jenis = 'TICKET'
   *
   * @param int $documentId
   * @return \\Symfony\\Component\\HttpFoundation\\BinaryFileResponse
   * @throws \\Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException
   */
  public function downloadAttachment($documentId)
  {
    $document = Document::where('id', $documentId)
      ->where('doc_jenis', 'TICKET')
      ->firstOrFail();

    $filePath = storage_path('app/public/ticket_attachments/' . $document->doc_storage_nm);

    if (!file_exists($filePath)) {
      abort(404, 'File tidak dijumpai');
    }

    return response()->download($filePath, $document->doc_name, [
      'Content-Type' => $document->doc_mime,
    ]);
  }

  /**
   * Add a comment to a ticket
   *
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\RedirectResponse
   */
  public function addComment(Request $request, Ticket $ticket)
  {
    Log::info('addComment method called', [
      'ticket_id' => $ticket->id,
      'user_id' => Auth::id(),
      'request_data' => $request->all()
    ]);

    $request->validate([
      'comment' => 'required|string|min:1|max:1000',
    ], [
      'comment.required' => 'Komen diperlukan.',
      'comment.min' => 'Komen mestilah sekurang-kurangnya 1 aksara.',
      'comment.max' => 'Komen tidak boleh melebihi 1000 aksara.',
    ]);

    try {
      // Create the comment using Eloquent
      $comment = Comment::create([
        'user_id' => Auth::id(),
        'ticket_id' => $ticket->id,
        'comment_text' => $request->comment,
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
      ]);

      Log::info('Comment created successfully', [
        'comment_id' => $comment->id,
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id()
      ]);

      return redirect()->back()->with('success', 'Komen berjaya ditambah!');
    } catch (\Exception $e) {
      Log::error('Error creating comment', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'request_data' => $request->all(),
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
      ]);

      return redirect()->back()
        ->with('error', 'Ralat semasa menambah komen: ' . $e->getMessage())
        ->withInput();
    }
  }
}
