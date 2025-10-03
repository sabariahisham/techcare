<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sla extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sla';

    protected $fillable = [
        'cat_id',
        'subcat_id',
        'sla_level',
        'sla_priority',
        'sla_days',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(RefCategory::class, 'cat_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(RefSubCategory::class, 'subcat_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sla_id');
    }
}

