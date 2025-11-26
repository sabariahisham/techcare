<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_categories';

    protected $fillable = [
        'cat_name',
        'cat_desc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function subCategories()
    {
        return $this->hasMany(RefSubCategory::class, 'cat_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'cat_id');
    }

    public function slas()
    {
        return $this->hasMany(Sla::class, 'cat_id');
    }

    public function officers()
    {
        return $this->hasMany(Officer::class, 'cat_id');
    }
}

