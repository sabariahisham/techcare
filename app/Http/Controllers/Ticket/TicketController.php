<?php

/*
 * Function TicketController.php:
 *  - Manage ticket listing and filtering for Pengadu and Pegawai Tugasan.
 *  - CRUD main ticket (create, read, update, delete)
 *  - status management (open, pending, closed).
 *
 * IMPORTANT: SLA Logic for ticket_chronologies table
 *
 * SITUASI 1 - Create Ticket (Initial chronology):
 *  - sla_severity: NULL (not set on creation)
 *  - sla_days: 3 (default 3 working days)
 *  - sla_duedate: created_at + 3 working days (skip weekends & public holidays)
 *
 * SITUASI 2 - Update Ticket (Subsequent chronologies):
 *  - sla_severity: High/Medium/Low (from user selection)
 *  - sla_days: Retrieved from slas table based on cat_id, subcat_id, and sla_severity
 *  - sla_duedate: current time + sla_days working days (skip weekends & public holidays)
 *
 * IMPORTANT: Officer Assignment Logic
 * The 'escalate_to' field in TicketChronology MUST always refer to user_id from the Officer table
 * where cat_id and subcat_id match between the ticket and officer records.
 *
 * This ensures:
 * 1. Only officers assigned to specific categories/subcategories receive tickets
 * 2. Proper load balancing among officers within the same category/subcategory
 * 3. Data integrity between tickets, chronologies, and officer assignments
 *
 * Key Methods:
 * - getAssignedOfficer(): Auto-assigns officer based on category/subcategory matching
 * - validateOfficerAssignment(): Validates escalate_to refers to valid officer
 * - getAvailableOfficers(): Returns officers for specific category/subcategory
 */

namespace App\Http\Controllers\Ticket;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Document;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\Sla;
use App\Models\TicketChronology;
use App\Models\Officer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RefTicketStatus;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Notifications\Ticket\PengaduStatusChangedNotification;
use App\Services\TicketNotificationService;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Controllers\Ticket\TicketAttachmentController;
use App\Http\Controllers\Ticket\TicketChronologyController;
use App\Helpers\Helpers;

class TicketController extends Controller
{
  protected $notificationService;

  public function __construct(TicketNotificationService $notificationService)
  {
    $this->notificationService = $notificationService;
  }

  public function index()
  {
    $categories = RefCategory::all();
    return view('tickets.index', compact('categories'));
  }

  /**
   * Get DataTables data for tickets listing
   */
  public function data(Request $request)
  {
    $type = $request->get('type', 'my');
    $ticketFilter = $request->get('ticket_filter', 'all');

    if ($type === 'my') {
      // My Tickets - tickets related to authenticated user
      $query = Ticket::with(['user', 'category', 'subCategory', 'status', 'createdBy', 'chronologies.escalatedTo'])
        ->where(function ($q) {
          $q->where('user_id', Auth::id())
            ->orWhere('created_by', Auth::id());
        });

      // Apply ticket filter
      if ($ticketFilter === 'diri_sendiri') {
        $query->where('ticket_type', 1);
      } elseif ($ticketFilter === 'bagi_pihak') {
        $query->where('ticket_type', 2);
      }

      // Order by latest date first
      $query->orderBy('created_at', 'desc');

      return DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('uuid', function ($ticket) {
          return $ticket->uuid;
        })
        ->filterColumn('no_log_tiket', function ($query, $keyword) {
          $query->where('ticket_nolog', 'like', "%{$keyword}%");
        })
        ->filterColumn('tarikh_aduan', function ($query, $keyword) {
          $query->whereRaw("DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') like ?", ["%{$keyword}%"]);
        })
        ->filterColumn('jenis_tiket', function ($query, $keyword) {
          if (stripos('Diri Sendiri', $keyword) !== false) {
            $query->where('ticket_type', 1);
          } elseif (stripos('Bagi Pihak', $keyword) !== false) {
            $query->where('ticket_type', 2);
          }
        })
        ->filterColumn('kategori', function ($query, $keyword) {
          $query->whereHas('category', function ($q) use ($keyword) {
            $q->where('cat_name', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('sub_kategori', function ($query, $keyword) {
          $query->whereHas('subCategory', function ($q) use ($keyword) {
            $q->where('subcat_name', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('status', function ($query, $keyword) {
          $statusMap = [
            'Aduan Baharu' => 1,
            'Aduan Diproses' => 2,
            'Serahan Kepada' => 3,
            'Aduan Dirujuk' => 4,
            'Aduan Dibatal' => 5,
            'Aduan Selesai' => 6
          ];
          foreach ($statusMap as $statusName => $statusValue) {
            if (stripos($statusName, $keyword) !== false) {
              $query->orWhere('tick_status', $statusValue);
            }
          }
        })
        ->filterColumn('pegawai_tugasan', function ($query, $keyword) {
          $query->whereHas('chronologies.escalatedTo', function ($q) use ($keyword) {
            $q->where('nama', 'like', "%{$keyword}%");
          });
        })
        ->addColumn('no_log_tiket', function ($ticket) {
          return $ticket->ticket_nolog ?? 'N/A';
        })
        ->addColumn('tarikh_aduan', function ($ticket) {
          return $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-';
        })
        ->addColumn('jenis_tiket', function ($ticket) {
          if ($ticket->ticket_type == 1) {
            return 'Diri Sendiri';
          } elseif ($ticket->ticket_type == 2) {
            return 'Bagi Pihak';
          } else {
            return '-';
          }
        })
        ->addColumn('kategori', function ($ticket) {
          return $ticket->category->cat_name ?? 'N/A';
        })
        ->addColumn('sub_kategori', function ($ticket) {
          return $ticket->subCategory->subcat_name ?? 'N/A';
        })
        ->addColumn('status', function ($ticket) {
          $statusName = match ($ticket->tick_status) {
            1 => 'Aduan Baharu',
            2 => 'Aduan Diproses',
            3 => 'Serahan Kepada',
            4 => 'Aduan Dirujuk',
            5 => 'Aduan Dibatal',
            6 => 'Aduan Selesai',
            default => 'Status Tidak Diketahui'
          };
          $statusClass = match ($ticket->tick_status) {
            1 => 'bg-primary',
            2 => 'bg-warning',
            3 => 'bg-info',
            4 => 'bg-danger',
            5 => 'bg-secondary',
            6 => 'bg-success',
            default => 'bg-black'
          };
          return "<span class=\"badge {$statusClass}\">{$statusName}</span>";
        })
        ->addColumn('pegawai_tugasan', function ($ticket) {
          // Get assigned officer from latest chronology for CURRENT ticket status
          $latestChronology = $ticket->chronologies()
            ->whereNotNull('escalate_to')
            ->where('tick_status', $ticket->tick_status)
            ->with('escalatedTo')
            ->latest()
            ->first();

          if ($latestChronology && $latestChronology->escalatedTo) {
            return $latestChronology->escalatedTo->nama;
          }

          return 'Belum Ditugaskan';
        })
        ->addColumn('action', function ($ticket) {
          $user = Auth::user();
          $actions = '<div class="d-flex gap-1">';

          // Escape UUID for HTML attributes
          $escapedUuid = htmlspecialchars($ticket->uuid, ENT_QUOTES, 'UTF-8');

          // View button - always available
          $actions .= '<a class="btn btn-sm view-record" data-uuid="' . $escapedUuid . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat" style="cursor: pointer;"><i class="bx bx-show text-success"></i></a>';

          // Edit button - commented out for "Tiket Saya" section
          // $canEdit = false;
          // if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
          //   $canEdit = true;
          // } elseif (($ticket->user_id == $user->id || $ticket->created_by == $user->id) && !in_array($ticket->tick_status, [5, 6])) {
          //   $canEdit = true;
          // }
          //
          // if ($canEdit) {
          //   $actions .= '<a class="btn btn-sm edit-record" data-uuid="' . $ticket->uuid . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Kemaskini"><i class="bx bx-edit-alt text-secondary"></i></a>';
          // }

          // Delete button - commented out for "Tiket Saya" section
          // $canDelete = false;
          // if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk'])) {
          //   $canDelete = true;
          // } elseif ($ticket->created_by == $user->id && $ticket->tick_status == 1) {
          //   $canDelete = true;
          // }
          //
          // if ($canDelete) {
          //   $actions .= '<a class="btn btn-sm delete-record" data-uuid="' . $ticket->uuid . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Padam"><i class="bx bx-trash text-danger"></i></a>';
          // }

          $actions .= '</div>';
          return $actions;
        })
        ->rawColumns(['status', 'action'])
        ->make(true);
    } else {
      // Manage Tickets - for administrators and helpdesk staff
      $user = Auth::user();

      $query = Ticket::with(['user', 'category', 'subCategory', 'status', 'createdBy', 'chronologies.escalatedTo']);

      // Apply role-based filtering
      if ($user->hasRole('Administrator')) {
        // Administrator sees all tickets
      } elseif ($user->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
        // These roles see all tickets except maybe some restricted ones
      } elseif ($user->hasRole('Pegawai Tugasan')) {
        // Only tickets assigned to this officer
        $query->whereHas('chronologies', function ($q) use ($user) {
          $q->where('escalate_to', $user->id);
        });
      }

      // Order by latest date first
      $query->orderBy('created_at', 'desc');

      return DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('uuid', function ($ticket) {
          return $ticket->uuid;
        })
        ->filterColumn('no_log_tiket', function ($query, $keyword) {
          $query->where('ticket_nolog', 'like', "%{$keyword}%");
        })
        ->filterColumn('tarikh_aduan', function ($query, $keyword) {
          $query->whereRaw("DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') like ?", ["%{$keyword}%"]);
        })
        ->filterColumn('nama_pengadu', function ($query, $keyword) {
          $query->whereHas('user', function ($q) use ($keyword) {
            $q->where('nama', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('kategori', function ($query, $keyword) {
          $query->whereHas('category', function ($q) use ($keyword) {
            $q->where('cat_name', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('sub_kategori', function ($query, $keyword) {
          $query->whereHas('subCategory', function ($q) use ($keyword) {
            $q->where('subcat_name', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('status', function ($query, $keyword) {
          $statusMap = [
            'Aduan Baharu' => 1,
            'Aduan Diproses' => 2,
            'Serahan Kepada' => 3,
            'Aduan Dirujuk' => 4,
            'Aduan Dibatal' => 5,
            'Aduan Selesai' => 6
          ];
          foreach ($statusMap as $statusName => $statusValue) {
            if (stripos($statusName, $keyword) !== false) {
              $query->orWhere('tick_status', $statusValue);
            }
          }
        })
        ->filterColumn('pegawai_tugasan', function ($query, $keyword) {
          $query->whereHas('chronologies.escalatedTo', function ($q) use ($keyword) {
            $q->where('nama', 'like', "%{$keyword}%");
          });
        })
        ->addColumn('no_log_tiket', function ($ticket) {
          return $ticket->ticket_nolog ?? 'N/A';
        })
        ->addColumn('tarikh_aduan', function ($ticket) {
          return $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-';
        })
        ->addColumn('nama_pengadu', function ($ticket) {
          return $ticket->user->nama ?? 'N/A';
        })
        ->addColumn('kategori', function ($ticket) {
          return $ticket->category->cat_name ?? 'N/A';
        })
        ->addColumn('sub_kategori', function ($ticket) {
          return $ticket->subCategory->subcat_name ?? 'N/A';
        })
        ->addColumn('status', function ($ticket) {
          $statusName = match ($ticket->tick_status) {
            1 => 'Aduan Baharu',
            2 => 'Aduan Diproses',
            3 => 'Serahan Kepada',
            4 => 'Aduan Dirujuk',
            5 => 'Aduan Dibatal',
            6 => 'Aduan Selesai',
            default => 'Status Tidak Diketahui'
          };
          $statusClass = match ($ticket->tick_status) {
            1 => 'bg-primary',
            2 => 'bg-warning',
            3 => 'bg-info',
            4 => 'bg-danger',
            5 => 'bg-secondary',
            6 => 'bg-success',
            default => 'bg-black'
          };
          return "<span class=\"badge {$statusClass}\">{$statusName}</span>";
        })
        ->addColumn('pegawai_tugasan', function ($ticket) {
          // Get assigned officer from latest chronology for CURRENT ticket status
          $latestChronology = $ticket->chronologies()
            ->whereNotNull('escalate_to')
            ->where('tick_status', $ticket->tick_status)
            ->with('escalatedTo')
            ->latest()
            ->first();

          if ($latestChronology && $latestChronology->escalatedTo) {
            return $latestChronology->escalatedTo->nama;
          }

          return 'Belum Ditugaskan';
        })
        ->addColumn('action', function ($ticket) {
          $user = Auth::user();
          $actions = '<div class="d-flex gap-1">';

          // Escape UUID for HTML attributes
          $escapedUuid = htmlspecialchars($ticket->uuid, ENT_QUOTES, 'UTF-8');

          // Manage button - for helpdesk staff
          $actions .= '<a class="btn btn-sm manage-ticket" data-uuid="' . $escapedUuid . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Urus"><i class="bx bx-cog text-primary"></i></a>';

          // Admin can edit any ticket
          $canEdit = false;
          if ($user->hasRole('Administrator')) {
            $canEdit = true;
          } elseif ($user->hasRole('Pegawai Tugasan')) {
            // Pegawai Tugasan can only edit if assigned to this ticket
            $isAssigned = $ticket->chronologies()
              ->where('escalate_to', $user->id)
              ->where('tick_status', $ticket->tick_status)
              ->exists();
            if ($isAssigned) {
              $canEdit = true;
            }
          }

          if ($canEdit) {
            $actions .= '<a class="btn btn-sm edit-record" data-uuid="' . $escapedUuid . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Kemaskini"><i class="bx bx-edit-alt text-secondary"></i></a>';
          }

          // Only Administrator and higher level roles can delete
          if ($user->hasAnyRole(['Administrator'])) {
            $actions .= '<a class="btn btn-sm delete-record" data-id="' . htmlspecialchars($ticket->uuid, ENT_QUOTES, 'UTF-8') . '" data-ticket-uuid="' . htmlspecialchars($ticket->uuid, ENT_QUOTES, 'UTF-8') . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Padam"><i class="bx bx-trash text-danger"></i></a>';
          }

          $actions .= '</div>';
          return $actions;
        })
        ->rawColumns(['status', 'action'])
        ->make(true);
    }
  }

  /**
   * Store a new ticket
   *
   * Logic untuk penyimpanan:
   * 1. Diri Sendiri (ticket_type = 1):
   *    - user_id = Auth::id() (ID pengguna yang login)
   *    - created_by = Auth::id() (ID pengguna yang buat tiket)
   *
   * 2. Bagi Pihak (ticket_type = 2):
   *    - user_id = ID pengguna yang dipilih (pengadu)
   *    - created_by = Auth::id() (ID pengguna yang log tiket untuk orang lain)
   */
  public function store(StoreTicketRequest $request)
  {
    $validated = $request->validated();

    // Determine user_id based on ticket_type
    if ($validated['ticket_type'] == 1) {
      // Diri Sendiri - use authenticated user
      $user_id = Auth::id();
    } else {
      // Bagi Pihak (ticket_type == 2) - find user by email from search
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

    try {
      // Use database transaction for data consistency
      $ticket = DB::transaction(function () use ($validated, $user_id, $defaultStatus) {
        // Generate unique ticket number with retry logic
        $ticketNumber = $this->generateTicketNumber();

        // Calculate due date: created_at + 3 working days (excluding weekends and Malaysian public holidays)
        $createdAt = Carbon::now();
        $tickDuedate = Helpers::addWorkingDays($createdAt, 3);

        $data = [
          'uuid' => Str::uuid(),
          'ticket_nolog' => $ticketNumber,
          'user_id' => $user_id,
          'cat_id' => $validated['cat_id'],
          'subcat_id' => $validated['subcat_id'],
          'serial_number' => $validated['no_siri'] ?? null,
          'ticket_title' => $validated['tick_title'],
          'ticket_desc' => $validated['tick_desc'],
          'ticket_type' => $validated['ticket_type'],
          'tick_status' => $defaultStatus ? $defaultStatus->id : 1,
          'tick_duedate' => $tickDuedate,
          'created_by' => Auth::id(),
        ];

        // Create ticket
        $ticket = Ticket::create($data);

        // Situasi 1: Create ticket - sla_severity = null, sla_days = 3 working days
        $slaDays = 3; // Default 3 working days
        $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);

        // Create initial chronology entry
        $chronologyData = [
          'ticket_id' => $ticket->id,
          'frm_user_id' => $user_id,
          'level_pegtugas_id' => 1,
          'tick_status' => $defaultStatus ? $defaultStatus->id : 1,
          'escalate_to' => $this->getAssignedOfficer($validated['cat_id'], $validated['subcat_id'], 1),
          'sla_duedate' => $slaDueDate,
          'sla_days' => $slaDays,
          'sla_severity' => null, // NULL for initial ticket creation
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
        ];

        TicketChronology::create($chronologyData);

        Log::info('Ticket and chronology created successfully', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticketNumber,
          'assigned_officer' => $chronologyData['escalate_to'],
          'sla_days' => $slaDays,
          'sla_severity' => null
        ]);

        return $ticket;
      });
    } catch (\Exception $e) {
      Log::error('Failed to create ticket', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mencipta tiket: ' . $e->getMessage()
      ], 500);
    }

    // Handle file attachments if any
    if ($request->hasFile('attachments')) {
      TicketAttachmentController::handleFileAttachments($request->file('attachments'), $ticket->id);
    }

    // Send email notifications using service
    $this->notificationService->sendNewTicketNotifications($ticket, Auth::user());

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

  /**
   * Show the form for editing the specified ticket
   * Only Administrator and Pegawai Tugasan can edit tickets
   */
  public function edit($uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    // Check authorization - ONLY Administrator or Pegawai Tugasan can edit
    $user = Auth::user();
    $canEdit = false;

    // Check if user can edit this ticket
    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      $canEdit = true;
    } elseif ($user->hasRole('Pegawai Tugasan')) {
      // Pegawai Tugasan can only edit if assigned to this ticket
      $isAssigned = $ticket->chronologies()
        ->where('escalate_to', $user->id)
        ->where('tick_status', $ticket->tick_status)
        ->exists();
      if ($isAssigned) {
        $canEdit = true;
      }
    }

    if (!$canEdit) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak dibenarkan untuk mengedit tiket ini. Hanya Administrator atau Pegawai Tugasan boleh mengedit tiket.'
      ], 403);
    }

    // Load ticket with relationships including additional user fields
    $ticket->load(['user.skim', 'category', 'subCategory', 'status', 'attachments']);

    // Enhance user data with additional fields
    if ($ticket->user) {
      $ticket->user->jawatan_name = $ticket->user->jawatan_name ?? '-';
      $ticket->user->kod_skim = $ticket->user->kod_skim ?? '-';
      $ticket->user->nama_skim = $ticket->user->skim?->nama_skim ?? null;
      $ticket->user->nama_gred = $ticket->user->nama_gred ?? '-';
    }

    // Get categories and subcategories
    $categories = RefCategory::all();
    $subCategories = RefSubCategory::where('cat_id', $ticket->cat_id)->get();

    // Get available officers for this ticket's category/subcategory (for authorized users)
    $availableOfficers = [];
    $currentOfficer = null;

    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      // Get officers assigned to this ticket's category and subcategory
      $officers = Officer::where('cat_id', $ticket->cat_id)
        ->where('subcat_id', $ticket->subcat_id)
        ->whereNotNull('user_id')
        ->with(['user' => function ($q) {
          $q->select('id', 'nama', 'email');
        }])
        ->get();

      foreach ($officers as $officer) {
        if ($officer->user) {
          $availableOfficers[] = [
            'user_id' => $officer->user_id,
            'nama' => $officer->user->nama,
            'email' => $officer->user->email,
            'level_tugasan' => $officer->level_tugasan
          ];
        }
      }

      // Get currently assigned officer from latest chronology
      $latestChronology = $ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->with('escalatedTo')
        ->latest()
        ->first();

      if ($latestChronology && $latestChronology->escalatedTo) {
        $currentOfficer = [
          'user_id' => $latestChronology->escalatedTo->id,
          'nama' => $latestChronology->escalatedTo->nama,
          'email' => $latestChronology->escalatedTo->email
        ];
      }
    }

    return response()->json([
      'success' => true,
      'ticket' => $ticket,
      'categories' => $categories,
      'subCategories' => $subCategories,
      'available_officers' => $availableOfficers,
      'current_officer' => $currentOfficer,
      'can_assign_officer' => $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])
    ]);
  }

  /**
   * Update the specified ticket
   * Only Administrator and Pegawai Tugasan can update tickets
   * Only these fields can be edited: tajuk, kategori, sub kategori, keterangan aduan, lampiran
   */
  public function update(UpdateTicketRequest $request, $uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    // Check authorization - ONLY Administrator or Pegawai Tugasan can update
    $user = Auth::user();
    $canEdit = false;

    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      $canEdit = true;
    } elseif ($user->hasRole('Pegawai Tugasan')) {
      // Pegawai Tugasan can only edit if assigned to this ticket
      $isAssigned = $ticket->chronologies()
        ->where('escalate_to', $user->id)
        ->where('tick_status', $ticket->tick_status)
        ->exists();
      if ($isAssigned) {
        $canEdit = true;
      }
    }

    if (!$canEdit) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak dibenarkan untuk mengemaskini tiket ini. Hanya Administrator atau Pegawai Tugasan boleh mengedit tiket.'
      ], 403);
    }

    $validated = $request->validated();

    // Update ONLY allowed fields: tajuk, kategori, sub kategori, keterangan, no_siri
    // DO NOT update: user_id, ticket_type, created_by, ticket_nolog, uuid, tick_status, etc.
    $updateData = [
      'ticket_title' => $validated['tick_title'],
      'cat_id' => $validated['cat_id'],
      'subcat_id' => $validated['subcat_id'],
      'ticket_desc' => $validated['tick_desc'],
      'serial_number' => $validated['no_siri'] ?? null,
      'updated_by' => Auth::id(),
    ];

    // Check if category or subcategory changed (requires SLA recalculation)
    $categoryChanged = $ticket->cat_id != $validated['cat_id'] || $ticket->subcat_id != $validated['subcat_id'];

    // Handle manual officer assignment (for authorized users)
    $manualOfficerAssignment = null;
    if ($request->has('escalate_to') && $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      $escalateToUserId = $request->input('escalate_to');

      if ($escalateToUserId) {
        // Validate that the assigned officer is valid for the ticket's category/subcategory
        $isValidAssignment = $this->validateOfficerAssignment(
          $validated['cat_id'],
          $validated['subcat_id'],
          $escalateToUserId
        );

        if (!$isValidAssignment) {
          return response()->json([
            'success' => false,
            'message' => 'Pegawai yang dipilih tidak sah untuk kategori/sub-kategori tiket ini.',
            'errors' => ['escalate_to' => ['Pegawai tidak ditugaskan untuk kategori/sub-kategori ini.']]
          ], 422);
        }

        $manualOfficerAssignment = $escalateToUserId;
        Log::info('Manual officer assignment in ticket update', [
          'ticket_id' => $ticket->id,
          'cat_id' => $validated['cat_id'],
          'subcat_id' => $validated['subcat_id'],
          'assigned_user_id' => $escalateToUserId,
          'assigned_by' => Auth::id()
        ]);
      }
    }

    $ticket->update($updateData);

    // Create chronology entry for category change OR manual officer assignment
    if ($categoryChanged || $manualOfficerAssignment) {
      try {
        $slaInfo = $this->calculateSLA($validated['cat_id'], $validated['subcat_id']);

        // Determine assigned officer
        $assignedOfficer = null;
        $assignmentType = 'unchanged';

        if ($manualOfficerAssignment) {
          // Use manually assigned officer
          $assignedOfficer = $manualOfficerAssignment;
          $assignmentType = 'manual';
        } elseif ($categoryChanged) {
          // Auto-assign officer based on new category and subcategory
          $assignedOfficer = $this->getAssignedOfficer($validated['cat_id'], $validated['subcat_id'], 1);
          $assignmentType = 'auto';
        }

        // Only create chronology if we have an officer to assign
        if ($assignedOfficer) {
          TicketChronology::create([
            'ticket_id' => $ticket->id,
            'frm_user_id' => Auth::id(),
            'level_pegtugas_id' => 1,
            'tick_status' => $ticket->tick_status,
            'escalate_to' => $assignedOfficer,
            'sla_duedate' => $slaInfo['sla_duedate'],
            'sla_days' => $slaInfo['sla_days'],
            'sla_severity' => $slaInfo['sla_severity'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
          ]);

          Log::info('Ticket chronology updated with officer assignment', [
            'ticket_id' => $ticket->id,
            'old_cat_id' => $categoryChanged ? $ticket->getOriginal('cat_id') : $validated['cat_id'],
            'new_cat_id' => $validated['cat_id'],
            'old_subcat_id' => $categoryChanged ? $ticket->getOriginal('subcat_id') : $validated['subcat_id'],
            'new_subcat_id' => $validated['subcat_id'],
            'assigned_officer' => $assignedOfficer,
            'assignment_type' => $assignmentType,
            'category_changed' => $categoryChanged
          ]);
        }
      } catch (\Exception $e) {
        Log::error('Failed to create chronology for ticket update', [
          'ticket_id' => $ticket->id,
          'category_changed' => $categoryChanged,
          'manual_assignment' => $manualOfficerAssignment,
          'error' => $e->getMessage()
        ]);
      }
    }    // Handle file attachments if any
    if ($request->hasFile('attachments')) {
      TicketAttachmentController::handleFileAttachments($request->file('attachments'), $ticket->id);
    }

    return response()->json([
      'success' => true,
      'message' => 'Tiket berjaya dikemaskini!',
      'ticket' => [
        'id' => $ticket->id,
        'uuid' => $ticket->uuid,
        'ticket_nolog' => $ticket->ticket_nolog,
        'ticket_title' => $ticket->ticket_title,
        'ticket_type' => $ticket->ticket_type,
        'updated_at' => $ticket->updated_at->format('d/m/Y H:i'),
      ]
    ]);
  }

  /**
   * Remove the specified ticket
   */
  public function destroy($uuid)
  {
    // Log incoming delete request
    Log::info('Ticket delete request received', [
      'uuid' => $uuid,
      'user_id' => Auth::id()
    ]);

    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    Log::info('Ticket found for deletion', [
      'ticket_id' => $ticket->id,
      'ticket_nolog' => $ticket->ticket_nolog,
      'uuid' => $ticket->uuid
    ]);

    // Check authorization - only allow deletion if:
    // 1. User has admin/helpdesk roles OR
    // 2. User is the ticket creator and ticket is not processed yet

    $user = Auth::user();
    $canDelete = false;

    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk'])) {
      $canDelete = true;
    } elseif ($ticket->created_by == $user->id) {
      // Only allow deletion if ticket is still new/open (status 1)
      if ($ticket->tick_status == 1) {
        $canDelete = true;
      }
    }

    if (!$canDelete) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak dibenarkan untuk memadam tiket ini.'
      ], 403);
    }

    try {
      // Store ticket number for response
      $ticketNumber = $ticket->ticket_nolog;

      // Delete associated attachments first
      $attachments = Document::where('doc_id', $ticket->id)
        ->where('doc_jenis', 'TICKET')
        ->get();

      foreach ($attachments as $attachment) {
        // Delete file from storage if exists
        if ($attachment->doc_storage_nm && Storage::exists('public/ticket_attachments/' . $attachment->doc_storage_nm)) {
          Storage::delete('public/ticket_attachments/' . $attachment->doc_storage_nm);
        }
        // Force delete document record completely
        $attachment->forceDelete();
      }

      // Delete associated chronologies
      if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem'])) {
        // Force delete chronologies completely
        TicketChronology::where('ticket_id', $ticket->id)->forceDelete();
        // Force delete comments completely
        TicketComment::where('ticket_id', $ticket->id)->forceDelete();
      } else {
        // Regular soft delete for other users
        TicketChronology::where('ticket_id', $ticket->id)->delete();
        TicketComment::where('ticket_id', $ticket->id)->delete();
      }

      // Delete the ticket
      if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem'])) {
        // Force delete to completely remove from database
        $ticket->forceDelete();
        Log::info('Ticket force deleted by administrator', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticketNumber,
          'deleted_by' => $user->id,
          'attachments_deleted' => $attachments->count()
        ]);
      } else {
        // Regular soft delete for other users
        $ticket->delete();
        Log::info('Ticket soft deleted', [
          'ticket_id' => $ticket->id,
          'ticket_nolog' => $ticketNumber,
          'deleted_by' => $user->id,
          'attachments_deleted' => $attachments->count()
        ]);
      }

      return response()->json([
        'success' => true,
        'message' => "Tiket {$ticketNumber} berjaya dipadam."
      ]);
    } catch (\Exception $e) {
      Log::error('Error deleting ticket', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa memadam tiket: ' . $e->getMessage()
      ], 500);
    }
  }

  public function show($uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

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
    $allCategories = RefCategory::all();
    $allSubCategories = RefSubCategory::all();

    // Get SLA data for the current ticket
    $currentSLA = null;
    if ($ticket->cat_id && $ticket->subcat_id) {
      $currentSLA = Sla::where('cat_id', $ticket->cat_id)
        ->where('subcat_id', $ticket->subcat_id)
        ->with(['category', 'subCategory'])
        ->get();
    }

    // Get officers with helpdesk roles
    $officers = User::whereHas('roles', function ($query) {
      $query->whereIn('name', ['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan']);
    })->select('id', 'nama', 'email')->get();

    Log::info('Showing ticket with comments', [
      'ticket_id' => $ticket->id,
      'comments_count' => $ticket->comments->count()
    ]);

    return view('tickets.show', compact('ticket', 'ticketHistory', 'allStatuses', 'allCategories', 'allSubCategories', 'officers', 'currentSLA'));
  }

  /**
   * Calculate SLA information based on category and subcategory
   */
  private function calculateSLA($catId, $subcatId)
  {
    try {
      // Get SLA configuration for this category and subcategory
      $slaConfig = Sla::where('cat_id', $catId)
        ->where('subcat_id', $subcatId)
        ->first();

      if ($slaConfig) {
        // Calculate due date based on SLA days
        $slaDueDate = Carbon::now()->addDays($slaConfig->sla_days);

        // Skip weekends (optional - adjust based on business rules)
        while ($slaDueDate->isWeekend()) {
          $slaDueDate->addDay();
        }

        return [
          'sla_duedate' => $slaDueDate,
          'sla_days' => $slaConfig->sla_days,
          'sla_severity' => $slaConfig->sla_severity,
        ];
      } else {
        // Default SLA if no specific configuration found
        $defaultDays = 5; // 5 working days default
        $slaDueDate = Carbon::now()->addDays($defaultDays);

        while ($slaDueDate->isWeekend()) {
          $slaDueDate->addDay();
        }

        return [
          'sla_duedate' => $slaDueDate,
          'sla_days' => $defaultDays,
          'sla_severity' => 'Medium', // Default severity
        ];
      }
    } catch (\Exception $e) {
      Log::error('Failed to calculate SLA', [
        'cat_id' => $catId,
        'subcat_id' => $subcatId,
        'error' => $e->getMessage()
      ]);

      // Return default values if calculation fails
      return [
        'sla_duedate' => Carbon::now()->addDays(5),
        'sla_days' => 5,
        'sla_severity' => 'Medium',
      ];
    }
  }

  /**
   * Assign or reassign officer to a ticket
   * This method handles both manual and automatic officer assignment
   */
  public function assignOfficer(Request $request, Ticket $ticket)
  {
    $request->validate([
      'escalate_to' => 'nullable|exists:users,id',
      'level_pegtugas_id' => 'nullable|integer|min:1|max:5',
      'notes' => 'nullable|string|max:1000'
    ]);

    // Check authorization
    $user = Auth::user();
    if (!$user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak dibenarkan untuk menugaskan pegawai.'
      ], 403);
    }

    try {
      $escalateTo = $request->escalate_to;
      $levelTugasan = $request->level_pegtugas_id ?? 1;

      // If escalate_to is provided, validate it
      if ($escalateTo) {
        if (!$this->validateOfficerAssignment($ticket->cat_id, $ticket->subcat_id, $escalateTo, $levelTugasan)) {
          return response()->json([
            'success' => false,
            'message' => 'Pegawai yang dipilih tidak sah untuk kategori/sub-kategori tiket ini.'
          ], 422);
        }
      } else {
        // Auto-assign officer if not provided
        $escalateTo = $this->getAssignedOfficer($ticket->cat_id, $ticket->subcat_id, $levelTugasan);

        if (!$escalateTo) {
          return response()->json([
            'success' => false,
            'message' => 'Tiada pegawai yang tersedia untuk kategori/sub-kategori ini.'
          ], 404);
        }
      }

      // Calculate SLA
      $slaInfo = $this->calculateSLA($ticket->cat_id, $ticket->subcat_id);

      // Create chronology entry
      $chronologyData = [
        'ticket_id' => $ticket->id,
        'frm_user_id' => $user->id,
        'level_pegtugas_id' => $levelTugasan,
        'tick_status' => $ticket->tick_status,
        'escalate_to' => $escalateTo,
        'sla_duedate' => $slaInfo['sla_duedate'],
        'sla_days' => $slaInfo['sla_days'],
        'sla_severity' => $slaInfo['sla_severity'],
        'created_by' => $user->id,
        'updated_by' => $user->id,
      ];

      $chronology = TicketChronology::create($chronologyData);

      // Add notes as comment if provided
      if ($request->notes) {
        TicketComment::create([
          'ticket_id' => $ticket->id,
          'user_id' => $user->id,
          'comment_text' => $request->notes,
          'created_by' => $user->id,
        ]);
      }

      // Get assigned officer details
      $assignedOfficer = User::find($escalateTo);

      Log::info('Officer assigned to ticket', [
        'ticket_id' => $ticket->id,
        'ticket_nolog' => $ticket->ticket_nolog,
        'cat_id' => $ticket->cat_id,
        'subcat_id' => $ticket->subcat_id,
        'assigned_to' => $assignedOfficer->nama,
        'assigned_user_id' => $escalateTo,
        'level_tugasan' => $levelTugasan,
        'assigned_by' => $user->id,
        'assignment_type' => $request->escalate_to ? 'manual' : 'auto'
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Pegawai berjaya ditugaskan!',
        'assigned_officer' => [
          'id' => $assignedOfficer->id,
          'nama' => $assignedOfficer->nama,
          'email' => $assignedOfficer->email
        ],
        'chronology' => $chronology
      ]);
    } catch (\Exception $e) {
      Log::error('Failed to assign officer to ticket', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa menugaskan pegawai: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Validate that escalate_to user is a valid officer for the ticket's category/subcategory
   */
  private function validateOfficerAssignment($ticketCatId, $ticketSubcatId, $escalateToUserId, $levelTugasan = null)
  {
    if (!$escalateToUserId) {
      return true; // Allow null assignments
    }

    $query = Officer::where('cat_id', $ticketCatId)
      ->where('subcat_id', $ticketSubcatId)
      ->where('user_id', $escalateToUserId);

    if ($levelTugasan) {
      $query->where('level_tugasan', $levelTugasan);
    }

    $isValid = $query->exists();

    if (!$isValid) {
      Log::warning('Invalid officer assignment attempted', [
        'ticket_cat_id' => $ticketCatId,
        'ticket_subcat_id' => $ticketSubcatId,
        'escalate_to_user_id' => $escalateToUserId,
        'level_tugasan' => $levelTugasan
      ]);
    }

    return $isValid;
  }

  /**
   * Get assigned officer based on category, subcategory, and level
   * Uses round-robin or load balancing algorithm to distribute tickets fairly
   * IMPORTANT: escalate_to MUST refer to user_id from officer table where
   * cat_id and subcat_id match between ticket and officer records
   */
  private function getAssignedOfficer($catId, $subcatId, $levelTugasan = 1)
  {
    try {
      // Validate input parameters
      if (!$catId || !$subcatId) {
        Log::warning('Invalid category or subcategory for officer assignment', [
          'cat_id' => $catId,
          'subcat_id' => $subcatId,
          'level_tugasan' => $levelTugasan
        ]);
        return null;
      }

      // Get officers assigned to this EXACT category and subcategory at the specified level
      // This ensures escalate_to refers to user_id from officer table with matching cat_id/subcat_id
      $officers = Officer::where('cat_id', $catId)
        ->where('subcat_id', $subcatId)
        ->where('level_tugasan', $levelTugasan)
        ->whereNotNull('user_id') // Ensure officer has valid user_id
        ->with(['user' => function ($query) {
          $query->whereNotNull('id'); // Ensure user exists and is not soft deleted
        }])
        ->get();

      // Filter out officers whose users don't exist or are inactive
      $validOfficers = $officers->filter(function ($officer) {
        return $officer->user && $officer->user->id;
      });

      if ($validOfficers->isEmpty()) {
        Log::warning('No valid officers found for assignment', [
          'cat_id' => $catId,
          'subcat_id' => $subcatId,
          'level_tugasan' => $levelTugasan,
          'total_officers_found' => $officers->count(),
          'valid_officers_found' => $validOfficers->count()
        ]);
        return null;
      }

      // Simple round-robin assignment based on ticket count
      // Get current ticket counts for each officer to balance load
      $officerLoads = [];
      foreach ($validOfficers as $officer) {
        // Count active tickets assigned to this officer
        // This ensures we're counting tickets where escalate_to matches officer's user_id
        $ticketCount = TicketChronology::where('escalate_to', $officer->user_id)
          ->whereHas('ticket', function ($q) {
            $q->whereNotIn('tick_status', [3, 4, 6]); // Exclude completed statuses
          })
          ->count();

        $officerLoads[] = [
          'user_id' => $officer->user_id,
          'user_name' => $officer->user->nama,
          'ticket_count' => $ticketCount,
          'cat_id' => $officer->cat_id,
          'subcat_id' => $officer->subcat_id,
          'level_tugasan' => $officer->level_tugasan
        ];
      }

      if (empty($officerLoads)) {
        Log::warning('No valid officers with user accounts found', [
          'cat_id' => $catId,
          'subcat_id' => $subcatId
        ]);
        return null;
      }

      // Sort by ticket count (ascending) to assign to officer with least load
      usort($officerLoads, function ($a, $b) {
        return $a['ticket_count'] - $b['ticket_count'];
      });

      $selectedOfficer = $officerLoads[0]; // Officer with least tickets

      Log::info('Officer auto-assigned with category/subcategory validation', [
        'ticket_cat_id' => $catId,
        'ticket_subcat_id' => $subcatId,
        'officer_cat_id' => $selectedOfficer['cat_id'],
        'officer_subcat_id' => $selectedOfficer['subcat_id'],
        'level_tugasan' => $levelTugasan,
        'assigned_to' => $selectedOfficer['user_name'],
        'assigned_user_id' => $selectedOfficer['user_id'],
        'current_load' => $selectedOfficer['ticket_count'],
        'escalate_to_value' => $selectedOfficer['user_id'] // This is what goes into escalate_to field
      ]);

      // Return the user_id from officer table - this becomes the escalate_to value
      return $selectedOfficer['user_id'];
    } catch (\Exception $e) {
      Log::error('Failed to get assigned officer', [
        'cat_id' => $catId,
        'subcat_id' => $subcatId,
        'level_tugasan' => $levelTugasan,
        'error' => $e->getMessage()
      ]);

      return null;
    }
  }

  /**
   * Get next level officer for escalation
   */
  private function getNextLevelOfficer($catId, $subcatId, $currentLevel)
  {
    $nextLevel = $currentLevel + 1;

    // Maximum level is typically 3 or 5, adjust based on your business rules
    if ($nextLevel > 5) {
      return null;
    }

    return $this->getAssignedOfficer($catId, $subcatId, $nextLevel);
  }

  /**
   * Generate sequential ticket number in format HELP-YYYY-MM-XXXX
   * Includes soft-deleted tickets to prevent duplicates
   */
  private function generateTicketNumber()
  {
    $currentDate = now();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    // Find the highest ticket number for the current month (including soft-deleted ones)
    $prefix = "HELP-{$year}-{$month}-";
    $lastTicket = Ticket::withTrashed() // Include soft-deleted tickets
      ->where('ticket_nolog', 'LIKE', $prefix . '%')
      ->orderBy('ticket_nolog', 'desc')
      ->first();

    $nextNumber = 1;

    if ($lastTicket) {
      // Extract the last 4 digits and increment
      $lastNumber = (int) substr($lastTicket->ticket_nolog, -4);
      $nextNumber = $lastNumber + 1;
    }

    // Format as 4-digit number with leading zeros
    $ticketNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    // Double check to make sure this number doesn't exist (safety check)
    $attempts = 0;
    while (Ticket::withTrashed()->where('ticket_nolog', $ticketNumber)->exists() && $attempts < 10) {
      $nextNumber++;
      $ticketNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
      $attempts++;
    }

    if ($attempts >= 10) {
      Log::error('Failed to generate unique ticket number after 10 attempts', [
        'prefix' => $prefix,
        'last_attempt' => $ticketNumber
      ]);
      // Fallback to timestamp-based number if we can't generate unique number
      $ticketNumber = $prefix . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }

    return $ticketNumber;
  }


  /**
   * Update ticket status and create chronology entry
   */
  public function updateTicketStatus(Request $request, $uuid)
  {
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    $request->validate([
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'escalate_to' => 'nullable|exists:users,id',
      'sla_duedate' => 'nullable|date',
      'level_pegtugas_id' => 'nullable|integer|min:1|max:5',
      'notes' => 'nullable|string|max:1000'
    ]);

    try {
      // Get current user
      $currentUser = Auth::user();

      // Determine SLA due date
      $slaDueDate = null;
      if ($request->sla_duedate) {
        $slaDueDate = Carbon::parse($request->sla_duedate);
      } else {
        // Calculate new SLA based on current status change
        $slaInfo = $this->calculateSLA($ticket->cat_id, $ticket->subcat_id);
        $slaDueDate = $slaInfo['sla_duedate'];
      }

      // Determine escalation officer if not provided
      $escalateTo = $request->escalate_to;

      // If escalate_to is provided, validate it's a valid officer for this ticket's category/subcategory
      if ($escalateTo) {
        $levelToValidate = $request->level_pegtugas_id ?? 1;
        if (!$this->validateOfficerAssignment($ticket->cat_id, $ticket->subcat_id, $escalateTo, $levelToValidate)) {
          return response()->json([
            'success' => false,
            'message' => 'Pegawai yang dipilih tidak sah untuk kategori/sub-kategori tiket ini.'
          ], 422);
        }
      }

      // Auto-assign officer based on category and subcategory if not provided
      if (!$escalateTo) {
        $levelToAssign = $request->level_pegtugas_id ?? 1;
        $escalateTo = $this->getAssignedOfficer(
          $ticket->cat_id,
          $ticket->subcat_id,
          $levelToAssign
        );

        // If no officers found at the requested level, try next level
        if (!$escalateTo && $levelToAssign < 5) {
          $escalateTo = $this->getNextLevelOfficer(
            $ticket->cat_id,
            $ticket->subcat_id,
            $levelToAssign
          );
        }
      }

      // Create chronology entry directly
      $chronologyData = [
        'ticket_id' => $ticket->id,
        'frm_user_id' => $currentUser->id,
        'level_pegtugas_id' => $request->level_pegtugas_id ?? 1,
        'tick_status' => $request->tick_status,
        'escalate_to' => $escalateTo,
        'sla_duedate' => $slaDueDate,
        'sla_days' => $slaDueDate ? Carbon::now()->diffInDays($slaDueDate) : null,
        'sla_severity' => $this->getSeverityFromStatus($request->tick_status),
        'created_by' => $currentUser->id,
        'updated_by' => $currentUser->id,
      ];

      $chronology = TicketChronology::create($chronologyData);

      // Store old status before update
      $oldStatus = $ticket->tick_status;

      // Update ticket status
      $ticket->update([
        'tick_status' => $request->tick_status,
        'updated_by' => $currentUser->id
      ]);

      // Send status change notification to ticket owner/complainant
      try {
        $this->notificationService->sendStatusChangeNotification(
          $ticket,
          $oldStatus,
          $request->tick_status,
          $currentUser,
          $request->notes
        );
      } catch (\Exception $e) {
        Log::error('Failed to send status change notification', [
          'ticket_id' => $ticket->id,
          'error' => $e->getMessage()
        ]);
        // Don't fail the status update if notification fails
      }

      // Add notes as comment if provided
      if ($request->notes) {
        TicketComment::create([
          'ticket_id' => $ticket->id,
          'user_id' => $currentUser->id,
          'comment_text' => $request->notes,
          'created_by' => $currentUser->id,
        ]);
      }

      // Reload ticket with latest data
      $ticket->refresh();
      $ticket->load(['status', 'chronologies.status', 'chronologies.createdBy', 'chronologies.fromUser', 'chronologies.escalatedTo']);

      Log::info('Ticket status updated', [
        'ticket_id' => $ticket->id,
        'new_status' => $request->tick_status,
        'escalate_to' => $escalateTo,
        'auto_assigned' => !$request->escalate_to && $escalateTo ? true : false,
        'updated_by' => $currentUser->id
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Status tiket berjaya dikemaskini!',
        'ticket' => $ticket,
        'chronology' => $chronology
      ]);
    } catch (\Exception $e) {
      Log::error('Failed to update ticket status', [
        'ticket_id' => $ticket->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mengemaskini status tiket: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get available officers for a category and subcategory (API endpoint)
   * Returns officers whose cat_id and subcat_id match the provided values
   * This ensures escalate_to will refer to valid officer user_ids
   */
  public function getAvailableOfficers(Request $request)
  {
    $request->validate([
      'cat_id' => 'required|exists:ref_categories,id',
      'subcat_id' => 'required|exists:ref_sub_categories,id',
      'level_tugasan' => 'nullable|integer|min:1|max:5'
    ]);

    $levelTugasan = $request->level_tugasan ?? 1;

    try {
      // Get officers assigned to this EXACT category and subcategory
      // This ensures the returned user_ids can be safely used as escalate_to values
      $officers = Officer::where('cat_id', $request->cat_id)
        ->where('subcat_id', $request->subcat_id)
        ->where('level_tugasan', $levelTugasan)
        ->whereNotNull('user_id') // Ensure officer has valid user_id
        ->with(['user' => function ($q) {
          $q->select('id', 'nama', 'email')->whereNotNull('id');
        }])
        ->get();

      $officersData = [];
      foreach ($officers as $officer) {
        if ($officer->user) {
          // Get current workload for each officer
          $currentLoad = TicketChronology::where('escalate_to', $officer->user_id)
            ->whereHas('ticket', function ($q) {
              $q->whereNotIn('tick_status', [3, 4, 6]); // Exclude completed statuses
            })
            ->count();

          $officersData[] = [
            'user_id' => $officer->user_id,
            'nama' => $officer->user->nama,
            'email' => $officer->user->email,
            'level_tugasan' => $officer->level_tugasan,
            'current_load' => $currentLoad,
            'cat_id' => $officer->cat_id, // Include for validation
            'subcat_id' => $officer->subcat_id // Include for validation
          ];
        }
      }

      // Sort by current load (ascending)
      usort($officersData, function ($a, $b) {
        return $a['current_load'] - $b['current_load'];
      });

      Log::info('Available officers retrieved for category/subcategory', [
        'cat_id' => $request->cat_id,
        'subcat_id' => $request->subcat_id,
        'level_tugasan' => $levelTugasan,
        'officers_found' => count($officersData)
      ]);

      return response()->json([
        'success' => true,
        'officers' => $officersData,
        'total_officers' => count($officersData),
        'recommended_officer' => !empty($officersData) ? $officersData[0] : null,
        'validation_info' => [
          'cat_id' => $request->cat_id,
          'subcat_id' => $request->subcat_id,
          'level_tugasan' => $levelTugasan,
          'message' => 'All returned user_ids are valid for escalate_to field'
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa mendapatkan senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get severity level based on ticket status
   */
  private function getSeverityFromStatus($statusId)
  {
    // Map status to severity (adjust based on your business rules)
    $severityMap = [
      1 => 'Low',    // New/Open
      2 => 'Medium', // In Progress
      3 => 'High',   // Escalated
      4 => 'Critical', // Urgent
      5 => 'Medium', // Pending
      6 => 'Low',    // Resolved
    ];

    return $severityMap[$statusId] ?? 'Medium';
  }
}
