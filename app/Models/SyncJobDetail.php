<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJobDetail extends Model
{
    protected $table = 'sync_jobs_details';

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

    /**
     * Get the parent sync job
     */
    public function syncJob()
    {
        return $this->belongsTo(SyncJob::class, 'sync_job_id');
    }
}
