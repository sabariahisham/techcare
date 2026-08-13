<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class TicketCatatanImage extends Model
{
  use HasFactory;

  protected $table = 'ticket_catatan_images';

  protected $fillable = [
    'ticket_id',
    'comment_id',
    'uploaded_by',
    'file_name',
    'file_path',
    'mime_type',
    'file_size',
  ];

  protected $casts = [
    'file_size' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  protected static function boot()
  {
    parent::boot();

    // Keep storage in sync: removing the record (individually or via a prune sweep)
    // always removes the physical file too, so nothing is ever orphaned in disk.
    static::deleting(function (TicketCatatanImage $image) {
      if ($image->file_path && Storage::disk('public')->exists($image->file_path)) {
        Storage::disk('public')->delete($image->file_path);
      }
    });
  }

  public function ticket()
  {
    return $this->belongsTo(Ticket::class, 'ticket_id');
  }

  public function comment()
  {
    return $this->belongsTo(TicketComment::class, 'comment_id');
  }

  public function uploadedBy()
  {
    return $this->belongsTo(User::class, 'uploaded_by');
  }

  /**
   * Public URL for the image (served via the storage:link symlink).
   */
  public function getUrlAttribute(): string
  {
    return Storage::disk('public')->url($this->file_path);
  }

  /**
   * Attach images referenced inside saved comment HTML to their comment.
   *
   * Images are uploaded (and stored) the moment the user inserts them into the
   * Quill editor, before the comment itself exists — so at upload time comment_id
   * is left null. Once the comment is actually saved, this scans the final HTML
   * for `ticket_comment_images/<file>` references and claims the matching
   * still-unattached rows for this ticket/uploader. Anything inserted then removed
   * again before submit is simply never matched here, and is swept later by the
   * `ticket-catatan-images:prune-orphans` command.
   */
  public static function attachReferencedImages(?string $html, int $ticketId, int $commentId, ?int $uploadedBy = null): void
  {
    if (!$html) {
      return;
    }

    preg_match_all('/ticket_comment_images\/([A-Za-z0-9\-]+\.[A-Za-z0-9]+)/i', $html, $matches);
    $fileNames = array_unique($matches[1] ?? []);

    if (empty($fileNames)) {
      return;
    }

    $filePaths = array_map(fn ($name) => 'ticket_comment_images/' . $name, $fileNames);

    static::query()
      ->where('ticket_id', $ticketId)
      ->whereIn('file_path', $filePaths)
      ->whereNull('comment_id')
      ->when($uploadedBy, fn ($query) => $query->where('uploaded_by', $uploadedBy))
      ->update(['comment_id' => $commentId]);
  }
}
