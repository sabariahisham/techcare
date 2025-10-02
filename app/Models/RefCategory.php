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
    ];

    public function subCategories()
    {
        return $this->hasMany(RefSubCategory::class, 'id');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'cat_id');
    }
}

