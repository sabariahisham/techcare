<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\Node\Block\Document;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tickets';

  protected $fillable = [
    'uuid',
    'ticket_nolog',
    'user_id',
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
}
