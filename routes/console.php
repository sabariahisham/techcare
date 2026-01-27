<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule: Send due date reminders daily at 9:00 AM
// Checks for tickets with status "Aduan Baru" that are due tomorrow
// Sends email reminder to Level 1 officers (Pegawai Tugasan L1)
Schedule::command('tickets:send-duedate-reminders')
  ->dailyAt('09:00')
  ->timezone('Asia/Kuala_Lumpur')
  ->name('Send Ticket Due Date Reminders')
  ->description('Send email reminders for tickets due tomorrow')
  ->onSuccess(function () {
    \Illuminate\Support\Facades\Log::info('Due date reminder schedule executed successfully');
  })
  ->onFailure(function () {
    \Illuminate\Support\Facades\Log::error('Due date reminder schedule failed');
  });
