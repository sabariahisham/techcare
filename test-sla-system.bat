@echo off
REM ============================================================================
REM Quick Test Script for SLA System
REM ============================================================================
echo.
echo ============================================================================
echo  SLA SYSTEM TEST
echo ============================================================================
echo.

cd /d "C:\Users\sabariah.hisham\Herd\ehelp-fork_29122025"

echo [1/4] Checking scheduled tasks...
php artisan schedule:list | findstr "sla:send-notifications"
echo.

echo [2/4] Running SLA notifications manually...
php artisan sla:send-notifications --type=all
echo.

echo [3/4] Checking today's log file...
if exist "storage\logs\sla-%date:~0,4%-%date:~5,2%-%date:~8,2%.log" (
    echo ✓ Log file exists!
    echo.
    echo Last 10 lines:
    powershell -Command "Get-Content 'storage\logs\sla-*' | Select-Object -Last 10"
) else (
    echo ✗ Log file not found
)
echo.

echo [4/4] Checking Task Scheduler status...
powershell -Command "Get-ScheduledTask -TaskName 'Laravel Scheduler*' -ErrorAction SilentlyContinue | Select-Object TaskName, State, NextRunTime"
echo.

echo ============================================================================
echo  TEST COMPLETE
echo ============================================================================
echo.
pause
