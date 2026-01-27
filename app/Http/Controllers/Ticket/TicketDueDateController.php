<?php

/*
 * TicketDueDateController.php
 *
 * Controller to handle ticket due date calculations and reminders.
 * Includes methods to calculate due dates, reminder dates, and send email notifications.
 */

namespace App\Http\Controllers\Ticket;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Notifications\Ticket\DueDateNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * TicketDueDateController
 *
 * Handles due date calculation and reminder notifications for tickets
 */
class TicketDueDateController extends Controller
{
  /**
   * Calculate ticket due date based on creation date
   * Due date = created_at + 3 working days (excluding weekends and Malaysian public holidays)
   *
   * @param Carbon|null $createdAt Starting date (defaults to now)
   * @return Carbon Calculated due date
   */
  public function calculateTicketDueDate(?Carbon $createdAt = null): Carbon
  {
    $createdAt = $createdAt ?? Carbon::now();
    return Helpers::addWorkingDays($createdAt, 3);
  }

  /**
   * Calculate reminder date for due date notification
   * Reminder date = due_date - 1 working day
   *
   * @param Carbon $dueDate Ticket due date
   * @return Carbon Reminder date (1 working day before due date)
   */
  public function calculateReminderDate(Carbon $dueDate): Carbon
  {
    $reminderDate = $dueDate->copy();
    $daysSubtracted = 0;

    // Subtract days until we have 1 working day before due date
    while ($daysSubtracted < 1) {
      $reminderDate->subDay();

      // Skip weekends
      if ($reminderDate->dayOfWeek === Carbon::SATURDAY || $reminderDate->dayOfWeek === Carbon::SUNDAY) {
        continue;
      }

      // Skip public holidays
      if (Helpers::isPublicHoliday($reminderDate)) {
        continue;
      }

      $daysSubtracted++;
    }

    return $reminderDate;
  }

  /**
   * Send due date reminder emails to Level 1 officers
   * Called by cronjob daily at 9:00 AM
   *
   * Criteria:
   * - Ticket status = 1 (Aduan Baru)
   * - Today = 1 working day before due date
   * - Has assigned Level 1 officer
   *
   * @return JsonResponse Summary of reminder process
   */
  public function sendDueDateReminders(): JsonResponse
  {
    try {
      Log::info('Starting ticket due date reminder process');

      $today = Carbon::today();
      $tomorrow = Carbon::tomorrow();

      // Find tickets where:
      // 1. Status = 1 (Aduan Baru)
      // 2. Due date is tomorrow (which means today is reminder date)
      // 3. Has tick_duedate set
      $tickets = Ticket::where('tick_status', 1)
        ->whereNotNull('tick_duedate')
        ->whereDate('tick_duedate', $tomorrow)
        ->with(['user', 'category', 'subCategory', 'status'])
        ->get();

      if ($tickets->isEmpty()) {
        Log::info('No tickets found requiring due date reminder today', [
          'date' => $today->format('Y-m-d'),
          'checking_for_duedate' => $tomorrow->format('Y-m-d')
        ]);

        return response()->json([
          'success' => true,
          'message' => 'No tickets found requiring reminder',
          'data' => [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0
          ]
        ]);
      }

      $sentCount = 0;
      $failedCount = 0;
      $skippedCount = 0;

      foreach ($tickets as $ticket) {
        try {
          // Get latest chronology entry with level 1 officer assignment
          $chronology = TicketChronology::where('ticket_id', $ticket->id)
            ->where('level_pegtugas_id', 1)
            ->whereNotNull('escalate_to')
            ->with('escalatedTo')
            ->latest()
            ->first();

          // Skip if no Level 1 officer assigned
          if (!$chronology || !$chronology->escalatedTo) {
            Log::warning('Ticket has no Level 1 officer assigned', [
              'ticket_id' => $ticket->id,
              'ticket_nolog' => $ticket->ticket_nolog
            ]);
            $skippedCount++;
            continue;
          }

          $officer = $chronology->escalatedTo;

          // Skip if officer has no email
          if (empty($officer->email)) {
            Log::warning('Officer has no email address', [
              'ticket_id' => $ticket->id,
              'ticket_nolog' => $ticket->ticket_nolog,
              'officer_id' => $officer->id,
              'officer_name' => $officer->nama ?? 'Unknown'
            ]);
            $skippedCount++;
            continue;
          }

          // Send email notification to officer
          $officer->notify(new DueDateNotification($ticket));

          $sentCount++;

          Log::info('Due date reminder sent successfully', [
            'ticket_id' => $ticket->id,
            'ticket_nolog' => $ticket->ticket_nolog,
            'officer_id' => $officer->id,
            'officer_name' => $officer->nama ?? 'Unknown',
            'officer_email' => $officer->email,
            'due_date' => $ticket->tick_duedate->format('Y-m-d'),
            'reminder_date' => $today->format('Y-m-d')
          ]);
        } catch (\Exception $e) {
          $failedCount++;
          Log::error('Failed to send due date reminder', [
            'ticket_id' => $ticket->id,
            'ticket_nolog' => $ticket->ticket_nolog,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
          ]);
        }
      }

      $summary = [
        'total' => $tickets->count(),
        'sent' => $sentCount,
        'failed' => $failedCount,
        'skipped' => $skippedCount,
        'date' => $today->format('Y-m-d'),
        'checking_for_duedate' => $tomorrow->format('Y-m-d')
      ];

      Log::info('Due date reminder process completed', $summary);

      return response()->json([
        'success' => true,
        'message' => "Sent {$sentCount} reminder(s) successfully",
        'data' => $summary
      ]);
    } catch (\Exception $e) {
      Log::error('Error in due date reminder process', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Error processing due date reminders',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Manual trigger for testing due date reminders
   * GET /api/tickets/test-due-date-reminders
   *
   * @return JsonResponse
   */
  public function testDueDateReminders(): JsonResponse
  {
    Log::info('Manual test of due date reminder triggered');
    return $this->sendDueDateReminders();
  }
}
