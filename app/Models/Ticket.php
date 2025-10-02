<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tickets';

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'cat_id',
        'doc_id',
        'subcat_id',
        'tick_status',
        'tick_duedate',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
            $model->ticket_nolog = 'TICK-' . strtoupper(\Illuminate\Support\Str::random(6));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(RefCategory::class, 'cat_id', 'id');
    }

    public function subCategory()
    {
        return $this->belongsTo(RefSubCategory::class, 'subcat_id');
    }

    public function status()
    {
        return $this->belongsTo(RefTicketStatus::class, 'tick_status', 'id');
    }
}
