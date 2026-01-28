# 06 - Dashboard: Database & Queries

## Database Schema

### Main Tables

#### 1. tickets
```sql
CREATE TABLE tickets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_no VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cat_id INT NOT NULL,
    priority_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (cat_id) REFERENCES ref_categories(id),
    FOREIGN KEY (priority_id) REFERENCES ref_priorities(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    INDEX idx_cat_id (cat_id),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 2. tickets_chronologies
```sql
CREATE TABLE tickets_chronologies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT NOT NULL,
    tick_status INT NOT NULL,
    escalate_to INT NULL,
    remarks TEXT,
    sla_duedate TIMESTAMP NULL,
    action_by INT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (tick_status) REFERENCES ref_ticket_statuses(id),
    FOREIGN KEY (escalate_to) REFERENCES users(id),
    FOREIGN KEY (action_by) REFERENCES users(id),
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_tick_status (tick_status),
    INDEX idx_escalate_to (escalate_to),
    INDEX idx_sla_duedate (sla_duedate),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_ticket_status_escalate (ticket_id, tick_status, escalate_to)
);
```

#### 3. ref_ticket_statuses
```sql
CREATE TABLE ref_ticket_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    st_name VARCHAR(100) NOT NULL,
    st_code VARCHAR(20) NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Status Values**:
| ID | Status Name | Code | Description |
|----|-------------|------|-------------|
| 1 | Aduan Baru | NEW | New ticket |
| 2 | Aduan Diproses | PROCESSING | In progress |
| 3 | Serahan Kepada | ASSIGNED | Assigned to officer |
| 4 | Aduan Dirujuk | REFERRED | Referred to other dept |
| 5 | Aduan DiBatal | CANCELLED | Cancelled |
| 6 | Aduan Selesai | COMPLETED | Completed |

#### 4. ref_categories
```sql
CREATE TABLE ref_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cat_name VARCHAR(100) NOT NULL,
    cat_code VARCHAR(20) NOT NULL,
    parent_id INT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES ref_categories(id)
);
```

#### 5. users
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    no_kad_pengenalan VARCHAR(20),
    no_telefon VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_deleted_at (deleted_at)
);
```

---

## Query Patterns untuk Dashboard

### 1. Today's Logs Count

**Purpose**: Count tickets created today

**Query**:
```sql
SELECT COUNT(*) as count
FROM tickets_chronologies tc
WHERE tc.deleted_at IS NULL
  AND DATE(tc.created_at) = CURDATE()
  -- For Pegawai Tugasan only:
  AND tc.escalate_to = ?
```

**Laravel Query Builder**:
```php
$query = DB::table('tickets_chronologies as tc')
    ->whereNull('tc.deleted_at')
    ->whereDate('tc.created_at', Carbon::today());

// Filter for Pegawai Tugasan
if ($isPegawaiOnly) {
    $query->where('tc.escalate_to', $userId);
}

$count = $query->count();
```

**Optimization**:
- Index pada `created_at` untuk fast date filtering
- Index pada `deleted_at` untuk soft delete check
- Composite index: `(escalate_to, created_at, deleted_at)` untuk Pegawai queries

---

### 2. Weekly Completed Tickets

**Purpose**: Count completed tickets (status=6) for current week (Mon-Fri)

**Query**:
```sql
SELECT 
    DAYOFWEEK(tc.updated_at) as day_of_week,
    DATE(tc.updated_at) as date,
    COUNT(*) as count
FROM tickets_chronologies tc
WHERE tc.deleted_at IS NULL
  AND tc.tick_status = 6
  AND tc.updated_at BETWEEN ? AND ?
  -- For Pegawai Tugasan:
  AND tc.escalate_to = ?
GROUP BY DATE(tc.updated_at), DAYOFWEEK(tc.updated_at)
ORDER BY DATE(tc.updated_at)
```

**Laravel Query Builder**:
```php
$startOfWeek = Carbon::now()->startOfWeek(); // Monday
$endOfWeek = Carbon::now()->endOfWeek()->subDays(2); // Friday

$query = DB::table('tickets_chronologies as tc')
    ->select(
        DB::raw('DAYOFWEEK(tc.updated_at) as day_of_week'),
        DB::raw('DATE(tc.updated_at) as date'),
        DB::raw('COUNT(*) as count')
    )
    ->whereNull('tc.deleted_at')
    ->where('tc.tick_status', 6)
    ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek]);

if ($isPegawaiOnly) {
    $query->where('tc.escalate_to', $userId);
}

$data = $query
    ->groupBy(DB::raw('DATE(tc.updated_at)'), DB::raw('DAYOFWEEK(tc.updated_at)'))
    ->orderBy(DB::raw('DATE(tc.updated_at)'))
    ->get();
```

**Optimization**:
- Index pada `(tick_status, updated_at)`
- Consider partitioning by date for large datasets

---

### 3. Pending Tickets Statistics

**Purpose**: Count tickets not completed or cancelled

**Query**:
```sql
-- Total tickets
SELECT COUNT(*) as total
FROM tickets t
JOIN tickets_chronologies tc ON t.id = tc.ticket_id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.id = (
      SELECT MAX(tc2.id)
      FROM tickets_chronologies tc2
      WHERE tc2.ticket_id = t.id
        AND tc2.deleted_at IS NULL
  )

-- Pending tickets (not status 5 or 6)
SELECT COUNT(*) as pending
FROM tickets t
JOIN tickets_chronologies tc ON t.id = tc.ticket_id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.tick_status NOT IN (5, 6)
  AND tc.id = (
      SELECT MAX(tc2.id)
      FROM tickets_chronologies tc2
      WHERE tc2.ticket_id = t.id
        AND tc2.deleted_at IS NULL
  )
```

**Laravel Query Builder**:
```php
// Base query builder
$baseQuery = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', function ($join) {
        $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                SELECT MAX(tc2.id)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = t.id
                AND tc2.deleted_at IS NULL
            )');
    })
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at');

// Total tickets
$totalTickets = clone $baseQuery;
$total = $totalTickets->count();

// Pending tickets
$pendingTickets = clone $baseQuery;
$pending = $pendingTickets
    ->whereNotIn('tc.tick_status', [5, 6])
    ->count();

$percentage = $total > 0 ? round(($pending / $total) * 100, 1) : 0;
```

**Key Technique**: Get latest chronology entry
```sql
tc.id = (
    SELECT MAX(tc2.id)
    FROM tickets_chronologies tc2
    WHERE tc2.ticket_id = t.id
    AND tc2.deleted_at IS NULL
)
```

**Optimization**:
- Index pada `(ticket_id, id, deleted_at)` untuk subquery
- Consider materialized view untuk latest chronology

---

### 4. Overdue SLA Count

**Purpose**: Count tickets past SLA due date and not completed

**Query**:
```sql
SELECT COUNT(*) as count
FROM tickets t
JOIN tickets_chronologies tc ON t.id = tc.ticket_id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.tick_status NOT IN (5, 6)
  AND tc.sla_duedate IS NOT NULL
  AND tc.sla_duedate < NOW()
  AND tc.id = (
      SELECT MAX(tc2.id)
      FROM tickets_chronologies tc2
      WHERE tc2.ticket_id = t.id
        AND tc2.deleted_at IS NULL
  )
```

**Laravel Query Builder**:
```php
$now = Carbon::now();

$query = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', function ($join) {
        $join->on('t.id', '=', 'tc.ticket_id')
            ->whereRaw('tc.id = (
                SELECT MAX(tc2.id)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = t.id
                AND tc2.deleted_at IS NULL
            )');
    })
    ->whereNull('t.deleted_at')
    ->whereNull('tc.deleted_at')
    ->where('tc.tick_status', '!=', 6)
    ->where('tc.tick_status', '!=', 5)
    ->whereNotNull('tc.sla_duedate')
    ->where('tc.sla_duedate', '<', $now);

if ($isPegawaiOnly) {
    $query->where('tc.escalate_to', $userId);
}

$overdueCount = $query->count();
```

**Optimization**:
- Index pada `(sla_duedate, tick_status, deleted_at)`
- Background job untuk cache overdue counts

---

### 5. Status Tickets Chart Data

**Purpose**: Get ticket count by status

**Query**:
```sql
SELECT 
    rts.id,
    rts.st_name,
    COUNT(tc.id) as count
FROM ref_ticket_statuses rts
LEFT JOIN tickets_chronologies tc ON tc.tick_status = rts.id
    AND tc.deleted_at IS NULL
WHERE rts.deleted_at IS NULL
GROUP BY rts.id, rts.st_name
ORDER BY rts.id
```

**Laravel Query Builder**:
```php
$statusQuery = DB::table('tickets_chronologies as tc')
    ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
    ->select('rts.id', 'rts.st_name', DB::raw('COUNT(tc.id) as count'))
    ->whereNull('tc.deleted_at')
    ->whereNull('rts.deleted_at');

if ($isPegawaiOnly) {
    $statusQuery->where('tc.escalate_to', $userId);
}

$statusCounts = $statusQuery
    ->groupBy('rts.id', 'rts.st_name')
    ->orderBy('rts.id')
    ->get();

// Get all statuses (including zero counts)
$allStatuses = RefTicketStatus::all();
$statusData = [];

foreach ($allStatuses as $status) {
    $count = $statusCounts->where('id', $status->id)->first();
    $statusData[] = [
        'id' => $status->id,
        'st_name' => $status->st_name,
        'count' => $count ? $count->count : 0
    ];
}
```

**Optimization**:
- Cache status reference data (rarely changes)
- Use GROUP BY efficiently

---

### 6. Category Tickets Chart Data

**Purpose**: Get ticket count by category

**Query**:
```sql
SELECT 
    rc.id,
    rc.cat_name,
    COUNT(t.id) as count
FROM ref_categories rc
LEFT JOIN tickets t ON t.cat_id = rc.id
    AND t.deleted_at IS NULL
WHERE rc.deleted_at IS NULL
GROUP BY rc.id, rc.cat_name
ORDER BY count DESC
```

**Laravel Query Builder**:
```php
// Get all categories
$allCategories = RefCategory::select('id', 'cat_name')
    ->orderBy('id')
    ->get();

// Get ticket counts
$categoryQuery = DB::table('tickets as t')
    ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
    ->select('rc.id', DB::raw('COUNT(t.id) as count'))
    ->whereNull('t.deleted_at')
    ->whereNull('rc.deleted_at');

// Filter for Pegawai Tugasan
if ($isPegawaiOnly) {
    $categoryQuery->join('tickets_chronologies as tc', function ($join) use ($userId) {
        $join->on('t.id', '=', 'tc.ticket_id')
            ->where('tc.escalate_to', $userId)
            ->whereRaw('tc.id = (
                SELECT MAX(tc2.id)
                FROM tickets_chronologies tc2
                WHERE tc2.ticket_id = t.id
                AND tc2.deleted_at IS NULL
            )');
    })->whereNull('tc.deleted_at');
}

$categoryCounts = $categoryQuery
    ->groupBy('rc.id')
    ->pluck('count', 'id');

// Build complete data
$categoryData = [];
foreach ($allCategories as $category) {
    $categoryData[] = [
        'id' => $category->id,
        'cat_name' => $category->cat_name,
        'count' => $categoryCounts[$category->id] ?? 0
    ];
}
```

---

### 7. Monthly Trend Data

**Purpose**: Get ticket counts for last 6 months

**Query**:
```sql
SELECT 
    YEAR(t.created_at) as year,
    MONTH(t.created_at) as month,
    COUNT(*) as count
FROM tickets t
WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  AND t.deleted_at IS NULL
GROUP BY YEAR(t.created_at), MONTH(t.created_at)
ORDER BY year, month
```

**Laravel Query Builder**:
```php
$months = 6;
$startDate = Carbon::now()->subMonths($months)->startOfMonth();

$query = DB::table('tickets as t')
    ->select(
        DB::raw('YEAR(t.created_at) as year'),
        DB::raw('MONTH(t.created_at) as month'),
        DB::raw('COUNT(*) as count')
    )
    ->where('t.created_at', '>=', $startDate)
    ->whereNull('t.deleted_at')
    ->groupBy(DB::raw('YEAR(t.created_at)'), DB::raw('MONTH(t.created_at)'))
    ->orderBy(DB::raw('YEAR(t.created_at)'))
    ->orderBy(DB::raw('MONTH(t.created_at)'))
    ->get();

// Format for chart
$chartData = [];
foreach ($query as $row) {
    $monthName = Carbon::createFromDate($row->year, $row->month, 1)
        ->format('M Y');
    $chartData[] = [
        'month' => $monthName,
        'count' => $row->count,
    ];
}
```

---

### 8. Overdue Tickets List (DataTables)

**Purpose**: Get list of overdue tickets for table

**Query**:
```sql
SELECT 
    t.id,
    t.ticket_no,
    t.title,
    rc.cat_name as category,
    u.nama as assigned_to,
    tc.sla_duedate,
    TIMESTAMPDIFF(HOUR, tc.sla_duedate, NOW()) as overdue_hours
FROM tickets t
JOIN tickets_chronologies tc ON t.id = tc.ticket_id
    AND tc.id = (
        SELECT MAX(tc2.id)
        FROM tickets_chronologies tc2
        WHERE tc2.ticket_id = t.id
        AND tc2.deleted_at IS NULL
    )
LEFT JOIN ref_categories rc ON t.cat_id = rc.id
LEFT JOIN users u ON tc.escalate_to = u.id
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL
  AND tc.tick_status NOT IN (5, 6)
  AND tc.sla_duedate IS NOT NULL
  AND tc.sla_duedate < NOW()
ORDER BY tc.sla_duedate ASC
```

**Laravel Query Builder** (with DataTables):
```php
public function getListOverdue(Request $request)
{
    $query = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', function ($join) {
            $join->on('t.id', '=', 'tc.ticket_id')
                ->whereRaw('tc.id = (
                    SELECT MAX(tc2.id)
                    FROM tickets_chronologies tc2
                    WHERE tc2.ticket_id = t.id
                    AND tc2.deleted_at IS NULL
                )');
        })
        ->leftJoin('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->leftJoin('users as u', 'tc.escalate_to', '=', 'u.id')
        ->select(
            't.id',
            't.ticket_no',
            't.title',
            'rc.cat_name as category',
            'u.nama as assigned_to',
            'tc.sla_duedate',
            DB::raw('TIMESTAMPDIFF(HOUR, tc.sla_duedate, NOW()) as overdue_hours')
        )
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('tc.tick_status', [5, 6])
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', Carbon::now());

    // Filter for Pegawai Tugasan
    if ($isPegawaiOnly) {
        $query->where('tc.escalate_to', $userId);
    }

    return DataTables::of($query)
        ->editColumn('sla_duedate', function ($row) {
            return Carbon::parse($row->sla_duedate)->format('d/m/Y H:i');
        })
        ->editColumn('overdue_hours', function ($row) {
            return '<span class="badge bg-danger">' . $row->overdue_hours . ' jam</span>';
        })
        ->addColumn('action', function ($row) {
            return '<a href="' . route('tickets.show', $row->id) . '" class="btn btn-sm btn-primary">
                <i class="bx bx-show"></i>
            </a>';
        })
        ->rawColumns(['overdue_hours', 'action'])
        ->make(true);
}
```

---

## Database Optimization

### 1. Indexes

**Critical Indexes**:
```sql
-- tickets table
ALTER TABLE tickets ADD INDEX idx_cat_created (cat_id, created_at, deleted_at);
ALTER TABLE tickets ADD INDEX idx_created_at (created_at);

-- tickets_chronologies table
ALTER TABLE tickets_chronologies ADD INDEX idx_ticket_status_escalate (
    ticket_id, tick_status, escalate_to, deleted_at
);
ALTER TABLE tickets_chronologies ADD INDEX idx_sla_status (
    sla_duedate, tick_status, deleted_at
);
ALTER TABLE tickets_chronologies ADD INDEX idx_escalate_created (
    escalate_to, created_at, deleted_at
);
ALTER TABLE tickets_chronologies ADD INDEX idx_ticket_id_desc (
    ticket_id, id DESC, deleted_at
);
```

### 2. Query Caching

**Implementation**:
```php
// Cache widget data for 5 minutes
public function getData(User $user): array
{
    $cacheKey = 'dashboard.widget.' . $this->widgetName . '.' . $user->id;
    
    return Cache::remember($cacheKey, 300, function () use ($user) {
        return $this->fetchData($user);
    });
}

// Clear cache on ticket update
public function updateTicket($ticketId, $data)
{
    // Update ticket...
    
    // Clear related caches
    Cache::tags(['dashboard'])->flush();
}
```

### 3. Database Views

Create materialized view untuk latest chronology:
```sql
CREATE VIEW v_tickets_latest_chronology AS
SELECT 
    t.id as ticket_id,
    t.ticket_no,
    t.title,
    t.cat_id,
    tc.id as chronology_id,
    tc.tick_status,
    tc.escalate_to,
    tc.sla_duedate,
    tc.created_at,
    tc.updated_at
FROM tickets t
JOIN tickets_chronologies tc ON t.id = tc.ticket_id
    AND tc.id = (
        SELECT MAX(tc2.id)
        FROM tickets_chronologies tc2
        WHERE tc2.ticket_id = t.id
        AND tc2.deleted_at IS NULL
    )
WHERE t.deleted_at IS NULL
  AND tc.deleted_at IS NULL;
```

Usage:
```php
$query = DB::table('v_tickets_latest_chronology as v')
    ->where('v.escalate_to', $userId)
    ->whereNotIn('v.tick_status', [5, 6])
    ->count();
```

### 4. Query Optimization Tips

1. **Avoid N+1 Queries**: Use eager loading
2. **Use indexes**: Check EXPLAIN for query plans
3. **Limit result sets**: Use pagination
4. **Cache expensive queries**: Especially for charts
5. **Use query builder**: Instead of raw queries
6. **Optimize JOINs**: Use appropriate join types
7. **Consider partitioning**: For very large tables

### 5. Monitoring

Track slow queries:
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; -- 2 seconds
```

Monitor with Laravel Telescope or Debugbar in development.
