# 06 - Dashboard Pegawai Tugasan: Testing

## Testing Strategy

### Testing Pyramid

```
           /\
          /  \         E2E Tests (5%)
         /____\        - Browser tests
        /      \       
       /        \      Integration Tests (15%)
      /__________\     - API tests
     /            \    
    /              \   Unit Tests (80%)
   /________________\  - Service tests
                       - Widget tests
```

### Coverage Goals
- **Services**: 100% coverage
- **Widgets**: 100% coverage  
- **Controllers**: 90% coverage
- **Overall**: 85%+

---

## Unit Tests

### 1. PegawaiTugasanDashboard Service Test

**File**: `tests/Unit/Services/Dashboard/Role/PegawaiTugasanDashboardTest.php`

```php
<?php

namespace Tests\Unit\Services\Dashboard\Role;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketChronology;
use App\Services\Dashboard\Role\PegawaiTugasanDashboard;
use App\Services\Dashboard\Widgets\TugasanAndaWidget;
use App\Services\Dashboard\Widgets\TicketSummaryWidget;
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
    public function it_returns_correct_structure()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $data = $this->service->getData($pegawai);

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('my_tasks', $data);
        $this->assertArrayHasKey('my_tickets_summary', $data);
        
        $this->assertTrue($data['sections']['pegawai']);
        $this->assertInstanceOf(User::class, $data['user']);
    }

    /** @test */
    public function it_returns_only_assigned_tickets()
    {
        $pegawai1 = User::factory()->create();
        $pegawai2 = User::factory()->create();
        
        $pegawai1->assignRole('Pegawai Tugasan');
        $pegawai2->assignRole('Pegawai Tugasan');

        // Create tickets for pegawai1
        $ticket1 = Ticket::factory()->create();
        TicketChronology::factory()->create([
            'ticket_id' => $ticket1->id,
            'escalate_to' => $pegawai1->id,
            'tick_status' => 2,
        ]);

        // Create tickets for pegawai2
        $ticket2 = Ticket::factory()->create();
        TicketChronology::factory()->create([
            'ticket_id' => $ticket2->id,
            'escalate_to' => $pegawai2->id,
            'tick_status' => 2,
        ]);

        $data = $this->service->getData($pegawai1);

        $this->assertEquals(1, $data['my_tasks']['assigned_to_me']);
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
            'tick_status' => 2,
            'sla_duedate' => now()->subDays(2),
        ]);

        $data = $this->service->getData($pegawai);

        $this->assertEquals(1, $data['my_tasks']['overdue']);
        $this->assertEquals(1, $data['my_tasks']['pending']);
    }

    /** @test */
    public function it_does_not_count_completed_as_pending()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $ticket1 = Ticket::factory()->create();
        $ticket2 = Ticket::factory()->create();

        // Completed ticket
        TicketChronology::factory()->create([
            'ticket_id' => $ticket1->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 6, // Completed
        ]);

        // Pending ticket
        TicketChronology::factory()->create([
            'ticket_id' => $ticket2->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2, // In Progress
        ]);

        $data = $this->service->getData($pegawai);

        $this->assertEquals(2, $data['my_tasks']['assigned_to_me']);
        $this->assertEquals(1, $data['my_tasks']['pending']);
    }

    /** @test */
    public function it_handles_missing_dependencies_gracefully()
    {
        // Mock widget to throw exception
        $this->mock(TugasanAndaWidget::class, function ($mock) {
            $mock->shouldReceive('getData')
                ->andThrow(new \Exception('Database error'));
        });

        $pegawai = User::factory()->create();

        $data = $this->service->getData($pegawai);

        // Should return default values
        $this->assertEquals(0, $data['my_tasks']['assigned_to_me']);
        $this->assertEquals(0, $data['my_tasks']['pending']);
    }

    /** @test */
    public function it_ignores_soft_deleted_tickets()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $ticket = Ticket::factory()->create();
        
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2,
            'deleted_at' => now(), // Soft deleted
        ]);

        $data = $this->service->getData($pegawai);

        $this->assertEquals(0, $data['my_tasks']['assigned_to_me']);
    }
}
```

---

### 2. TugasanAndaWidget Test

**File**: `tests/Unit/Services/Dashboard/Widgets/TugasanAndaWidgetTest.php`

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
    public function it_counts_pending_tickets()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2, // In Progress
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(1, $data['pending']);
    }

    /** @test */
    public function it_does_not_count_completed_as_pending()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 6, // Completed
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(0, $data['pending']);
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
            'tick_status' => 6,
            'updated_at' => Carbon::now()->startOfWeek()->addDays(2),
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(1, $data['completed_this_week']);
    }

    /** @test */
    public function it_does_not_count_last_week_completions()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();

        // Completed last week
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 6,
            'updated_at' => Carbon::now()->subWeek(),
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(0, $data['completed_this_week']);
    }

    /** @test */
    public function it_counts_overdue_tickets()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2,
            'sla_duedate' => Carbon::now()->subHours(5),
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(1, $data['overdue']);
    }

    /** @test */
    public function it_does_not_count_completed_as_overdue()
    {
        $pegawai = User::factory()->create();
        $ticket = Ticket::factory()->create();

        // Completed but was overdue
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 6, // Completed
            'sla_duedate' => Carbon::now()->subDays(2),
        ]);

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(0, $data['overdue']);
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

    /** @test */
    public function it_returns_zero_for_empty_results()
    {
        $pegawai = User::factory()->create();

        $data = $this->widget->getData($pegawai);

        $this->assertEquals(0, $data['assigned_to_me']);
        $this->assertEquals(0, $data['pending']);
        $this->assertEquals(0, $data['completed_this_week']);
        $this->assertEquals(0, $data['overdue']);
    }
}
```

---

## Integration Tests

### 1. Dashboard API Test

**File**: `tests/Feature/Dashboard/PegawaiDashboardApiTest.php`

```php
<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketChronology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PegawaiDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pegawai_can_access_dashboard()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        Sanctum::actingAs($pegawai);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('sections');
        $response->assertViewHas('my_tasks');
    }

    /** @test */
    public function pegawai_can_get_my_tasks_via_api()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $ticket = Ticket::factory()->create();
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2,
        ]);

        Sanctum::actingAs($pegawai);

        $response = $this->getJson('/api/dashboard/my-tasks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'ticket_no',
                    'title',
                    'category',
                    'status',
                    'sla_duedate',
                ]
            ]
        ]);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function pegawai_cannot_see_other_pegawai_tasks()
    {
        $pegawai1 = User::factory()->create();
        $pegawai2 = User::factory()->create();
        
        $pegawai1->assignRole('Pegawai Tugasan');
        $pegawai2->assignRole('Pegawai Tugasan');

        $ticket = Ticket::factory()->create();
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai2->id,
        ]);

        Sanctum::actingAs($pegawai1);

        $response = $this->getJson('/api/dashboard/my-tasks');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    /** @test */
    public function non_pegawai_cannot_access_pegawai_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('User'); // Not Pegawai Tugasan

        Sanctum::actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('sections', function ($sections) {
            return $sections['pegawai'] === false;
        });
    }
}
```

---

## Browser Tests (E2E)

### Laravel Dusk Test

**File**: `tests/Browser/PegawaiDashboardTest.php`

```php
<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketChronology;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PegawaiDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function pegawai_can_see_dashboard_cards()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $ticket = Ticket::factory()->create();
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
            'tick_status' => 2,
        ]);

        $this->browse(function (Browser $browser) use ($pegawai) {
            $browser->loginAs($pegawai)
                    ->visit('/dashboard')
                    ->assertSee('Ditugaskan Kepada Saya')
                    ->assertSee('Belum Selesai')
                    ->assertSee('Selesai Minggu Ini')
                    ->assertSee('Lewat SLA')
                    ->assertSee('1'); // Should show 1 assigned ticket
        });
    }

    /** @test */
    public function pegawai_can_view_my_tasks_table()
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('Pegawai Tugasan');

        $ticket = Ticket::factory()->create([
            'title' => 'Test Ticket for Pegawai'
        ]);
        
        TicketChronology::factory()->create([
            'ticket_id' => $ticket->id,
            'escalate_to' => $pegawai->id,
        ]);

        $this->browse(function (Browser $browser) use ($pegawai, $ticket) {
            $browser->loginAs($pegawai)
                    ->visit('/dashboard')
                    ->assertSee('Tugasan Saya')
                    ->waitForText($ticket->ticket_no)
                    ->assertSee($ticket->title);
        });
    }
}
```

---

## Test Helpers

### Factory: TicketChronology

```php
<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketChronologyFactory extends Factory
{
    public function definition()
    {
        return [
            'ticket_id' => Ticket::factory(),
            'tick_status' => 1,
            'escalate_to' => User::factory(),
            'action_by' => User::factory(),
            'remarks' => $this->faker->sentence(),
            'sla_duedate' => now()->addDays(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function overdue()
    {
        return $this->state(function (array $attributes) {
            return [
                'sla_duedate' => now()->subDays(2),
                'tick_status' => 2,
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'tick_status' => 6,
            ];
        });
    }
}
```

---

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific file
php artisan test tests/Unit/Services/Dashboard/Role/PegawaiTugasanDashboardTest.php
```

### Run with Coverage
```bash
php artisan test --coverage --min=85
```

### Run Browser Tests
```bash
php artisan dusk
```

---

## Coverage Report

### Generate Coverage HTML
```bash
php artisan test --coverage-html coverage-report
```

### Expected Coverage
```
Services/Dashboard/Role/PegawaiTugasanDashboard ... 100%
Services/Dashboard/Widgets/TugasanAndaWidget ..... 100%
Controllers/DashboardController ................... 95%
--------------------------------
Overall .......................................... 87%
```

---

## Continuous Integration

### GitHub Actions Workflow

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Tests
        run: php artisan test --coverage --min=85
        
      - name: Upload Coverage
        uses: codecov/codecov-action@v2
```

---

## Best Practices

### 1. Test Data Isolation
```php
use RefreshDatabase; // Each test gets fresh database
```

### 2. Descriptive Test Names
```php
/** @test */
public function it_returns_only_assigned_tickets()
```

### 3. Arrange-Act-Assert Pattern
```php
// Arrange
$pegawai = User::factory()->create();

// Act
$data = $this->service->getData($pegawai);

// Assert
$this->assertEquals(1, $data['assigned']);
```

### 4. Test Edge Cases
- Empty results
- Soft deleted records
- Multiple chronologies per ticket
- Overdue calculations
- NULL SLA dates

---

## Dokumentasi Berkaitan

- [03-services.md](03-services.md) - Services to test
- [04-frontend.md](04-frontend.md) - Frontend testing
- [05-database.md](05-database.md) - Database queries

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
