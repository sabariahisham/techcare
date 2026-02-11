<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\LogParserService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SLALogController extends Controller
{
  protected $logParser;

  public function __construct(LogParserService $logParser)
  {
    $this->logParser = $logParser;
  }

  /**
   * Display SLA log viewer
   */
  public function index(Request $request)
  {
    $date = $request->get('date', null);
    $category = $request->get('category', '');

    // Parse logs for the selected date (only if date is provided)
    $logs = $date ? $this->logParser->parseSlaLog($date) : [];

    // Filter out COMMAND category logs - only show REMINDER and OVERDUE
    if (!empty($logs)) {
      $logs = array_filter($logs, function ($log) {
        $category = strtoupper($log['category'] ?? '');
        return in_array($category, ['REMINDER', 'OVERDUE']);
      });

      // Sort by category priority (OVERDUE = 1, REMINDER = 2), then by time descending
      usort($logs, function ($a, $b) {
        // Define priority order (lower number = higher priority)
        // OVERDUE = 1 (highest priority)
        // REMINDER = 2 (lower priority)
        $getPriority = function ($log) {
          $category = strtoupper($log['category'] ?? '');
          return $category === 'OVERDUE' ? 1 : 2;
        };

        $priorityA = $getPriority($a);
        $priorityB = $getPriority($b);

        // First sort by priority
        if ($priorityA !== $priorityB) {
          return $priorityA <=> $priorityB;
        }

        // If same priority, sort by time descending (newest first)
        $dateA = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $a['date'] . ' ' . $a['time']);
        $dateB = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $b['date'] . ' ' . $b['time']);
        return $dateB <=> $dateA;
      });
    }

    // Calculate statistics BEFORE category filtering (to show all available counts)
    $allLogs = $logs; // Keep copy of all logs for statistics
    $statistics = [
      'total' => count($allLogs),
      'by_category' => []
    ];

    foreach ($allLogs as $log) {
      $logCategory = $log['category'] ?? 'UNKNOWN';
      $statistics['by_category'][$logCategory] = ($statistics['by_category'][$logCategory] ?? 0) + 1;
    }

    // NOW apply filter by category if provided (this only affects $logs, not statistics)
    if (!empty($logs) && $category) {
      $logs = array_filter($logs, function ($log) use ($category) {
        return strtoupper($log['category']) === $category;
      });
    }

    // Get available log dates
    $availableDates = $this->logParser->getAvailableSlaLogDates();

    return view('audit.logs-sla.index', [
      'logs' => $logs,
      'availableDates' => $availableDates,
      'selectedDate' => $date,
      'selectedCategory' => $category,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get SLA log data via AJAX (for DataTables)
   */
  public function getSlaLogData(Request $request)
  {
    $date = $request->get('date', Carbon::today()->format('Y-m-d'));
    $logs = $this->logParser->parseSlaLog($date);

    return response()->json([
      'success' => true,
      'data' => $logs,
      'statistics' => $this->logParser->getLogStatistics($logs)
    ]);
  }

  /**
   * Manual run SLA notifications task
   * This is a backup option to run SLA notifications manually
   * Useful for:
   * - Local development testing
   * - Production backup when scheduler fails
   * - Immediate notification sending
   */
  public function runTask(Request $request)
  {
    try {
      // Log the manual execution
      \Illuminate\Support\Facades\Log::channel('sla')->info('[SLA][MANUAL_RUN][START] Manual task execution triggered', [
        'user_id' => auth()->id(),
        'user_email' => auth()->user()->email ?? 'N/A',
        'ip_address' => $request->ip(),
        'timestamp' => now()->toDateTimeString()
      ]);

      // Execute the SLA notification command
      \Illuminate\Support\Facades\Artisan::call('sla:send-notifications', [
        '--type' => 'all'
      ]);

      // Get the output
      $output = \Illuminate\Support\Facades\Artisan::output();

      // Parse results from output (extract sent/failed/skipped counts)
      $sent = 0;
      $failed = 0;
      $skipped = 0;

      if (preg_match('/Total Sent: (\d+)/', $output, $matches)) {
        $sent = (int)$matches[1];
      }
      if (preg_match('/Total Failed: (\d+)/', $output, $matches)) {
        $failed = (int)$matches[1];
      }
      if (preg_match('/Total Skipped: (\d+)/', $output, $matches)) {
        $skipped = (int)$matches[1];
      }

      \Illuminate\Support\Facades\Log::channel('sla')->info('[SLA][MANUAL_RUN][SUCCESS] Manual task completed', [
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'user_id' => auth()->id()
      ]);

      return response()->json([
        'success' => true,
        'message' => 'SLA notifications task executed successfully!',
        'results' => [
          'sent' => $sent,
          'failed' => $failed,
          'skipped' => $skipped
        ],
        'timestamp' => now()->format('d/m/Y H:i:s')
      ]);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::channel('sla')->error('[SLA][MANUAL_RUN][ERROR] Manual task failed', [
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
}
