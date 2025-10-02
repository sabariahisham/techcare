<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefTicketStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_ticket_statuses';

    protected $fillable = [
        'st_name',
        'st_desc',
        'created_by',
        'updated_by',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'tick_status');
    }
}

