<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PublicHoliday extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'holiday_name',
        'holiday_date',
        'day',
        'states',
        'states_except',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'holiday_date' => 'datetime',
        'states' => 'json',
        'states_except' => 'json',
    ];

    /**
     * Generate audit tags.
     */
    public function generateTags(): array
    {
        return ['public_holidays'];
    }
}
