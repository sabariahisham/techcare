# 04 - Dashboard: Services

## Service Layer Overview

Service layer mengandungi business logic untuk Dashboard. Ia dipisahkan kepada:
1. **DashboardService** (Dispatcher)
2. **Role-specific Services** (AdminDashboard, PegawaiTugasanDashboard, dll)
3. **Widget Services** (Reusable components)

---

## 1. DashboardService (Dispatcher)

### File Location
```
app/Services/Dashboard/DashboardService.php
```

### Purpose
Bertindak sebagai **traffic controller** yang route request kepada role-specific services.

### Class Structure
```php
namespace App\Services\Dashboard;

class DashboardService
{
    protected AdminDashboard $adminDashboard;
    protected PegawaiTugasanDashboard $pegawaiDashboard;
    protected PengurusanDashboard $pengurusanDashboard;
    protected HelpdeskDashboard $helpdeskDashboard;

    public function __construct(
        AdminDashboard $adminDashboard,
        PegawaiTugasanDashboard $pegawaiDashboard,
        PengurusanDashboard $pengurusanDashboard,
        HelpdeskDashboard $helpdeskDashboard
    ) {
        $this->adminDashboard = $adminDashboard;
        $this->pegawaiDashboard = $pegawaiDashboard;
        $this->pengurusanDashboard = $pengurusanDashboard;
        $this->helpdeskDashboard = $helpdeskDashboard;
    }

    public function getDashboardData(User $user): array
    {
        // Routing logic
    }
}
```

### Main Method: `getDashboardData()`

**Signature**:
```php
public function getDashboardData(User $user): array
```

**Purpose**: 
- Check user's roles
- Dispatch ke appropriate role service(s)
- Merge data dari multiple roles
- Return complete data array untuk Blade

**Algorithm**:
```
1. Initialize mergedData structure:
   - user: User object
   - sections: [pegawai, admin, pengurusan, helpdesk] = false

2. Initialize ticketData = []

3. FOR EACH role user has:
   a. Call role-specific service getData()
   b. Set sections[role] = true
   c. Remove 'user' and 'sections' from returned data
   d. Merge data into ticketData

4. Merge mergedData + ticketData

5. Return complete array
```

**Code**:
```php
public function getDashboardData(User $user): array
{
    // Step 1: Initialize structure
    $mergedData = [
        'user' => $user,
        'sections' => [
            'pegawai' => false,
            'admin' => false,
            'pengurusan' => false,
            'helpdesk' => false,
        ],
    ];

    $ticketData = [];

    // Step 2: Check Pegawai Tugasan role
    if ($user->hasRole('Pegawai Tugasan')) {
        $data = $this->pegawaiDashboard->getData($user);
        $mergedData['sections']['pegawai'] = true;
        unset($data['user'], $data['sections']);
        $ticketData = array_merge($ticketData, $data);
    }

    // Step 3: Check Administrator role
    if ($user->hasRole('Administrator')) {
        $data = $this->adminDashboard->getData($user);
        $mergedData['sections']['admin'] = true;
        unset($data['user'], $data['sections']);
        $ticketData = array_merge($ticketData, $data);
    }

    // Step 4: Check Pengurusan MOF role
    if ($user->hasRole('Pengurusan MOF')) {
        $data = $this->pengurusanDashboard->getData($user);
        $mergedData['sections']['pengurusan'] = true;
        unset($data['user'], $data['sections']);
        $ticketData = array_merge($ticketData, $data);
    }

    // Step 5: Check Helpdesk roles
    if ($user->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])) {
        $data = $this->helpdeskDashboard->getData($user);
        $mergedData['sections']['helpdesk'] = true;
        unset($data['user'], $data['sections']);
        $ticketData = array_merge($ticketData, $data);
    }

    // Step 6: Merge and return
    return array_merge($mergedData, $ticketData);
}
```

**Multi-Role Support**:
- User boleh ada multiple roles
- Setiap role dapat section tersendiri dalam dashboard
- Data dari semua roles di-merge
- Sections flags control UI visibility

**Example Output**:
```php
[
    'user' => User {...},
    'sections' => [
        'pegawai' => true,    // User has Pegawai Tugasan role
        'admin' => true,       // User has Administrator role
        'pengurusan' => false, // User does NOT have Pengurusan role
        'helpdesk' => false,   // User does NOT have Helpdesk roles
    ],
    'tickets_summary' => [...],
    'monthly_trend' => [...],
    'my_tasks' => [...],
    // ... more data
]
```

---

## 2. Role-Specific Dashboard Services

### Base Pattern
Setiap role dashboard service mengikut pattern yang sama:

```php
namespace App\Services\Dashboard\Role;

use App\Models\User;

class [Role]Dashboard
{
    protected [Widget1] $widget1;
    protected [Widget2] $widget2;
    
    public function __construct([Widget1] $widget1, [Widget2] $widget2)
    {
        $this->widget1 = $widget1;
        $this->widget2 = $widget2;
    }
    
    public function getData(User $user): array
    {
        return [
            'key1' => $this->widget1->getData($user),
            'key2' => $this->widget2->getData($user),
            // ...
        ];
    }
}
```

---

### 2.1 AdminDashboard

**File**: `app/Services/Dashboard/Role/AdminDashboard.php`

**Purpose**: Menyediakan dashboard untuk Administrator

**Widgets**:
- TicketSummaryWidget
- TrendBulananWidget
- KategoriWidget
- SLAWidget

**Data Scope**: ALL tickets (keseluruhan sistem)

**Method**:
```php
public function getData(User $user): array
{
    return [
        'user' => $user,
        'sections' => ['admin' => true],
        'tickets_summary' => $this->ticketSummary->getData($user),
        'monthly_trend' => $this->trendBulanan->getData($user),
        'category_breakdown' => $this->kategoriWidget->getData($user),
        'sla_metrics' => $this->slaWidget->getData($user),
    ];
}
```

**Sample Return Data**:
```php
[
    'user' => User,
    'sections' => ['admin' => true],
    'tickets_summary' => [
        'total' => 150,
        'new' => 20,
        'in_progress' => 30,
        'completed' => 100,
    ],
    'monthly_trend' => [
        ['month' => 'Jan 2025', 'count' => 45],
        ['month' => 'Feb 2025', 'count' => 52],
    ],
    'category_breakdown' => [...],
    'sla_metrics' => [
        'on_time' => 90,
        'overdue' => 10,
        'percentage' => 90.0,
    ],
]
```

---

### 2.2 HelpdeskDashboard

**File**: `app/Services/Dashboard/Role/HelpdeskDashboard.php`

**Purpose**: Dashboard untuk Helpdesk team (Pentadbir Sistem, Penyelia, On Duty)

**Widgets**:
- TicketSummaryWidget
- TrendBulananWidget
- PegawaiPerformanceWidget

**Data Scope**: ALL tickets (untuk monitoring purposes)

**Method**:
```php
public function getData(User $user): array
{
    return [
        'user' => $user,
        'sections' => ['helpdesk' => true],
        'tickets_summary' => $this->ticketSummary->getData($user),
        'monthly_trend' => $this->trendBulanan->getData($user),
        'pegawai_performance' => $this->pegawaiPerformance->getData($user),
    ];
}
```

**Sample Return Data**:
```php
[
    'user' => User,
    'sections' => ['helpdesk' => true],
    'tickets_summary' => [...],
    'monthly_trend' => [...],
    'pegawai_performance' => [
        [
            'pegawai_name' => 'Ahmad bin Ali',
            'assigned_count' => 15,
            'completed_count' => 10,
            'pending_count' => 5,
            'overdue_count' => 2,
            'completion_rate' => 66.7,
        ],
        // ... more pegawai
    ],
]
```

---

### 2.3 PegawaiTugasanDashboard

**File**: `app/Services/Dashboard/Role/PegawaiTugasanDashboard.php`

**Purpose**: Dashboard untuk Pegawai Tugasan

**Widgets**:
- TugasanAndaWidget
- TicketSummaryWidget (filtered)

**Data Scope**: ONLY tickets assigned to this pegawai

**Method**:
```php
public function getData(User $user): array
{
    return [
        'user' => $user,
        'sections' => ['pegawai' => true],
        'my_tasks' => $this->tugasanAnda->getData($user),
        'my_tickets_summary' => $this->ticketSummary->getDataForPegawai($user),
    ];
}
```

**Sample Return Data**:
```php
[
    'user' => User,
    'sections' => ['pegawai' => true],
    'my_tasks' => [
        'assigned_to_me' => 12,
        'pending' => 8,
        'completed_this_week' => 4,
        'overdue' => 2,
    ],
    'my_tickets_summary' => [
        'total' => 12,
        'new' => 3,
        'in_progress' => 5,
        'completed' => 4,
    ],
]
```

**Important**: 
- Data di-filter by `escalate_to = $user->id`
- Pegawai hanya nampak tickets assigned to them

---

### 2.4 PengurusanDashboard

**File**: `app/Services/Dashboard/Role/PengurusanDashboard.php`

**Purpose**: Dashboard untuk Management (Pengurusan MOF)

**Widgets**:
- Executive summary widgets
- Department performance
- KPI metrics

**Data Scope**: High-level summaries

**Method**:
```php
public function getData(User $user): array
{
    return [
        'user' => $user,
        'sections' => ['pengurusan' => true],
        'executive_summary' => $this->executiveSummary->getData($user),
        'department_performance' => $this->deptPerformance->getData($user),
        'kpi_metrics' => $this->kpiMetrics->getData($user),
    ];
}
```

---

## 3. Widget Services

Widgets adalah **reusable, single-purpose services** yang menyediakan specific data.

### Base Widget Pattern

```php
namespace App\Services\Dashboard\Widgets;

use App\Models\User;

class [WidgetName]Widget
{
    public function getData(User $user, array $filters = []): array
    {
        // 1. Query database
        // 2. Transform/format data
        // 3. Return array
    }
}
```

---

### 3.1 TugasanAndaWidget

**File**: `app/Services/Dashboard/Widgets/TugasanAndaWidget.php`

**Purpose**: Menyediakan data "My Tasks" untuk Pegawai Tugasan

**Method**:
```php
public function getData(User $user): array
{
    $userId = $user->id;
    
    // Get assigned tickets
    $assigned = DB::table('tickets_chronologies as tc')
        ->where('tc.escalate_to', $userId)
        ->whereNull('tc.deleted_at')
        ->count();
    
    // Get pending tickets
    $pending = DB::table('tickets_chronologies as tc')
        ->where('tc.escalate_to', $userId)
        ->whereNotIn('tc.tick_status', [5, 6])
        ->whereNull('tc.deleted_at')
        ->count();
    
    // Get completed this week
    $completedThisWeek = DB::table('tickets_chronologies as tc')
        ->where('tc.escalate_to', $userId)
        ->where('tc.tick_status', 6)
        ->whereBetween('tc.updated_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])
        ->whereNull('tc.deleted_at')
        ->count();
    
    // Get overdue
    $overdue = DB::table('tickets_chronologies as tc')
        ->where('tc.escalate_to', $userId)
        ->where('tc.tick_status', '!=', 6)
        ->where('tc.sla_duedate', '<', Carbon::now())
        ->whereNull('tc.deleted_at')
        ->count();
    
    return [
        'assigned_to_me' => $assigned,
        'pending' => $pending,
        'completed_this_week' => $completedThisWeek,
        'overdue' => $overdue,
    ];
}
```

---

### 3.2 TrendBulananWidget

**File**: `app/Services/Dashboard/Widgets/TrendBulananWidget.php`

**Purpose**: Menyediakan trend data untuk last 6 months

**Method**:
```php
public function getData(User $user, array $filters = []): array
{
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
    
    // Format untuk chart
    $chartData = [];
    foreach ($query as $row) {
        $monthName = Carbon::createFromDate($row->year, $row->month, 1)
            ->format('M Y');
        $chartData[] = [
            'month' => $monthName,
            'count' => $row->count,
        ];
    }
    
    return $chartData;
}
```

**Return Format**:
```php
[
    ['month' => 'Aug 2025', 'count' => 45],
    ['month' => 'Sep 2025', 'count' => 52],
    ['month' => 'Oct 2025', 'count' => 48],
    ['month' => 'Nov 2025', 'count' => 60],
    ['month' => 'Dec 2025', 'count' => 55],
    ['month' => 'Jan 2026', 'count' => 50],
]
```

---

### 3.3 TicketSummaryWidget

**File**: `app/Services/Dashboard/Widgets/TicketSummaryWidget.php`

**Purpose**: Menyediakan ringkasan tiket mengikut status

**Methods**:
1. `getData(User $user)` - For admin (all tickets)
2. `getDataForPegawai(User $user)` - For pegawai (filtered)

**Method 1: getData()**
```php
public function getData(User $user): array
{
    // Query all tickets with latest chronology
    $summary = DB::table('tickets as t')
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
        ->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN tc.tick_status = 1 THEN 1 ELSE 0 END) as new'),
            DB::raw('SUM(CASE WHEN tc.tick_status IN (2,3,4) THEN 1 ELSE 0 END) as in_progress'),
            DB::raw('SUM(CASE WHEN tc.tick_status = 6 THEN 1 ELSE 0 END) as completed'),
            DB::raw('SUM(CASE WHEN tc.tick_status = 5 THEN 1 ELSE 0 END) as cancelled')
        )
        ->first();
    
    return [
        'total' => $summary->total ?? 0,
        'new' => $summary->new ?? 0,
        'in_progress' => $summary->in_progress ?? 0,
        'completed' => $summary->completed ?? 0,
        'cancelled' => $summary->cancelled ?? 0,
    ];
}
```

**Method 2: getDataForPegawai()**
```php
public function getDataForPegawai(User $user): array
{
    $userId = $user->id;
    
    // Same query but add filter
    // ... WHERE tc.escalate_to = $userId
    
    return [
        'total' => ...,
        'new' => ...,
        'in_progress' => ...,
        'completed' => ...,
    ];
}
```

---

### 3.4 SLAWidget

**File**: `app/Services/Dashboard/Widgets/SLAWidget.php`

**Purpose**: Menyediakan SLA metrics

**Method**:
```php
public function getData(User $user): array
{
    $now = Carbon::now();
    
    // Total active tickets (not completed/cancelled)
    $totalActive = DB::table('tickets_chronologies as tc')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('tc.tick_status', [5, 6])
        ->count();
    
    // Overdue tickets
    $overdue = DB::table('tickets_chronologies as tc')
        ->whereNull('tc.deleted_at')
        ->whereNotIn('tc.tick_status', [5, 6])
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', $now)
        ->count();
    
    // On time tickets
    $onTime = $totalActive - $overdue;
    
    // Calculate percentage
    $percentage = $totalActive > 0 
        ? round(($onTime / $totalActive) * 100, 2) 
        : 100;
    
    return [
        'total_active' => $totalActive,
        'on_time' => $onTime,
        'overdue' => $overdue,
        'on_time_percentage' => $percentage,
    ];
}
```

---

### 3.5 KategoriWidget

**File**: `app/Services/Dashboard/Widgets/KategoriWidget.php`

**Purpose**: Breakdown tiket mengikut kategori

**Method**:
```php
public function getData(User $user): array
{
    $categories = DB::table('tickets as t')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->select('rc.id', 'rc.cat_name', DB::raw('COUNT(t.id) as count'))
        ->whereNull('t.deleted_at')
        ->whereNull('rc.deleted_at')
        ->groupBy('rc.id', 'rc.cat_name')
        ->orderBy('count', 'DESC')
        ->get();
    
    return $categories->toArray();
}
```

**Return Format**:
```php
[
    ['id' => 1, 'cat_name' => 'Hardware', 'count' => 45],
    ['id' => 2, 'cat_name' => 'Software', 'count' => 38],
    ['id' => 3, 'cat_name' => 'Network', 'count' => 25],
]
```

---

### 3.6 PegawaiPerformanceWidget

**File**: `app/Services/Dashboard/Widgets/PegawaiPerformanceWidget.php`

**Purpose**: Performance metrics untuk setiap pegawai tugasan

**Method**:
```php
public function getData(User $user): array
{
    // Get all users with Pegawai Tugasan role
    $pegawaiUsers = User::role('Pegawai Tugasan')->get();
    
    $performance = [];
    
    foreach ($pegawaiUsers as $pegawai) {
        $userId = $pegawai->id;
        
        // Get assigned count
        $assigned = DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->whereNull('tc.deleted_at')
            ->count();
        
        // Get completed count
        $completed = DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->where('tc.tick_status', 6)
            ->whereNull('tc.deleted_at')
            ->count();
        
        // Get pending count
        $pending = DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->whereNotIn('tc.tick_status', [5, 6])
            ->whereNull('tc.deleted_at')
            ->count();
        
        // Get overdue count
        $overdue = DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->where('tc.tick_status', '!=', 6)
            ->where('tc.sla_duedate', '<', Carbon::now())
            ->whereNull('tc.deleted_at')
            ->count();
        
        // Calculate completion rate
        $completionRate = $assigned > 0 
            ? round(($completed / $assigned) * 100, 2) 
            : 0;
        
        $performance[] = [
            'pegawai_id' => $userId,
            'pegawai_name' => $pegawai->nama,
            'assigned_count' => $assigned,
            'completed_count' => $completed,
            'pending_count' => $pending,
            'overdue_count' => $overdue,
            'completion_rate' => $completionRate,
        ];
    }
    
    // Sort by completion rate descending
    usort($performance, function ($a, $b) {
        return $b['completion_rate'] <=> $a['completion_rate'];
    });
    
    return $performance;
}
```

---

## Service Testing

### Unit Test Example

```php
namespace Tests\Unit\Services\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_admin_gets_admin_section()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        
        $service = app(DashboardService::class);
        $data = $service->getDashboardData($admin);
        
        $this->assertTrue($data['sections']['admin']);
        $this->assertArrayHasKey('tickets_summary', $data);
    }
    
    public function test_pegawai_gets_filtered_data()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');
        
        $service = app(DashboardService::class);
        $data = $service->getDashboardData($pegawai);
        
        $this->assertTrue($data['sections']['pegawai']);
        $this->assertArrayHasKey('my_tasks', $data);
    }
    
    public function test_multi_role_user_gets_merged_data()
    {
        $user = User::factory()->create();
        $user->assignRole(['Administrator', 'Pegawai Tugasan']);
        
        $service = app(DashboardService::class);
        $data = $service->getDashboardData($user);
        
        $this->assertTrue($data['sections']['admin']);
        $this->assertTrue($data['sections']['pegawai']);
    }
}
```

---

## Best Practices

### 1. Single Responsibility
- Setiap widget handle satu jenis data sahaja
- Jangan campur multiple concerns dalam satu widget

### 2. Dependency Injection
- Inject dependencies melalui constructor
- Memudahkan testing dan flexibility

### 3. Return Type Hints
- Gunakan return type array untuk consistency
- Document return structure dalam PHPDoc

### 4. Error Handling
- Gunakan try-catch dalam methods
- Return empty/default data jika error
- Log errors untuk debugging

### 5. Query Optimization
- Gunakan proper indexes
- Avoid N+1 queries
- Use query builder efficiently

### 6. Caching
- Cache expensive queries
- Set appropriate TTL
- Invalidate on data changes
