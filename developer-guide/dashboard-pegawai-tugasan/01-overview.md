# 01 - Dashboard Pegawai Tugasan: Gambaran Keseluruhan

## Tujuan
Dashboard Pegawai Tugasan menyediakan paparan khusus untuk pegawai yang telah diperuntukkan tugasan tiket. Dashboard ini menunjukkan **HANYA tiket yang ditugaskan kepada pegawai tersebut**, berbeza dengan dashboard admin/helpdesk yang menunjukkan semua tiket.

## Peranan: Pegawai Tugasan

### Definisi
**Pegawai Tugasan** adalah staff yang diberi tugasan spesifik untuk menyelesaikan tiket-tiket yang diperuntukkan kepada mereka oleh helpdesk atau sistem.

### Tanggungjawab
- Menyemak dan menyelesaikan tiket yang ditugaskan
- Mengemas kini status tiket secara berkala
- Memastikan tiket diselesaikan sebelum SLA due date
- Memberi maklum balas kepada pengguna
- Melaporkan sebarang halangan atau masalah

### Perbezaan dengan Peranan Lain
| Peranan | Data Scope | Fokus Utama |
|---------|-----------|-------------|
| **Pegawai Tugasan** | Tiket ditugaskan kepada mereka sahaja | Menyelesaikan tugasan assigned |
| Administrator | Semua tiket | Pengurusan sistem keseluruhan |
| Helpdesk | Semua tiket | Triaging dan assignment |
| Pengurusan MOF | High-level summaries | Monitoring dan laporan |

---

## Ciri-ciri Utama

### 1. My Tasks Summary Cards
Empat kad ringkasan yang menunjukkan:
- ✅ **Tiket Ditugaskan** - Jumlah tiket assigned kepada pegawai
- ⏳ **Tiket Pending** - Tiket yang belum selesai
- 🎯 **Selesai Minggu Ini** - Achievement minggu semasa
- ⚠️ **Lewat SLA** - Tiket yang overdue (memerlukan perhatian segera)

### 2. Senarai Tugasan Saya
Jadual interaktif (DataTable) menunjukkan:
- Semua tiket assigned to pegawai
- Details: No tiket, tajuk, kategori, status, SLA
- Quick action buttons
- Sortable dan searchable
- Real-time updates

### 3. Carta Status Tiket
Bar/pie chart menunjukkan breakdown status tiket pegawai:
- Baru
- In Progress
- Completed
- Cancelled

### 4. Carta Kategori Tiket
Pie chart menunjukkan distribution mengikut kategori:
- Hardware
- Software
- Network
- Lain-lain

### 5. Tiket Lewat SLA
Special section highlighting tiket yang overdue dengan:
- Warning indicators
- Time overdue calculation
- Priority sorting

---

## Komponen Dashboard

```
┌─────────────────────────────────────────────────────────┐
│  Welcome Card: "Selamat datang, [Nama Pegawai]"        │
│  Session Timer: [Remaining time]                        │
└─────────────────────────────────────────────────────────┘

┌──────────┬──────────┬──────────┬──────────┐
│ Ditugask │  Pending │ Selesai  │  Lewat   │
│    12    │    8     │    4     │    2     │
└──────────┴──────────┴──────────┴──────────┘

┌────────────────────────────────────────────────────────┐
│  My Tasks Table                                        │
│  ┌──────┬───────────┬──────────┬────────┬─────────┐  │
│  │ No   │ Tajuk     │ Kategori │ Status │ SLA     │  │
│  ├──────┼───────────┼──────────┼────────┼─────────┤  │
│  │ T001 │ Laptop... │ Hardware │ New    │ 2 days  │  │
│  │ T002 │ Network...│ Network  │ Prog   │ OVERDUE │  │
│  └──────┴───────────┴──────────┴────────┴─────────┘  │
└────────────────────────────────────────────────────────┘

┌──────────────────────┬──────────────────────────┐
│  Status Chart        │  Category Chart          │
│  [Bar/Pie Chart]     │  [Pie Chart]             │
└──────────────────────┴──────────────────────────┘
```

---

## Struktur Fail

### Backend (Services)
```
app/Services/Dashboard/
├── DashboardService.php           # Dispatcher (calls PegawaiTugasanDashboard)
├── Role/
│   └── PegawaiTugasanDashboard.php  # Main service untuk pegawai
└── Widgets/
    ├── TugasanAndaWidget.php         # My tasks widget
    └── TicketSummaryWidget.php       # Summary widget (with filtering)
```

### Frontend (Views)
```
resources/views/dashboard/
├── dashboard.blade.php              # Main file (includes pegawai section)
└── partials/pegawai-tugasan/
    ├── cards-status-pegawai.blade.php
    ├── chart-status-pegawai.blade.php
    ├── chart-category-pegawai.blade.php
    ├── table-ticket-overdue-pegawai.blade.php
    └── my-tasks.blade.php
```

### JavaScript
```
resources/assets/js/
└── dashboard.js                     # Contains pegawai-specific sections
```

---

## Mekanisme Data Filtering

### Konsep Utama
Dashboard pegawai **MESTI filter data** by `escalate_to` column:

```sql
WHERE escalate_to = :current_user_id
```

### Kenapa Penting?
- **Privacy**: Pegawai tidak patut melihat tiket pegawai lain
- **Focus**: Show only relevant tasks
- **Performance**: Reduce data load
- **Security**: Enforce access control at query level

### Implementation di Service Layer
```php
public function getData(User $user): array
{
    // All queries MUST filter by user ID
    $userId = $user->id;
    
    // Example
    $tickets = DB::table('tickets_chronologies')
        ->where('escalate_to', $userId)  // ← CRITICAL
        ->get();
    
    return [...];
}
```

---

## Aliran Data

```
User Login (Pegawai Tugasan)
    ↓
DashboardController::index()
    ↓
DashboardService::getDashboardData($user)
    ↓
Check: hasRole('Pegawai Tugasan')? → YES
    ↓
PegawaiTugasanDashboard::getData($user)
    ↓
┌──────────────────────────────────────┐
│ TugasanAndaWidget::getData($user)    │ → Filter by escalate_to
│ TicketSummaryWidget::getData($user)  │ → Filter by escalate_to
└──────────────────────────────────────┘
    ↓
Return array dengan:
  - my_tasks: [assigned, pending, completed, overdue]
  - my_tickets_summary: [total, new, in_progress, ...]
    ↓
Blade: dashboard.blade.php
    ↓
@if($sections['pegawai'])
    @include('dashboard/partials/pegawai-tugasan/...')
@endif
    ↓
Display dashboard dengan data pegawai sahaja
```

---

## Keselamatan

### 1. Backend Filtering
- **Semua queries mesti filter by `escalate_to = current_user_id`**
- Jangan trust frontend parameters
- Validate user role sebelum return data

### 2. Role Checking
```php
if ($user->hasRole('Pegawai Tugasan')) {
    // OK to proceed
}
```

### 3. No Direct Database Access dari Frontend
- Frontend call API endpoints
- API validate user dan filter data
- Return JSON response

### 4. CSRF Protection
- Semua forms protected dengan `@csrf`
- AJAX requests include CSRF token

---

## Prinsip Reka Bentuk

### 1. Single Responsibility
- PegawaiTugasanDashboard: Handle pegawai dashboard logic only
- TugasanAndaWidget: Handle "my tasks" data only

### 2. Data Privacy
- Never expose other pegawai's data
- Always filter at query level
- Log unauthorized access attempts

### 3. User Experience
- Clear visual indicators untuk overdue tickets
- Quick actions for common tasks
- Auto-refresh untuk real-time data
- Responsive design

### 4. Performance
- Efficient queries dengan proper indexes
- Pagination for large datasets
- Lazy loading charts
- Cache where appropriate

---

## Teknologi Digunakan

### Backend
- **Laravel 11** - PHP framework
- **PHP 8.x** - Programming language
- **MySQL** - Database
- **Laravel Query Builder** - Database abstraction

### Frontend
- **Blade Templates** - Server-side rendering
- **Bootstrap 5** - CSS framework
- **Chart.js** - Charting library
- **DataTables** - Interactive tables
- **Vanilla JavaScript** - Frontend logic

---

## Workflow Pegawai Tugasan

### Daily Routine
1. **Login** → Lihat dashboard summary
2. **Check overdue tickets** → Prioritize
3. **Work on pending tickets** → Update status
4. **Monitor SLA** → Ensure compliance
5. **Update progress** → Provide feedback

### Indicators
- 🔴 **Red** = Overdue (urgent action needed)
- 🟡 **Yellow** = Due soon (< 24 hours)
- 🟢 **Green** = On track
- ✅ **Checkmark** = Completed

---

## Dokumentasi Berkaitan

- [02-architecture.md](02-architecture.md) - Arkitektur dashboard pegawai
- [03-services.md](03-services.md) - Backend services implementation
- [04-frontend.md](04-frontend.md) - Frontend components
- [05-database.md](05-database.md) - Database queries dan filtering
- [06-testing.md](06-testing.md) - Testing strategy

- [Dashboard Management](../dashboard/README.md) - Dashboard untuk roles lain
- [Ticket System](../ticket/README.md) - Sistem tiket keseluruhan

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
