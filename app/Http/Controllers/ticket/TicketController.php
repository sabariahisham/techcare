<?php

namespace App\Http\Controllers\Ticket;

use App\Models\User;
use App\Models\Ticket;
use App\Models\RefCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RefTicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;

class TicketController extends Controller
{
  public function getData(Request $request)
  {
    $tickets = Ticket::with(['user', 'category', 'subCategory', 'status'])->latest();

    return DataTables::of($tickets)
      ->addIndexColumn()
      ->addColumn('no_log_tiket', function ($ticket) {
        return e($ticket->ticket_nolog);
      })
      ->addColumn('tarikh_aduan', function ($ticket) {
        return $ticket->created_at ? $ticket->created_at->format('d-m-Y') : '-';
      })
      ->addColumn('nama_pengadu', function ($ticket) {
        // Now we always show the user's name since user_id points to the correct user
        return $ticket->user ? e($ticket->user->nama) : '-';
      })
      ->addColumn('kategori', function ($ticket) {
        return $ticket->category ? e($ticket->category->cat_name) : '-';
      })
      ->addColumn('sub_kategori', function ($ticket) {
        return $ticket->subCategory ? e($ticket->subCategory->subcat_name) : '-';
      })
      ->addColumn('status', function ($ticket) {
        return $ticket->status ? e($ticket->status->st_name) : '-';
      })
      ->addColumn('pegawai_tugasan', function ($ticket) {
        // Assuming there's a relationship or field for assigned officer
        // Replace 'assignedOfficer' with the actual relationship or field name
        return $ticket->assignedOfficer ? e($ticket->assignedOfficer->nama) : '-';
      })
      ->addColumn('action', function ($ticket) {
        $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Edit"><i class="bx bx-edit"></i></button>';
        $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Delete"><i class="bx bx-trash"></i></button>';
        $viewBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm view-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="View"><i class="bx bx-show"></i></button>';
        return '<div class="d-inline-block text-nowrap">' . $editBtn . ' ' . $deleteBtn . ' ' . $viewBtn . '</div>';
      })
      ->rawColumns(['action'])
      ->make(true);
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
        $otherUser = \App\Models\User::where('email', $other_email)->first();
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
    // TODO: Implement file handling logic later

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
      'updatedBy'
    ]);

    // Get ticket history/audit trail if available
    $ticketHistory = $this->getTicketHistory($ticket);

    // Get other relevant data
    $allStatuses = RefTicketStatus::all();

    return view('tickets.show', compact('ticket', 'ticketHistory', 'allStatuses'));
  }

  public function edit(Ticket $ticket)
  {
    // Load the ticket with its relationships including user's organizational data
    $ticket->load(['category', 'subCategory', 'user.sektor', 'user.bahagian', 'user.seksyen', 'user.unit', 'user.jawatan', 'user.skim']);

    $categories = RefCategory::all();

    return response()->json([
      'ticket' => $ticket,
      'categories' => $categories
    ]);
  }

  public function update(UpdateTicketRequest $request, Ticket $ticket)
  {
    $validated = $request->validated();

    // Determine user_id based on ticket_type
    $user_id = $ticket->user_id; // Keep existing user_id by default

    if ($validated['ticket_type'] == 1) {
      // Diri Sendiri - use authenticated user
      $user_id = Auth::id();
    } else {
      // Bagi Pihak - find user by email from search
      $other_email = $validated['other_email'] ?? null;
      if ($other_email) {
        $otherUser = \App\Models\User::where('email', $other_email)->first();
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

    $data = [
      'user_id' => $user_id,
      'cat_id' => $validated['cat_id'],
      'subcat_id' => $validated['subcat_id'],
      'ticket_title' => $validated['tick_title'], // Map tick_title to ticket_title
      'ticket_desc' => $validated['tick_desc'], // Map tick_desc to ticket_desc
      'ticket_type' => $validated['ticket_type'],
      'updated_by' => Auth::id(),
    ];

    $ticket->update($data);

    return response()->json([
      'success' => true,
      'message' => 'Tiket berjaya dikemaskini!',
      'ticket' => $ticket
    ]);
  }

  public function destroy(Ticket $ticket)
  {
    $ticket->delete();

    return response()->json([
      'success' => true,
      'message' => 'Tiket berjaya dipadam!'
    ]);
  }

  public function searchUserByEmail(Request $request)
  {
    $request->validate([
      'email' => 'required|email'
    ]);

    $email = $request->input('email');

    // Search for user by email with relationships
    $user = User::with(['sektor', 'bahagian', 'seksyen', 'unit', 'jawatan', 'skim'])
      ->where('email', $email)
      ->first();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Pengguna tidak ditemui dengan alamat emel tersebut.'
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
   * Get ticket history/kronologi for display
   */
  private function getTicketHistory($ticket)
  {
    // For now, we'll create basic history from available data
    // Later this can be enhanced with proper audit trail
    $history = collect([
      [
        'action' => 'Tiket Dicipta',
        'description' => 'Tiket aduan telah dicipta dalam sistem',
        'user' => $ticket->createdBy ? $ticket->createdBy->nama : 'Sistem',
        'timestamp' => $ticket->created_at,
        'details' => [
          'Kategori' => $ticket->category ? $ticket->category->cat_name : '-',
          'Sub Kategori' => $ticket->subCategory ? $ticket->subCategory->subcat_name : '-',
          'Jenis Tiket' => $ticket->ticket_type == 1 ? 'Diri Sendiri' : 'Bagi Pihak'
        ]
      ]
    ]);

    // Add update history if ticket was updated
    if ($ticket->updated_at && $ticket->updated_at != $ticket->created_at) {
      $history->push([
        'action' => 'Tiket Dikemaskini',
        'description' => 'Maklumat tiket telah dikemaskini',
        'user' => $ticket->updatedBy ? $ticket->updatedBy->nama : 'Sistem',
        'timestamp' => $ticket->updated_at,
        'details' => []
      ]);
    }

    return $history->sortBy('timestamp');
  }
}
