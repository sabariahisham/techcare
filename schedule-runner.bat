@echo off
REM ============================================================================
REM Laravel Scheduler Runner for Windows
REM ============================================================================
REM This batch file runs Laravel's task scheduler every minute
REM It should be added to Windows Task Scheduler
REM ============================================================================

cd /d "C:\Users\sabariah.hisham\Herd\ehelp-fork_29122025"
php artisan schedule:run >> NUL 2>&1
