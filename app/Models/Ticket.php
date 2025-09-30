<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'ticket_nolog',
        'user_id',
        'cat_id',
        'doc_id',
        'subcat_id',
        'tick_status',
        'tick_duedate',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tick_duedate' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            // UUID-Ramsey
            $ticket->uuid = (string) Uuid::uuid4();

            // Generate Ticket Number
            $year  = now()->format('Y');
            $month = now()->format('m');

            $count = static::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count() + 1;

            $ticket->ticket_nolog = sprintf("HELP-%s-%s-%04d", $year, $month, $count);

            // Created by (jika login)
            if (auth()->check()) {
                $ticket->created_by = auth()->id();
            }
        });

        static::updating(function ($ticket) {
            if (auth()->check()) {
                $ticket->updated_by = auth()->id();
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function category()
    // {
    //     return $this->belongsTo(RefCategory::class, 'cat_id');
    // }

    // public function subCategory()
    // {
    //     return $this->belongsTo(RefSubCategory::class, 'subcat_id');
    // }

    // public function document()
    // {
    //     return $this->belongsTo(Document::class, 'doc_id');
    // }

    // public function status()
    // {
    //     return $this->belongsTo(RefTicketStatus::class, 'tick_status');
    // }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
