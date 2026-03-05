# 05 - Dashboard Pegawai Tugasan: Database & Queries

## Database Schema

### Relevant Tables

#### 1. tickets
```sql
CREATE TABLE tickets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ticket_no VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cat_id INT UNSIGNED,
    user_id BIGINT UNSIGNED,  -- Ticket creator
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (cat_id) REFERENCES ref_categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### 2. tickets_chronologies ⭐ (Critical for Pegawai)
```sql
CREATE TABLE tickets_chronologies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    tick_status TINYINT NOT NULL,
    escalate_to BIGINT UNSIGNED,  -- ⭐ PEGAWAI ID (Most Important!)
    action_by BIGINT UNSIGNED,
    remarks TEXT,
    sla_duedate DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (escalate_to) REFERENCES users(id),
    FOREIGN KEY (action_by) REFERENCES users(id),
    FOREIGN KEY (tick_status) REFERENCES ref_ticket_statuses(id)
);
```

**Key Columns**:
- `escalate_to` - ID pegawai yang assigned (THIS IS THE FILTER KEY)
- `tick_status` - Status semasa tiket
- `sla_duedate` - Tarikh SLA perlu diselesaikan

#### 3. ref_ticket_statuses
```sql
CREATE TABLE ref_ticket_statuses (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Sample Data
INSERT INTO ref_ticket_statuses (id, name) VALUES
(1, 'Baru'),
(2, 'Dalam Proses'),
(3, 'Menunggu'),
(4, 'Sedang Dikerjakan'),
(5, 'Dibatalkan'),
(6, 'Selesai');
```

#### 4. ref_categories
```sql
CREATE TABLE ref_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cat_name VARCHAR(100) NOT NULL,
    cat_code VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

#### 5. users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Critical Indexes

### Required Indexes for Performance

```sql
-- 1. Most Important: Index on escalate_to
CREATE INDEX idx_escalate_to ON tickets_chronologies(escalate_to);

-- 2. Index for status filtering
CREATE INDEX idx_tick_status ON tickets_chronologies(tick_status);

-- 3. Index for SLA queries
CREATE INDEX idx_sla_duedate ON tickets_chronologies(sla_duedate);

-- 4. Composite index for common filter combination
CREATE INDEX idx_escalate_status ON tickets_chronologies(escalate_to, tick_status);

-- 5. Composite index for SLA overdue queries
CREATE INDEX idx_escalate_sla ON tickets_chronologies(escalate_to, sla_duedate, tick_status);

-- 6. Index for soft deletes
CREATE INDEX idx_deleted_at ON tickets_chronologies(deleted_at);

-- 7. Index on ticket relationship
CREATE INDEX idx_ticket_id ON tickets_chronologies(ticket_id);
```

### Why These Indexes?

#### idx_escalate_to
```sql
-- Used in EVERY pegawai query
WHERE escalate_to = :userId
```
**Impact**: Without this, full table scan on every query.

#### idx_escalate_status
```sql
-- Used for pending count
WHERE escalate_to = :userId 
  AND tick_status NOT IN (5, 6)
```
**Impact**: Reduces query time by 80-90%.

#### idx_escalate_sla
```sql
-- Used for overdue count
WHERE escalate_to = :userId
  AND tick_status != 6
  AND sla_duedate < NOW()
```
**Impact**: Critical for SLA monitoring performance.

---

## Query Patterns for Pegawai Tugasan

### 1. Get Assigned Tickets Count

```sql
SELECT COUNT(DISTINCT tc.ticket_id) as assigned_count
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.deleted_at IS NULL;
```

**Laravel Query Builder**:
```php
$count = DB::table('tickets_chronologies as tc')
    ->where('tc.escalate_to', $userId)
    ->whereNull('tc.deleted_at')
    ->distinct('tc.ticket_id')
    ->count('tc.ticket_id');
```

**Performance**: ~5-10ms with proper index.

---

### 2. Get Pending Tickets Count

```sql
SELECT COUNT(DISTINCT tc.ticket_id) as pending_count
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status NOT IN (5, 6)  -- Not cancelled/completed
  AND tc.deleted_at IS NULL;
```

**Laravel Query Builder**:
```php
$pending = DB::table('tickets_chronologies as tc')
    ->where('tc.escalate_to', $userId)
    ->whereNotIn('tc.tick_status', [5, 6])
    ->whereNull('tc.deleted_at')
    ->distinct('tc.ticket_id')
    ->count('tc.ticket_id');
```

**EXPLAIN Analysis**:
```
+----+------+-------+------------------------+------+---------------+
| id | type | table | key                    | rows | Extra         |
+----+------+-------+------------------------+------+---------------+
|  1 | ref  | tc    | idx_escalate_status    |   50 | Using index   |
+----+------+-------+------------------------+------+---------------+
```

---

### 3. Get Completed This Week Count

```sql
SELECT COUNT(DISTINCT tc.ticket_id) as completed_count
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status = 6  -- Completed
  AND tc.updated_at BETWEEN :startOfWeek AND :endOfWeek
  AND tc.deleted_at IS NULL;
```

**Laravel Query Builder**:
```php
$startOfWeek = Carbon::now()->startOfWeek();
$endOfWeek = Carbon::now()->endOfWeek();

$completed = DB::table('tickets_chronologies as tc')
    ->where('tc.escalate_to', $userId)
    ->where('tc.tick_status', 6)
    ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek])
    ->whereNull('tc.deleted_at')
    ->distinct('tc.ticket_id')
    ->count('tc.ticket_id');
```

---

### 4. Get Overdue Tickets Count

```sql
SELECT COUNT(DISTINCT tc.ticket_id) as overdue_count
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status != 6  -- Not completed
  AND tc.sla_duedate IS NOT NULL
  AND tc.sla_duedate < NOW()
  AND tc.deleted_at IS NULL;
```

**Laravel Query Builder**:
```php
$overdue = DB::table('tickets_chronologies as tc')
    ->where('tc.escalate_to', $userId)
    ->where('tc.tick_status', '!=', 6)
    ->whereNotNull('tc.sla_duedate')
    ->where('tc.sla_duedate', '<', Carbon::now())
    ->whereNull('tc.deleted_at')
    ->distinct('tc.ticket_id')
    ->count('tc.ticket_id');
```

**Uses Index**: `idx_escalate_sla`

---

### 5. Get Ticket Summary by Status

```sql
SELECT 
    COUNT(DISTINCT t.id) as total,
    SUM(CASE WHEN tc.tick_status = 1 THEN 1 ELSE 0 END) as new,
    SUM(CASE WHEN tc.tick_status IN (2,3,4) THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN tc.tick_status = 6 THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN tc.tick_status = 5 THEN 1 ELSE 0 END) as cancelled
FROM tickets t
INNER JOIN tickets_chronologies tc ON t.id = tc.ticket_id
WHERE tc.escalate_to = :userId
  AND tc.id = (
      SELECT MAX(tc2.id)
      FROM tickets_chronologies tc2
      WHERE tc2.ticket_id = t.id
        AND tc2.escalate_to = :userId
        AND tc2.deleted_at IS NULL
  )
  AND t.deleted_at IS NULL
  AND tc.deleted_at IS NULL;
```

**Explanation**:
- Gets latest chronology for each ticket
- Filters by pegawai (escalate_to)
- Aggregates by status using CASE WHEN

**Laravel Query Builder**:
```php
$summary = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', function ($join) use ($userId) {
        $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', '=', $userId)
            ->whereRaw('tc.id = (
                SELECT MAX(tc2.id)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = t.id
                  AND tc2.escalate_to = ?
                  AND tc2.deleted_at IS NULL
            )', [$userId]);
    })
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->select(
        DB::raw('COUNT(DISTINCT t.id) as total'),
        DB::raw('SUM(CASE WHEN tc.tick_status = 1 THEN 1 ELSE 0 END) as new'),
        DB::raw('SUM(CASE WHEN tc.tick_status IN (2,3,4) THEN 1 ELSE 0 END) as in_progress'),
        DB::raw('SUM(CASE WHEN tc.tick_status = 6 THEN 1 ELSE 0 END) as completed'),
        DB::raw('SUM(CASE WHEN tc.tick_status = 5 THEN 1 ELSE 0 END) as cancelled')
    )
    ->first();
```

---

### 6. Get My Tasks (DataTable)

```sql
SELECT 
    t.id,
    t.ticket_no,
    t.title,
    rc.cat_name as category,
    rts.name as status,
    tc.tick_status,
    tc.sla_duedate,
    CASE WHEN tc.sla_duedate < NOW() THEN 1 ELSE 0 END as is_overdue
FROM tickets t
INNER JOIN tickets_chronologies tc ON t.id = tc.ticket_id
INNER JOIN ref_categories rc ON t.cat_id = rc.id
INNER JOIN ref_ticket_statuses rts ON tc.tick_status = rts.id
WHERE tc.escalate_to = :userId
  AND t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
ORDER BY tc.sla_duedate ASC;
```

**Laravel Query Builder**:
```php
$tasks = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
    ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
    ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
    ->where('tc.escalate_to', $userId)
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->select(
        't.id',
        't.ticket_no',
        't.title',
        'rc.cat_name as category',
        'rts.name as status',
        'tc.tick_status',
        'tc.sla_duedate',
        DB::raw('CASE WHEN tc.sla_duedate < NOW() THEN 1 ELSE 0 END as is_overdue')
    )
    ->orderBy('tc.sla_duedate', 'ASC')
    ->get();
```

---

### 7. Get Overdue Tickets (Detailed)

```sql
SELECT 
    t.id,
    t.ticket_no,
    t.title,
    rc.cat_name as category,
    tc.sla_duedate,
    TIMESTAMPDIFF(HOUR, tc.sla_duedate, NOW()) as hours_overdue
FROM tickets t
INNER JOIN tickets_chronologies tc ON t.id = tc.ticket_id
INNER JOIN ref_categories rc ON t.cat_id = rc.id
WHERE tc.escalate_to = :userId
  AND tc.tick_status != 6
  AND tc.sla_duedate IS NOT NULL
  AND tc.sla_duedate < NOW()
  AND t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
ORDER BY hours_overdue DESC;
```

**Laravel Query Builder**:
```php
$overdue = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
    ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
    ->where('tc.escalate_to', $userId)
    ->where('tc.tick_status', '!=', 6)
    ->whereNotNull('tc.sla_duedate')
    ->where('tc.sla_duedate', '<', now())
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->select(
        't.id',
        't.ticket_no',
        't.title',
        'rc.cat_name as category',
        'tc.sla_duedate',
        DB::raw('TIMESTAMPDIFF(HOUR, tc.sla_duedate, NOW()) as hours_overdue')
    )
    ->orderBy('hours_overdue', 'DESC')
    ->get();
```

---

### 8. Get Categories Breakdown

```sql
SELECT 
    rc.id,
    rc.cat_name as category_name,
    COUNT(DISTINCT t.id) as count
FROM tickets t
INNER JOIN tickets_chronologies tc ON t.id = tc.ticket_id
INNER JOIN ref_categories rc ON t.cat_id = rc.id
WHERE tc.escalate_to = :userId
  AND t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND rc.deleted_at IS NULL
GROUP BY rc.id, rc.cat_name
ORDER BY count DESC;
```

**Laravel Query Builder**:
```php
$categories = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
    ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
    ->where('tc.escalate_to', $userId)
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->whereNull('rc.deleted_at')
    ->groupBy('rc.id', 'rc.cat_name')
    ->select(
        'rc.id',
        'rc.cat_name as category_name',
        DB::raw('COUNT(DISTINCT t.id) as count')
    )
    ->orderBy('count', 'DESC')
    ->get();
```

---

## Query Optimization Tips

### 1. Use DISTINCT Wisely
```php
// ✅ GOOD: When tickets_chronologies may have duplicates
->distinct('tc.ticket_id')->count('tc.ticket_id')

// ❌ BAD: When joining with latest chronology only
->count() // Duplication already handled by join
```

### 2. Select Only Needed Columns
```php
// ✅ GOOD
->select('id', 'ticket_no', 'title')

// ❌ BAD: Increases memory/network overhead
->select('*')
```

### 3. Use whereNull vs where deleted_at NULL
```php
// ✅ GOOD: Uses index
->whereNull('deleted_at')

// ❌ BAD: Full table scan
->where('deleted_at', NULL)
```

### 4. Batch Queries When Possible
```php
// ✅ GOOD: One query for all metrics
$summary = DB::table(...)->select(
    DB::raw('COUNT(DISTINCT ...) as total'),
    DB::raw('SUM(CASE ...) as new')
)->first();

// ❌ BAD: Multiple separate queries
$total = DB::table(...)->count();
$new = DB::table(...)->where('status', 1)->count();
```

---

## Caching Strategy

### Cache Keys
```php
$cacheKey = "dashboard.pegawai.{$userId}.my_tasks";
$cacheTTL = 300; // 5 minutes
```

### Implementation
```php
use Illuminate\Support\Facades\Cache;

$data = Cache::remember($cacheKey, $cacheTTL, function () use ($userId) {
    return DB::table('tickets_chronologies')
        ->where('escalate_to', $userId)
        ->whereNull('deleted_at')
        ->distinct('ticket_id')
        ->count('ticket_id');
});
```

### Cache Invalidation
```php
// When ticket assigned to pegawai
Cache::forget("dashboard.pegawai.{$pegawaiId}.my_tasks");

// When ticket status updated
Cache::forget("dashboard.pegawai.{$pegawaiId}.my_tasks");
Cache::forget("dashboard.pegawai.{$pegawaiId}.summary");
```

---

## Query Performance Monitoring

### Using Laravel Debugbar
```php
// In development
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if ($query->time > 100) { // Slower than 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time
        ]);
    }
});
```

### Using EXPLAIN
```sql
EXPLAIN 
SELECT COUNT(DISTINCT tc.ticket_id)
FROM tickets_chronologies tc
WHERE tc.escalate_to = 123
  AND tc.deleted_at IS NULL;
```

**Expected Output** (with proper index):
```
+----+------+-------+------------------+------+---------+-------+------+-------------+
| id | type | table | key              | rows | ref     | Extra                     |
+----+------+-------+------------------+------+---------+-------+------+-------------+
|  1 | ref  | tc    | idx_escalate_to  |   50 | const   | Using index condition     |
+----+------+-------+------------------+------+---------+-------+------+-------------+
```

---

## Database Migration

### Create Index Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            // Add indexes for pegawai dashboard queries
            $table->index('escalate_to', 'idx_escalate_to');
            $table->index(['escalate_to', 'tick_status'], 'idx_escalate_status');
            $table->index(['escalate_to', 'sla_duedate', 'tick_status'], 'idx_escalate_sla');
            $table->index('deleted_at', 'idx_deleted_at');
        });
    }

    public function down()
    {
        Schema::table('tickets_chronologies', function (Blueprint $table) {
            $table->dropIndex('idx_escalate_to');
            $table->dropIndex('idx_escalate_status');
            $table->dropIndex('idx_escalate_sla');
            $table->dropIndex('idx_deleted_at');
        });
    }
};
```

---

## Best Practices

### 1. Always Filter by User
```php
// ✅ Every query MUST have this
->where('escalate_to', $userId)
```

### 2. Handle Soft Deletes
```php
// ✅ Check deleted_at
->whereNull('deleted_at')
```

### 3. Use Proper Joins
```php
// ✅ INNER JOIN when relationship required
->join('tickets', ...)

// Use LEFT JOIN when optional
->leftJoin('tickets', ...)
```

### 4. Prevent N+1 Queries
```php
// ✅ Eager load relationships
Ticket::with('category', 'status')->where(...)->get();
```

---

## Dokumentasi Berkaitan

- [03-services.md](03-services.md) - Backend services implementation
- [04-frontend.md](04-frontend.md) - Frontend implementation
- [06-testing.md](06-testing.md) - Testing queries

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
