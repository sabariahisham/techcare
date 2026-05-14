<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\SyncJobDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SyncRefSPPLogController extends Controller
{
  /**
   * Display Sync RefSPP log viewer
   */
  public function index(Request $request)
  {
    $dateFrom = $request->input('date_from', null);
    $dateTo = $request->input('date_to', null);

    // Build query - filter by parent job_name via relationship
    $query = SyncJobDetail::whereHas('syncJob', function($q) {
        $q->where('job_name', 'sync_refs_spp');
      })
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

    return view('audit.logs-sync-ref-spp.index', [
      'logs' => $logs,
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get Sync RefSPP log data via AJAX (for DataTables)
   */
  public function getSyncRefSppLogData(Request $request)
  {
    $dateFrom = $request->input('date_from', null);
    $dateTo = $request->input('date_to', null);

    $query = SyncJobDetail::whereHas('syncJob', function($q) {
        $q->where('job_name', 'sync_refs_spp');
      })
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
   * Manual run Sync RefSPP job
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
      
      \Illuminate\Support\Facades\Log::info('[SYNC_REF_SPP][MANUAL_RUN] Starting manual sync task', [
        'user_id' => auth()->id(),
        'user_email' => auth()->user()->email ?? 'unknown',
        'timestamp' => now()->toDateTimeString(),
        'time_limit' => ini_get('max_execution_time')
      ]);

      // Call the sync command (assuming you have this command)
      \Illuminate\Support\Facades\Artisan::call('app:sync-refs-data', ['--all' => true]);
      $output = \Illuminate\Support\Facades\Artisan::output();

      \Illuminate\Support\Facades\Log::info('[SYNC_REF_SPP][MANUAL_RUN] Task completed', [
        'output' => $output,
        'user_id' => auth()->id()
      ]);

      // Get the latest sync job details (summary of all tables synced)
      $latestDetails = SyncJobDetail::whereHas('syncJob', function($q) {
          $q->where('job_name', 'sync_refs_spp');
        })
        ->orderBy('started_at', 'desc')
        ->get();

      $totalRecords = $latestDetails->sum('total_records');
      $totalInserted = $latestDetails->sum('inserted_records');
      $totalUpdated = $latestDetails->sum('updated_records');

      return response()->json([
        'success' => true,
        'message' => 'Sync task completed successfully',
        'results' => [
          'status' => 'success',
          'total_records' => $totalRecords,
          'inserted' => $totalInserted,
          'updated' => $totalUpdated,
          'execution_time' => $latestDetails->sum('execution_time'),
          'updated' => $latestJob->updated_records ?? 0,
          'execution_time' => $latestJob->execution_time ?? 0,
        ],
        'timestamp' => now()->format('d/m/Y H:i:s')
      ]);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('[SYNC_REF_SPP][MANUAL_RUN][ERROR] Manual task failed', [
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
   * Mark all running jobs as failed ifHas('syncJob', function($q) {
          $q->where('job_name', 'sync_refs_spp');
        }o long
   */
  public function killStuckJobs()
  {
    try {
      // Find jobs that have been running for more than 10 minutes
      $stuckJobs = SyncJobDetail::where('job_name', 'sync_refs_spp')
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

      \Illuminate\Support\Facades\Log::info('[SYNC_REF_SPP][KILL_JOBS] Killed stuck jobs', [
        'killed_count' => $killedCount,
        'user_id' => auth()->id()
      ]);

      return response()->json([
        'success' => true,
        'message' => "Successfully killed {$killedCount} stuck job(s)",
        'killed_count' => $killedCount
      ]);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('[SYNC_REF_SPP][KILL_JOBS][ERROR] Failed to kill jobs', [
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
