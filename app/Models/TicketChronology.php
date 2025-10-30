<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketChronology extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tickets_chronologies';

  protected $fillable = [
    'ticket_id',
    'frm_user_id',
    'level_pegtugas_id',
    'tick_status',
    'escalate_to',
    'sla_duedate',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'sla_duedate' => 'datetime',
  ];

  // Relationships
  public function ticket()
  {
    return $this->belongsTo(Ticket::class, 'ticket_id');
  }

  public function fromUser()
  {
    return $this->belongsTo(User::class, 'frm_user_id');
  }

  public function status()
  {
    return $this->belongsTo(RefTicketStatus::class, 'tick_status');
  }

  public function escalatedTo()
  {
    return $this->belongsTo(User::class, 'escalate_to');
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  // Helper method to generate timeline description
  public function getTimelineDescription()
  {
    $status = $this->status->st_name ?? 'Status tidak diketahui';
    $description = $this->status->st_desc ?? 'Tiada penerangan tersedia.';

    return $description;
  }

  // Helper method to get timeline details
  public function getTimelineDetails()
  {
    $details = [];

    if ($this->escalate_to && $this->escalatedTo) {
      $details['Diserahkan kepada'] = $this->escalatedTo->nama;
    }

    if ($this->sla_duedate) {
      $details['SLA Due Date'] = $this->sla_duedate->format('d/m/Y H:i:s');
    }

    if ($this->level_pegtugas_id) {
      $details['Level Pegtugas'] = $this->level_pegtugas_id;
    }

    return $details;
  }
}
