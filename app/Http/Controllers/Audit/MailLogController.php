<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Services\LogParserService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MailLogController extends Controller
{
  protected $logParser;

  public function __construct(LogParserService $logParser)
  {
    $this->logParser = $logParser;
  }

  /**
   * Display Mail log viewer
   */
  public function index(Request $request)
  {
    $date = $request->get('date', null);
    $status = $request->get('status', '');

    // Parse logs for the selected date (only if date is provided)
    $logs = $date ? $this->logParser->parseMailLog($date) : [];

    // Sort by date and time descending (newest first)
    if (!empty($logs)) {
      usort($logs, function ($a, $b) {
        $dateA = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $a['date'] . ' ' . $a['time']);
        $dateB = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $b['date'] . ' ' . $b['time']);
        return $dateB <=> $dateA;
      });
    }

    // Calculate statistics BEFORE filtering (to show all available counts)
    $allLogs = $logs; // Keep copy of all logs for statistics
    $statistics = [
      'total' => count($allLogs),
      'by_type' => [],
      'by_status' => []
    ];

    foreach ($allLogs as $log) {
      $type = $log['type'] ?? 'UNKNOWN';
      $logStatus = $log['status'] ?? 'UNKNOWN';
      $statistics['by_type'][$type] = ($statistics['by_type'][$type] ?? 0) + 1;
      $statistics['by_status'][$logStatus] = ($statistics['by_status'][$logStatus] ?? 0) + 1;
    }

    // NOW apply filter by status if provided (this only affects $logs, not statistics)
    if (!empty($logs) && $status) {
      $logs = array_filter($logs, function ($log) use ($status) {
        return $log['status'] === $status;
      });
    }

    // Get available log dates
    $availableDates = $this->logParser->getAvailableMailLogDates();

    return view('audit.logs-mail.index', [
      'logs' => $logs,
      'availableDates' => $availableDates,
      'selectedDate' => $date,
      'selectedStatus' => $status,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get Mail log data via AJAX (for DataTables)
   */
  public function getMailLogData(Request $request)
  {
    $date = $request->get('date', Carbon::today()->format('Y-m-d'));
    $logs = $this->logParser->parseMailLog($date);

    return response()->json([
      'success' => true,
      'data' => $logs,
      'statistics' => $this->logParser->getLogStatistics($logs)
    ]);
  }
}
