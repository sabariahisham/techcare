<?php

/*
 * Function TicketSearchController.php:
 *    - Search tickets based on various criteria.
 *    - Support filtering by status, category, date range, and keywords.
 *    - Provide results for Pengadu and Pegawai Tugasan.
 *    - Search users by name or email for "Bagi Pihak" ticket creation.
 *    - Handle ticket data retrieval and management functions.
 */

namespace App\Http\Controllers\Ticket;

use App\Models\User;
use App\Models\Ticket;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Ticket\TicketAssignmentController;

class TicketSearchController extends Controller
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
        $query->where('ticket_type', 2)
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

    // Search for users by name, excluding the current user
    $users = User::select('id', 'nama', 'email', 'kod_sektor', 'kod_bah')
      ->where('nama', 'like', '%' . $query . '%')
      ->where('id', '!=', Auth::id()) // Exclude current user
      ->whereNotNull('nama') // Ensure nama is not null
      ->where('nama', '!=', '') // Ensure nama is not empty
      ->orderBy('nama', 'asc') // Order by name alphabetically
      ->limit(10) // Limit results to prevent too many options
      ->get()
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
}
