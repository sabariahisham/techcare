<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ============================================================================
// SLA NOTIFICATION SYSTEM (SIMPLIFIED - 2 TYPES ONLY)
// ============================================================================
// Daily at 7:30 AM - Send BOTH reminder and overdue notifications
//
// 1. REMINDER: 1 working day BEFORE sla_duedate (ALL statuses except 5=Batal, 6=Selesai)
//    - Checks: tickets_chronologies.sla_duedate = tomorrow (working day)
//    - Email: sla-reminder.blade.php
//    - Recipient: escalate_to (assigned officer)
//
// 2. OVERDUE: 1 working day AFTER sla_duedate (ALL statuses except 5=Batal, 6=Selesai)
//    - Checks: tickets_chronologies.sla_duedate = yesterday (working day)
//    - Email: sla-overdue.blade.php
//    - Recipient: escalate_to (assigned officer)
//
// Features:
// - NO QUEUE (direct email sending)
// - Working days calculation (skip weekends & public holidays)
// - Uses tickets_chronologies.sla_duedate ONLY
// - Single unified command
// ============================================================================

Schedule::command('sla:send-notifications --type=all')
  ->dailyAt('07:30')
  ->timezone('Asia/Kuala_Lumpur')
  ->skip(function () {
    // Skip weekends - no need to send emails on Saturday and Sunday
    $today = \Carbon\Carbon::now('Asia/Kuala_Lumpur');
    $isWeekend = $today->isSaturday() || $today->isSunday();

    if ($isWeekend) {
      \Illuminate\Support\Facades\Log::channel('sla')->info('[SLA][SCHEDULER][SKIPPED] Skipped execution on weekend', [
        'day' => $today->format('l'),
        'date' => $today->format('Y-m-d'),
        'reason' => 'Weekend - No email sending on Saturday/Sunday'
      ]);
    }

    return $isWeekend;
  })
  ->name('Send SLA Notifications (Reminder & Overdue)')
  ->description('Send SLA reminder (-1 working day) and overdue (+1 working day) notifications at 7:30 AM daily (weekdays only)')
  ->onSuccess(function () {
    \Illuminate\Support\Facades\Log::channel('sla')->info('[SLA][SCHEDULER][SUCCESS] Scheduled task executed successfully', [
      'task' => 'sla:send-notifications',
      'executed_at' => now()->toDateTimeString(),
      'timezone' => 'Asia/Kuala_Lumpur'
    ]);
  })
  ->onFailure(function () {
    \Illuminate\Support\Facades\Log::channel('sla')->error('[SLA][SCHEDULER][FAILURE] Scheduled task failed', [
      'task' => 'sla:send-notifications',
      'failed_at' => now()->toDateTimeString(),
      'timezone' => 'Asia/Kuala_Lumpur'
    ]);
  });


// ============================================================================
// SYNC USERS FROM SPP DATABASE
// ============================================================================
// Daily at 2:00 AM - Sync users data from spp_db.users to ehelp.users
// Features:
// - Runs every day at 2:00 AM
// - Syncs all users from SPP database to eHelp database
// - Uses updateOrInsert to prevent duplicates
// ============================================================================

//if config default false, the scheduler will be disabled and won't run. To enable, set ENABLE_SYNC_USERS_SPP=true in .env file
if (config('app.enable_sync_users_spp')) {
  Schedule::command('sync:users-spp')
    ->dailyAt('2:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->name('Sync Users from SPP Database')
    ->description('Sync users data from spp_db.users to ehelp.users at 2:00 AM daily')
    ->onSuccess(function () {
      \Illuminate\Support\Facades\Log::info('✓ Users sync from SPP executed successfully at ' . now()->toDateTimeString());
    })
    ->onFailure(function () {
      \Illuminate\Support\Facades\Log::error('✗ Users sync from SPP failed at ' . now()->toDateTimeString());
    });
}
 
