# 📝 NOTA RINGKAS: SCHEDULED TASK - SYNC USERS FROM SPP

## 🎯 Tujuan
Sync data users dari database SPP (`spp_db.users`) ke database eHelp (`ehelp.users`) setiap hari pada pukul 2:00 pagi.

---

## 📂 File Terlibat

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
  - ✅ Track updated/inserted count
  - ✅ Calculate execution time
  - ✅ Error handling & logging

### 2. **Schedule Configuration**
```
c:\laragon\www\ehelp\routes\console.php
```
- Schedule Time: `02:00` (2:00 AM)
- Timezone: `Asia/Kuala_Lumpur`
- Runs: Daily
- Logging: Success/Failure logs

### 3. **Service Provider**
```
c:\laragon\www\ehelp\app\Providers\ConsoleServiceProvider.php
```
- Register command manually
- Load commands from `app/Console/Commands`

### 4. **Database Configuration**
```
c:\laragon\www\ehelp\config\database.php
c:\laragon\www\ehelp\.env
```
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

| Task | Time | Timezone | Description |
|------|------|----------|-------------|
| `sync:users-spp` | 02:00 AM | Asia/Kuala_Lumpur | Sync users from SPP to eHelp daily |
| `sla:send-notifications` | 07:30 AM | Asia/Kuala_Lumpur | Send SLA reminder & overdue notifications |

---

## 🗄️ Database Sync Logic

### Data Flow
```
spp_db.users → [sync:users-spp] → ehelp.users
```

### Sync Process
1. Connect to SPP database (`DB::connection('spp')`)
2. Fetch all users from `spp_db.users`
3. For each user:
   - Check if user exists in `ehelp.users` by `id`
   - If exists: **UPDATE** record
   - If not exists: **INSERT** new record
4. Log results (updated count, inserted count, execution time)

### Fields Synced
```php
'uuid', 'id_pgn_ldap', 'email', 'password', 'nama', 'no_kp', 
'kodjantina', 'kod_skim', 'kod_kump_khidmat', 'kod_jwtn', 
'kod_jawatan', 'nama_gred', 'tel_no_p', 'tel_no_hp', 
'kod_sektor', 'kod_bah', 'kod_sec', 'kod_unit', 
'remember_token', 'tarikh_lapormof', 'wf_ramadhan', 
'hiraki', 'lastlogin_at', 'kodaktif', 'dataaktif', 
'hadir_kurang_jam', 'created_by', 'updated_by', 
'deleted_by', 'deleted_at'
```

---

## 📊 Logging

### Success Log
```
[2026-02-25 02:00:15] local.INFO: 
✓ Users sync from SPP executed successfully at 2026-02-25 02:00:15
Users sync completed - Updated: 150, Inserted: 5, Time: 2.47s
```

### Error Log
```
[2026-02-25 02:00:15] local.ERROR: 
✗ Users sync from SPP failed at 2026-02-25 02:00:15
Users sync failed: SQLSTATE[HY000] [2002] Connection refused (Time: 0.53s)
```

---

## 🚀 Production Setup

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

---

## ✅ Checklist

- [x] Command created: `SyncUsersFromSpp.php`
- [x] Command registered in `ConsoleServiceProvider.php`
- [x] Schedule configured in `console.php` (2:00 AM daily)
- [x] Database connections configured (SPP + eHelp)
- [x] Logging enabled (success/failure)
- [x] Execution time tracking
- [x] Error handling implemented

---

## 🔍 Troubleshooting

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
- Check cron job is active: `crontab -l`
- Run manually: `php artisan schedule:run`
- Check logs: `storage/logs/laravel.log`

---

## 📞 Support
Created by: GitHub Copilot  
Date: 25 February 2026  
Version: Laravel 12