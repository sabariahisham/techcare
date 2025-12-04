<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class KnowledgeBase extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'knowledge_base';

    protected $fillable = [
        'uuid',
        'cat_id',
        'subcat_id',
        'kb_title',
        'kb_desc',
        'kb_tag',
        'kb_status',
        'kb_jenis',
        'created_by',
        'updated_by',
    ];

    /**
     * Attributes to include in audit.
     */
    protected $auditInclude = [
        'cat_id',
        'subcat_id',
        'kb_title',
        'kb_desc',
        'kb_tag',
        'kb_status',
        'kb_jenis',
        'updated_by'
    ];

    /**
     * Generate audit tags.
     */
    public function generateTags(): array
    {
        return ['knowledge-base'];
    }

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

    public function documents()
    {
        return $this->hasMany(Document::class, 'doc_id', 'id');
    }
}



