# 03 - Dashboard Pegawai Tugasan: Backend Services

## Service Overview

Backend untuk Dashboard Pegawai Tugasan terdiri daripada:
1. **PegawaiTugasanDashboard** - Main service
2. **TugasanAndaWidget** - My tasks widget
3. **TicketSummaryWidget** - Ticket summary (filtered version)

---

## 1. PegawaiTugasanDashboard Service

### File Location
```
app/Services/Dashboard/Role/PegawaiTugasanDashboard.php
```

### Full Implementation

```php
<?php

namespace App\Services\Dashboard\Role;

use App\Models\User;
use App\Services\Dashboard\Widgets\TugasanAndaWidget;
use App\Services\Dashboard\Widgets\TicketSummaryWidget;
use Illuminate\Support\Facades\Log;

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
     * IMPORTANT: All data returned is filtered by escalate_to = $user->id
     * 
     * @param User $user - Current logged-in pegawai
     * @return array
     */
    public function getData(User $user): array
    {
        try {
            return [
                'user' => $user,
                'sections' => ['pegawai' => true],
                
                // My tasks summary (4 cards)
                'my_tasks' => $this->tugasanAnda->getData($user),
                
                // Ticket summary by status
                'my_tickets_summary' => $this->ticketSummary->getDataForPegawai($user),
                
                // Optional: Add more widgets here
                // 'my_performance' => $this->performance->getData($user),
            ];
            
        } catch (\Exception $e) {
            Log::error('PegawaiTugasanDashboard::getData error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe defaults
            return [
                'user' => $user,
                'sections' => ['pegawai' => true],
                'my_tasks' => $this->getDefaultMyTasks(),
                'my_tickets_summary' => $this->getDefaultSummary(),
            ];
        }
    }

    /**
     * Default data when error occurs
     */
    protected function getDefaultMyTasks(): array
    {
        return [
            'assigned_to_me' => 0,
            'pending' => 0,
            'completed_this_week' => 0,
            'overdue' => 0,
        ];
    }

    /**
     * Default summary when error occurs
     */
    protected function getDefaultSummary(): array
    {
        return [
            'total' => 0,
            'new' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
    }
}
```

### Return Data Structure

```php
[
    'user' => User {
        id: 123,
        nama: "Ahmad bin Ali",
        email: "ahmad@mof.gov.my",
        // ...
    },
    'sections' => [
        'pegawai' => true,  // Flag untuk Blade template
    ],
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
        'cancelled' => 0,
    ],
]
```

---

## 2. TugasanAndaWidget

### File Location
```
app/Services/Dashboard/Widgets/TugasanAndaWidget.php
```

### Purpose
Menyediakan ringkasan tugasan yang assigned kepada pegawai.

### Full Implementation

```php
<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TugasanAndaWidget
{
    /**
     * Get "My Tasks" data for pegawai
     * 
     * Returns 4 key metrics:
     * - assigned_to_me: Total tickets assigned
     * - pending: Tickets not completed/cancelled
     * - completed_this_week: Completed within current week
     * - overdue: Tickets past SLA due date
     * 
     * @param User $user
     * @return array
     */
    public function getData(User $user): array
    {
        try {
            $userId = $user->id;
            
            return [
                'assigned_to_me' => $this->getAssignedCount($userId),
                'pending' => $this->getPendingCount($userId),
                'completed_this_week' => $this->getCompletedThisWeek($userId),
                'overdue' => $this->getOverdueCount($userId),
            ];
            
        } catch (\Exception $e) {
            Log::error('TugasanAndaWidget::getData error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'assigned_to_me' => 0,
                'pending' => 0,
                'completed_this_week' => 0,
                'overdue' => 0,
            ];
        }
    }

    /**
     * Get total assigned tickets count
     * 
     * @param int $userId
     * @return int
     */
    protected function getAssignedCount(int $userId): int
    {
        return DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->whereNull('tc.deleted_at')
            ->distinct('tc.ticket_id')
            ->count('tc.ticket_id');
    }

    /**
     * Get pending tickets count
     * Status: NOT in [5 (Cancelled), 6 (Completed)]
     * 
     * @param int $userId
     * @return int
     */
    protected function getPendingCount(int $userId): int
    {
        return DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->whereNotIn('tc.tick_status', [5, 6])
            ->whereNull('tc.deleted_at')
            ->distinct('tc.ticket_id')
            ->count('tc.ticket_id');
    }

    /**
     * Get tickets completed this week
     * 
     * @param int $userId
     * @return int
     */
    protected function getCompletedThisWeek(int $userId): int
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        return DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->where('tc.tick_status', 6) // Completed
            ->whereBetween('tc.updated_at', [$startOfWeek, $endOfWeek])
            ->whereNull('tc.deleted_at')
            ->distinct('tc.ticket_id')
            ->count('tc.ticket_id');
    }

    /**
     * Get overdue tickets count
     * Criteria: NOT completed AND past SLA due date
     * 
     * @param int $userId
     * @return int
     */
    protected function getOverdueCount(int $userId): int
    {
        $now = Carbon::now();
        
        return DB::table('tickets_chronologies as tc')
            ->where('tc.escalate_to', $userId)
            ->where('tc.tick_status', '!=', 6) // Not completed
            ->whereNotNull('tc.sla_duedate')
            ->where('tc.sla_duedate', '<', $now)
            ->whereNull('tc.deleted_at')
            ->distinct('tc.ticket_id')
            ->count('tc.ticket_id');
    }
}
```

### Query Breakdown

#### 1. Assigned Count
```sql
SELECT COUNT(DISTINCT tc.ticket_id)
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.deleted_at IS NULL
```

#### 2. Pending Count
```sql
SELECT COUNT(DISTINCT tc.ticket_id)
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status NOT IN (5, 6)
  AND tc.deleted_at IS NULL
```

#### 3. Completed This Week
```sql
SELECT COUNT(DISTINCT tc.ticket_id)
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status = 6
  AND tc.updated_at BETWEEN :startOfWeek AND :endOfWeek
  AND tc.deleted_at IS NULL
```

#### 4. Overdue Count
```sql
SELECT COUNT(DISTINCT tc.ticket_id)
FROM tickets_chronologies tc
WHERE tc.escalate_to = :userId
  AND tc.tick_status != 6
  AND tc.sla_duedate IS NOT NULL
  AND tc.sla_duedate < NOW()
  AND tc.deleted_at IS NULL
```

### Why DISTINCT?
- `tickets_chronologies` can have multiple records per ticket
- Use `DISTINCT ticket_id` to count unique tickets only

---

## 3. TicketSummaryWidget (Pegawai Version)

### File Location
```
app/Services/Dashboard/Widgets/TicketSummaryWidget.php
```

### Method: getDataForPegawai()

```php
/**
 * Get ticket summary for specific pegawai
 * Filtered by escalate_to
 * 
 * @param User $user
 * @return array
 */
public function getDataForPegawai(User $user): array
{
    try {
        $userId = $user->id;
        
        // Get latest chronology for each ticket assigned to this pegawai
        $summary = DB::table('tickets as t')
            ->join('tickets_chronologies as tc', function ($join) use ($userId) {
                $join->on('t.id', '=', 'tc.ticket_id')
                    ->where('tc.escalate_to', '=', $userId)
                    ->whereRaw('tc.id = (
                        SELECT MAX(tc2.id)
                        FROM tickets_chronologies tc2
                        WHERE tc2.ticket_id = t.id
                          AND tc2.escalate_to = ' . $userId . '
                          AND tc2.deleted_at IS NULL
                    )');
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
        
        return [
            'total' => $summary->total ?? 0,
            'new' => $summary->new ?? 0,
            'in_progress' => $summary->in_progress ?? 0,
            'completed' => $summary->completed ?? 0,
            'cancelled' => $summary->cancelled ?? 0,
        ];
        
    } catch (\Exception $e) {
        Log::error('TicketSummaryWidget::getDataForPegawai error', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
        
        return [
            'total' => 0,
            'new' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
    }
}
```

### SQL Explanation

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
  AND tc.deleted_at IS NULL
```

**Key Points**:
1. Join dengan latest chronology only (`MAX(tc2.id)`)
2. Filter by `escalate_to = userId`
3. Use CASE WHEN for status breakdown
4. Aggregate dengan COUNT dan SUM

---

## Data Scope Comparison

### Admin/Helpdesk Dashboard
```php
// See ALL tickets
$summary = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', ...)
    ->whereNull('t.deleted_at')
    ->select(...)
    ->first();
```

### Pegawai Tugasan Dashboard
```php
// See ONLY assigned tickets
$summary = DB::table('tickets as t')
    ->join('tickets_chronologies as tc', ...)
    ->where('tc.escalate_to', $userId)  // ← THE DIFFERENCE
    ->whereNull('t.deleted_at')
    ->select(...)
    ->first();
```

---

## Testing Services

### Unit Test: PegawaiTugasanDashboard

```php
<?php

namespace Tests\Unit\Services\Dashboard\Role;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Services\Dashboard\Role\PegawaiTugasanDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PegawaiTugasanDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected PegawaiTugasanDashboard $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PegawaiTugasanDashboard::class);
    }

    /** @test */
public function it_returns_data_only_for_assigned_tickets()
    {
        // Create pegawai
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');
        
        // Create another pegawai
        $otherPegawai = User::factory()->create();
        
        // Create tickets
        $ticket1 = Ticket::factory()->create();
        $ticket2 = Ticket::factory()->create();
        
        // Assign ticket1 to $pegawai
        TicketChronology::factory()->create([
            'ticket_id' => $ticket1->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2,
        ]);
        
        // Assign ticket2 to $otherPegawai
        TicketChronology::factory()->create([
            'ticket_id' => $ticket2->id,
            'escalate_to' => $otherPegawai->id,
            'tick_status' => 2,
        ]);
        
        // Get data
        $data = $this->service->getData($pegawai);
        
        // Assert
        $this->assertTrue($data['sections']['pegawai']);
        $this->assertEquals(1, $data['my_tasks']['assigned_to_me']);
        $this->assertEquals(0, $data['my_tasks']['overdue']);
    }

    /** @test */
    public function it_counts_overdue_tickets_correctly()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');
        
        $ticket = Ticket::factory()->create();
        
        // Create overdue chronology
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2, // In progress
            'sla_duedate' => now()->subDays(2), // 2 days overdue
        ]);
        
        $data = $this->service->getData($pegawai);
        
        $this->assertEquals(1, $data['my_tasks']['overdue']);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Mock to throw exception
        $this->mock(TugasanAndaWidget::class)
            ->shouldReceive('getData')
            ->andThrow(new \Exception('Database error'));
        
        $pegawai = User::factory()->create();
        
        $data = $this->service->getData($pegawai);
        
        // Should return default values
        $this->assertEquals(0, $data['my_tasks']['assigned_to_me']);
    }
}
```

### Unit Test: TugasanAndaWidget

```php
<?php

namespace Tests\Unit\Services\Dashboard\Widgets;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Services\Dashboard\Widgets\TugasanAndaWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TugasanAndaWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected TugasanAndaWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widget = app(TugasanAndaWidget::class);
    }

    /** @test */
    public function it_counts_assigned_tickets()
    {
        $pegawai = User::factory()->create();
        
        $ticket1 = Ticket::factory()->create();
        $ticket2 = Ticket::factory()->create();
        
        TicketChronology::factory()->create([
            'ticket_id' => $ticket1->id,
            'escalate_to' => $pegawai->id,
        ]);
        
        TicketChronology::factory()->create([
            'ticket_id' => $ticket2->id,
            'escalate_to' => $pegawai->id,
        ]);
        
        $data = $this->widget->getData($pegawai);
        
        $this->assertEquals(2, $data['assigned_to_me']);
    }

    /** @test */
    public function it_counts_completed_this_week()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();
        
        // Completed within this week
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 6, // Completed
            'updated_at' => Carbon::now()->startOfWeek()->addDays(2),
        ]);
        
        $data = $this->widget->getData($pegawai);
        
        $this->assertEquals(1, $data['completed_this_week']);
    }

    /** @test */
    public function it_does_not_count_other_pegawai_tickets()
    {
        $pegawai1 = User::factory()->create();
        $pegawai2 = User::factory()->create();
        
        $ticket = Ticket::factory()->create();
        
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai2->id,
        ]);
        
        $data = $this->widget->getData($pegawai1);
        
        $this->assertEquals(0, $data['assigned_to_me']);
    }
}
```

---

## Best Practices

### 1. Always Filter by User
```php
// ✅ CORRECT
->where('escalate_to', $user->id)

// ❌ WRONG - exposes all data
->get()
```

### 2. Use DISTINCT for Counting
```php
// ✅ CORRECT - count unique tickets
->distinct('ticket_id')->count('ticket_id')

// ❌ WRONG - may count duplicates
->count()
```

### 3. Error Handling
```php
try {
    // Query
} catch (\Exception $e) {
    Log::error(...);
    return [/* safe defaults */];
}
```

### 4. Null Checks
```php
// ✅ CORRECT
'total' => $summary->total ?? 0,

// ❌ WRONG - may cause errors
'total' => $summary->total,
```

### 5. Performance
```php
// Use indexes on escalate_to column
// Cache results for 5 minutes
Cache::remember($cacheKey, 300, fn() => $query);
```

---

## Dokumentasi Berkaitan

- [02-architecture.md](02-architecture.md) - Architecture patterns
- [04-frontend.md](04-frontend.md) - Frontend implementation
- [05-database.md](05-database.md) - Database queries
- [06-testing.md](06-testing.md) - Testing strategy

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
