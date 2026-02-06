# Laravel Scheduler Cron Job Setup Guide

## 🖥️ LOCAL Testing (Windows)

### Method 1: Manual Run (Immediate Test)
```bash
# Test individual notification directly via API
curl -X POST http://localhost/api/sla/notifications/reminder \
  -H "Authorization: Bearer YOUR_TOKEN"

# Or test via controller directly
php artisan tinker
>>> app(\App\Http\Controllers\Notifications\TicketReminderController::class)->sendBeforeDueReminders();
```

### Method 2: Run Scheduler Manually
```bash
# Run all due scheduled tasks once
php artisan schedule:run

# Keep scheduler running (auto-run when time matches)
php artisan schedule:work
```

### Method 3: Windows Task Scheduler (Production-like on Windows)
1. Open **Task Scheduler**
2. Create new task: `Laravel Scheduler`
3. Trigger: Daily at specific time or every 1 minute
4. Action: Run program
   - Program: `C:\path\to\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\Users\sabariah.hisham\Herd\ehelp-fork_29122025`

---

## 🐧 SERVER (Linux/Ubuntu/Debian) - Production Setup

### Step 1: Access server via SSH
```bash
ssh user@your-server-ip
cd /path/to/your/laravel/project
```

### Step 2: Open crontab editor
```bash
crontab -e
```

### Step 3: Add Laravel scheduler cron job
Add this single line to crontab:

```cron
* * * * * cd /path/to/your/laravel/project && php artisan schedule:run >> /dev/null 2>&1
```

**Example for your project:**
```cron
* * * * * cd /var/www/ehelp && php artisan schedule:run >> /dev/null 2>&1
```

Or with logging:
```cron
* * * * * cd /var/www/ehelp && php artisan schedule:run >> /var/www/ehelp/storage/logs/scheduler.log 2>&1
```

### Step 4: Save and verify
```bash
# Save crontab (Ctrl+O, Enter, Ctrl+X for nano)

# Verify cron is added
crontab -l

# Check cron service is running
sudo systemctl status cron
# or for some systems:
sudo systemctl status crond
```

---

## 🔍 Troubleshooting

### Check if scheduler is working
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check scheduler log (if using logging)
tail -f storage/logs/scheduler.log
```

### Test scheduler immediately
```bash
# Force run all scheduled tasks now
php artisan schedule:run

# Test specific schedule
php artisan schedule:test
```

### Check mail configuration
```bash
# Check .env mail settings
cat .env | grep MAIL

# Test mail sending
php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### Common Issues

#### 1. Cron job not running
```bash
# Check cron service
sudo systemctl status cron
sudo systemctl restart cron

# Check cron logs
grep CRON /var/log/syslog
```

#### 2. PHP path issues
Find correct PHP path:
```bash
which php
# Use full path in crontab, e.g.:
* * * * * cd /var/www/ehelp && /usr/bin/php artisan schedule:run
```

#### 3. Permission issues
```bash
# Set correct permissions
sudo chown -R www-data:www-data /var/www/ehelp
sudo chmod -R 775 /var/www/ehelp/storage
sudo chmod -R 775 /var/www/ehelp/bootstrap/cache
```

#### 4. Environment variables not loaded
Add to crontab (at the top):
```cron
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
SHELL=/bin/bash

* * * * * cd /var/www/ehelp && php artisan schedule:run >> /dev/null 2>&1
```

---

## ⏰ Current Schedule Configuration

**File:** `routes/console.php`

### 1. SLA Reminder (1 day before due)
- **Time:** 7:30 AM daily
- **Timezone:** Asia/Kuala_Lumpur
- **Function:** Send notifications 1 working day BEFORE sla_duedate

### 2. After Due Notification (1 day after due)
- **Time:** 7:30 AM daily  
- **Timezone:** Asia/Kuala_Lumpur
- **Function:** Send notifications 1 working day AFTER sla_duedate

---

## 📧 Verify Mail Configuration

### Check .env settings:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@ehelp.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Test email manually:
```bash
php artisan tinker

# Test basic email
>>> \Illuminate\Support\Facades\Mail::raw('Test email from scheduler', function($msg) { 
...   $msg->to('test@mailtrap.io')->subject('Test'); 
... });

# Check if email was queued/sent
>>> exit
```

---

## 📝 Quick Reference

| Action | Command |
|--------|---------|
| List schedules | `php artisan schedule:list` |
| Run schedules now | `php artisan schedule:run` |
| Keep scheduler running | `php artisan schedule:work` |
| Test specific schedule | `php artisan schedule:test` |
| Check logs | `tail -f storage/logs/laravel.log` |
| Edit crontab (Linux) | `crontab -e` |
| View crontab | `crontab -l` |

---

## ✅ Checklist

### Local Testing
- [ ] Mail configuration correct in `.env`
- [ ] Test email sending works (`php artisan tinker`)
- [ ] Run `php artisan schedule:work` in terminal
- [ ] Check `storage/logs/laravel.log` for execution logs

### Server Production
- [ ] SSH access to server
- [ ] Cron job added to crontab (`crontab -e`)
- [ ] Cron service running (`systemctl status cron`)
- [ ] File permissions correct (775 on storage)
- [ ] PHP path correct in crontab
- [ ] Test with `php artisan schedule:run`
- [ ] Monitor logs for 24 hours

---

## 🎯 Expected Behavior

When properly configured:
1. Cron runs `schedule:run` **every minute**
2. Laravel checks scheduled tasks
3. At **7:30 AM** daily, both notifications are triggered
4. Emails sent to Pegawai Tugasan via Mailtrap
5. Logs written to `storage/logs/laravel.log`

---

**Last Updated:** February 5, 2026
