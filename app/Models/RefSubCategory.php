<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefSubCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_sub_categories';

    protected $fillable = [
        'cat_id',
        'subcat_name',
        'subcat_desc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function category()
    {
        return $this->belongsTo(RefCategory::class, 'cat_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'subcat_id');
    }

    public function slas()
    {
        return $this->hasMany(Sla::class, 'subcat_id');
    }

    public function officers()
    {
        return $this->hasMany(Officer::class, 'subcat_id');
    }
}
