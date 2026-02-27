<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    protected $table = 'sync_jobs';

    protected $fillable = [
        'job_name',
        'status',
        'started_at',
        'finished_at',
        'total_records',
        'updated_records',
        'inserted_records',
        'execution_time',
        'message',
    ];

    public $timestamps = false; 
}
