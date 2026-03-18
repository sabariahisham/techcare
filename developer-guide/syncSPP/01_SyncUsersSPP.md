# 📝 NOTA RINGKAS: SCHEDULED TASK - SYNC USERS FROM SPP

## 🎯 Tujuan
Sync data users dari database SPP (`spp_db.users`) ke database eHelp (`ehelp.users`) setiap hari pada pukul 2:00 pagi.

## 📌 Ringkasan Cepat
- **Command**: `php artisan sync:users-spp`
- **Schedule**: Daily at 2:00 AM (Asia/Kuala_Lumpur)
- **Status**: Conditional - Controlled by `ENABLE_SYNC_USERS_SPP` in `.env` (default: **disabled**)
- **Tracking**: All sync jobs are logged in `sync_jobs` table
- **Features**: Update existing users, insert new users, track statistics, error handling

---

## � Quick Start

### First Time Setup
```bash
# 1. Ensure SPP database connection is configured in .env
DB_SPP_CONNECTION=mysql
DB_SPP_HOST=127.0.0.1
DB_SPP_PORT=3306
DB_SPP_DATABASE=spp_db
DB_SPP_USERNAME=root
DB_SPP_PASSWORD=

# 2. Enable sync scheduler
ENABLE_SYNC_USERS_SPP=true

# 3. Clear config cache
php artisan config:clear

# 4. Test manually first
php artisan sync:users-spp

# 5. Verify sync_jobs table
php artisan tinker
>>> \App\Models\SyncJob::latest()->first()
```

### Quick Commands
```bash
# Run sync manually
php artisan sync:users-spp

# List all scheduled tasks
php artisan schedule:list

# Test if schedule would run now
php artisan schedule:test

# View recent sync history
php artisan tinker
>>> \App\Models\SyncJob::where('job_name', 'sync_users_spp')->latest()->take(5)->get()
```

---

## �📂 File Terlibat

### 1. **Command File**
```
c:\laragon\www\ehelp\app\Console\Commands\SyncUsersFromSpp.php
```
- Class: `SyncUsersFromSpp`
- Signature: `sync:users-spp`
- Function: Sync data users dari SPP ke eHelp
- Features:
  - ✅ Update existing users (by ID)
  - ✅ Insert new users
  - ✅ Track updated/inserted count via SyncJob model
  - ✅ Calculate execution time
  - ✅ Error handling & logging
  - ✅ Job status tracking (running/success/failed)

### 2. **Schedule Configuration**
```
c:\laragon\www\ehelp\routes\console.php
```
- Schedule Time: `02:00` (2:00 AM)
- Timezone: `Asia/Kuala_Lumpur`
- Runs: Daily (conditional)
- Logging: Success/Failure logs
- **Conditional Execution**: Wrapped in `if (config('app.enable_sync_users_spp'))`

### 3. **Service Provider**
```
c:\laragon\www\ehelp\app\Providers\ConsoleServiceProvider.php
```
- Register command manually
- Load commands from `app/Console/Commands`

### 4. **SyncJob Model**
```
c:\laragon\www\ehelp\app\Models\SyncJob.php
```
- Table: `sync_jobs`
- Tracks sync job execution details:
  - `job_name` - Name of sync job ('sync_users_spp')
  - `status` - Job status (running/success/failed)
  - `started_at` - Job start timestamp
  - `finished_at` - Job finish timestamp
  - `total_records` - Total records processed
  - `updated_records` - Count of updated records
  - `inserted_records` - Count of inserted records
  - `execution_time` - Time taken in seconds
  - `message` - Success/error message

### 5. **Configuration Files**
```
c:\laragon\www\ehelp\config\app.php
c:\laragon\www\ehelp\config\database.php
c:\laragon\www\ehelp\.env
```
- Config: `app.enable_sync_users_spp` (default: false)
- Env: `ENABLE_SYNC_USERS_SPP` (true/false)
- Connection: `spp` (SPP database)
- Connection: `mysql` (eHelp database)

---

## 🔧 Command Usage

### Manual Run
```bash
# Run sync command manually
php artisan sync:users-spp

# Output example:
# Mula sync dari spp_db.users ke ehelp.users ...
# Jumlah rekod dijumpai: 155
# ✅ Sync selesai!
# 📊 Updated: 150 records
# 📊 Inserted: 5 records
# ⏱️  Masa diambil: 2.47 saat
```

### Test Schedule
```bash
# List all scheduled tasks
php artisan schedule:list

# Test schedule (dry run)
php artisan schedule:test

# Run all due schedules once
php artisan schedule:run

# Run schedule continuously (development)
php artisan schedule:work
```

---

## ⚙️ Scheduled Tasks

| Task | Time | Timezone | Status | Description |
|------|------|----------|--------|-------------|
| `sync:users-spp` | 02:00 AM | Asia/Kuala_Lumpur | Conditional* | Sync users from SPP to eHelp daily |
| `sla:send-notifications` | 07:30 AM | Asia/Kuala_Lumpur | Active | Send SLA reminder & overdue notifications |

**\*Conditional**: Requires `ENABLE_SYNC_USERS_SPP=true` in `.env` file to run.

### Schedule Control
The sync scheduler uses conditional execution:
```php
// In routes/console.php
if (config('app.enable_sync_users_spp')) {
  Schedule::command('sync:users-spp')
    ->dailyAt('2:00')
    ->timezone('Asia/Kuala_Lumpur');
}
```

This means:
- ❌ Default behavior: Schedule does NOT run (safety feature)
- ✅ To enable: Set `ENABLE_SYNC_USERS_SPP=true` in `.env`
- 🔄 After changing: Run `php artisan config:clear`

---

## � SyncJob Tracking System

### Database Table: `sync_jobs`
Every sync execution is tracked in the `sync_jobs` table with the following information:

| Field | Type | Description |
|-------|------|-------------|
| `id` | Integer | Primary key |
| `job_name` | String | Name of the sync job ('sync_users_spp') |
| `status` | Enum | running / success / failed |
| `started_at` | Timestamp | When the job started |
| `finished_at` | Timestamp | When the job finished |
| `total_records` | Integer | Total records processed |
| `updated_records` | Integer | Count of updated records |
| `inserted_records` | Integer | Count of inserted records |
| `execution_time` | Float | Time taken in seconds |
| `message` | Text | Success/error message |
| `created_at` | Timestamp | Record creation time |
| `updated_at` | Timestamp | Record update time |

### Job Lifecycle
1. **START**: Create record with `status='running'`, `started_at=now()`
2. **PROCESSING**: Sync users from SPP to eHelp
3. **SUCCESS**: Update record with `status='success'`, stats, and time
4. **FAILURE**: Update record with `status='failed'`, error message, and time

### Query Examples
```sql
-- Check recent sync jobs
SELECT * FROM sync_jobs WHERE job_name='sync_users_spp' ORDER BY id DESC LIMIT 10;

-- Check failed jobs
SELECT * FROM sync_jobs WHERE status='failed' AND job_name='sync_users_spp';

-- Get sync statistics
SELECT 
  DATE(started_at) as date,
  COUNT(*) as total_runs,
  SUM(updated_records) as total_updated,
  SUM(inserted_records) as total_inserted,
  AVG(execution_time) as avg_time
FROM sync_jobs 
WHERE job_name='sync_users_spp' AND status='success'
GROUP BY DATE(started_at);
```

---

## 🗄️ Database Sync Logic

### Data Flow
```
spp_db.users → [sync:users-spp] → ehelp.users
                      ↓
                 sync_jobs (tracking)
```

### Sync Process
1. Create SyncJob record with status 'running'
2. Connect to SPP database (`DB::connection('spp')`)
3. Fetch all users from `spp_db.users`
4. For each user:
   - Check if user exists in `ehelp.users` by `id`
   - If exists: **UPDATE** record
   - If not exists: **INSERT** new record
5. Update SyncJob record:
   - Status: 'success' or 'failed'
   - Total records, updated count, inserted count
   - Execution time and message
6. Log results to `storage/logs/laravel.log`

### Fields Synced
```php
'uuid', 'id_pgn_ldap', 'email', 'email_verified_at', 'password', 
'nama', 'no_kp', 'kodjantina', 'kod_skim', 'kod_kump_khidmat', 
'kod_jwtn', 'kod_jawatan', 'nama_gred', 'tel_no_p', 'tel_no_hp', 
'kod_sektor', 'kod_bah', 'kod_sec', 'kod_unit', 'remember_token', 
'tarikh_lapormof', 'wf_ramadhan', 'hiraki', 'lastlogin_at', 
'kodaktif', 'dataaktif', 'hadir_kurang_jam', 'created_by', 
'created_at', 'updated_by', 'updated_at', 'deleted_by', 'deleted_at'
```

---

## ⚙️ Configuration

### Enable/Disable Scheduler

By default, the sync scheduler is **DISABLED**. You need to enable it via environment variable.

**File: `.env`**
```env
# Set to true to enable sync scheduler, false to disable
ENABLE_SYNC_USERS_SPP=false
```

**File: `config/app.php`**
```php
'enable_sync_users_spp' => env('ENABLE_SYNC_USERS_SPP', false),
```

### How It Works
- If `ENABLE_SYNC_USERS_SPP=false` → Schedule will NOT run
- If `ENABLE_SYNC_USERS_SPP=true` → Schedule runs daily at 2:00 AM
- Allows selective testing of schedulers without running all tasks

**Example Use Cases:**
```bash
# Test sync users only
ENABLE_SYNC_USERS_SPP=true
ENABLE_SYNC_REFS_SPP=false
ENABLE_SLA_NOTIFICATION=false

# Enable all schedulers for production
ENABLE_SYNC_USERS_SPP=true
ENABLE_SYNC_REFS_SPP=true
ENABLE_SLA_NOTIFICATION=true
```

---

## 📊 Logging

### Laravel Logs (`storage/logs/laravel.log`)

#### Success Log
```
[2026-03-18 02:00:15] local.INFO: 
Users sync completed - Updated: 150, Inserted: 5, Time: 2.47s
```

#### Scheduler Success
```
[2026-03-18 02:00:15] local.INFO: 
✓ Users sync from SPP executed successfully at 2026-03-18 02:00:15
```

#### Scheduler Failure
```
[2026-03-18 02:00:15] local.ERROR: 
✗ Users sync from SPP failed at 2026-03-18 02:00:15
```

#### Error Log
```
[2026-03-18 02:00:15] local.ERROR: 
Users sync failed: SQLSTATE[HY000] [2002] Connection refused (Time: 0.53s)
```

### Database Tracking (`sync_jobs` table)

All sync executions are automatically tracked in the `sync_jobs` table:

```sql
-- View recent sync history
SELECT 
  id,
  status,
  started_at,
  finished_at,
  total_records,
  updated_records,
  inserted_records,
  execution_time,
  message
FROM sync_jobs 
WHERE job_name = 'sync_users_spp'
ORDER BY id DESC 
LIMIT 10;
```

**Example Output:**
| id | status | started_at | finished_at | total | updated | inserted | time | message |
|----|--------|------------|-------------|-------|---------|----------|------|---------|
| 25 | success | 2026-03-18 02:00:10 | 2026-03-18 02:00:12 | 155 | 150 | 5 | 2.47 | Sync completed successfully |
| 24 | success | 2026-03-17 02:00:10 | 2026-03-17 02:00:12 | 155 | 155 | 0 | 2.31 | Sync completed successfully |
| 23 | failed | 2026-03-16 02:00:10 | 2026-03-16 02:00:11 | 0 | 0 | 0 | 0.53 | SQLSTATE[HY000] [2002] Connection refused |

### Monitoring Commands
```bash
# View logs in real-time
tail -f storage/logs/laravel.log | grep "sync"

# Check sync_jobs table
php artisan tinker
>>> \App\Models\SyncJob::where('job_name', 'sync_users_spp')->latest()->first()

# Get sync statistics
>>> \App\Models\SyncJob::where('job_name', 'sync_users_spp')
    ->where('status', 'success')
    ->selectRaw('
        COUNT(*) as total_runs,
        AVG(execution_time) as avg_time,
        SUM(updated_records) as total_updated,
        SUM(inserted_records) as total_inserted
    ')
    ->first()
```

---

## 🚀 Production Setup

**⚠️ IMPORTANT**: Before setting up cron/scheduler, enable the sync in `.env`:
```env
ENABLE_SYNC_USERS_SPP=true
```

Then clear the config cache:
```bash
php artisan config:clear
```

### Linux Server (Crontab)
```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /var/www/ehelp && php artisan schedule:run >> /dev/null 2>&1
```

### Windows Server (Task Scheduler)
1. Open Task Scheduler
2. Create Basic Task: `Laravel Schedule Runner`
3. Trigger: Daily, repeat every 1 minute
4. Action: Start a program
   - Program: `C:\laragon\bin\php\php-8.x\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\laragon\www\ehelp`

**Note**: The cron/scheduler will only execute `sync:users-spp` if `ENABLE_SYNC_USERS_SPP=true` is set.

---

## ✅ Checklist

- [x] Command created: `SyncUsersFromSpp.php`
- [x] Command registered in `ConsoleServiceProvider.php`
- [x] Schedule configured in `console.php` (2:00 AM daily)
- [x] SyncJob model created for tracking
- [x] Database connections configured (SPP + eHelp)
- [x] Configuration flag added (ENABLE_SYNC_USERS_SPP)
- [x] Logging enabled (success/failure)
- [x] Execution time tracking
- [x] Error handling implemented
- [x] Job status tracking (running/success/failed)

---

## � Best Practices

### 1. Development vs Production
```env
# Development - disable schedulers during local development
ENABLE_SYNC_USERS_SPP=false
ENABLE_SYNC_REFS_SPP=false
ENABLE_SLA_NOTIFICATION=false

# Production - enable as needed
ENABLE_SYNC_USERS_SPP=true
ENABLE_SYNC_REFS_SPP=true
ENABLE_SLA_NOTIFICATION=true
```

### 2. Testing Before Production
```bash
# Always test manually first
php artisan sync:users-spp

# Check if data synced correctly
php artisan tinker
>>> DB::table('users')->count()
>>> \App\Models\SyncJob::latest()->first()

# Verify no errors in logs
tail -f storage/logs/laravel.log
```

### 3. Monitoring Recommendations
- **Daily**: Check `sync_jobs` table for failed jobs
- **Weekly**: Review average execution time for performance issues
- **Monthly**: Clean up old sync_jobs records (keep 90 days)

```sql
-- Delete sync_jobs older than 90 days
DELETE FROM sync_jobs 
WHERE job_name = 'sync_users_spp' 
AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### 4. Performance Tips
- Run at 2:00 AM (low traffic period)
- Monitor execution time (typical: 2-3 seconds for 150 users)
- If execution time > 10 seconds, check database indexes
- Consider adding indexes on `users(id)` if not exists

### 5. Error Handling
- Failed jobs are logged in both `laravel.log` and `sync_jobs` table
- Status remains 'failed' in sync_jobs for easy identification
- Check `message` field in sync_jobs for error details
- Set up alerts for failed jobs in production

---

## �🔍 Troubleshooting

### Problem: Command not found
**Solution:**
```bash
php artisan optimize:clear
php artisan config:clear
composer dump-autoload
```

### Problem: Database connection failed
**Solution:**
- Check `.env` file (DB_SPP_* settings)
- Test connection: `php artisan tinker` → `DB::connection('spp')->table('users')->count()`

### Problem: Schedule not running
**Solution:**
- Check if enabled in .env: `ENABLE_SYNC_USERS_SPP=true`
- Clear config cache: `php artisan config:clear`
- Check cron job is active: `crontab -l`
- Run manually: `php artisan schedule:run`
- Check logs: `storage/logs/laravel.log`
- Check sync_jobs table: `SELECT * FROM sync_jobs ORDER BY id DESC LIMIT 10;`

### Problem: Sync job stuck in 'running' status
**Solution:**
```sql
-- Check for stuck jobs
SELECT * FROM sync_jobs WHERE status='running' AND started_at < NOW() - INTERVAL 1 HOUR;

-- Manually mark as failed (if needed)
UPDATE sync_jobs SET status='failed', message='Job timeout', finished_at=NOW() WHERE id=xxx;
```

### Problem: Sync successful but no records updated
**Solution:**
- Check if SPP database has data: `php artisan tinker` → `DB::connection('spp')->table('users')->count()`
- Verify SPP connection is correct in `.env`
- Check if user IDs match between SPP and eHelp databases
- Review sync_jobs table for execution details

---

## 🧪 Testing & Debugging

### Manual Testing
```bash
# 1. Enable sync in .env (if disabled)
ENABLE_SYNC_USERS_SPP=true

# 2. Clear config cache
php artisan config:clear

# 3. Run command manually
php artisan sync:users-spp

# 4. Check sync_jobs table
php artisan tinker
>>> \App\Models\SyncJob::latest()->first()
>>> \App\Models\SyncJob::where('job_name', 'sync_users_spp')->latest()->get()

# 5. Test scheduler
php artisan schedule:list
php artisan schedule:test
```

### Debugging Tips
```bash
# View last 5 sync jobs with details
php artisan tinker
>>> \App\Models\SyncJob::where('job_name', 'sync_users_spp')->latest()->take(5)->get()

# Check SPP connection
>>> DB::connection('spp')->getPdo()
>>> DB::connection('spp')->table('users')->count()

# Check eHelp users count
>>> DB::table('users')->count()

# View sync execution times
>>> \App\Models\SyncJob::where('status', 'success')->avg('execution_time')
```

---

## 📞 Support
Created by: GitHub Copilot  
Last Updated: 18 March 2026  
Version: Laravel 12