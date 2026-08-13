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
use App\Models\TicketCatatanImage;
use App\Http\Requests\Ticket\StoreCommentRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class TicketCommentController extends Controller
{
  /**
   * Store a new comment for a ticket
   */
  public function store(StoreCommentRequest $request, $uuid)
  {
    try {
      $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
      $commentText = $request->validated()['comment'];

      $comment = TicketComment::create([
        'user_id' => Auth::id(),
        'ticket_id' => $ticket->id,
        'comment_text' => $commentText,
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
      ]);

      TicketCatatanImage::attachReferencedImages($commentText, $ticket->id, $comment->id, Auth::id());

      return redirect()->back()->with('success', 'Komen berjaya ditambah!');
    } catch (\Exception $e) {
      Log::error('Error creating comment', [
        'error' => $e->getMessage(),
        'ticket_id' => $uuid ?? null,
        'user_id' => Auth::id(),
      ]);

      return redirect()->back()->with('error', 'Ralat semasa menambah komen.');
    }
  }

  /**
   * Upload an image embedded from the Quill editor (catatan tindakan / komen).
   *
   * The comment this image will belong to does not exist yet at upload time
   * (the user is still composing in the Quill editor), so the image is stored
   * with comment_id = null and later claimed by TicketCatatanImage::attachReferencedImages()
   * once the comment/catatan is actually saved. Unclaimed rows are swept by the
   * ticket-catatan-images:prune-orphans scheduled command.
   *
   * @param \Illuminate\Http\Request $request
   * @param string $uuid
   * @return \Illuminate\Http\JsonResponse
   */
  public function uploadEditorImage(Request $request, $uuid)
  {
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

    if (!$this->userCanUploadCatatanImage($ticket)) {
      abort(403, 'Anda tidak mempunyai kebenaran untuk memuat naik gambar bagi tiket ini.');
    }

    $request->validate([
      'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
    ], [
      'image.required' => 'Sila pilih fail gambar.',
      'image.image' => 'Fail mestilah gambar yang sah.',
      'image.mimes' => 'Format gambar mestilah JPG, JPEG, PNG atau WEBP sahaja.',
      'image.max' => 'Saiz gambar tidak boleh melebihi 2MB.',
    ]);

    $file = $request->file('image');
    $storageFileName = (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
    $path = null;

    try {
      $path = $file->storeAs('ticket_comment_images', $storageFileName, 'public');

      $image = TicketCatatanImage::create([
        'ticket_id' => $ticket->id,
        'comment_id' => null,
        'uploaded_by' => Auth::id(),
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $path,
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
      ]);

      return response()->json([
        'success' => true,
        'url' => Storage::disk('public')->url($path),
        'image_id' => $image->id,
      ]);
    } catch (\Exception $e) {
      // Never leave a physical file behind if the DB record could not be created.
      if ($path && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
      }

      Log::error('Error uploading comment editor image', [
        'error' => $e->getMessage(),
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Ralat semasa memuat naik gambar.',
      ], 500);
    }
  }

  /**
   * Mirrors the visibility rules used in tickets/show.blade.php for who may act
   * on a ticket's comments/catatan tindakan: the ticket's own pengadu, ticket
   * administrators/supervisors, or a Pegawai Tugasan currently assigned to it.
   */
  private function userCanUploadCatatanImage(Ticket $ticket): bool
  {
    $user = Auth::user();

    if (!$user) {
      return false;
    }

    if ((int) $ticket->user_id === (int) $user->id) {
      return true;
    }

    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
      return true;
    }

    if ($user->hasRole('Pegawai Tugasan')) {
      $assignedOfficerIds = $ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->where('tick_status', $ticket->tick_status)
        ->latest()
        ->pluck('escalate_to')
        ->unique()
        ->toArray();

      return in_array($user->id, $assignedOfficerIds);
    }

    return false;
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
