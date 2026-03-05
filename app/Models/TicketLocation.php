<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class TicketLocation extends Model implements Auditable
{ 
  use HasFactory, SoftDeletes;
  use \OwenIt\Auditing\Auditable;

  protected $table = 'tickets_locations';

  protected $fillable = [
    'ticket_id',
    'building_loc',
    'block_loc',
    'level_loc',
    'created_by',
    'updated_by',
  ];

  /**
   * Attributes to include in audit.
   */
  protected $auditInclude = [
    'ticket_id',
    'building_loc',
    'block_loc',
    'level_loc',
    'updated_by'
  ];

  /**
   * Generate audit tags.
   */
  public function generateTags(): array
  {
    return ['ticket-location'];
  }

  // Relationships
  public function ticket()
  {
    return $this->belongsTo(Ticket::class, 'ticket_id');
  }
}