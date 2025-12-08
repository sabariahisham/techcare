<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
