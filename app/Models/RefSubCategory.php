<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class RefSubCategory extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'ref_sub_categories';

    protected $fillable = [
        'cat_id',
        'subcat_name',
        'subcat_desc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Generate audit tags.
     */
    public function generateTags(): array
    {
        return ['ref_sub_categories'];
    }
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
