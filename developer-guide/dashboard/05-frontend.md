# 05 - Dashboard: Frontend (Blade & JavaScript)

## Overview
Frontend dashboard terdiri daripada:
1. **Blade Templates** - HTML structure dan presentation
2. **JavaScript** - Interactive charts, AJAX calls, real-time updates
3. **CSS** - Styling dan layout

---

## 1. Blade Templates

### Main Dashboard File

**File**: `resources/views/dashboard/dashboard.blade.php`

### Structure
```blade
@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
    {{-- CSS imports --}}
@endsection

@section('vendor-script')
    {{-- JS library imports --}}
@endsection

@section('page-script')
    {{-- Custom dashboard JS --}}
@endsection

@section('content')
    {{-- Dashboard content --}}
@endsection
```

### Key Principles

#### 1. No Role Logic in Blade
**WRONG** ❌:
```blade
@if(Auth::user()->hasRole('Administrator'))
    {{-- admin content --}}
@endif
```

**CORRECT** ✅:
```blade
@if($sections['admin'])
    {{-- admin content --}}
@endif
```

**Reason**: Role checking should be done in backend (Service layer), not in View.

#### 2. Use Data from Controller
Blade hanya use data yang diterima dari Controller:
- `$user` - User object
- `$sections` - Array of boolean flags
- Widget data arrays

#### 3. Partial Includes
Pecahkan content kepada partials untuk reusability:

```blade
@include('dashboard/partials/impersonate-card')
@include('dashboard/partials/cards-status')
@include('dashboard/partials/chart-status')
```

---

### Sections Structure

Dashboard dibahagikan kepada sections berdasarkan roles:

```blade
@section('content')
    <!-- Impersonation Banner (shown for all if impersonating) -->
    @include('dashboard/partials/impersonate-card')

    <!-- Welcome Card & Session Timer (for all users) -->
    <div class="row g-4 mb-6">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-primary mb-1">
                        Selamat datang, {{ $user->nama }}!
                    </h4>
                    <p class="mb-0">...</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @include('dashboard/partials/session-timer')
        </div>
    </div>

    <!-- Admin/Helpdesk Section -->
    @if($sections['admin'] || $sections['helpdesk'])
        <div class="row g-4 mb-6">
            @include('dashboard/partials/cards-status')
        </div>
        <div class="row g-4 mb-6">
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
        </div>
    @endif

    <!-- Pegawai Tugasan Section -->
    @if($sections['pegawai'])
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pegawai-tugasan/cards-status-pegawai')
        </div>
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pegawai-tugasan/my-tasks')
        </div>
    @endif

    <!-- Pengurusan MOF Section -->
    @if($sections['pengurusan'])
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pengurusan/executive-summary')
        </div>
    @endif
@endsection
```

---

## 2. Partial Templates

### 2.1 Impersonate Card

**File**: `resources/views/dashboard/partials/impersonate-card.blade.php`

**Purpose**: Show warning banner when admin is impersonating another user

```blade
@if(session()->has('impersonate'))
    <div class="alert alert-warning alert-dismissible" role="alert">
        <h5 class="alert-heading mb-2">
            <i class='bx bx-user-check'></i>
            Mode Impersonasi Aktif
        </h5>
        <p class="mb-2">
            Anda sedang melihat sistem sebagai: 
            <strong>{{ Auth::user()->nama }}</strong>
        </p>
        <form method="POST" action="{{ route('impersonate.leave') }}">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">
                <i class='bx bx-log-out'></i>
                Keluar dari Impersonasi
            </button>
        </form>
    </div>
@endif
```

---

### 2.2 Status Cards (Admin/Helpdesk)

**File**: `resources/views/dashboard/partials/cards-status.blade.php`

**Purpose**: Display 4 main status cards

```blade
<!-- Card 1: Log Hari Ini -->
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-primary rounded">
                        <i class='bx bx-file fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0" id="todayLogsCount">
                <span class="spinner-border spinner-border-sm"></span>
            </h4>
            <small class="text-muted">Log Hari Ini</small>
        </div>
    </div>
</div>

<!-- Card 2: Selesai Minggu Ini -->
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-success rounded">
                        <i class='bx bx-check-circle fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0" id="weeklyCompletedCount">
                <span class="spinner-border spinner-border-sm"></span>
            </h4>
            <small class="text-muted">Selesai Minggu Ini</small>
        </div>
    </div>
</div>

<!-- Card 3: Belum Selesai -->
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-warning rounded">
                        <i class='bx bx-time-five fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0" id="pendingCount">
                <span class="spinner-border spinner-border-sm"></span>
            </h4>
            <small class="text-muted">Belum Selesai</small>
            <div class="mt-2">
                <small class="text-muted">
                    <span id="pendingPercentage">0</span>% daripada jumlah tiket
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Card 4: Lewat SLA -->
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-danger rounded">
                        <i class='bx bx-error fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0" id="overdueSlaCount">
                <span class="spinner-border spinner-border-sm"></span>
            </h4>
            <small class="text-muted">Lewat SLA</small>
        </div>
    </div>
</div>
```

**Key Features**:
- Loading spinner while fetching data
- IDs for JavaScript to update values
- Responsive grid (col-lg-3 col-sm-6)
- Icon indicators for each card type

---

### 2.3 Chart Partials

#### Chart Status

**File**: `resources/views/dashboard/partials/chart-status.blade.php`

```blade
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Status Tiket</h5>
        </div>
        <div class="card-body">
            <canvas id="chartStatus" height="300"></canvas>
        </div>
    </div>
</div>
```

#### Chart Category

**File**: `resources/views/dashboard/partials/chart-category.blade.php`

```blade
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Kategori Tiket</h5>
        </div>
        <div class="card-body">
            <canvas id="chartCategory" height="300"></canvas>
        </div>
    </div>
</div>
```

#### Chart Trend

**File**: `resources/views/dashboard/partials/chart-trend.blade.php`

```blade
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Trend Bulanan</h5>
        </div>
        <div class="card-body">
            <canvas id="chartTrend" height="100"></canvas>
        </div>
    </div>
</div>
```

---

### 2.4 Table Partial

**File**: `resources/views/dashboard/partials/table-ticket-overdue.blade.php`

```blade
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Tiket Lewat SLA</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableOverdue" class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Tajuk</th>
                            <th>Kategori</th>
                            <th>Diperuntukkan Kepada</th>
                            <th>SLA Due Date</th>
                            <th>Lewat (Jam)</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTable will populate this --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

---

### 2.5 Pegawai Tugasan Partials

Located in `resources/views/dashboard/partials/pegawai-tugasan/`

#### My Tasks

**File**: `my-tasks.blade.php`

```blade
<div class="col-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Tugasan Anda</h5>
            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-primary">
                Lihat Semua
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="myTasksTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Tajuk</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTable will populate --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

---

## 3. JavaScript (dashboard.js)

**File**: `resources/assets/js/dashboard.js`

### Structure

```javascript
// Global config
const DashboardConfig = {
    routes: {
        todayLogs: '/dashboard/today-logs-count',
        weeklyCompleted: '/dashboard/weekly-completed',
        pendingStats: '/dashboard/pending-stats',
        overdueSla: '/dashboard/overdue-sla',
        statusChart: '/dashboard/status-tickets',
        categoryChart: '/dashboard/category-tickets',
        trendChart: '/dashboard/monthly-trend',
        overdueTable: '/dashboard/list-overdue',
    },
    refreshInterval: 300000, // 5 minutes
};

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

function initDashboard() {
    loadCardData();
    initCharts();
    initTables();
    setupAutoRefresh();
}
```

---

### 3.1 Load Card Data

```javascript
function loadCardData() {
    // Load today logs count
    fetch(DashboardConfig.routes.todayLogs)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('todayLogsCount').textContent = data.count;
            }
        })
        .catch(error => {
            console.error('Error loading today logs:', error);
            document.getElementById('todayLogsCount').textContent = '-';
        });

    // Load weekly completed
    fetch(DashboardConfig.routes.weeklyCompleted)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('weeklyCompletedCount').textContent = data.total;
            }
        })
        .catch(error => {
            console.error('Error loading weekly completed:', error);
            document.getElementById('weeklyCompletedCount').textContent = '-';
        });

    // Load pending stats
    fetch(DashboardConfig.routes.pendingStats)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('pendingCount').textContent = data.pending_count;
                document.getElementById('pendingPercentage').textContent = data.percentage;
            }
        })
        .catch(error => {
            console.error('Error loading pending stats:', error);
            document.getElementById('pendingCount').textContent = '-';
        });

    // Load overdue SLA
    fetch(DashboardConfig.routes.overdueSla)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('overdueSlaCount').textContent = data.count;
            }
        })
        .catch(error => {
            console.error('Error loading overdue SLA:', error);
            document.getElementById('overdueSlaCount').textContent = '-';
        });
}
```

---

### 3.2 Initialize Charts

#### Chart.js Configuration

```javascript
function initCharts() {
    initStatusChart();
    initCategoryChart();
    initTrendChart();
}

// Status Chart (Doughnut)
function initStatusChart() {
    fetch(DashboardConfig.routes.statusChart)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('chartStatus');
                
                // Prepare data
                const labels = data.data.map(item => item.st_name);
                const counts = data.data.map(item => item.count);
                const colors = [
                    '#007bff', // Aduan Baru - Blue
                    '#ffc107', // Aduan Diproses - Yellow
                    '#17a2b8', // Serahan Kepada - Cyan
                    '#6c757d', // Aduan Dirujuk - Gray
                    '#dc3545', // Aduan DiBatal - Red
                    '#28a745', // Aduan Selesai - Green
                ];
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: colors,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading status chart:', error));
}

// Category Chart (Pie)
function initCategoryChart() {
    fetch(DashboardConfig.routes.categoryChart)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('chartCategory');
                
                const labels = data.data.map(item => item.cat_name);
                const counts = data.data.map(item => item.count);
                
                // Generate colors dynamically
                const colors = generateColors(data.data.length);
                
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: colors,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading category chart:', error));
}

// Trend Chart (Line)
function initTrendChart() {
    fetch(DashboardConfig.routes.trendChart)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('chartTrend');
                
                const labels = data.data.map(item => item.month);
                const counts = data.data.map(item => item.count);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Tiket',
                            data: counts,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error loading trend chart:', error));
}

// Helper: Generate colors
function generateColors(count) {
    const baseColors = [
        '#007bff', '#28a745', '#dc3545', '#ffc107', 
        '#17a2b8', '#6c757d', '#e83e8c', '#fd7e14'
    ];
    
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(baseColors[i % baseColors.length]);
    }
    return colors;
}
```

---

### 3.3 Initialize Tables (DataTables)

```javascript
function initTables() {
    // Overdue tickets table
    if (document.getElementById('tableOverdue')) {
        $('#tableOverdue').DataTable({
            processing: true,
            serverSide: true,
            ajax: DashboardConfig.routes.overdueTable,
            columns: [
                { data: 'ticket_no', name: 'ticket_no' },
                { data: 'title', name: 'title' },
                { data: 'category', name: 'category' },
                { data: 'assigned_to', name: 'assigned_to' },
                { 
                    data: 'sla_duedate', 
                    name: 'sla_duedate',
                    render: function(data) {
                        return moment(data).format('DD/MM/YYYY HH:mm');
                    }
                },
                { 
                    data: 'overdue_hours', 
                    name: 'overdue_hours',
                    render: function(data) {
                        return `<span class="badge bg-danger">${data} jam</span>`;
                    }
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return `
                            <a href="/tickets/${data}" class="btn btn-sm btn-primary">
                                <i class='bx bx-show'></i>
                            </a>
                        `;
                    }
                }
            ],
            order: [[5, 'desc']], // Sort by overdue hours
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ms.json'
            }
        });
    }
    
    // My tasks table (for pegawai)
    if (document.getElementById('myTasksTable')) {
        $('#myTasksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/dashboard/my-tasks-data',
            columns: [
                { data: 'ticket_no', name: 'ticket_no' },
                { data: 'title', name: 'title' },
                { data: 'category', name: 'category' },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        const statusClass = getStatusClass(data);
                        return `<span class="badge ${statusClass}">${data}</span>`;
                    }
                },
                { 
                    data: 'sla_duedate', 
                    name: 'sla_duedate',
                    render: function(data, type, row) {
                        const now = moment();
                        const due = moment(data);
                        const isOverdue = due.isBefore(now);
                        
                        if (isOverdue) {
                            return `<span class="text-danger">
                                ${due.format('DD/MM/YYYY HH:mm')}
                                <i class='bx bx-error'></i>
                            </span>`;
                        }
                        return due.format('DD/MM/YYYY HH:mm');
                    }
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return `
                            <a href="/tickets/${data}" class="btn btn-sm btn-primary">
                                Tindakan
                            </a>
                        `;
                    }
                }
            ],
            order: [[4, 'asc']], // Sort by SLA due date
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ms.json'
            }
        });
    }
}

// Helper: Get status badge class
function getStatusClass(status) {
    const statusMap = {
        'Aduan Baru': 'bg-primary',
        'Aduan Diproses': 'bg-warning',
        'Serahan Kepada': 'bg-info',
        'Aduan Dirujuk': 'bg-secondary',
        'Aduan DiBatal': 'bg-danger',
        'Aduan Selesai': 'bg-success',
    };
    return statusMap[status] || 'bg-secondary';
}
```

---

### 3.4 Auto Refresh

```javascript
function setupAutoRefresh() {
    // Refresh card data every 5 minutes
    setInterval(function() {
        loadCardData();
    }, DashboardConfig.refreshInterval);
    
    // Refresh tables every 5 minutes
    setInterval(function() {
        if ($.fn.DataTable.isDataTable('#tableOverdue')) {
            $('#tableOverdue').DataTable().ajax.reload(null, false);
        }
        if ($.fn.DataTable.isDataTable('#myTasksTable')) {
            $('#myTasksTable').DataTable().ajax.reload(null, false);
        }
    }, DashboardConfig.refreshInterval);
}
```

---

## 4. CSS Styling

**File**: `resources/assets/css/dashboard-layout.css`

```css
/* Dashboard specific styles */

/* Card styling */
.card {
    box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
    border-radius: 0.5rem;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px 0 rgba(67, 89, 113, 0.16);
}

/* Avatar icons */
.avatar-initial {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
}

/* Chart containers */
canvas {
    max-height: 400px;
}

/* Table overrides */
.table-responsive {
    border-radius: 0.5rem;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Status badges */
.badge {
    padding: 0.35em 0.65em;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-lg-3 {
        margin-bottom: 1rem;
    }
    
    canvas {
        max-height: 300px;
    }
}

/* Impersonate alert */
.alert-warning {
    border-left: 4px solid #ff9f43;
}

/* Session timer */
#sessionTimerWidget .card {
    border: 2px solid #ffc107;
}

/* Overdue indicator */
.text-danger i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
```

---

## 5. Best Practices

### Blade Templates
1. ✅ Use `$sections` array untuk conditional rendering
2. ✅ Pecahkan content kepada partials
3. ✅ Gunakan Bootstrap grid system
4. ✅ Provide loading states (spinners)
5. ❌ Jangan guna role checking dalam Blade
6. ❌ Jangan guna business logic dalam Blade

### JavaScript
1. ✅ Modularize code (separate functions)
2. ✅ Handle errors gracefully
3. ✅ Show loading states
4. ✅ Use async/await atau Promises
5. ✅ Implement auto-refresh untuk real-time data
6. ❌ Jangan hardcode URLs (use config)
7. ❌ Jangan expose sensitive data

### CSS
1. ✅ Use BEM naming convention
2. ✅ Mobile-first responsive design
3. ✅ Consistent spacing (use utility classes)
4. ✅ Smooth transitions and animations
5. ❌ Jangan override framework styles unnecessarily

---

## 6. Performance Optimization

### Lazy Loading
Load charts selepas page load:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Load critical content first
    loadCardData();
    
    // Delay chart loading
    setTimeout(function() {
        initCharts();
    }, 500);
    
    // Delay table loading
    setTimeout(function() {
        initTables();
    }, 1000);
});
```

### Debouncing
Untuk search/filter functions:

```javascript
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Usage
const debouncedSearch = debounce(function(query) {
    // Perform search
}, 300);
```

### Caching
Cache chart instances:

```javascript
const chartInstances = {};

function getOrCreateChart(canvasId, config) {
    if (chartInstances[canvasId]) {
        return chartInstances[canvasId];
    }
    
    const ctx = document.getElementById(canvasId);
    const chart = new Chart(ctx, config);
    chartInstances[canvasId] = chart;
    return chart;
}
```
