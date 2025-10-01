<?php

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['user', 'category', 'subCategory', 'status'])
            ->latest()
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
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
        return view('tickets.edit', compact('ticket'));
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
