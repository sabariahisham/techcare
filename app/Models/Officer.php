<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Officer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cat_id',
        'subcat_id',
        'user_id',
        'level_tugasan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'level_tugasan' => 'integer',
    ];

    /**
     * Get the category that owns the officer assignment.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RefCategory::class, 'cat_id');
    }

    /**
     * Get the subcategory that owns the officer assignment.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(RefSubCategory::class, 'subcat_id');
    }

    /**
     * Get the user assigned as officer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this officer assignment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this officer assignment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
