<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * LogParserService - Parse structured log files for display
 *
 * Parses sla.log and mail.log files with structured tags like:
 * [SLA][REMINDER][SENT], [MAIL][SLA_REMINDER][SUCCESS]
 */
class LogParserService
{
  /**
   * Map status ID to status name
   *
   * @param int $statusId
   * @return string
   */
  private function mapStatusIdToName(int $statusId): string
  {
    $statusMap = [
      1 => 'Aduan Baru',
      2 => 'Aduan Diproses',
      3 => 'Serahan Kepada',
      4 => 'Aduan Dirujuk',
      5 => 'Aduan Dibatal',
      6 => 'Aduan Selesai'
    ];

    return $statusMap[$statusId] ?? 'Status ' . $statusId;
  }

  /**
   * Parse SLA log file
   *
   * @param string $date Date in format Y-m-d (e.g., 2026-02-09)
   * @return array Array of parsed log entries
   */
  public function parseSlaLog(?string $date = null): array
  {
    $date = $date ?? Carbon::today()->format('Y-m-d');
    $logPath = storage_path("logs/sla-{$date}.log");

    if (!file_exists($logPath)) {
      return [];
    }

    $logs = [];
    $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
      $parsed = $this->parseSlaLogLine($line);
      if ($parsed) {
        // Only show final statuses (SENT/FAILED) - filter out intermediate process statuses
        // This prevents duplicate entries and shows only relevant ticket notification results
        $relevantStatuses = ['SENT', 'FAILED'];
        if (in_array($parsed['status'], $relevantStatuses)) {
          $logs[] = $parsed;
        }
      }
    }

    return $logs;
  }

  /**
   * Parse Mail log file
   *
   * @param string $date Date in format Y-m-d
   * @return array Array of parsed log entries
   */
  public function parseMailLog(?string $date = null): array
  {
    $date = $date ?? Carbon::today()->format('Y-m-d');
    $logPath = storage_path("logs/mail-{$date}.log");

    if (!file_exists($logPath)) {
      return [];
    }

    $logs = [];
    $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
      $parsed = $this->parseMailLogLine($line);
      if ($parsed) {
        // Skip SENDING status to avoid duplicates - only show final status (SENT/FAILED)
        // Also skip SLA-related emails (REMINDER/OVERDUE) as they should only appear in SLA log
        if ($parsed['status'] !== 'SENDING' && !in_array($parsed['type'], ['REMINDER', 'OVERDUE'])) {
          $logs[] = $parsed;
        }
      }
    }

    return $logs;
  }

  /**
   * Parse a single SLA log line
   *
   * Example format:
   * [2026-02-09 07:30:01] sla.INFO: [SLA][REMINDER][START] Starting reminder notifications process {"timestamp":"2026-02-09 07:30:01"}
   *
   * @param string $line
   * @return array|null
   */
  private function parseSlaLogLine(string $line): ?array
  {
    // Match pattern: [timestamp] channel.LEVEL: [TAG1][TAG2][TAG3] message {context}
    if (!preg_match('/^\[([^\]]+)\]\s+\w+\.(\w+):\s+\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]\s+(.+?)(\s+\{.+\})?$/', $line, $matches)) {
      return null;
    }

    $timestamp = $matches[1];
    $level = $matches[2];
    $category = $matches[3]; // SLA
    $type = $matches[4]; // REMINDER, OVERDUE, COMMAND, SCHEDULER
    $status = $matches[5]; // START, SENT, FAILED, etc.
    $message = $matches[6];
    $context = isset($matches[7]) ? json_decode($matches[7], true) : [];

    // Extract ticket number from context or message
    $ticket = $context['ticket'] ?? $this->extractTicketFromMessage($message);

    // Parse date and time separately
    $dt = \Carbon\Carbon::parse($timestamp);

    // Fetch actual data from database tables
    $recipientName = null;
    $email = null;
    $ticketStatus = null;

    if ($ticket) {
      // Get ticket data from database
      $ticketData = DB::table('tickets')
        ->leftJoin('tickets_chronologies', function ($join) {
          $join->on('tickets.id', '=', 'tickets_chronologies.ticket_id')
            ->whereNull('tickets_chronologies.deleted_at')
            ->whereRaw('tickets_chronologies.id = (
              SELECT MAX(tc.id)
              FROM tickets_chronologies tc
              WHERE tc.ticket_id = tickets.id
              AND tc.deleted_at IS NULL
            )');
        })
        ->leftJoin('users', 'tickets_chronologies.escalate_to', '=', 'users.id')
        ->leftJoin('ref_ticket_statuses', 'tickets_chronologies.tick_status', '=', 'ref_ticket_statuses.id')
        ->where('tickets.ticket_nolog', $ticket)
        ->whereNull('tickets.deleted_at')
        ->select(
          'users.nama as recipient_name',
          'users.email as recipient_email',
          'ref_ticket_statuses.st_name as ticket_status'
        )
        ->first();

      if ($ticketData) {
        $recipientName = $ticketData->recipient_name;
        $email = $ticketData->recipient_email;
        $ticketStatus = $ticketData->ticket_status;
      }
    }

    return [
      'date' => $dt->format('d/m/Y'),
      'time' => $dt->format('H:i:s'),
      'ticket' => $ticket ?? '-',
      'category' => $type, // Use the type (REMINDER/OVERDUE) as category
      'type' => $type,
      'step' => $this->formatStepName($type, $status),
      'status' => $this->mapSlaStatus($status),
      'level' => $level,
      'message' => $message,
      'context' => $context,
      'recipient_name' => $recipientName,
      'email' => $email,
      'ticket_status' => $ticketStatus
    ];
  }

  /**
   * Parse a single Mail log line
   *
   * Example format:
   * [2026-02-09 07:30:03] mail.INFO: [MAIL][SLA_REMINDER][SENDING] Preparing reminder email {"ticket":"TICKET-123","recipient_email":"j***n@example.com"}
   *
   * @param string $line
   * @return array|null
   */
  private function parseMailLogLine(string $line): ?array
  {
    // Match pattern: [timestamp] channel.LEVEL: [TAG1][TAG2][TAG3] message {context}
    if (!preg_match('/^\[([^\]]+)\]\s+\w+\.(\w+):\s+\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]\s+(.+?)\s+(\{.+\})\s*$/', $line, $matches)) {
      return null;
    }

    $timestamp = $matches[1];
    $level = $matches[2];
    $category = $matches[3]; // MAIL
    $type = $matches[4]; // SLA_REMINDER, SLA_OVERDUE, TICKET_CREATED, TICKET_ASSIGNED, TICKET_CLOSED
    $status = $matches[5]; // SENDING, SUCCESS, FAILED
    $message = $matches[6];
    $context = isset($matches[7]) ? json_decode($matches[7], true) : [];

    // Extract data from context
    $ticket = $context['ticket'] ?? $this->extractTicketFromMessage($message);
    $email = $context['recipient_email'] ?? null;

    // Parse date and time separately
    $dt = \Carbon\Carbon::parse($timestamp);

    // Get ticket status from log context FIRST (historical status at time of email)
    $ticketStatus = null;
    $statusFromLog = false;

    // Priority 1: Use status from context (historical)
    if (isset($context['new_status'])) {
      $ticketStatus = $this->mapStatusIdToName($context['new_status']);
      $statusFromLog = true;
    } elseif (isset($context['tick_status'])) {
      $ticketStatus = $this->mapStatusIdToName($context['tick_status']);
      $statusFromLog = true;
    } elseif (isset($context['ticket_status'])) {
      // For old logs that have ticket_status as string
      $ticketStatus = $context['ticket_status'];
      $statusFromLog = true;
    }

    // For TICKET_CREATED, always status 1
    if ($type === 'TICKET_CREATED') {
      $ticketStatus = 'Aduan Baru';
      $statusFromLog = true;
    }

    // For TICKET_ASSIGNED: Detect based on level and presence of old_status
    if ($type === 'TICKET_ASSIGNED') {
      // Initial assignment (level 1, no old_status) = Status 1 (Aduan Baru)
      if (isset($context['level']) && $context['level'] == 1 && !isset($context['old_status'])) {
        $ticketStatus = 'Aduan Baru';
        $statusFromLog = true;
      }
      // If has new_status, use that (for status 3 or 4 assignments)
      elseif (isset($context['new_status'])) {
        $ticketStatus = $this->mapStatusIdToName($context['new_status']);
        $statusFromLog = true;
      }
    }

    // Fetch actual data from database tables
    $recipientName = null;
    $creatorName = null;
    $creatorEmail = null;
    $assignedOfficerName = null;
    $assignedOfficerEmail = null;
    $chronologies = [];

    // Priority 1: Use recipient_name from context if available (for TICKET_CREATED and TICKET_CLOSED to owner)
    if (isset($context['recipient_name'])) {
      $recipientName = $context['recipient_name'];
    }

    if ($ticket) {
      // Get ticket data with creator and latest assigned officer
      $ticketData = DB::table('tickets')
        ->leftJoin('users as creator', 'tickets.user_id', '=', 'creator.id')
        ->leftJoin('tickets_chronologies', function ($join) {
          $join->on('tickets.id', '=', 'tickets_chronologies.ticket_id')
            ->whereNull('tickets_chronologies.deleted_at')
            ->whereRaw('tickets_chronologies.id = (
              SELECT MAX(tc.id)
              FROM tickets_chronologies tc
              WHERE tc.ticket_id = tickets.id
              AND tc.deleted_at IS NULL
            )');
        })
        ->leftJoin('users as officer', 'tickets_chronologies.escalate_to', '=', 'officer.id')
        ->leftJoin('ref_ticket_statuses', 'tickets_chronologies.tick_status', '=', 'ref_ticket_statuses.id')
        ->where('tickets.ticket_nolog', $ticket)
        ->whereNull('tickets.deleted_at')
        ->select(
          'tickets.id as ticket_id',
          'creator.nama as creator_name',
          'creator.email as creator_email',
          'officer.nama as assigned_officer_name',
          'officer.email as officer_email',
          'ref_ticket_statuses.st_name as current_ticket_status'
        )
        ->first();

      if ($ticketData) {
        $creatorName = $ticketData->creator_name;
        $creatorEmail = $ticketData->creator_email;
        $assignedOfficerName = $ticketData->assigned_officer_name;
        $assignedOfficerEmail = $ticketData->officer_email;

        // Determine the correct unmasked email based on email type and recipient name
        // Match recipient name to find the correct email
        if ($recipientName) {
          // Try to find the matching user email by name
          if ($recipientName === $creatorName) {
            $email = $creatorEmail;
          } elseif ($recipientName === $assignedOfficerName) {
            $email = $assignedOfficerEmail;
          } else {
            // Search for user by name in the database
            $matchedUser = DB::table('users')
              ->where('nama', $recipientName)
              ->whereNull('deleted_at')
              ->value('email');
            $email = $matchedUser ?? $context['recipient_email'] ?? null;
          }
        } else {
          // Fallback logic based on email type
          if (in_array($type, ['TICKET_CREATED']) || ($type === 'TICKET_CLOSED' && !isset($context['level']))) {
            // Email to ticket owner (creator)
            $email = $creatorEmail;
            $recipientName = $creatorName;
          } else {
            // Email to assigned officer
            $email = $assignedOfficerEmail;
            $recipientName = $assignedOfficerName;
          }
        }

        // Final fallback to masked email if unmasked email is not available
        if (!$email) {
          $email = $context['recipient_email'] ?? null;
        }

        // Only use current status if we don't have historical status from log
        if (!$statusFromLog) {
          $ticketStatus = $ticketData->current_ticket_status;
        }

        // Get all chronologies for this ticket (status 1 to 6)
        $chronologies = DB::table('tickets_chronologies')
          ->join('ref_ticket_statuses', 'tickets_chronologies.tick_status', '=', 'ref_ticket_statuses.id')
          ->leftJoin('users', 'tickets_chronologies.escalate_to', '=', 'users.id')
          ->where('tickets_chronologies.ticket_id', $ticketData->ticket_id)
          ->whereIn('tickets_chronologies.tick_status', [1, 2, 3, 4, 5, 6]) // Status 1-6 only
          ->whereNull('tickets_chronologies.deleted_at')
          ->select(
            'ref_ticket_statuses.st_name',
            'ref_ticket_statuses.id as status_id',
            'users.nama as officer_name',
            'tickets_chronologies.created_at'
          )
          ->orderBy('tickets_chronologies.created_at', 'asc')
          ->get()
          ->toArray();
      }
    }

    return [
      'date' => $dt->format('d/m/Y'),
      'time' => $dt->format('H:i:s'),
      'ticket' => $ticket ?? '-',
      'email' => $email,
      'type' => str_replace('SLA_', '', $type), // REMINDER or OVERDUE
      'status' => $this->mapMailStatus($status),
      'level' => $level,
      'message' => $message,
      'info' => $message, // Add info field for display
      'context' => $context,
      'recipient_name' => $recipientName,
      'recipient_email' => $email, // Add recipient_email field
      'ticket_status' => $ticketStatus,
      'creator_name' => $creatorName,
      'creator_email' => $creatorEmail ?? null,
      'assigned_officer_name' => $assignedOfficerName,
      'assigned_officer_email' => $assignedOfficerEmail ?? null,
      'chronologies' => $chronologies,
      'tick_status' => $context['tick_status'] ?? $context['new_status'] ?? $context['old_status'] ?? null
    ];
  }

  /**
   * Extract ticket number from message text
   *
   * @param string $message
   * @return string|null
   */
  private function extractTicketFromMessage(string $message): ?string
  {
    if (preg_match('/\b(TICKET-\d+|[A-Z]{2,}-\d{4}-\d{2}-\d{4})\b/', $message, $matches)) {
      return $matches[1];
    }
    return null;
  }

  /**
   * Format step name for display
   *
   * @param string $type
   * @param string $status
   * @return string
   */
  private function formatStepName(string $type, string $status): string
  {
    $typeMap = [
      'REMINDER' => 'Peringatan',
      'OVERDUE' => 'Lewat Tempoh',
      'COMMAND' => 'Arahan',
      'SCHEDULER' => 'Penjadual'
    ];

    $statusMap = [
      'START' => 'Mula',
      'QUERY' => 'Carian',
      'FOUND' => 'Dijumpai',
      'SENT' => 'Dihantar',
      'SENDING' => 'Menghantar',
      'SUCCESS' => 'Berjaya',
      'FAILED' => 'Gagal',
      'SKIPPED' => 'Dilangkau',
      'COMPLETE' => 'Selesai',
      'ERROR' => 'Ralat',
      'FAILURE' => 'Kegagalan'
    ];

    $typeText = $typeMap[$type] ?? $type;
    $statusText = $statusMap[$status] ?? $status;

    return "{$typeText} - {$statusText}";
  }

  /**
   * Map SLA status to display status
   *
   * @param string $status
   * @return string
   */
  private function mapSlaStatus(string $status): string
  {
    $statusMap = [
      'START' => 'STARTED',
      'QUERY' => 'QUERYING',
      'FOUND' => 'FOUND',
      'SENT' => 'SENT',
      'SENDING' => 'SENDING',
      'SUCCESS' => 'SUCCESS',
      'COMPLETE' => 'COMPLETED',
      'FAILED' => 'FAILED',
      'SKIPPED' => 'SKIPPED',
      'ERROR' => 'ERROR',
      'FAILURE' => 'FAILED'
    ];

    return $statusMap[$status] ?? $status;
  }

  /**
   * Map mail status to display status
   *
   * @param string $status
   * @return string
   */
  private function mapMailStatus(string $status): string
  {
    $statusMap = [
      'SENDING' => 'SENDING',
      'SUCCESS' => 'SENT',
      'FAILED' => 'FAILED'
    ];

    return $statusMap[$status] ?? $status;
  }

  /**
   * Get available SLA log dates
   *
   * @return array Array of dates with log files
   */
  public function getAvailableSlaLogDates(): array
  {
    $logPath = storage_path('logs');
    $dates = [];

    if (!is_dir($logPath)) {
      return $dates;
    }

    $files = scandir($logPath);
    foreach ($files as $file) {
      if (preg_match('/^sla-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
        $dates[] = [
          'date' => $matches[1],
          'formatted' => Carbon::parse($matches[1])->format('d/m/Y'),
          'file' => $file,
          'size' => filesize($logPath . '/' . $file)
        ];
      }
    }

    // Sort by date descending
    usort($dates, function ($a, $b) {
      return strcmp($b['date'], $a['date']);
    });

    return $dates;
  }

  /**
   * Get available Mail log dates
   *
   * @return array Array of dates with log files
   */
  public function getAvailableMailLogDates(): array
  {
    $logPath = storage_path('logs');
    $dates = [];

    if (!is_dir($logPath)) {
      return $dates;
    }

    $files = scandir($logPath);
    foreach ($files as $file) {
      if (preg_match('/^mail-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
        $dates[] = [
          'date' => $matches[1],
          'formatted' => Carbon::parse($matches[1])->format('d/m/Y'),
          'file' => $file,
          'size' => filesize($logPath . '/' . $file)
        ];
      }
    }

    // Sort by date descending
    usort($dates, function ($a, $b) {
      return strcmp($b['date'], $a['date']);
    });

    return $dates;
  }

  /**
   * Get log statistics
   *
   * @param array $logs
   * @return array
   */
  public function getLogStatistics(array $logs): array
  {
    $total = count($logs);
    $byStatus = [];

    foreach ($logs as $log) {
      $status = $log['status'] ?? 'UNKNOWN';
      $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
    }

    return [
      'total' => $total,
      'by_status' => $byStatus
    ];
  }
}
