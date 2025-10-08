<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'knowledge_base';

    protected $fillable = [
        'uuid',
        'cat_id',
        'subcat_id',
        'kb_title',
        'kb_desc',
        'kb_tag',
        'kb_status',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}



