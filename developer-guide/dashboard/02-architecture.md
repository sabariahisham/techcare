# 02 - Dashboard: Arkitektur

## Pattern Arkitektur

Dashboard menggunakan **Service-Oriented Architecture** dengan **Role-based Dispatcher Pattern**.

## Lapisan Aplikasi

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                   │
│                  (Blade Templates)                      │
│  • dashboard.blade.php                                  │
│  • partials/*.blade.php                                 │
│  • dashboard.js                                         │
└─────────────────────────────────────────────────────────┘
                            ↑
                            │ Data Array
                            │
┌─────────────────────────────────────────────────────────┐
│                    Controller Layer                     │
│                (DashboardController)                    │
│  • index() - Main entry point                           │
│  • API endpoints for charts/widgets                     │
└─────────────────────────────────────────────────────────┘
                            ↑
                            │ Request
                            │
┌─────────────────────────────────────────────────────────┐
│                    Service Layer                        │
│               (DashboardService)                        │
│  Role: Traffic Dispatcher (Pengarah Lalu Lintas)       │
│  • getDashboardData($user)                              │
│  • Routes to role-specific services                     │
└─────────────────────────────────────────────────────────┘
                            ↓
              ┌─────────────┴─────────────┐
              │                           │
        ┌─────▼─────┐              ┌─────▼─────┐
        │   Admin   │              │  Helpdesk │
        │ Dashboard │              │ Dashboard │
        └─────┬─────┘              └─────┬─────┘
              │                           │
        ┌─────▼─────┐              ┌─────▼─────┐
        │ Pegawai   │              │Pengurusan │
        │  Tugasan  │              │ Dashboard │
        │ Dashboard │              └─────┬─────┘
        └─────┬─────┘                    │
              │                           │
              └───────────┬───────────────┘
                          ↓
              ┌───────────────────────┐
              │    Widget Services    │
              ├───────────────────────┤
              │ • TugasanAndaWidget   │
              │ • TrendBulananWidget  │
              │ • TicketSummaryWidget │
              │ • SLAWidget           │
              │ • PegawaiPerformance  │
              │ • KategoriWidget      │
              └───────────────────────┘
                          ↓
              ┌───────────────────────┐
              │      Data Layer       │
              │    (Models & DB)      │
              └───────────────────────┘
```

## Dispatcher Pattern (DashboardService)

### Konsep
DashboardService bertindak sebagai **traffic controller** yang mengalihkan permintaan kepada service yang betul berdasarkan peranan pengguna.

### Aliran Kerja
1. User login dan navigate ke dashboard
2. DashboardController terima request
3. DashboardController panggil DashboardService
4. DashboardService:
   - Check user roles
   - Dispatch ke role-specific dashboard service(s)
   - Merge data dari multiple roles (jika user ada beberapa roles)
   - Return merged data array
5. Controller pass data ke Blade view
6. Blade render dengan conditional sections

### Contoh Code Flow

```php
// DashboardController.php
public function index()
{
    $user = Auth::user();
    $data = $this->dashboardService->getDashboardData($user);
    return view('dashboard.dashboard', $data);
}

// DashboardService.php
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

    // Check each role and merge data
    if ($user->hasRole('Pegawai Tugasan')) {
        $data = $this->pegawaiDashboard->getData($user);
        $mergedData['sections']['pegawai'] = true;
        unset($data['user'], $data['sections']);
        $ticketData = array_merge($ticketData, $data);
    }

    // ... repeat for other roles

    return array_merge($mergedData, $ticketData);
}
```

## Role-Specific Dashboard Services

### 1. AdminDashboard
**Tujuan**: Menyediakan data dashboard untuk Administrator

**Widgets yang digunakan**:
- TicketSummaryWidget (keseluruhan sistem)
- TrendBulananWidget
- KategoriWidget
- SLAWidget

**Data yang dikembalikan**:
- Jumlah tiket mengikut status
- Trend bulanan
- Breakdown kategori
- Tiket lewat SLA

### 2. HelpdeskDashboard
**Tujuan**: Menyediakan data dashboard untuk Helpdesk team

**Widgets yang digunakan**:
- TicketSummaryWidget (scope helpdesk)
- TrendBulananWidget
- PegawaiPerformanceWidget

**Data yang dikembalikan**:
- Tiket dalam kendalian helpdesk
- Performance pegawai
- Queue management

### 3. PegawaiTugasanDashboard
**Tujuan**: Menyediakan data dashboard untuk Pegawai Tugasan

**Widgets yang digunakan**:
- TugasanAndaWidget
- TicketSummaryWidget (filtered by assigned user)

**Data yang dikembalikan**:
- Tiket yang diperuntukkan kepada pegawai
- Status tugasan
- Tiket lewat milik pegawai

### 4. PengurusanDashboard
**Tujuan**: Menyediakan data dashboard untuk Management (Pengurusan MOF)

**Widgets yang digunakan**:
- Executive summary widgets
- High-level statistics
- KPI metrics

**Data yang dikembalikan**:
- Executive-level summaries
- Department performance
- SLA compliance

## Widget Architecture

### Konsep Widget
Setiap widget adalah **single-purpose service** yang:
1. Mengambil data dari database
2. Format/transform data
3. Return data array untuk Blade

### Widget Contract
```php
interface WidgetInterface
{
    public function getData(User $user): array;
}
```

### Widget Pattern
- **Reusable**: Boleh digunakan dalam berbagai role dashboard
- **Configurable**: Accept parameters untuk customize behavior
- **Testable**: Mudah untuk unit testing
- **Independent**: Tidak bergantung pada widget lain

## Data Flow Pattern

### 1. Request Phase
```
User → Route → Middleware → Controller
```

### 2. Processing Phase
```
Controller → Service Dispatcher → Role Service → Widget(s) → Model/DB
```

### 3. Response Phase
```
DB → Model → Widget → Role Service → Service Dispatcher → Controller → View
```

## Separation of Concerns

### Controller
- **Responsibility**: Handle HTTP requests and responses
- **Does NOT**: Contain business logic
- **Does NOT**: Query database directly
- **Does**: Delegate to services

### Service Layer
- **Responsibility**: Business logic and data orchestration
- **Does**: Query database through models
- **Does**: Transform and format data
- **Does**: Aggregate data from multiple sources

### View Layer (Blade)
- **Responsibility**: Presentation only
- **Does NOT**: Contain role-checking logic
- **Does NOT**: Contain business logic
- **Does**: Use boolean flags from controller
- **Does**: Display data received from controller

## Prinsip SOLID dalam Dashboard

### Single Responsibility
- Setiap widget handle satu jenis data sahaja
- Setiap role service handle satu peranan sahaja

### Open/Closed
- Mudah tambah role dashboard baru tanpa ubah existing code
- Mudah tambah widget baru tanpa ubah existing widgets

### Liskov Substitution
- Semua role dashboard boleh digantikan dengan implementation lain

### Interface Segregation
- Widget interface specific kepada keperluan

### Dependency Inversion
- Controller depend pada abstraction (Service), bukan concrete implementation

## Caching Strategy

### Level 1: Query Result Cache
- Cache query results untuk widget data
- TTL: 5 minit
- Cache key berdasarkan user role dan filter

### Level 2: View Fragment Cache
- Cache rendered HTML fragments
- TTL: 2 minit
- Untuk charts dan statistik yang tidak kerap berubah

### Cache Invalidation
- Automatic invalidation bila ada perubahan data tiket
- Manual invalidation melalui admin panel
- Event-based cache clearing

## Error Handling

### Graceful Degradation
- Jika widget fail, show fallback data
- Jika service fail, log error dan show default dashboard
- User tetap dapat lihat dashboard walaupun ada errors

### Logging
- Log semua errors ke storage/logs
- Track performance metrics
- Audit trail untuk data access

## Scalability Considerations

### Horizontal Scaling
- Services stateless, mudah untuk scale
- Widget independent, boleh distributed

### Performance
- Lazy loading untuk charts
- Pagination untuk tables
- Async loading untuk non-critical widgets

### Database
- Optimized queries dengan proper indexes
- Query result caching
- Database connection pooling
