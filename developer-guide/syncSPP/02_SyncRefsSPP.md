# 📝 NOTA RINGKAS: SCHEDULED TASK - SYNC REFERENCE DATA FROM SPP

## 🎯 Tujuan
Sync data rujukan (reference tables) dari database SPP ke database eHelp setiap 4 bulan sekali pada hari pertama bulan, pukul 3:00 pagi.

## 📌 Ringkasan Cepat
- **Command**: `php artisan app:sync-refs-data`
- **Schedule**: Quarterly - 1st day of every 4 months at 3:00 AM (Asia/Kuala_Lumpur)
- **Status**: Conditional - Controlled by `ENABLE_SYNC_REFS_SPP` in `.env` (default: **disabled**)
- **Tracking**: All sync jobs logged in `sync_jobs` + detailed tracking in `sync_job_details`
- **Features**: Sync 8 reference tables, dependency validation, selective sync, error handling

---

## 🚀 Quick Start

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
ENABLE_SYNC_REFS_SPP=true

# 3. Clear config cache
php artisan config:clear

# 4. Test manually first (sync all tables)
php artisan app:sync-refs-data --all

# 5. Or sync specific table
php artisan app:sync-refs-data sektor

# 6. Verify sync_jobs and sync_job_details tables
php artisan tinker
>>> \App\Models\SyncJob::latest()->first()
>>> \App\Models\SyncJobDetail::where('sync_job_id', 1)->get()
```

### Quick Commands
```bash
# Sync all reference tables
php artisan app:sync-refs-data --all

# Sync specific table
php artisan app:sync-refs-data sektor
php artisan app:sync-refs-data bahagian
php artisan app:sync-refs-data jawatan

# List available tables
# - sektor
# - bahagian  
# - seksyen
# - unit
# - jawatan
# - khidmat
# - gred
# - skim

# List all scheduled tasks
php artisan schedule:list

# View recent sync history
php artisan tinker
>>> \App\Models\SyncJob::where('job_name', 'sync_refs_spp')->latest()->first()
>>> \App\Models\SyncJobDetail::latest()->take(10)->get()
```

---

## 📂 File Terlibat

### 1. **Command File**
```
c:\laragon\www\ehelp\app\Console\Commands\SyncRefsData.php
```
- Class: `SyncRefsData`
- Signature: `app:sync-refs-data {table?} {--all}`
- Function: Sync reference data dari SPP ke eHelp
- Features:
  - ✅ Sync 8 reference tables (sektor, bahagian, seksyen, unit, jawatan, khidmat, gred, skim)
  - ✅ Selective sync (one table at a time)
  - ✅ Bulk sync (all tables with --all option)
  - ✅ Dependency validation (e.g., seksyen requires bahagian to exist)
  - ✅ Skip empty/null records
  - ✅ Track insert/update counts per table
  - ✅ Detailed logging with SyncJobDetail
  - ✅ Error handling & recovery

### 2. **Schedule Configuration**
```
c:\laragon\www\ehelp\routes\console.php
```
- Schedule Time: `03:00` (3:00 AM)
- Schedule Pattern: `0 3 1 */4 *` (1st day of every 4 months)
- Timezone: `Asia/Kuala_Lumpur`
- Runs: Quarterly (January 1, May 1, September 1)
- Option: `withoutOverlapping()` to prevent concurrent runs
- **Conditional Execution**: Wrapped in `if (config('app.enable_sync_refs_spp'))`

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
- Tracks overall sync job execution:
  - `job_name` - Name of sync job ('sync_refs_spp')
  - `status` - Job status (running/success/failed)
  - `started_at` - Job start timestamp
  - `finished_at` - Job finish timestamp
  - `execution_time` - Total time taken in seconds
  - `message` - Success/error message

### 5. **SyncJobDetail Model**
```
c:\laragon\www\ehelp\app\Models\SyncJobDetail.php
```
- Table: `sync_job_details`
- Tracks detailed sync per table:
  - `sync_job_id` - Foreign key to sync_jobs
  - `table_name` - Name of table synced (e.g., 'sektor', 'bahagian')
  - `status` - Table sync status (success/failed)
  - `total_records` - Total records processed for this table
  - `inserted_records` - Count of new records inserted
  - `updated_records` - Count of existing records updated
  - `started_at` - Table sync start timestamp
  - `finished_at` - Table sync finish timestamp
  - `message` - Error message if failed

### 6. **Configuration Files**
```
c:\laragon\www\ehelp\config\app.php
c:\laragon\www\ehelp\config\database.php
c:\laragon\www\ehelp\.env
```
- Config: `app.enable_sync_refs_spp` (default: false)
- Env: `ENABLE_SYNC_REFS_SPP` (true/false)
- Connection: `spp` (SPP database)
- Connection: `mysql` (eHelp database)

---

## 🔧 Command Usage

### Sync All Tables
```bash
# Sync all 8 reference tables at once
php artisan app:sync-refs-data --all

# Output example:
# 🚀 Mula sync SPP reference...
# ✔ sektor: insert 5, update 10
# ✔ bahagian: insert 12, update 25
# ✔ seksyen: insert 8, update 30
# ✔ unit: insert 15, update 40
# ✔ jawatan: insert 3, update 20
# ✔ khidmat: insert 2, update 8
# ✔ gred: insert 4, update 15
# ✔ skim: insert 5, update 12
# ✅ Sync selesai
```

### Sync Specific Table
```bash
# Sync only sektor table
php artisan app:sync-refs-data sektor

# Sync only bahagian table
php artisan app:sync-refs-data bahagian

# Output example:
# 🚀 Mula sync SPP reference...
# ✔ sektor: insert 5, update 10
# ✅ Sync selesai
```

### Available Tables
```
sektor      - ref_sektor (department sectors)
bahagian    - ref_bahagian (departments)
seksyen     - ref_seksyen (sections, requires: sektor, bahagian)
unit        - ref_unit (units, requires: sektor, bahagian, seksyen)
jawatan     - ref_jawatan (positions)
khidmat     - ref_khidmat (service types)
gred        - ref_gred (grades)
skim        - ref_skim (schemes)
```

---

## ⚙️ Scheduled Tasks

| Task | Schedule | Timezone | Status | Description |
|------|----------|----------|--------|-------------|
| `app:sync-refs-data --all` | Quarterly, 1st day at 3:00 AM | Asia/Kuala_Lumpur | Conditional* | Sync all reference tables from SPP |

**\*Conditional**: Requires `ENABLE_SYNC_REFS_SPP=true` in `.env` file to run.

### Schedule Pattern Explanation
- **Cron**: `0 3 1 */4 *`
  - `0` - At minute 0
  - `3` - At hour 3 (3:00 AM)
  - `1` - On day 1 of the month
  - `*/4` - Every 4 months (January, May, September)
  - `*` - Every year

### Schedule Control
The sync scheduler uses conditional execution:
```php
// In routes/console.php
if (config('app.enable_sync_refs_spp')) {
  Schedule::command('app:sync-refs-data --all')
    ->cron('0 3 1 */4 *')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();
}
```

This means:
- ❌ Default behavior: Schedule does NOT run (safety feature)
- ✅ To enable: Set `ENABLE_SYNC_REFS_SPP=true` in `.env`
- 🔄 After changing: Run `php artisan config:clear`
- 🔒 withoutOverlapping: Prevents multiple sync jobs from running simultaneously

---

## 📊 Tracking System

### 1. SyncJob Table (`sync_jobs`)
Tracks overall sync job execution:

```sql
-- View recent sync jobs
SELECT * FROM sync_jobs WHERE job_name='sync_refs_spp' ORDER BY id DESC LIMIT 10;
```

Example:
| id | job_name | status | started_at | finished_at | execution_time | message |
|----|----------|--------|------------|-------------|----------------|---------|
| 10 | sync_refs_spp | success | 2026-03-01 03:00:10 | 2026-03-01 03:01:25 | 75.23 | Sync completed |
| 9 | sync_refs_spp | success | 2025-11-01 03:00:10 | 2025-11-01 03:01:15 | 65.18 | Sync completed |

### 2. SyncJobDetail Table (`sync_job_details`)
Tracks detailed sync per table:

```sql
-- View details for a specific sync job
SELECT * FROM sync_job_details WHERE sync_job_id=10 ORDER BY id;
```

Example:
| sync_job_id | table_name | status | total_records | inserted | updated | started_at | finished_at |
|-------------|------------|--------|---------------|----------|---------|------------|-------------|
| 10 | sektor | success | 15 | 5 | 10 | 2026-03-01 03:00:10 | 2026-03-01 03:00:15 |
| 10 | bahagian | success | 37 | 12 | 25 | 2026-03-01 03:00:20 | 2026-03-01 03:00:28 |
| 10 | seksyen | success | 38 | 8 | 30 | 2026-03-01 03:00:33 | 2026-03-01 03:00:42 |
| 10 | unit | success | 55 | 15 | 40 | 2026-03-01 03:00:47 | 2026-03-01 03:01:05 |

### Query Examples
```sql
-- Check failed jobs
SELECT * FROM sync_jobs WHERE status='failed' AND job_name='sync_refs_spp';

-- Get sync statistics by table
SELECT 
  table_name,
  COUNT(*) as total_sync_runs,
  SUM(inserted_records) as total_inserted,
  SUM(updated_records) as total_updated,
  AVG(total_records) as avg_records
FROM sync_job_details 
WHERE status='success'
GROUP BY table_name;

-- Check which tables failed in last sync
SELECT * FROM sync_job_details 
WHERE sync_job_id = (SELECT MAX(id) FROM sync_jobs WHERE job_name='sync_refs_spp')
AND status='failed';
```

---

## 🗄️ Database Sync Logic

### Data Flow
```
SPP Database                    eHelp Database
-----------------              -----------------
spp_db.sektor      →           ref_sektor
spp_db.bahagian    →           ref_bahagian
spp_db.seksyen     →           ref_seksyen
spp_db.unit        →           ref_unit
spp_db.jawatan     →           ref_jawatan
spp_db.khidmat     →           ref_khidmat
spp_db.gred        →           ref_gred
spp_db.skim        →           ref_skim
                   ↓
        sync_jobs + sync_job_details (tracking)
```

### Sync Process (--all mode)
1. Create SyncJob record with status 'running'
2. Sync tables in sequence with 5-second delay between each:
   - **Sektor** (independent)
   - **Bahagian** (requires: sektor)
   - **Seksyen** (requires: sektor, bahagian)
   - **Unit** (requires: sektor, bahagian, seksyen)
   - **Jawatan** (independent)
   - **Khidmat** (independent)
   - **Gred** (independent)
   - **Skim** (independent)
3. For each table:
   - Fetch all records from SPP database
   - Validate dependencies (if required)
   - Skip records with empty/null names
   - Use `updateOrInsert()` to insert new or update existing
   - Track insert/update counts
   - Log to SyncJobDetail table
4. Update SyncJob with final status and execution time
5. Log results to `storage/logs/laravel.log`

### Dependency Validation
Some tables have dependencies and will skip records if dependencies are missing:

- **Seksyen** requires:
  - `kod_sektor` exists in `ref_sektor`
  - `kod_bah` exists in `ref_bahagian`
  
- **Unit** requires:
  - `kod_sektor` exists in `ref_sektor`
  - `kod_bah` exists in `ref_bahagian`
  - `kod_sec` exists in `ref_seksyen`

### Fields Synced

#### Sektor
```php
'kod_sektor', 'nama_sektor'
```

#### Bahagian
```php
'kod_bah', 'nama_bah', 'singkat_bah', 'kod_sektor'
```

#### Seksyen
```php
'kod_sec', 'nama_sec', 'kod_bah', 'kod_sektor'
```

#### Unit
```php
'kod_unit', 'nama_unit', 'kod_sektor', 'kod_bah', 'kod_sec'
```

#### Jawatan
```php
'kod_jawatan', 'nama_jawatan'
```

#### Khidmat
```php
'kod_khidmat', 'nama_khidmat'
```

#### Gred
```php
'kod_gred', 'nama_gred'
```

#### Skim
```php
'kod_skim', 'nama_skim'
```

---

## ⚙️ Configuration

### Enable/Disable Scheduler

By default, the sync scheduler is **DISABLED**. You need to enable it via environment variable.

**File: `.env`**
```env
# Set to true to enable sync scheduler, false to disable
ENABLE_SYNC_REFS_SPP=false
```

**File: `config/app.php`**
```php
'enable_sync_refs_spp' => env('ENABLE_SYNC_REFS_SPP', false),
```

### How It Works
- If `ENABLE_SYNC_REFS_SPP=false` → Schedule will NOT run
- If `ENABLE_SYNC_REFS_SPP=true` → Schedule runs quarterly at 3:00 AM
- Allows selective testing of schedulers without running all tasks

**Example Use Cases:**
```bash
# Test sync refs only
ENABLE_SYNC_USERS_SPP=false
ENABLE_SYNC_REFS_SPP=true
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
[2026-03-01 03:00:15] local.INFO: 
✔ sektor: insert 5, update 10
✔ bahagian: insert 12, update 25
...
✅ Sync selesai
```

#### Error Log
```
[2026-03-01 03:00:15] local.ERROR: 
Sync refs failed: SQLSTATE[HY000] [2002] Connection refused
```

#### Dependency Warning
```
[2026-03-01 03:00:25] local.WARNING: 
Skipping seksyen SEC001 - kod_bah BAH999 not found in ref_bahagian
```

### Database Tracking

#### View Sync Job Summary
```bash
php artisan tinker
>>> \App\Models\SyncJob::where('job_name', 'sync_refs_spp')->latest()->first()
```

#### View Detailed Breakdown by Table
```bash
php artisan tinker
>>> $job = \App\Models\SyncJob::latest()->first()
>>> \App\Models\SyncJobDetail::where('sync_job_id', $job->id)->get()
```

#### SQL Queries
```sql
-- View all details for latest sync
SELECT 
  sjd.table_name,
  sjd.status,
  sjd.total_records,
  sjd.inserted_records,
  sjd.updated_records,
  TIMESTAMPDIFF(SECOND, sjd.started_at, sjd.finished_at) as seconds
FROM sync_job_details sjd
JOIN sync_jobs sj ON sjd.sync_job_id = sj.id
WHERE sj.job_name = 'sync_refs_spp'
ORDER BY sj.id DESC, sjd.id ASC
LIMIT 10;

-- Check which tables have most inserts
SELECT 
  table_name,
  SUM(inserted_records) as total_new_records,
  SUM(updated_records) as total_updates,
  COUNT(*) as sync_count
FROM sync_job_details
WHERE status = 'success'
GROUP BY table_name
ORDER BY total_new_records DESC;
```

---

## 🚀 Production Setup

**⚠️ IMPORTANT**: Before setting up cron/scheduler, enable the sync in `.env`:
```env
ENABLE_SYNC_REFS_SPP=true
```

Then clear the config cache:
```bash
php artisan config:clear
```

### Linux Server (Crontab)
```bash
# Edit crontab
crontab -e

# Add this line (Laravel scheduler runs every minute)
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

**Note**: The cron/scheduler will only execute `app:sync-refs-data` if `ENABLE_SYNC_REFS_SPP=true` is set, and only on the scheduled dates (1st day of January, May, September at 3:00 AM).

---

## ✅ Checklist

- [x] Command created: `SyncRefsData.php`
- [x] Command registered in `ConsoleServiceProvider.php`
- [x] Schedule configured in `console.php` (quarterly at 3:00 AM)
- [x] SyncJob model created for main tracking
- [x] SyncJobDetail model created for detailed tracking per table
- [x] Database connections configured (SPP + eHelp)
- [x] Configuration flag added (ENABLE_SYNC_REFS_SPP)
- [x] Dependency validation implemented
- [x] Error handling & logging
- [x] 5-second delay between table syncs (prevent overload)
- [x] withoutOverlapping() to prevent concurrent runs

---

## 📋 Best Practices

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
# Test sync single table first
php artisan app:sync-refs-data sektor

# Check results
php artisan tinker
>>> DB::table('ref_sektor')->count()

# Then test all tables
php artisan app:sync-refs-data --all

# Verify sync_jobs and sync_job_details
>>> \App\Models\SyncJob::latest()->first()
>>> \App\Models\SyncJobDetail::where('sync_job_id', 1)->get()

# Check logs for warnings/errors
tail -f storage/logs/laravel.log | grep -i "sync\|skip\|error"
```

### 3. Monitoring Recommendations
- **Quarterly**: Check `sync_jobs` table after scheduled run
- **Monthly**: Review `sync_job_details` for any failed table syncs
- **Weekly**: Check for dependency warnings in logs
- **On-demand**: Run specific table sync if data seems outdated

```sql
-- Check if last quarterly sync was successful
SELECT * FROM sync_jobs 
WHERE job_name = 'sync_refs_spp' 
ORDER BY id DESC LIMIT 1;

-- Check if any tables failed in last sync
SELECT * FROM sync_job_details 
WHERE sync_job_id = (SELECT MAX(id) FROM sync_jobs WHERE job_name='sync_refs_spp')
AND status = 'failed';

-- Clean up old records (keep 1 year)
DELETE FROM sync_job_details 
WHERE sync_job_id IN (
  SELECT id FROM sync_jobs 
  WHERE job_name = 'sync_refs_spp' 
  AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
);
```

### 4. Performance Tips
- Runs at 3:00 AM quarterly (very low traffic period)
- 5-second delay between tables prevents database overload
- `withoutOverlapping()` prevents concurrent runs
- Typical execution time: 60-90 seconds for all 8 tables
- If execution time > 5 minutes, check database indexes

### 5. Error Handling
- Failed jobs are logged in both `laravel.log` and `sync_jobs` table
- Individual table failures are logged in `sync_job_details`
- Dependency warnings are logged (won't stop sync)
- Status remains 'failed' for easy identification
- Check `message` field in both tables for error details

### 6. Dependency Best Practices
- Always sync independent tables before dependent ones
- Sync order in --all mode:
  1. Sektor (independent)
  2. Bahagian (depends on sektor)
  3. Seksyen (depends on sektor + bahagian)
  4. Unit (depends on sektor + bahagian + seksyen)
  5. Jawatan, Khidmat, Gred, Skim (all independent)
- Records with missing dependencies are skipped (logged as warnings)
- Re-run sync after fixing dependency issues

---

## 🔍 Troubleshooting

### Problem: Command not found
**Solution:**
```bash
php artisan optimize:clear
php artisan config:clear
composer dump-autoload
```

### Problem: "Table tidak sah" warning
**Cause:** Invalid table name provided
**Solution:**
```bash
# Use correct table names (lowercase)
php artisan app:sync-refs-data sektor
php artisan app:sync-refs-data bahagian

# Valid table names:
# sektor, bahagian, seksyen, unit, jawatan, khidmat, gred, skim
```

### Problem: Database connection failed
**Solution:**
- Check `.env` file (DB_SPP_* settings)
- Test connection: `php artisan tinker` → `DB::connection('spp')->table('sektor')->count()`
- Verify SPP database is accessible from server

### Problem: Schedule not running
**Solution:**
- Check if enabled in .env: `ENABLE_SYNC_REFS_SPP=true`
- Clear config cache: `php artisan config:clear`
- Check cron job is active: `crontab -l`
- Run manually: `php artisan schedule:run`
- Check logs: `storage/logs/laravel.log`
- Verify schedule date: only runs on 1st day of Jan/May/Sep

### Problem: Sync job stuck in 'running' status
**Solution:**
```sql
-- Check for stuck jobs
SELECT * FROM sync_jobs 
WHERE status='running' 
AND started_at < NOW() - INTERVAL 2 HOUR;

-- Manually mark as failed (if needed)
UPDATE sync_jobs 
SET status='failed', 
    message='Job timeout', 
    finished_at=NOW() 
WHERE id=xxx;
```

### Problem: Many "Skipping" warnings in logs
**Cause:** Dependency records missing (e.g., seksyen references non-existent bahagian)
**Solution:**
```bash
# Sync independent tables first
php artisan app:sync-refs-data sektor
php artisan app:sync-refs-data bahagian

# Then sync dependent tables
php artisan app:sync-refs-data seksyen
php artisan app:sync-refs-data unit

# Or check SPP database for data integrity
php artisan tinker
>>> DB::connection('spp')->table('seksyen')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))
          ->from('bahagian')
          ->whereRaw('bahagian.kod_bah = seksyen.kod_bah');
    })->count()
```

### Problem: Some tables successful, others failed
**Solution:**
```sql
-- Check which tables failed
SELECT * FROM sync_job_details 
WHERE sync_job_id = [LATEST_JOB_ID]
AND status = 'failed';

-- Re-run specific failed table
php artisan app:sync-refs-data [table_name]

-- Check detailed error message
SELECT table_name, message FROM sync_job_details 
WHERE status = 'failed' 
ORDER BY id DESC LIMIT 10;
```

### Problem: Duplicate records after sync
**Cause:** Primary keys not properly configured
**Solution:**
- Verify `updateOrInsert()` uses correct unique key
- Check if primary key exists in destination table
- Example for sektor:
  ```sql
  -- Check primary key
  SHOW KEYS FROM ref_sektor WHERE Key_name = 'PRIMARY';
  
  -- Should be on 'kod_sektor' column
  -- If not, add constraint:
  ALTER TABLE ref_sektor ADD PRIMARY KEY (kod_sektor);
  ```

---

## 🧪 Testing & Debugging

### Manual Testing
```bash
# 1. Enable sync in .env (if disabled)
ENABLE_SYNC_REFS_SPP=true

# 2. Clear config cache
php artisan config:clear

# 3. Test single table first
php artisan app:sync-refs-data sektor

# 4. Check results
php artisan tinker
>>> DB::table('ref_sektor')->count()
>>> \App\Models\SyncJobDetail::latest()->first()

# 5. Test all tables
php artisan app:sync-refs-data --all

# 6. View detailed results
>>> $job = \App\Models\SyncJob::latest()->first()
>>> \App\Models\SyncJobDetail::where('sync_job_id', $job->id)->get()

# 7. Test scheduler
php artisan schedule:list
php artisan schedule:test
```

### Debugging Tips
```bash
# View last sync job with all table details
php artisan tinker
>>> $job = \App\Models\SyncJob::where('job_name', 'sync_refs_spp')->latest()->first()
>>> $details = \App\Models\SyncJobDetail::where('sync_job_id', $job->id)->get()
>>> foreach($details as $d) { 
      echo "{$d->table_name}: {$d->inserted_records} inserted, {$d->updated_records} updated\n"; 
    }

# Check SPP connection and data
>>> DB::connection('spp')->getPdo()
>>> DB::connection('spp')->table('sektor')->count()
>>> DB::connection('spp')->table('bahagian')->count()

# Compare counts between SPP and eHelp
>>> DB::connection('spp')->table('sektor')->count()
>>> DB::table('ref_sektor')->count()

# Check execution times per table
>>> \App\Models\SyncJobDetail::where('status', 'success')
    ->selectRaw('table_name, AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) as avg_seconds')
    ->groupBy('table_name')
    ->get()
```

### Test Dependency Validation
```bash
php artisan tinker

# Create test scenario: seksyen without valid bahagian
>>> DB::connection('spp')->table('seksyen')->insert([
      'kod_sec' => 'TEST001',
      'nama_sec' => 'Test Seksyen',
      'kod_bah' => 'INVALID',  // This doesn't exist
      'kod_sektor' => 'SEK01'
    ])

# Run sync and check logs for skip warning
php artisan app:sync-refs-data seksyen

# Should see: "Skipping seksyen TEST001 - kod_bah INVALID not found in ref_bahagian"

# Clean up test data
>>> DB::connection('spp')->table('seksyen')->where('kod_sec', 'TEST001')->delete()
```

---

## 📞 Support & Notes

**Created by:** GitHub Copilot  
**Last Updated:** 26 March 2026  
**Version:** Laravel 12  

**Related Documentation:**
- [01_SyncUsersSPP.md](01_SyncUsersSPP.md) - User sync documentation
- [database-rules.md](../database-rules.md) - Database conventions

**Key Differences from SyncUsersSPP:**
- ✅ Multiple tables (8 tables vs 1 table)
- ✅ Dependency validation (seksyen/unit require parent records)
- ✅ Detailed per-table tracking (SyncJobDetail model)
- ✅ Quarterly schedule (vs daily for users)
- ✅ Selective sync supported (can sync one table at a time)
- ✅ 5-second delay between tables (prevent database overload)

**Important Notes:**
- Reference data changes infrequently, hence quarterly schedule
- Dependency order matters: sync parent tables before child tables
- Use `--all` flag for complete sync, or specify table name for selective sync
- Monitor `sync_job_details` table for per-table sync results
