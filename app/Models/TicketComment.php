<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class TicketComment extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'comments';

  protected $fillable = [
    'user_id',
    'ticket_id',
    'comment_text',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      if (Auth::check()) {
        // Only set if not already provided
        if (empty($model->created_by)) {
          $model->created_by = Auth::id();
        }
        if (empty($model->updated_by)) {
          $model->updated_by = Auth::id();
        }
      }
    });

    static::updating(function ($model) {
      if (Auth::check()) {
        $model->updated_by = Auth::id();
      }
    });

    // Permanent deletion removes the comment for good, so its Quill-uploaded
    // images (and their physical files) must go with it — each delete() call
    // triggers TicketCatatanImage's own `deleting` hook, which removes the file.
    static::forceDeleting(function (TicketComment $model) {
      $model->images()->get()->each->delete();
    });
  }

  /**
   * Get the user who made the comment
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Get the ticket this comment belongs to
   */
  public function ticket()
  {
    return $this->belongsTo(Ticket::class, 'ticket_id');
  }

  /**
   * Get the user who created this comment record
   */
  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * Get the user who last updated this comment record
   */
  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  /**
   * Images uploaded from the Quill editor and embedded in comment_text
   */
  public function images()
  {
    return $this->hasMany(TicketCatatanImage::class, 'comment_id');
  }

  /**
   * Get the comment text attribute
   * This provides compatibility with the blade template expecting 'comment' field
   */
  public function getCommentAttribute()
  {
    return $this->comment_text;
  }
}
