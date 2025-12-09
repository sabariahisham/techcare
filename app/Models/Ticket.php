<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class Ticket extends Model implements Auditable
{
  use HasFactory, SoftDeletes;
  use \OwenIt\Auditing\Auditable;

  protected $table = 'tickets';

  protected $fillable = [
    'uuid',
    'ticket_nolog',
    'user_id',
    'serial_number',
    'cat_id',
    'doc_id',
    'subcat_id',
    'tick_status',
    'tick_duedate',
    'ticket_title',
    'ticket_desc',
    'ticket_type',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'tick_duedate' => 'datetime',
  ];

  /**
   * Attributes to include in audit.
   */
  protected $auditInclude = [
    'ticket_nolog',
    'user_id',
    'serial_number',
    'cat_id',
    'subcat_id',
    'tick_status',
    'tick_duedate',
    'ticket_title',
    'ticket_desc',
    'ticket_type',
    'updated_by'
  ];

  /**
   * Generate audit tags.
   */
  public function generateTags(): array
  {
    return ['ticket'];
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      if (empty($model->uuid)) {
        $model->uuid = Uuid::uuid4()->toString();
      }

      if (empty($model->ticket_nolog)) {
        $model->ticket_nolog = static::generateTicketNumber();
      }
    });
  }

  /**
   * Generate ticket number in format: HELP-YYYY-MM-0001
   * The last number will increment based on tickets created in current month
   * Uses database locking to prevent duplicate numbers in high concurrency
   */
  private static function generateTicketNumber()
  {
    return DB::transaction(function () {
      $currentYear = date('Y');
      $currentMonth = date('m');
      $prefix = "HELP-{$currentYear}-{$currentMonth}-";

      // Get the last ticket number for current month with lock
      $lastTicket = static::where('ticket_nolog', 'LIKE', $prefix . '%')
        ->lockForUpdate()
        ->orderBy('ticket_nolog', 'desc')
        ->first();

      if ($lastTicket) {
        // Extract the number part from the last ticket
        $lastNumber = (int) substr($lastTicket->ticket_nolog, -4);
        $nextNumber = $lastNumber + 1;
      } else {
        // First ticket of the month
        $nextNumber = 1;
      }

      // Format number with leading zeros (4 digits)
      $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

      return $prefix . $formattedNumber;
    });
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function category()
  {
    return $this->belongsTo(RefCategory::class, 'cat_id');
  }

  public function subCategory()
  {
    return $this->belongsTo(RefSubCategory::class, 'subcat_id');
  }

  public function status()
  {
    return $this->belongsTo(RefTicketStatus::class, 'tick_status');
  }

  public function document()
  {
    return $this->belongsTo(Document::class, 'doc_id');
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function chronologies()
  {
    return $this->hasMany(TicketChronology::class, 'ticket_id')->orderBy('created_at', 'asc');
  }

  /**
   * Get all file attachments for this ticket
   *
   * Attachments are stored in the documents table with:
   * - doc_jenis = 'TICKET'
   * - doc_id = ticket ID (this ticket's ID)
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function attachments()
  {
    return $this->hasMany(Document::class, 'doc_id')->where('doc_jenis', 'TICKET');
  }

  /**
   * Get all comments for this ticket
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function comments()
  {
    return $this->hasMany(Comment::class, 'ticket_id')->orderBy('created_at', 'asc');
  }


  /**
   * Create a chronology entry when status changes
   */
  public function addChronologyEntry($newStatusId, $escalateTo = null, $slaDueDate = null, $userId = null, $levelPegtugasId = null)
  {
    $currentUserId = $userId ?? (Auth::check() ? Auth::id() : null) ?? $this->created_by;

    // Create chronology entry
    $chronology = TicketChronology::create([
      'ticket_id' => $this->id,
      'frm_user_id' => $currentUserId,
      'level_pegtugas_id' => $levelPegtugasId,
      'tick_status' => $newStatusId,
      'escalate_to' => $escalateTo,
      'sla_duedate' => $slaDueDate,
      'created_by' => $currentUserId,
    ]);

    // Update this ticket's current status to reflect the latest status
    $this->update([
      'tick_status' => $newStatusId,
      'updated_by' => $currentUserId
    ]);

    return $chronology;
  }
}
