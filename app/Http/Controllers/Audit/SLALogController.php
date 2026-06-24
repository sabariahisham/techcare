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
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);
    $category = $request->get('category', '');
    $ticket = trim((string) $request->get('ticket', ''));

    // Backward compatibility for old single-date filter
    if (!$dateFrom && !$dateTo && $request->get('date')) {
      $dateFrom = $request->get('date');
      $dateTo = $request->get('date');
    }

    $logs = $this->loadSlaLogs($dateFrom, $dateTo, $ticket);
    $logs = $this->filterSlaCategories($logs);

    if ($ticket !== '') {
      $logs = array_values(array_filter($logs, function ($log) use ($ticket) {
        return $this->matchesTicketSearch((string) ($log['ticket'] ?? ''), $ticket);
      }));
    }

    if (!empty($logs)) {
      usort($logs, function ($a, $b) {
        $getPriority = function ($log) {
          $logCategory = strtoupper($log['category'] ?? '');
          return $logCategory === 'OVERDUE' ? 1 : 2;
        };

        $priorityA = $getPriority($a);
        $priorityB = $getPriority($b);

        if ($priorityA !== $priorityB) {
          return $priorityA <=> $priorityB;
        }

        $dateA = Carbon::createFromFormat('d/m/Y H:i:s', $a['date'] . ' ' . $a['time']);
        $dateB = Carbon::createFromFormat('d/m/Y H:i:s', $b['date'] . ' ' . $b['time']);

        return $dateB <=> $dateA;
      });
    }

    $logs = $this->logParser->enrichMailLogsWithTicketUuids($logs);

    $allLogs = $logs;
    $statistics = [
      'total' => count($allLogs),
      'by_category' => []
    ];

    foreach ($allLogs as $log) {
      $logCategory = $log['category'] ?? 'UNKNOWN';
      $statistics['by_category'][$logCategory] = ($statistics['by_category'][$logCategory] ?? 0) + 1;
    }

    if (!empty($logs) && $category) {
      $logs = array_filter($logs, function ($log) use ($category) {
        return strtoupper($log['category']) === $category;
      });
    }

    $logs = array_values($logs);
    $availableDates = $this->logParser->getAvailableSlaLogDates();

    return view('audit.logs-sla.index', [
      'logs' => $logs,
      'availableDates' => $availableDates,
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'selectedCategory' => $category,
      'selectedTicket' => $ticket,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get SLA log data via AJAX (for DataTables)
   */
  public function getSlaLogData(Request $request)
  {
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);
    $ticket = trim((string) $request->get('ticket', ''));

    $logs = $this->loadSlaLogs($dateFrom, $dateTo, $ticket);
    $logs = $this->filterSlaCategories($logs);
    $logs = $this->logParser->enrichMailLogsWithTicketUuids($logs);

    if ($ticket !== '') {
      $logs = array_values(array_filter($logs, function ($log) use ($ticket) {
        return $this->matchesTicketSearch((string) ($log['ticket'] ?? ''), $ticket);
      }));
    }

    return response()->json([
      'success' => true,
      'data' => $logs,
      'statistics' => $this->logParser->getLogStatistics($logs)
    ]);
  }

  /**
   * Manual run SLA notifications task
   */
  public function runTask(Request $request)
  {
    try {
      \Illuminate\Support\Facades\Log::channel('sla')->info('[SLA][MANUAL_RUN][START] Manual task execution triggered', [
        'user_id' => auth()->id(),
        'user_email' => auth()->user()->email ?? 'N/A',
        'ip_address' => $request->ip(),
        'timestamp' => now()->toDateTimeString()
      ]);

      \Illuminate\Support\Facades\Artisan::call('sla:send-notifications', [
        '--type' => 'all'
      ]);

      $output = \Illuminate\Support\Facades\Artisan::output();

      $sent = 0;
      $failed = 0;
      $skipped = 0;

      if (preg_match('/Total Sent: (\d+)/', $output, $matches)) {
        $sent = (int) $matches[1];
      }
      if (preg_match('/Total Failed: (\d+)/', $output, $matches)) {
        $failed = (int) $matches[1];
      }
      if (preg_match('/Total Skipped: (\d+)/', $output, $matches)) {
        $skipped = (int) $matches[1];
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

  /**
   * Load SLA logs based on date range or ticket-only search.
   */
  private function loadSlaLogs(?string $dateFrom, ?string $dateTo, string $ticket = ''): array
  {
    if ($dateFrom || $dateTo) {
      return $this->logParser->parseSlaLogRange($dateFrom, $dateTo);
    }

    if ($ticket !== '') {
      return $this->logParser->parseAllSlaLogs();
    }

    return [];
  }

  /**
   * Keep only REMINDER and OVERDUE SLA log entries.
   */
  private function filterSlaCategories(array $logs): array
  {
    return array_values(array_filter($logs, function ($log) {
      $logCategory = strtoupper($log['category'] ?? '');
      return in_array($logCategory, ['REMINDER', 'OVERDUE'], true);
    }));
  }

  /**
   * Match ticket number using partial or normalized search.
   */
  private function matchesTicketSearch(string $ticketNumber, string $search): bool
  {
    $search = trim($search);
    if ($search === '' || $ticketNumber === '' || $ticketNumber === '-') {
      return false;
    }

    if (stripos($ticketNumber, $search) !== false) {
      return true;
    }

    $normalizedTicket = preg_replace('/[^a-zA-Z0-9]/', '', $ticketNumber);
    $normalizedSearch = preg_replace('/[^a-zA-Z0-9]/', '', $search);

    return $normalizedSearch !== '' && stripos($normalizedTicket, $normalizedSearch) !== false;
  }
}
