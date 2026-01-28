# 03 - Dashboard: Controller

## File Location
```
app/Http/Controllers/Dashboard/DashboardController.php
```

## Class Overview
```php
namespace App\Http\Controllers\Dashboard;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
}
```

## Prinsip Controller

### 1. Thin Controller
Controller TIDAK mengandungi business logic. Semua logic di Service layer.

### 2. Delegation Pattern
Controller hanya delegate kepada service dan return view/response.

### 3. No Direct Database Access
Controller TIDAK query database secara langsung.

## Methods

### Main Method

#### `index()`
**Tujuan**: Display main dashboard page

**Signature**:
```php
public function index(): \Illuminate\View\View
```

**Aliran**:
1. Get authenticated user
2. Call `DashboardService::getDashboardData($user)`
3. Return view with data

**Code**:
```php
public function index()
{
    $user = Auth::user();
    $data = $this->dashboardService->getDashboardData($user);
    
    return view('dashboard.dashboard', $data);
}
```

**Data yang dikembalikan ke Blade**:
```php
[
    'user' => User object,
    'sections' => [
        'pegawai' => bool,
        'admin' => bool,
        'pengurusan' => bool,
        'helpdesk' => bool,
    ],
    // ... widget data
]
```

---

## API Endpoints (AJAX)

Dashboard mengandungi beberapa API endpoints untuk charts dan widgets yang load secara async.

### Card Status Endpoints

#### `getTodayLogsCount()`
**Route**: `/dashboard/today-logs-count`
**Method**: GET
**Purpose**: Get count of tickets created today

**Response**:
```json
{
    "success": true,
    "count": 15
}
```

**Filter Logic**:
- Admin roles: Show ALL tickets
- Pegawai Tugasan only: Show tickets assigned to user
- Menggunakan `tickets_chronologies` table
- Filter by `created_at` = today

**Code Pattern**:
```php
public function getTodayLogsCount()
{
    try {
        $today = Carbon::today();
        $userId = Auth::id();

        $query = DB::table('tickets_chronologies as tc')
            ->whereNull('tc.deleted_at')
            ->whereDate('tc.created_at', $today);

        // Apply role filter
        if (!Auth::user()->hasAnyRole([...admin roles]) && 
            Auth::user()->hasRole('Pegawai Tugasan')) {
            $query->where('tc.escalate_to', $userId);
        }

        $todayCount = $query->count();

        return response()->json([
            'success' => true,
            'count' => $todayCount
        ]);
    } catch (\Exception $e) {
        // Return fallback data
        return response()->json([
            'success' => true,
            'count' => 15
        ]);
    }
}
```

---

#### `getWeeklyCompletedTickets()`
**Route**: `/dashboard/weekly-completed`
**Method**: GET
**Purpose**: Get completed tickets for current week (Monday-Friday)

**Response**:
```json
{
    "success": true,
    "total": 40,
    "daily_data": [
        {"day": "Isnin", "count": 8},
        {"day": "Selasa", "count": 12},
        {"day": "Rabu", "count": 6},
        {"day": "Khamis", "count": 9},
        {"day": "Jumaat", "count": 5}
    ]
}
```

**Filter Logic**:
- Tickets with `tick_status = 6` (Completed)
- Updated between Monday to Friday of current week
- Admin: All tickets
- Pegawai Tugasan: Assigned tickets only

---

#### `getPendingTicketsStats()`
**Route**: `/dashboard/pending-stats`
**Method**: GET
**Purpose**: Get pending tickets statistics

**Response**:
```json
{
    "success": true,
    "pending_count": 25,
    "total_count": 100,
    "percentage": 25.0
}
```

**Filter Logic**:
- Pending = NOT status 5 (Cancelled) or 6 (Completed)
- Calculate percentage: (pending/total) * 100
- Use latest chronology entry for each ticket

**Query Pattern**:
```php
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
    ->whereNotIn('tc.tick_status', [5, 6]);
```

---

#### `getOverdueSLACount()`
**Route**: `/dashboard/overdue-sla`
**Method**: GET
**Purpose**: Get count of tickets that exceeded SLA

**Response**:
```json
{
    "success": true,
    "count": 7
}
```

**Filter Logic**:
- `sla_duedate < current_time`
- NOT completed (status ≠ 6)
- NOT cancelled (status ≠ 5)

---

### Chart Endpoints

#### `getStatusTickets()`
**Route**: `/dashboard/status-tickets`
**Method**: GET
**Purpose**: Get ticket counts by status for chart

**Response**:
```json
{
    "success": true,
    "data": [
        {"id": 1, "st_name": "Aduan Baru", "count": 15},
        {"id": 2, "st_name": "Aduan Diproses", "count": 8},
        {"id": 3, "st_name": "Serahan Kepada", "count": 5},
        {"id": 4, "st_name": "Aduan Dirujuk", "count": 3},
        {"id": 5, "st_name": "Aduan DiBatal", "count": 2},
        {"id": 6, "st_name": "Aduan Selesai", "count": 25}
    ]
}
```

**Data Source**: `ref_ticket_statuses` + `tickets_chronologies`

**Query Logic**:
```php
$statusQuery = DB::table('tickets_chronologies as tc')
    ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
    ->select('rts.id', 'rts.st_name', DB::raw('COUNT(tc.id) as count'))
    ->whereNull('tc.deleted_at')
    ->whereNull('rts.deleted_at')
    ->groupBy('rts.id', 'rts.st_name');
```

**Note**: Include all statuses with zero count if not in chronologies.

---

#### `getCategoryTickets()`
**Route**: `/dashboard/category-tickets`
**Method**: GET
**Purpose**: Get ticket counts by category for chart

**Response**:
```json
{
    "success": true,
    "data": [
        {"id": 1, "cat_name": "Hardware", "count": 25},
        {"id": 2, "cat_name": "Software", "count": 30},
        {"id": 3, "cat_name": "Network", "count": 15}
    ]
}
```

**Data Source**: `ref_categories` + `tickets`

**Query Logic**:
- Join tickets with categories
- Count tickets per category
- Include categories with zero tickets
- Filter by user role if Pegawai Tugasan

---

#### `getTotalTickets()`
**Route**: `/dashboard/total-tickets`
**Method**: GET
**Purpose**: Get total ticket count and breakdown

**Response**:
```json
{
    "success": true,
    "total": 150,
    "breakdown": {
        "new": 20,
        "in_progress": 30,
        "completed": 100
    }
}
```

---

#### `getMonthlyTrends()`
**Route**: `/dashboard/monthly-trend`
**Method**: GET
**Purpose**: Get monthly ticket trends for last 6 months

**Response**:
```json
{
    "success": true,
    "data": [
        {"month": "Jul 2025", "count": 45},
        {"month": "Aug 2025", "count": 52},
        {"month": "Sep 2025", "count": 48},
        {"month": "Oct 2025", "count": 60},
        {"month": "Nov 2025", "count": 55},
        {"month": "Dec 2025", "count": 50}
    ]
}
```

**Query Logic**:
- Get data for last 6 months
- Group by month and year
- Count tickets created per month

---

### Table Endpoints

#### `getTotalAssigned()`
**Route**: `/dashboard/total-assigned`
**Method**: GET
**Purpose**: Get assigned tickets statistics

**Response**:
```json
{
    "success": true,
    "assigned_count": 45,
    "unassigned_count": 5
}
```

---

#### `getListOverdue()`
**Route**: `/dashboard/list-overdue`
**Method**: GET
**Purpose**: Get list of overdue tickets for DataTable

**Response**: DataTables JSON format
```json
{
    "draw": 1,
    "recordsTotal": 100,
    "recordsFiltered": 10,
    "data": [
        {
            "ticket_no": "TK-2025-0001",
            "title": "Computer not working",
            "category": "Hardware",
            "assigned_to": "Ahmad",
            "sla_duedate": "2025-01-15 10:00:00",
            "overdue_hours": 24
        }
    ]
}
```

**Features**:
- Server-side processing
- Sortable columns
- Searchable
- Pagination

---

## Route Registration

### File: `routes/web.php`

```php
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        // Main dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');
        
        // Card APIs
        Route::get('/today-logs-count', [DashboardController::class, 'getTodayLogsCount'])
            ->name('today-logs-count');
        Route::get('/weekly-completed', [DashboardController::class, 'getWeeklyCompletedTickets'])
            ->name('weekly-completed');
        Route::get('/pending-stats', [DashboardController::class, 'getPendingTicketsStats'])
            ->name('pending-stats');
        Route::get('/overdue-sla', [DashboardController::class, 'getOverdueSLACount'])
            ->name('overdue-sla');
        
        // Chart APIs
        Route::get('/status-tickets', [DashboardController::class, 'getStatusTickets'])
            ->name('status-tickets');
        Route::get('/category-tickets', [DashboardController::class, 'getCategoryTickets'])
            ->name('category-tickets');
        Route::get('/total-tickets', [DashboardController::class, 'getTotalTickets'])
            ->name('total-tickets');
        Route::get('/monthly-trend', [DashboardController::class, 'getMonthlyTrends'])
            ->name('monthly-trend');
        
        // Table APIs
        Route::get('/total-assigned', [DashboardController::class, 'getTotalAssigned'])
            ->name('total-assigned');
        Route::get('/list-overdue', [DashboardController::class, 'getListOverdue'])
            ->name('list-overdue');
    });
```

---

## Error Handling Strategy

### Try-Catch Pattern
Semua API endpoints menggunakan try-catch:

```php
public function getApiData()
{
    try {
        // Query and process data
        $data = $this->queryData();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    } catch (\Exception $e) {
        // Log error
        Log::error('Dashboard API Error: ' . $e->getMessage());
        
        // Return fallback/sample data
        return response()->json([
            'success' => true,
            'data' => $this->getFallbackData()
        ]);
    }
}
```

### Fallback Data
- Setiap endpoint ada fallback/sample data
- Memastikan UI tidak break walaupun ada error
- User tetap dapat lihat dashboard

---

## Testing Guidelines

### Unit Tests
Test setiap method dengan:
1. Valid user with correct role
2. Valid user with wrong role
3. Unauthenticated user
4. Database error scenario

### Example Test
```php
public function test_admin_can_see_all_tickets()
{
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');
    
    $this->actingAs($admin)
        ->get(route('dashboard.today-logs-count'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'count'
        ]);
}
```

---

## Performance Optimization

### 1. Query Optimization
- Use proper indexes
- Avoid N+1 queries
- Use query builder efficiently

### 2. Caching
```php
public function getTodayLogsCount()
{
    $cacheKey = 'dashboard.today_logs.' . Auth::id();
    
    return Cache::remember($cacheKey, 300, function () {
        return $this->queryTodayLogs();
    });
}
```

### 3. Response Caching
- Cache API responses for 5 minutes
- Invalidate on data change

### 4. Lazy Loading
- Load charts async after page load
- Reduce initial page load time
