<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJobDetail extends Model
{
    protected $table = 'sync_job_details';

    protected $fillable = [
        'sync_job_id',
        'table_name',
        'total_records',
        'updated_records',
        'inserted_records',
        'status',
        'started_at',
        'finished_at',
        'execution_time',
        'message',
    ];

    public $timestamps = true;  
}
