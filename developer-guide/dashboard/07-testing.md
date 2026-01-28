# 07 - Dashboard: Testing

## Testing Strategy

Dashboard testing meliputi:
1. **Unit Tests** - Test individual components (Services, Widgets)
2. **Feature Tests** - Test HTTP requests and responses
3. **Browser Tests** - Test frontend interactions (optional)
4. **Integration Tests** - Test complete flow

---

## 1. Unit Tests

### Testing Services

**File**: `tests/Unit/Services/Dashboard/DashboardServiceTest.php`

```php
<?php

namespace Tests\Unit\Services\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardService::class);
    }

    /** @test */
    public function it_returns_admin_section_for_administrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $data = $this->service->getDashboardData($admin);

        $this->assertTrue($data['sections']['admin']);
        $this->assertFalse($data['sections']['pegawai']);
        $this->assertFalse($data['sections']['pengurusan']);
        $this->assertFalse($data['sections']['helpdesk']);
    }

    /** @test */
    public function it_returns_pegawai_section_for_pegawai_tugasan()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $data = $this->service->getDashboardData($pegawai);

        $this->assertTrue($data['sections']['pegawai']);
        $this->assertFalse($data['sections']['admin']);
        $this->assertArrayHasKey('my_tasks', $data);
    }

    /** @test */
    public function it_returns_helpdesk_section_for_helpdesk_roles()
    {
        $helpdesk = User::factory()->create();
        $helpdesk->assignRole('Penyelia Helpdesk');

        $data = $this->service->getDashboardData($helpdesk);

        $this->assertTrue($data['sections']['helpdesk']);
        $this->assertFalse($data['sections']['admin']);
        $this->assertFalse($data['sections']['pegawai']);
    }

    /** @test */
    public function it_merges_data_for_multi_role_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['Administrator', 'Pegawai Tugasan']);

        $data = $this->service->getDashboardData($user);

        $this->assertTrue($data['sections']['admin']);
        $this->assertTrue($data['sections']['pegawai']);
        $this->assertArrayHasKey('user', $data);
    }

    /** @test */
    public function it_includes_user_object_in_data()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $data = $this->service->getDashboardData($user);

        $this->assertArrayHasKey('user', $data);
        $this->assertInstanceOf(User::class, $data['user']);
        $this->assertEquals($user->id, $data['user']->id);
    }
}
```

---

### Testing Widgets

**File**: `tests/Unit/Services/Dashboard/Widgets/TugasanAndaWidgetTest.php`

```php
<?php

namespace Tests\Unit\Services\Dashboard\Widgets;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketsChronology;
use App\Services\Dashboard\Widgets\TugasanAndaWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TugasanAndaWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected TugasanAndaWidget $widget;
    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widget = app(TugasanAndaWidget::class);
        
        $this->pegawai = User::factory()->create();
        $this->pegawai->assignRole('Pegawai Tugasan');
    }

    /** @test */
    public function it_counts_assigned_tickets()
    {
        // Create 3 tickets assigned to pegawai
        $tickets = Ticket::factory()->count(3)->create();
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'escalate_to' => $this->pegawai->id,
                'tick_status' => 2, // In progress
            ]);
        }

        $data = $this->widget->getData($this->pegawai);

        $this->assertEquals(3, $data['assigned_to_me']);
    }

    /** @test */
    public function it_counts_pending_tickets()
    {
        // Create 2 pending tickets
        $tickets = Ticket::factory()->count(2)->create();
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'escalate_to' => $this->pegawai->id,
                'tick_status' => 2, // Pending (not 5 or 6)
            ]);
        }

        // Create 1 completed ticket
        $completedTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $completedTicket->id,
            'escalate_to' => $this->pegawai->id,
            'tick_status' => 6, // Completed
        ]);

        $data = $this->widget->getData($this->pegawai);

        $this->assertEquals(2, $data['pending']);
    }

    /** @test */
    public function it_counts_completed_this_week()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        // Create 2 tickets completed this week
        $tickets = Ticket::factory()->count(2)->create();
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'escalate_to' => $this->pegawai->id,
                'tick_status' => 6,
                'updated_at' => $startOfWeek->copy()->addDays(2),
            ]);
        }

        // Create 1 ticket completed last week
        $lastWeekTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $lastWeekTicket->id,
            'escalate_to' => $this->pegawai->id,
            'tick_status' => 6,
            'updated_at' => $startOfWeek->copy()->subWeek(),
        ]);

        $data = $this->widget->getData($this->pegawai);

        $this->assertEquals(2, $data['completed_this_week']);
    }

    /** @test */
    public function it_counts_overdue_tickets()
    {
        // Create 1 overdue ticket
        $overdueTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $overdueTicket->id,
            'escalate_to' => $this->pegawai->id,
            'tick_status' => 2,
            'sla_duedate' => Carbon::now()->subHours(5),
        ]);

        // Create 1 ticket not overdue
        $onTimeTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $onTimeTicket->id,
            'escalate_to' => $this->pegawai->id,
            'tick_status' => 2,
            'sla_duedate' => Carbon::now()->addHours(5),
        ]);

        $data = $this->widget->getData($this->pegawai);

        $this->assertEquals(1, $data['overdue']);
    }

    /** @test */
    public function it_only_counts_pegawai_own_tickets()
    {
        $otherPegawai = User::factory()->create();
        
        // Create ticket for this pegawai
        $myTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $myTicket->id,
            'escalate_to' => $this->pegawai->id,
        ]);

        // Create ticket for other pegawai
        $otherTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $otherTicket->id,
            'escalate_to' => $otherPegawai->id,
        ]);

        $data = $this->widget->getData($this->pegawai);

        $this->assertEquals(1, $data['assigned_to_me']);
    }
}
```

---

## 2. Feature Tests

### Testing Controller Endpoints

**File**: `tests/Feature/Dashboard/DashboardControllerTest.php`

```php
<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketsChronology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $response = $this->actingAs($user)
            ->get(route('dashboard.index'));

        $response->assertSuccessful();
        $response->assertViewIs('dashboard.dashboard');
        $response->assertViewHas('user');
        $response->assertViewHas('sections');
    }

    /** @test */
    public function admin_sees_admin_sections()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)
            ->get(route('dashboard.index'));

        $response->assertSuccessful();
        $response->assertViewHas('sections', function ($sections) {
            return $sections['admin'] === true;
        });
    }

    /** @test */
    public function pegawai_sees_pegawai_sections()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $response = $this->actingAs($pegawai)
            ->get(route('dashboard.index'));

        $response->assertSuccessful();
        $response->assertViewHas('sections', function ($sections) {
            return $sections['pegawai'] === true && 
                   $sections['admin'] === false;
        });
    }
}
```

---

### Testing API Endpoints

**File**: `tests/Feature/Dashboard/DashboardApiTest.php`

```php
<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketsChronology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');
    }

    /** @test */
    public function it_returns_today_logs_count()
    {
        // Create 3 tickets today
        $tickets = Ticket::factory()->count(3)->create();
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'created_at' => Carbon::today()->addHours(2),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.today-logs-count'));

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'count' => 3,
            ]);
    }

    /** @test */
    public function it_returns_weekly_completed_tickets()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        // Create completed tickets
        $tickets = Ticket::factory()->count(2)->create();
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'tick_status' => 6,
                'updated_at' => $startOfWeek->copy()->addDays(2),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.weekly-completed'));

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'total',
                'daily_data' => [
                    '*' => ['day', 'count']
                ]
            ]);
    }

    /** @test */
    public function it_returns_pending_stats()
    {
        // Create 2 pending tickets
        $pendingTickets = Ticket::factory()->count(2)->create();
        foreach ($pendingTickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'tick_status' => 2, // Pending
            ]);
        }

        // Create 1 completed ticket
        $completedTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $completedTicket->id,
            'tick_status' => 6, // Completed
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.pending-stats'));

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'pending_count' => 2,
                'total_count' => 3,
            ]);
    }

    /** @test */
    public function it_returns_overdue_sla_count()
    {
        // Create 2 overdue tickets
        $overdueTickets = Ticket::factory()->count(2)->create();
        foreach ($overdueTickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'tick_status' => 2,
                'sla_duedate' => Carbon::now()->subHours(5),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.overdue-sla'));

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'count' => 2,
            ]);
    }

    /** @test */
    public function it_returns_status_tickets_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.status-tickets'));

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'st_name', 'count']
                ]
            ]);
    }

    /** @test */
    public function it_returns_category_tickets_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.category-tickets'));

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'cat_name', 'count']
                ]
            ]);
    }

    /** @test */
    public function pegawai_only_sees_own_tickets()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        // Create ticket for pegawai
        $myTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $myTicket->id,
            'escalate_to' => $pegawai->id,
            'created_at' => Carbon::today(),
        ]);

        // Create ticket for another user
        $otherUser = User::factory()->create();
        $otherTicket = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $otherTicket->id,
            'escalate_to' => $otherUser->id,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($pegawai)
            ->getJson(route('dashboard.today-logs-count'));

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'count' => 1, // Only pegawai's ticket
            ]);
    }

    /** @test */
    public function admin_sees_all_tickets()
    {
        $pegawai1 = User::factory()->create();
        $pegawai2 = User::factory()->create();

        // Create tickets for different users
        $ticket1 = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $ticket1->id,
            'escalate_to' => $pegawai1->id,
            'created_at' => Carbon::today(),
        ]);

        $ticket2 = Ticket::factory()->create();
        TicketsChronology::factory()->create([
            'ticket_id' => $ticket2->id,
            'escalate_to' => $pegawai2->id,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('dashboard.today-logs-count'));

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'count' => 2, // Admin sees all
            ]);
    }
}
```

---

## 3. Integration Tests

**File**: `tests/Feature/Dashboard/DashboardIntegrationTest.php`

```php
<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketsChronology;
use App\Models\RefTicketStatus;
use App\Models\RefCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed reference data
        $this->seedReferenceData();
    }

    protected function seedReferenceData()
    {
        RefTicketStatus::factory()->create(['id' => 1, 'st_name' => 'Aduan Baru']);
        RefTicketStatus::factory()->create(['id' => 2, 'st_name' => 'Aduan Diproses']);
        RefTicketStatus::factory()->create(['id' => 6, 'st_name' => 'Aduan Selesai']);
        
        RefCategory::factory()->create(['id' => 1, 'cat_name' => 'Hardware']);
        RefCategory::factory()->create(['id' => 2, 'cat_name' => 'Software']);
    }

    /** @test */
    public function complete_dashboard_flow_for_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        // Create sample tickets
        $this->createSampleTickets();

        // 1. Visit dashboard
        $response = $this->actingAs($admin)
            ->get(route('dashboard.index'));

        $response->assertSuccessful()
            ->assertViewIs('dashboard.dashboard')
            ->assertViewHas('sections', function ($sections) {
                return $sections['admin'] === true;
            });

        // 2. Check today logs count API
        $logsResponse = $this->actingAs($admin)
            ->getJson(route('dashboard.today-logs-count'));

        $logsResponse->assertSuccessful()
            ->assertJsonStructure(['success', 'count']);

        // 3. Check status chart API
        $statusResponse = $this->actingAs($admin)
            ->getJson(route('dashboard.status-tickets'));

        $statusResponse->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'st_name', 'count']
                ]
            ]);

        // 4. Check category chart API
        $categoryResponse = $this->actingAs($admin)
            ->getJson(route('dashboard.category-tickets'));

        $categoryResponse->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'cat_name', 'count']
                ]
            ]);
    }

    /** @test */
    public function complete_dashboard_flow_for_pegawai()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        // Create tickets assigned to pegawai
        $this->createSampleTicketsForPegawai($pegawai);

        // 1. Visit dashboard
        $response = $this->actingAs($pegawai)
            ->get(route('dashboard.index'));

        $response->assertSuccessful()
            ->assertViewHas('sections', function ($sections) {
                return $sections['pegawai'] === true &&
                       $sections['admin'] === false;
            })
            ->assertViewHas('my_tasks');

        // 2. Check filtered data
        $logsResponse = $this->actingAs($pegawai)
            ->getJson(route('dashboard.today-logs-count'));

        $logsResponse->assertSuccessful();
        
        // Should only show pegawai's tickets
        $count = $logsResponse->json('count');
        $this->assertLessThanOrEqual(3, $count);
    }

    protected function createSampleTickets()
    {
        $tickets = Ticket::factory()->count(5)->create();
        
        foreach ($tickets as $index => $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'tick_status' => ($index < 2) ? 6 : 2,
                'created_at' => Carbon::today()->addHours($index),
            ]);
        }
    }

    protected function createSampleTicketsForPegawai(User $pegawai)
    {
        $tickets = Ticket::factory()->count(3)->create();
        
        foreach ($tickets as $ticket) {
            TicketsChronology::factory()->create([
                'ticket_id' => $ticket->id,
                'escalate_to' => $pegawai->id,
                'tick_status' => 2,
                'created_at' => Carbon::today(),
            ]);
        }
    }
}
```

---

## 4. Testing Best Practices

### 1. Use Factories
```php
// Create test data easily
$user = User::factory()->create();
$ticket = Ticket::factory()->create();
$chronology = TicketsChronology::factory()->create([
    'ticket_id' => $ticket->id,
    'tick_status' => 6,
]);
```

### 2. Use RefreshDatabase
```php
class DashboardTest extends TestCase
{
    use RefreshDatabase; // Reset database after each test
}
```

### 3. Seed Reference Data
```php
protected function setUp(): void
{
    parent::setUp();
    $this->seedReferenceData();
}
```

### 4. Test Edge Cases
- Empty data
- Null values
- Invalid dates
- Permission denied scenarios

### 5. Use Descriptive Test Names
```php
/** @test */
public function it_returns_only_pegawai_own_tickets_when_filtering()
{
    // Test implementation
}
```

### 6. Assert JSON Structure
```php
$response->assertJsonStructure([
    'success',
    'data' => [
        '*' => ['id', 'name', 'count']
    ]
]);
```

### 7. Test Authentication
```php
// Guest
$this->get(route('dashboard.index'))
    ->assertRedirect(route('login'));

// Authenticated
$this->actingAs($user)
    ->get(route('dashboard.index'))
    ->assertSuccessful();
```

---

## 5. Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test tests/Feature/Dashboard/DashboardControllerTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter it_returns_admin_section_for_administrator
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run Parallel Tests
```bash
php artisan test --parallel
```

---

## 6. Continuous Integration

### GitHub Actions Example

**.github/workflows/tests.yml**:
```yaml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo, pdo_mysql
      
      - name: Install Dependencies
        run: composer install --prefer-dist --no-interaction
      
      - name: Create Database
        run: |
          mkdir -p database
          touch database/database.sqlite
      
      - name: Run Tests
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: database/database.sqlite
        run: php artisan test
```

---

## 7. Test Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Feature Tests**: All endpoints tested
- **Integration Tests**: Main user flows covered

### Check Coverage
```bash
php artisan test --coverage --min=80
```

This will fail if coverage is below 80%.
