<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'documents';

  protected $fillable = [
    'doc_id',
    'uuid',
    'doc_mime',
    'doc_name',
    'doc_storage_nm',
    'doc_jenis',
    'created_by',
    'updated_by',
  ];

  protected $appends = ['doc_url', 'doc_size'];

  /**
   * Get the route key for the model.
   *
   * @return string
   */
  public function getRouteKeyName()
  {
    return 'uuid';
  }

  /**
   * Boot function from Laravel.
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      if (empty($model->uuid)) {
        $model->uuid = Uuid::uuid4()->toString();
      }
    });
  }

  public function tickets()
  {
    return $this->hasMany(Ticket::class, 'doc_id');
  }

  /**
   * Get the ticket this document is attached to (for ticket attachments)
   *
   * For ticket attachments:
   * - doc_jenis = 'TICKET'
   * - doc_id contains the ticket ID (not a document ID)
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function ticketOwner()
  {
    return $this->belongsTo(Ticket::class, 'doc_id');
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function knowledgeBase()
  {
    return $this->belongsTo(KnowledgeBase::class, 'doc_id');
  }

  /**
   * Get the full file path for ticket attachments
   *
   * @return string|null
   */
  public function getTicketAttachmentPath()
  {
    if ($this->doc_jenis === 'TICKET' && $this->doc_storage_nm) {
      return 'public/ticket_attachments/' . $this->doc_storage_nm;
    }
    return null;
  }

  /**
   * Check if the attachment file exists in storage
   *
   * @return bool
   */
  public function attachmentExists()
  {
    $path = $this->getTicketAttachmentPath();
    return $path && Storage::exists($path);
  }

  /**
   * Get the download URL for the attachment
   *
   * @return string|null
   */
  public function getDocUrlAttribute()
  {
    if ($this->doc_jenis === 'TICKET' && $this->uuid) {
      return route('ticket-attachments.download', ['uuid' => $this->uuid]);
    }
    return null;
  }

  /**
   * Get the file size from storage
   *
   * @return int
   */
  public function getDocSizeAttribute()
  {
    if ($this->doc_jenis === 'TICKET' && $this->doc_storage_nm) {
      $path = 'public/ticket_attachments/' . $this->doc_storage_nm;
      if (Storage::exists($path)) {
        return Storage::size($path);
      }
    }
    return 0;
  }
}
