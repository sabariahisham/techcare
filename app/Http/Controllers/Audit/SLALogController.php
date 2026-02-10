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

      // Sort by date and time descending (newest first)
      usort($logs, function ($a, $b) {
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
}
