<?php

/*
 * Function TicketCommentController.php:
 *  - CRUD comments on tickets (create and show) for Pengadu and Pegawai Tugasan.
 *  - manage comment.
 *  - associate comments with users and tickets.
 *  - comments will update according to current status in chronological view
 */

namespace App\Http\Controllers\Ticket;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Http\Requests\Ticket\StoreCommentRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TicketCommentController extends Controller
{
  /**
   * Store a new comment for a ticket
   */
  public function store(StoreCommentRequest $request, Ticket $ticket)
  {
    try {
      TicketComment::create([
        'user_id' => Auth::id(),
        'ticket_id' => $ticket->id,
        'comment_text' => $request->validated()['comment'],
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
      ]);

      return redirect()->back()->with('success', 'Komen berjaya ditambah!');
    } catch (\Exception $e) {
      Log::error('Error creating comment', [
        'error' => $e->getMessage(),
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
      ]);

      return redirect()->back()->with('error', 'Ralat semasa menambah komen.');
    }
  }

  /**
   * Get comments for a ticket (DataTables format)
   *
   * @param \App\Models\Ticket $ticket
   * @return \Illuminate\Http\JsonResponse
   */
  public function getComments(Ticket $ticket)
  {
    $comments = TicketComment::with('user')
      ->where('ticket_id', $ticket->id)
      ->orderBy('created_at', 'desc');

    return DataTables::of($comments)
      ->addIndexColumn()
      ->addColumn('user_name', function ($comment) {
        return $comment->user ? e($comment->user->nama) : 'Unknown User';
      })
      ->addColumn('comment_text', function ($comment) {
        //return e($comment->comment_text);
        return $comment->comment_text;
      })
      ->addColumn('created_date', function ($comment) {
        return $comment->created_at ? $comment->created_at->format('d/m/Y H:i:s') : '-';
      })
      ->addColumn('action', function ($comment) {
        $user = Auth::user();
        $buttons = [];

        // Only allow comment creator or admin to delete
        if ($comment->user_id == Auth::id() || $user->hasAnyRole(['Administrator', 'Pentadbir Sistem'])) {
          $buttons[] = '<button type="button" class="btn btn-icon btn-text-danger rounded-pill btn-sm delete-comment" data-id="' . $comment->id . '" data-bs-toggle="tooltip" title="Delete"><i class="bx bx-trash"></i></button>';
        }

        return '<div class="d-inline-block text-nowrap">' . implode(' ', $buttons) . '</div>';
      })
      //->rawColumns(['action'])
      ->rawColumns(['comment_text', 'action'])
      ->make(true);
  }
}
