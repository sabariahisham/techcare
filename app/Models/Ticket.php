<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
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
   * Get the route key for the model.
   *
   * @return string
   */
  public function getRouteKeyName()
  {
    return 'uuid';
  }

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

  public function slaConfig()
  {
    // Get the latest chronology entry with SLA information
    $latestChronology = $this->chronologies()
      ->whereNotNull('sla_severity')
      ->latest()
      ->first();

    if (!$latestChronology) {
      return null;
    }

    return Sla::where('cat_id', $this->cat_id)
      ->where('subcat_id', $this->subcat_id)
      ->where('sla_severity', $latestChronology->sla_severity)
      ->first();
  }

  public function getSlaSeverityAttribute()
  {
    // Get the latest SLA severity from chronology
    $latestChronology = $this->chronologies()
      ->whereNotNull('sla_severity')
      ->latest()
      ->first();

    return $latestChronology ? $latestChronology->sla_severity : null;
  }

  public function chronologies()
  {
    return $this->hasMany(TicketChronology::class, 'ticket_id')->orderBy('created_at', 'asc');
  }

  /**
   * Pegawai tugasan pada rentetan kronologi terakhir sebelum tutup/batal (status 5/6).
   *
   * Urutan mengikut id kronologi (bukan created_at) supaya rentetan betul walaupun
   * "Tarikh Tindakan" pada borang menyebabkan created_at tidak seiring dengan urutan sebenar.
   */
  public function getActiveAssigneeUsersBeforeLatestTerminalChronology(): Collection
  {
    $terminal = [5, 6];
    $rows = $this->chronologies()
      ->orderBy('id', 'asc')
      ->get(['id', 'tick_status', 'escalate_to']);

    if ($rows->isEmpty()) {
      return collect();
    }

    $maxTerminalId = $rows
      ->filter(fn ($r) => in_array((int) $r->tick_status, $terminal, true))
      ->max('id');

    if ($maxTerminalId === null) {
      return collect();
    }

    $before = $rows->filter(function ($r) use ($maxTerminalId, $terminal) {
      return (int) $r->id < (int) $maxTerminalId
        && !in_array((int) $r->tick_status, $terminal, true);
    })->values();

    if ($before->isEmpty()) {
      return collect();
    }

    $i = $before->count() - 1;
    $lastPreCloseStatus = (int) $before[$i]->tick_status;
    $j = $i;
    while ($j >= 0 && (int) $before[$j]->tick_status === $lastPreCloseStatus) {
      $j--;
    }

    $orderedIds = $before
      ->slice($j + 1, $i - $j)
      ->pluck('escalate_to')
      ->filter()
      ->map(fn ($id) => (int) $id)
      ->unique()
      ->values();

    if ($orderedIds->isEmpty()) {
      return collect();
    }

    $users = User::query()
      ->whereIn('id', $orderedIds->all())
      ->get(['id', 'nama', 'email']);

    return $orderedIds
      ->map(fn (int $id) => $users->firstWhere('id', $id))
      ->filter(fn ($user) => $user && !blank($user->nama ?? null))
      ->values();
  }

  /**
   * Get the currently assigned user (from latest chronology)
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
   */
  public function assignedUser()
  {
    $latestChronology = $this->chronologies()
      ->whereNotNull('escalate_to')
      ->latest()
      ->first();

    return $latestChronology ? $latestChronology->escalatedTo() : null;
  }

  /**
   * Get the latest SLA due date from chronologies
   *
   * @return \Carbon\Carbon|null
   */
  public function getLatestSlaDueDateAttribute()
  {
    $latestChronology = $this->chronologies()
      ->whereNotNull('sla_duedate')
      ->latest()
      ->first();

    return $latestChronology ? $latestChronology->sla_duedate : null;
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
    return $this->hasMany(TicketComment::class, 'ticket_id')->orderBy('created_at', 'asc');
  }

  /**
   * Get all action comments for this ticket (alias for comments)
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function actionComments()
  {
    return $this->comments();
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
