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
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);
    $status = $request->get('status', '');
    $ticket = trim((string) $request->get('ticket', ''));

    // Backward compatibility for old single-date filter
    if (!$dateFrom && !$dateTo && $request->get('date')) {
      $dateFrom = $request->get('date');
      $dateTo = $request->get('date');
    }

    $logs = $this->loadMailLogs($dateFrom, $dateTo, $ticket);

    if ($ticket !== '') {
      $logs = array_values(array_filter($logs, function ($log) use ($ticket) {
        return $this->matchesTicketSearch((string) ($log['ticket'] ?? ''), $ticket);
      }));
    }
    // Sort by email type priority, then by time descending within each type
    if (!empty($logs)) {
      usort($logs, function ($a, $b) {
        $getPriority = function ($log) {
          $type = $log['type'] ?? '';
          $context = $log['context'] ?? [];

          if ($type === 'TICKET_CLOSED') {
            if (isset($context['level'])) {
              return 2;
            }

            return 1;
          } elseif ($type === 'TICKET_ASSIGNED') {
            $level = $context['level'] ?? 1;
            if ($level == 2) {
              return 3;
            }

            return 4;
          } elseif ($type === 'TICKET_CREATED') {
            return 5;
          }

          return 99;
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

    // Calculate statistics BEFORE filtering (to show all available counts)
    $allLogs = $logs;
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

    if (!empty($logs) && $status) {
      $logs = array_filter($logs, function ($log) use ($status) {
        return $log['status'] === $status;
      });
    }

    $logs = array_values($logs);
    $availableDates = $this->logParser->getAvailableMailLogDates();

    return view('audit.logs-mail.index', [
      'logs' => $logs,
      'availableDates' => $availableDates,
      'dateFrom' => $dateFrom,
      'dateTo' => $dateTo,
      'selectedStatus' => $status,
      'selectedTicket' => $ticket,
      'statistics' => $statistics
    ]);
  }

  /**
   * Get Mail log data via AJAX (for DataTables)
   */
  public function getMailLogData(Request $request)
  {
    $dateFrom = $request->get('date_from', null);
    $dateTo = $request->get('date_to', null);
    $ticket = trim((string) $request->get('ticket', ''));

    $logs = $this->loadMailLogs($dateFrom, $dateTo, $ticket);
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
   * Load mail logs based on date range or ticket-only search.
   */
  private function loadMailLogs(?string $dateFrom, ?string $dateTo, string $ticket = ''): array
  {
    if ($dateFrom || $dateTo) {
      return $this->logParser->parseMailLogRange($dateFrom, $dateTo);
    }

    if ($ticket !== '') {
      return $this->logParser->parseAllMailLogs();
    }

    return [];
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
