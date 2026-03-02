<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\SyncJob;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SyncSPPLogController extends Controller
{
  /**
   * Display Sync SPP log viewer
   */
  public function index(Request $request)
  {
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);

    // Build query
    $query = SyncJob::where('job_name', 'sync_users_spp')
      ->orderBy('started_at', 'desc');

    // Apply date filter if provided
    if ($dateFrom && $dateTo) {
      $query->whereBetween('started_at', [
        Carbon::parse($dateFrom)->startOfDay(),
        Carbon::parse($dateTo)->endOfDay()
      ]);
    } elseif ($dateFrom) {
      $query->whereDate('started_at', '>=', Carbon::parse($dateFrom));
    } elseif ($dateTo) {
      $query->whereDate('started_at', '<=', Carbon::parse($dateTo));
    }

    $logs = $query->get();

    // Calculate statistics
    $statistics = [
      'total' => $logs->count(),
      'success' => $logs->where('status', 'success')->count(),
      'failed' => $logs->where('status', 'failed')->count(),
      'running' => $logs->where('status', 'running')->count(),
      'total_inserted' => $logs->sum('inserted_records'),
      'total_updated' => $logs->sum('updated_records'),
    ];

    return view('audit.logs-sync-spp.index', [
      'logs' => $logs,
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get Sync SPP log data via AJAX (for DataTables)
   */
  public function getSyncSppLogData(Request $request)
  {
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);

    $query = SyncJob::where('job_name', 'sync_users_spp')
      ->orderBy('started_at', 'desc');

    // Apply date filter
    if ($dateFrom && $dateTo) {
      $query->whereBetween('started_at', [
        Carbon::parse($dateFrom)->startOfDay(),
        Carbon::parse($dateTo)->endOfDay()
      ]);
    } elseif ($dateFrom) {
      $query->whereDate('started_at', '>=', Carbon::parse($dateFrom));
    } elseif ($dateTo) {
      $query->whereDate('started_at', '<=', Carbon::parse($dateTo));
    }

    $logs = $query->get();

    return response()->json([
      'success' => true,
      'data' => $logs
    ]);
  }

  /**
   * Manual run Sync User SPP job
   * This is a backup option to run sync manually
   * Useful for:
   * - Local development testing
   * - When cron job fails or not running
   * - Emergency sync needed
   */
  public function runTask()
  {
    try {
      // Increase execution time to 10 minutes for sync process
      set_time_limit(600);
      ini_set('max_execution_time', '600');
      
      \Illuminate\Support\Facades\Log::info('[SYNC_SPP][MANUAL_RUN] Starting manual sync task', [
        'user_id' => auth()->id(),
        'user_email' => auth()->user()->email ?? 'unknown',
        'timestamp' => now()->toDateTimeString(),
        'time_limit' => ini_get('max_execution_time')
      ]);

      // Call the sync command
      \Illuminate\Support\Facades\Artisan::call('sync:users-spp');
      $output = \Illuminate\Support\Facades\Artisan::output();

      \Illuminate\Support\Facades\Log::info('[SYNC_SPP][MANUAL_RUN] Task completed', [
        'output' => $output,
        'user_id' => auth()->id()
      ]);

      // Get the latest sync job result
      $latestJob = SyncJob::where('job_name', 'sync_users_spp')
        ->orderBy('started_at', 'desc')
        ->first();

      return response()->json([
        'success' => true,
        'message' => 'Sync task completed successfully',
        'results' => [
          'status' => $latestJob->status ?? 'unknown',
          'total_records' => $latestJob->total_records ?? 0,
          'inserted' => $latestJob->inserted_records ?? 0,
          'updated' => $latestJob->updated_records ?? 0,
          'execution_time' => $latestJob->execution_time ?? 0,
        ],
        'timestamp' => now()->format('d/m/Y H:i:s')
      ]);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('[SYNC_SPP][MANUAL_RUN][ERROR] Manual task failed', [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'user_id' => auth()->id()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to execute task: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Kill/Reset stuck running jobs
   * Mark all running jobs as failed if they've been running for too long
   */
  public function killStuckJobs()
  {
    try {
      // Find jobs that have been running for more than 10 minutes
      $stuckJobs = SyncJob::where('job_name', 'sync_users_spp')
        ->where('status', 'running')
        ->where('started_at', '<', Carbon::now()->subMinutes(10))
        ->get();

      $killedCount = 0;
      foreach ($stuckJobs as $job) {
        $job->status = 'failed';
        $job->finished_at = Carbon::now();
        $job->message = 'Job killed - exceeded maximum execution time (10 minutes)';
        $job->execution_time = Carbon::parse($job->started_at)->diffInSeconds(Carbon::now());
        $job->save();
        $killedCount++;
      }

      \Illuminate\Support\Facades\Log::info('[SYNC_SPP][KILL_JOBS] Killed stuck jobs', [
        'killed_count' => $killedCount,
        'user_id' => auth()->id()
      ]);

      return response()->json([
        'success' => true,
        'message' => "Successfully killed {$killedCount} stuck job(s)",
        'killed_count' => $killedCount
      ]);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('[SYNC_SPP][KILL_JOBS][ERROR] Failed to kill jobs', [
        'error_message' => $e->getMessage(),
        'user_id' => auth()->id()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to kill jobs: ' . $e->getMessage()
      ], 500);
    }
  }
}
