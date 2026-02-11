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

    // Sort by email type priority, then by time descending within each type
    if (!empty($logs)) {
      usort($logs, function ($a, $b) {
        // Define priority order
        // TICKET_CLOSED (Owner) = 1
        // TICKET_CLOSED (Officer/Level) = 2
        // TICKET_ASSIGNED (Level 2) = 3
        // TICKET_ASSIGNED (Level 1) = 4
        // TICKET_CREATED (Owner) = 5

        $getPriority = function ($log) {
          $type = $log['type'] ?? '';
          $context = $log['context'] ?? [];

          if ($type === 'TICKET_CLOSED') {
            // Check if it's to owner (no level) or to officer (has level)
            if (isset($context['level'])) {
              return 2; // TICKET_CLOSED to officer
            } else {
              return 1; // TICKET_CLOSED to owner
            }
          } elseif ($type === 'TICKET_ASSIGNED') {
            // Check level
            $level = $context['level'] ?? 1;
            if ($level == 2) {
              return 3; // TICKET_ASSIGNED Level 2
            } else {
              return 4; // TICKET_ASSIGNED Level 1
            }
          } elseif ($type === 'TICKET_CREATED') {
            return 5; // TICKET_CREATED (always owner)
          }

          return 99; // Unknown types go to the end
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
