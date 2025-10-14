<?php

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Models\RefCategory;
use App\Models\RefTicketStatus;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
  public function getData(Request $request)
  {
    $tickets = Ticket::with(['user', 'category', 'subCategory', 'status'])->latest();

    return DataTables::of($tickets)
      ->addIndexColumn()
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
        return $ticket->status ? e($ticket->status->st_name) : '-';
      })
      ->addColumn('action', function ($ticket) {
        $editBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm edit-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Edit"><i class="bx bx-edit"></i></button>';
        $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Delete"><i class="bx bx-trash"></i></button>';
        return '<div class="d-inline-block text-nowrap">' . $editBtn . ' ' . $deleteBtn . '</div>';
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
      // Bagi Pihak - create or find user based on email
      // For now, we'll use the authenticated user
      // You may want to implement user creation logic here
      $user_id = Auth::id();
    }

    // Get default status (assuming first status is 'Open' or 'New')
    $defaultStatus = RefTicketStatus::first();

    $data = [
      'uuid' => Str::uuid(),
      'ticket_nolog' => 'HELP-' . strtoupper(Str::random(6)),
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
    return view('tickets.show', compact('ticket'));
  }

  public function edit(Ticket $ticket)
  {
    // Load the ticket with its relationships
    $ticket->load(['category', 'subCategory', 'user']);
    
    $categories = RefCategory::all();
    
    return response()->json([
      'ticket' => $ticket,
      'categories' => $categories
    ]);
  }

  public function update(UpdateTicketRequest $request, Ticket $ticket)
  {
    $validated = $request->validated();

    $data = [
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
}
