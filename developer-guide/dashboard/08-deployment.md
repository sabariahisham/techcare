# 08 - Dashboard: Deployment & Maintenance

## Deployment Checklist

### Pre-Deployment

#### 1. Code Quality
- ✅ All tests passing
- ✅ No linting errors
- ✅ Code review completed
- ✅ Documentation updated

#### 2. Database
- ✅ Migrations created and tested
- ✅ Seeders for reference data ready
- ✅ Indexes optimized
- ✅ Backup strategy in place

#### 3. Configuration
- ✅ Environment variables set
- ✅ Cache configuration checked
- ✅ Queue configuration verified
- ✅ Session settings confirmed

#### 4. Assets
- ✅ JavaScript compiled and minified
- ✅ CSS compiled and minified
- ✅ Images optimized
- ✅ Vendor assets published

---

## Deployment Steps

### 1. Backup Current System

```bash
# Backup database
php artisan backup:database

# Backup files
tar -czf ehelp-backup-$(date +%Y%m%d).tar.gz \
  app/ resources/ public/ database/

# Store backup in safe location
mv ehelp-backup-*.tar.gz /backup/location/
```

### 2. Put System in Maintenance Mode

```bash
php artisan down --message="Dashboard upgrade in progress" --retry=60
```

### 3. Pull Latest Code

```bash
git pull origin main

# Or specific tag
git checkout v1.2.0
```

### 4. Install Dependencies

```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# JavaScript dependencies
npm ci --production
```

### 5. Run Migrations

```bash
# Check migration status
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Seed reference data if needed
php artisan db:seed --class=RefDataSeeder
```

### 6. Compile Assets

```bash
# Build production assets
npm run build

# Or using Vite
php artisan vite:build
```

### 7. Clear and Optimize Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Set Permissions

```bash
# Storage and cache directories
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Test Deployment

```bash
# Run smoke tests
php artisan test --testsuite=Smoke

# Check system health
php artisan dashboard:health-check
```

### 10. Bring System Back Online

```bash
php artisan up
```

---

## Environment Configuration

### Production `.env` Settings

```env
APP_NAME="eHelp Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ehelp.example.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ehelp_production
DB_USERNAME=ehelp_user
DB_PASSWORD=secure_password_here

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Dashboard Specific
DASHBOARD_CACHE_TTL=300
DASHBOARD_REFRESH_INTERVAL=300000
```

---

## Performance Optimization

### 1. Database Optimization

#### Create Indexes
```sql
-- Run these indexes for optimal dashboard performance
ALTER TABLE tickets ADD INDEX idx_created_cat_deleted (created_at, cat_id, deleted_at);
ALTER TABLE tickets_chronologies ADD INDEX idx_escalate_status_sla (escalate_to, tick_status, sla_duedate, deleted_at);
```

#### Query Monitoring
```bash
# Enable slow query log
sudo nano /etc/mysql/my.cnf

[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

### 2. Caching Strategy

#### Implement Cache Tags
```php
// In Controller
public function getTodayLogsCount()
{
    return Cache::tags(['dashboard', 'today-logs'])
        ->remember('today_logs_' . Auth::id(), 300, function () {
            return $this->calculateTodayLogs();
        });
}

// Clear cache on data change
public function updateTicket($id, $data)
{
    // Update logic...
    
    Cache::tags(['dashboard'])->flush();
}
```

#### Redis Configuration
```bash
# Install Redis
sudo apt-get install redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 3. Queue Configuration

```bash
# Install Supervisor for queue workers
sudo apt-get install supervisor

# Configure worker
sudo nano /etc/supervisor/conf.d/ehelp-worker.conf
```

**Supervisor Config**:
```ini
[program:ehelp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ehelp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ehelp/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ehelp-worker:*
```

### 4. Asset Optimization

```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'chart': ['chart.js'],
                    'datatables': ['datatables.net'],
                }
            }
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
            }
        }
    }
});
```

---

## Monitoring & Logging

### 1. Application Monitoring

#### Laravel Telescope (Development)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### New Relic (Production)
```bash
# Install New Relic PHP agent
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
sudo sh -c 'echo "deb http://apt.newrelic.com/debian/ newrelic non-free" > /etc/apt/sources.list.d/newrelic.list'
sudo apt-get update
sudo apt-get install newrelic-php5
```

### 2. Error Tracking

#### Sentry Integration
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

**config/sentry.php**:
```php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'environment' => env('APP_ENV'),
'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
```

### 3. Log Management

#### Log Rotation
```bash
sudo nano /etc/logrotate.d/laravel
```

**Logrotate Config**:
```
/var/www/ehelp/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

#### Custom Logging Channel
**config/logging.php**:
```php
'channels' => [
    'dashboard' => [
        'driver' => 'daily',
        'path' => storage_path('logs/dashboard.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

**Usage**:
```php
use Illuminate\Support\Facades\Log;

Log::channel('dashboard')->info('Dashboard accessed', [
    'user_id' => Auth::id(),
    'role' => Auth::user()->getRoleNames(),
]);
```

---

## Health Checks

### Create Health Check Command

**app/Console/Commands/DashboardHealthCheck.php**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardHealthCheck extends Command
{
    protected $signature = 'dashboard:health-check';
    protected $description = 'Check dashboard system health';

    public function handle()
    {
        $this->info('Running Dashboard Health Check...');
        
        // Check database connection
        $this->checkDatabase();
        
        // Check cache connection
        $this->checkCache();
        
        // Check required tables
        $this->checkTables();
        
        // Check permissions
        $this->checkPermissions();
        
        $this->info('Health check completed!');
    }

    protected function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection OK');
        } catch (\Exception $e) {
            $this->error('✗ Database connection failed: ' . $e->getMessage());
        }
    }

    protected function checkCache()
    {
        try {
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            
            if ($value === true) {
                $this->info('✓ Cache connection OK');
            } else {
                $this->error('✗ Cache not working properly');
            }
        } catch (\Exception $e) {
            $this->error('✗ Cache connection failed: ' . $e->getMessage());
        }
    }

    protected function checkTables()
    {
        $tables = ['tickets', 'tickets_chronologies', 'ref_ticket_statuses', 'ref_categories', 'users'];
        
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $this->info("✓ Table '{$table}' exists");
            } else {
                $this->error("✗ Table '{$table}' missing");
            }
        }
    }

    protected function checkPermissions()
    {
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];
        
        foreach ($directories as $dir) {
            if (is_writable($dir)) {
                $this->info("✓ Directory '{$dir}' is writable");
            } else {
                $this->error("✗ Directory '{$dir}' is not writable");
            }
        }
    }
}
```

### Schedule Health Checks

**app/Console/Kernel.php**:
```php
protected function schedule(Schedule $schedule)
{
    // Run health check every hour
    $schedule->command('dashboard:health-check')
        ->hourly()
        ->appendOutputTo(storage_path('logs/health-check.log'));
        
    // Clear old logs weekly
    $schedule->command('dashboard:clear-old-logs')
        ->weekly();
}
```

---

## Maintenance Tasks

### 1. Clear Old Data

**app/Console/Commands/ClearOldDashboardCache.php**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearOldDashboardCache extends Command
{
    protected $signature = 'dashboard:clear-cache';
    protected $description = 'Clear old dashboard cache data';

    public function handle()
    {
        Cache::tags(['dashboard'])->flush();
        $this->info('Dashboard cache cleared successfully!');
    }
}
```

### 2. Database Maintenance

```bash
# Optimize tables
php artisan db:optimize

# Analyze query performance
php artisan db:analyze
```

### 3. Log Cleanup

**app/Console/Commands/ClearOldLogs.php**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class ClearOldLogs extends Command
{
    protected $signature = 'dashboard:clear-old-logs';
    protected $description = 'Clear log files older than 30 days';

    public function handle()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $cutoffDate = Carbon::now()->subDays(30);
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Carbon::createFromTimestamp(File::lastModified($file))->lt($cutoffDate)) {
                File::delete($file);
                $deletedCount++;
            }
        }

        $this->info("Deleted {$deletedCount} old log files.");
    }
}
```

---

## Troubleshooting

### Common Issues

#### 1. Dashboard Shows No Data
**Causes**:
- Cache not cleared
- Database connection issues
- Permission problems

**Solutions**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan dashboard:health-check
```

#### 2. Slow Dashboard Performance
**Causes**:
- Missing indexes
- No caching
- Large dataset without pagination

**Solutions**:
```bash
# Check slow queries
php artisan db:analyze

# Rebuild indexes
php artisan db:optimize

# Enable query caching
php artisan cache:clear
```

#### 3. Charts Not Loading
**Causes**:
- JavaScript errors
- API endpoint issues
- CORS problems

**Solutions**:
```bash
# Check browser console for errors
# Verify API endpoints
php artisan route:list | grep dashboard

# Check logs
tail -f storage/logs/laravel.log
```

#### 4. Permission Denied Errors
**Solutions**:
```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear compiled views
php artisan view:clear
```

---

## Rollback Procedure

If deployment fails:

```bash
# 1. Put system in maintenance
php artisan down

# 2. Restore from backup
tar -xzf /backup/ehelp-backup-YYYYMMDD.tar.gz

# 3. Restore database
php artisan db:restore --backup=YYYYMMDD

# 4. Restore configuration
cp .env.backup .env

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Bring system back up
php artisan up

# 7. Verify functionality
php artisan dashboard:health-check
```

---

## Security Considerations

### 1. Rate Limiting

**routes/web.php**:
```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('dashboard')
    ->group(function () {
        // Dashboard routes
    });
```

### 2. CSRF Protection
Ensure all API endpoints use CSRF tokens:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

```javascript
fetch(url, {
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});
```

### 3. SQL Injection Prevention
Always use query builder or Eloquent:
```php
// GOOD ✓
DB::table('tickets')->where('id', $id)->get();

// BAD ✗
DB::select("SELECT * FROM tickets WHERE id = " . $id);
```

### 4. XSS Prevention
Blade automatically escapes output:
```blade
{{-- GOOD ✓ --}}
{{ $user->nama }}

{{-- BAD ✗ --}}
{!! $user->nama !!}
```

---

## Documentation Maintenance

Keep documentation updated:
- Update on feature changes
- Document new endpoints
- Update architecture diagrams
- Maintain changelog

### Changelog Format
```markdown
## [1.2.0] - 2026-01-28
### Added
- New widget for SLA metrics
- Real-time dashboard updates

### Changed
- Improved query performance
- Updated chart colors

### Fixed
- Fixed cache invalidation issue
- Corrected date filtering bug
```
