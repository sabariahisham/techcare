<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class RefCategory extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'ref_categories';

    protected $fillable = [
        'cat_name',
        'cat_desc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Generate audit tags.
     */
    public function generateTags(): array
    {
        return ['ref_categories'];
    }

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

