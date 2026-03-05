# 02 - Dashboard Pegawai Tugasan: Arkitektur

## Pattern Overview

Dashboard Pegawai Tugasan menggunakan **Service-Oriented Architecture** dengan **Data Filtering Pattern** sebagai core principle.

### Key Architectural Principles
1. **Separation of Concerns** - Controller, Service, Widget, View dipisahkan
2. **Data Filtering** - All queries filtered by `escalate_to`
3. **Single Responsibility** - Each component has one job
4. **Dependency Injection** - Services injected, not instantiated
5. **Testability** - All components independently testable

---

## Architecture Layers

```
┌──────────────────────────────────────────────────┐
│  Presentation Layer (Blade Templates)            │
│  - partials/pegawai-tugasan/*.blade.php          │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────┐
│  Controller Layer (DashboardController)          │
│  - index() method                                 │
│  - API endpoints                                  │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────┐
│  Service Layer (DashboardService - Dispatcher)   │
│  - getDashboardData()                             │
│  - Role checking                                  │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────┐
│  Role Service (PegawaiTugasanDashboard)          │
│  - getData($user)                                 │
│  - Coordinate widgets                             │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────┐
│  Widget Layer (Reusable Components)              │
│  - TugasanAndaWidget                              │
│  - TicketSummaryWidget                            │
└────────────────┬─────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────┐
│  Data Layer (Laravel Query Builder + Models)     │
│  - Database queries with filtering                │
└──────────────────────────────────────────────────┘
```

---

## Component Interaction

### Request Flow

```
1. User Request
   ↓
2. Route: /dashboard
   ↓
3. DashboardController::index()
   │
   ├─→ Call: DashboardService::getDashboardData($user)
   │
   ├─→ Check: $user->hasRole('Pegawai Tugasan')? → YES
   │
   ├─→ Call: PegawaiTugasanDashboard::getData($user)
   │       │
   │       ├─→ TugasanAndaWidget::getData($user)
   │       │       │
   │       │       └─→ DB Query: WHERE escalate_to = $user->id
   │       │
   │       ├─→ TicketSummaryWidget::getData($user)
   │       │       │
   │       │       └─→ DB Query: WHERE escalate_to = $user->id
   │       │
   │       └─→ Return: [
   │               'my_tasks' => [...],
   │               'my_tickets_summary' => [...]
   │           ]
   │
   ├─→ Set: $mergedData['sections']['pegawai'] = true
   │
   └─→ Return to view: compact('user', 'sections', 'my_tasks', ...)
   
4. Blade Template Render
   │
   └─→ @if($sections['pegawai'])
           @include('dashboard/partials/pegawai-tugasan/...')
       @endif

5. Frontend Display
```

---

## PegawaiTugasanDashboard Service

### Class Structure

**File**: `app/Services/Dashboard/Role/PegawaiTugasanDashboard.php`

```php
<?php

namespace App\Services\Dashboard\Role;

use App\Models\User;
use App\Services\Dashboard\Widgets\TugasanAndaWidget;
use App\Services\Dashboard\Widgets\TicketSummaryWidget;

class PegawaiTugasanDashboard
{
    protected TugasanAndaWidget $tugasanAnda;
    protected TicketSummaryWidget $ticketSummary;

    /**
     * Constructor - Dependency Injection
     */
    public function __construct(
        TugasanAndaWidget $tugasanAnda,
        TicketSummaryWidget $ticketSummary
    ) {
        $this->tugasanAnda = $tugasanAnda;
        $this->ticketSummary = $ticketSummary;
    }

    /**
     * Get dashboard data for Pegawai Tugasan
     * 
     * @param User $user
     * @return array
     */
    public function getData(User $user): array
    {
        return [
            'user' => $user,
            'sections' => ['pegawai' => true],
            'my_tasks' => $this->tugasanAnda->getData($user),
            'my_tickets_summary' => $this->ticketSummary->getDataForPegawai($user),
        ];
    }
}
```

### Responsibilities
1. ✅ Coordinate widget calls
2. ✅ Pass $user object to widgets
3. ✅ Structure return data
4. ✅ Set section flag
5. ❌ NOT responsible for data filtering (widgets handle this)
6. ❌ NOT responsible for database queries (widgets handle this)

---

## Widget Architecture

### Widget Base Pattern

```php
namespace App\Services\Dashboard\Widgets;

use App\Models\User;

class [WidgetName]Widget
{
    /**
     * Get widget data
     * 
     * @param User $user - Current logged-in user
     * @param array $filters - Additional filters (optional)
     * @return array
     */
    public function getData(User $user, array $filters = []): array
    {
        // 1. Extract user ID
        $userId = $user->id;
        
        // 2. Query database with filtering
        $data = DB::table('...')
            ->where('escalate_to', $userId)  // ← CRITICAL FILTER
            ->get();
        
        // 3. Transform/format data
        $formatted = $this->formatData($data);
        
        // 4. Return array
        return $formatted;
    }
}
```

### Widget Principles
1. **Single Purpose** - One widget = one type of data
2. **Filtered by Default** - Always filter by user
3. **Reusable** - Can be used by any service
4. **Stateless** - No internal state, pure functions
5. **Testable** - Easy to unit test

---

## Data Filtering Mechanism

### The Critical Filter

**Every query for pegawai MUST include**:

```php
->where('escalate_to', $user->id)
```

### Why This Column?

In `tickets_chronologies` table:
- `escalate_to` = ID of user yang menerima assignment
- Updated when helpdesk assign tiket
- Used to track who is responsible

### Implementation at Widget Level

#### TugasanAndaWidget
```php
public function getData(User $user): array
{
    $userId = $user->id;  // Get current user ID
    
    // Count assigned tickets
    $assigned = DB::table('tickets_chronologies as tc')
        ->where('tc.escalate_to', $userId)  // ← FILTER
        ->whereNull('tc.deleted_at')
        ->count();
    
    // ... more queries, all with same filter
}
```

#### TicketSummaryWidget::getDataForPegawai()
```php
public function getDataForPegawai(User $user): array
{
    $userId = $user->id;
    
    $summary = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', ...)
        ->where('tc.escalate_to', $userId)  // ← FILTER
        ->whereNull('t.deleted_at')
        ->select(...)
        ->first();
    
    return [...];
}
```

### Security Implication

Filter di **query level** ensures:
- No accidental data leakage
- Cannot be bypassed by frontend
- Logged in database query logs
- Performance: smaller result sets

---

## Dispatcher Pattern

### DashboardService as Dispatcher

```php
public function getDashboardData(User $user): array
{
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

    // Check Pegawai Tugasan role
    if ($user->hasRole('Pegawai Tugasan')) {
        $data = $this->pegawaiDashboard->getData($user);
        $mergedData['sections']['pegawai'] = true;
        
        // Remove duplicate keys before merge
        unset($data['user'], $data['sections']);
        
        // Merge into ticketData
        $ticketData = array_merge($ticketData, $data);
    }

    // Check other roles...
    // (user can have multiple roles)

    // Final merge
    return array_merge($mergedData, $ticketData);
}
```

### Why Dispatcher Pattern?

**Problem**: Users can have multiple roles
- User bisa jadi Admin + Pegawai Tugasan
- Need to show both dashboards

**Solution**: Dispatcher pattern
- Loop through each role
- Call appropriate service
- Merge results
- Set section flags

**Benefit**:
- Flexible: easy to add new roles
- Maintainable: each role service independent
- Testable: can test each role separately

---

## Data Flow Pattern

### From Database to View

```
┌─────────────────────────────────────────┐
│  Database (tickets_chronologies)        │
│  - escalate_to = 123                     │
│  - tick_status, sla_duedate, etc.        │
└──────────────┬──────────────────────────┘
               │ Query dengan WHERE escalate_to = $userId
┌──────────────┴──────────────────────────┐
│  Widget (TugasanAndaWidget)              │
│  - Execute query                          │
│  - Count/aggregate data                   │
│  - Format hasil                           │
└──────────────┬──────────────────────────┘
               │ Return array
┌──────────────┴──────────────────────────┐
│  Service (PegawaiTugasanDashboard)       │
│  - Collect dari widgets                   │
│  - Structure data                         │
└──────────────┬──────────────────────────┘
               │ Return array
┌──────────────┴──────────────────────────┐
│  Dispatcher (DashboardService)           │
│  - Set section['pegawai'] = true          │
│  - Merge dengan data roles lain           │
└──────────────┬──────────────────────────┘
               │ Pass to view
┌──────────────┴──────────────────────────┐
│  Controller (DashboardController)        │
│  - Return view dengan data                │
└──────────────┬──────────────────────────┘
               │ Render
┌──────────────┴──────────────────────────┐
│  View (dashboard.blade.php)              │
│  - @if($sections['pegawai'])              │
│  - @include partials                      │
└──────────────┬──────────────────────────┘
               │ Display
┌──────────────┴──────────────────────────┐
│  Browser (User sees dashboard)           │
└──────────────────────────────────────────┘
```

---

## Error Handling

### At Widget Level
```php
public function getData(User $user): array
{
    try {
        $userId = $user->id;
        
        $data = DB::table('...')
            ->where('escalate_to', $userId)
            ->get();
        
        return $this->formatData($data);
        
    } catch (\Exception $e) {
        // Log error
        Log::error('TugasanAndaWidget error: ' . $e->getMessage());
        
        // Return default/empty data
        return [
            'assigned_to_me' => 0,
            'pending' => 0,
            'completed_this_week' => 0,
            'overdue' => 0,
        ];
    }
}
```

### At Service Level
```php
public function getData(User $user): array
{
    try {
        return [
            'my_tasks' => $this->tugasanAnda->getData($user),
            'my_tickets_summary' => $this->ticketSummary->getDataForPegawai($user),
        ];
    } catch (\Exception $e) {
        Log::error('PegawaiTugasanDashboard error: ' . $e->getMessage());
        
        // Return minimal data
        return [
            'my_tasks' => [],
            'my_tickets_summary' => [],
        ];
    }
}
```

### Error Recovery Strategy
1. **Log** the error with context
2. **Return** safe default values
3. **Don't break** the entire dashboard
4. **Alert** monitoring system if critical

---

## Caching Strategy

### Cache Key Pattern
```php
$cacheKey = "dashboard.pegawai.{$userId}.my_tasks";
```

### Implementation
```php
public function getData(User $user): array
{
    $userId = $user->id;
    $cacheKey = "dashboard.pegawai.{$userId}.my_tasks";
    $cacheTTL = 300; // 5 minutes
    
    return Cache::remember($cacheKey, $cacheTTL, function () use ($userId) {
        return DB::table('tickets_chronologies')
            ->where('escalate_to', $userId)
            ->whereNull('deleted_at')
            ->count();
    });
}
```

### Cache Invalidation
Invalidate when:
- Ticket assigned to pegawai
- Ticket status updated
- Ticket completed
- Ticket deleted

```php
// After update
Cache::forget("dashboard.pegawai.{$userId}.my_tasks");
```

---

## Performance Optimization

### 1. Query Optimization
- Use proper indexes on `escalate_to`
- Select only needed columns
- Use joins efficiently
- Avoid N+1 queries

### 2. Eager Loading
```php
$user = User::with('roles', 'tickets')->find($id);
```

### 3. Lazy Loading Charts
Frontend load charts after page render

### 4. Pagination
For large datasets (my tasks table)

---

## SOLID Principles Application

### Single Responsibility
- ✅ PegawaiTugasanDashboard: Coordinate pegawai dashboard
- ✅ TugasanAndaWidget: Handle my tasks data only

### Open/Closed
- Open for extension: New widgets can be added
- Closed for modification: Existing widgets don't change

### Liskov Substitution
- All widgets follow same interface pattern

### Interface Segregation
- Widgets have minimal, focused methods

### Dependency Inversion
- Services depend on abstractions (constructor injection)
- Not on concrete implementations

---

## Dokumentasi Berkaitan

- [03-services.md](03-services.md) - Detailed service implementation
- [04-frontend.md](04-frontend.md) - Frontend architecture
- [05-database.md](05-database.md) - Database queries

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
