<?php

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Models\RefCategory;

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
        return $ticket->user ? e($ticket->user->name) : '-';
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
        $viewBtn = '<a href="' . route('tickets.show', $ticket->id) . '" class="btn btn-icon btn-text-secondary rounded-pill btn-sm" data-bs-toggle="tooltip" title="View"><i class="bx bx-show"></i></a>';
        $editBtn = '<a href="' . route('tickets.edit', $ticket->id) . '" class="btn btn-icon btn-text-secondary rounded-pill btn-sm" data-bs-toggle="tooltip" title="Edit"><i class="bx bx-edit"></i></a>';
        $deleteBtn = '<button type="button" class="btn btn-icon btn-text-secondary rounded-pill btn-sm delete-record" data-id="' . $ticket->id . '" data-bs-toggle="tooltip" title="Delete"><i class="bx bx-trash"></i></button>';
        return '<div class="d-inline-block text-nowrap">' . $viewBtn . ' ' . $editBtn . ' ' . $deleteBtn . '</div>';
      })
      ->rawColumns(['action'])
      ->make(true);
  }


  public function index()
  {
    return view('tickets.index');
  }

  public function create()
  {
    //using index.blade.php
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'user_id' => 'required|exists:users,id',
      'cat_id' => 'required|exists:ref_categories,id',
      'subcat_id' => 'required|exists:ref_sub_categories,id',
      'doc_id' => 'nullable|exists:documents,id',
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'tick_duedate' => 'nullable|date',
    ]);

    $data['uuid'] = Str::uuid();
    $data['ticket_nolog'] = 'TICK-' . strtoupper(Str::random(6));
    $data['created_by'] = auth()->id();

    Ticket::create($data);

    return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
  }

  public function show(Ticket $ticket)
  {
    return view('tickets.show', compact('ticket'));
  }

  public function edit(Ticket $ticket)
  {
    $categories = RefCategory::where('is_active', 1)->get();
    return response()->json([
      'ticket' => $ticket,
      'categories' => $categories
    ]);
  }

  public function update(Request $request, Ticket $ticket)
  {
    $data = $request->validate([
      'cat_id' => 'required|exists:ref_categories,id',
      'subcat_id' => 'required|exists:ref_sub_categories,id',
      'doc_id' => 'nullable|exists:documents,id',
      'tick_status' => 'required|exists:ref_ticket_statuses,id',
      'tick_duedate' => 'nullable|date',
    ]);

    $data['updated_by'] = auth()->id();

    $ticket->update($data);

    return redirect()->route('tickets.index')->with('success', 'Ticket updated successfully.');
  }

  public function destroy(Ticket $ticket)
  {
    $ticket->delete();

    return redirect()->route('tickets.index')->with('success', 'Ticket deleted successfully.');
  }
}
