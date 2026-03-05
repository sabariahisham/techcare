# 04 - Dashboard Pegawai Tugasan: Frontend Implementation

## Overview

Frontend untuk Dashboard Pegawai Tugasan terdiri daripada:
1. **Blade Partials** - Reusable view components
2. **JavaScript** - Interactive elements
3. **CSS** - Styling (shared dengan dashboard lain)

---

## Blade Templates Structure

### Main Dashboard File

**File**: `resources/views/dashboard/dashboard.blade.php`

### Pegawai Tugasan Section

```blade
@section('content')
    {{-- Welcome & Session Timer (shown for all users) --}}
    <div class="row g-4 mb-6">
        ...
    </div>

    {{-- PEGAWAI TUGASAN SECTION --}}
    @if($sections['pegawai'])
        {{-- Status Cards --}}
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pegawai-tugasan/cards-status-pegawai')
        </div>

        {{-- My Tasks Table --}}
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pegawai-tugasan/my-tasks')
        </div>

        {{-- Charts Row --}}
        <div class="row g-4 mb-6">
            @include('dashboard/partials/pegawai-tugasan/chart-status-pegawai')
            @include('dashboard/partials/pegawai-tugasan/chart-category-pegawai')
        </div>

        {{-- Overdue Tickets Table --}}
        @if(isset($my_tasks['overdue']) && $my_tasks['overdue'] > 0)
            <div class="row g-4 mb-6">
                @include('dashboard/partials/pegawai-tugasan/table-ticket-overdue-pegawai')
            </div>
        @endif
    @endif

    {{-- Other sections (admin, helpdesk, etc.) --}}
    ...
@endsection
```

### Key Principles

#### 1. Section Flag Check
```blade
@if($sections['pegawai'])
    {{-- Show pegawai content --}}
@endif
```

**Why?**
- User boleh punya multiple roles
- Flag control visibility of sections
- Set di backend (DashboardService)

#### 2. No Role Logic in Blade
```blade
{{-- ❌ WRONG --}}
@if(Auth::user()->hasRole('Pegawai Tugasan'))
    ...
@endif

{{-- ✅ CORRECT --}}
@if($sections['pegawai'])
    ...
@endif
```

#### 3. Data Directly from Backend
```blade
{{-- All data passed from controller --}}
{{ $my_tasks['assigned_to_me'] }}
{{ $my_tickets_summary['total'] }}
```

---

## Partial Templates

### 1. Status Cards (Pegawai)

**File**: `resources/views/dashboard/partials/pegawai-tugasan/cards-status-pegawai.blade.php`

```blade
{{-- Card 1: Ditugaskan Kepada Saya --}}
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-primary rounded">
                        <i class='bx bx-food-menu fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0">{{ $my_tasks['assigned_to_me'] ?? 0 }}</h4>
            <small class="text-muted">Ditugaskan Kepada Saya</small>
        </div>
    </div>
</div>

{{-- Card 2: Belum Selesai --}}
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
            <h4 class="mb-0">{{ $my_tasks['pending'] ?? 0 }}</h4>
            <small class="text-muted">Belum Selesai</small>
        </div>
    </div>
</div>

{{-- Card 3: Selesai Minggu Ini --}}
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
            <h4 class="mb-0">{{ $my_tasks['completed_this_week'] ?? 0 }}</h4>
            <small class="text-muted">Selesai Minggu Ini</small>
        </div>
    </div>
</div>

{{-- Card 4: Lewat SLA --}}
<div class="col-lg-3 col-sm-6">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="avatar">
                    <div class="avatar-initial bg-label-danger rounded">
                        <i class='bx bx-error-circle fs-4'></i>
                    </div>
                </div>
            </div>
            <h4 class="mb-0 text-danger">{{ $my_tasks['overdue'] ?? 0 }}</h4>
            <small class="text-muted">Lewat SLA</small>
            @if(($my_tasks['overdue'] ?? 0) > 0)
                <div class="mt-2">
                    <span class="badge bg-danger">Perhatian Segera!</span>
                </div>
            @endif
        </div>
    </div>
</div>
```

**Features**:
- 4 cards dengan color coding:
  - Primary (blue) - Assigned
  - Warning (yellow) - Pending
  - Success (green) - Completed
  - Danger (red) - Overdue
- Alert badge for overdue tickets
- Icons untuk visual clarity

---

### 2. My Tasks Table

**File**: `resources/views/dashboard/partials/pegawai-tugasan/my-tasks.blade.php`

```blade
<div class="col-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Tugasan Saya</h5>
            <a href="{{ route('tickets.my-tasks') }}" class="btn btn-sm btn-primary">
                <i class='bx bx-right-arrow-alt'></i>
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
                            <th>SLA Due Date</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTable will populate via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

**JavaScript Initialization**:
```javascript
// In dashboard.js
if ($('#myTasksTable').length) {
    $('#myTasksTable').DataTable({
        ajax: {
            url: '/api/dashboard/my-tasks',
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        columns: [
            { data: 'ticket_no' },
            { data: 'title' },
            { data: 'category' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return renderStatusBadge(data);
                }
            },
            { 
                data: 'sla_duedate',
                render: function(data, type, row) {
                    return renderSLAStatus(data, row.is_overdue);
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <a href="/tickets/${row.id}" class="btn btn-sm btn-primary">
                            <i class='bx bx-show'></i> Lihat
                        </a>
                    `;
                }
            }
        ],
        order: [[4, 'asc']], // Sort by SLA due date
        pageLength: 10,
        responsive: true
    });
}
```

---

### 3. Status Chart (Pegawai)

**File**: `resources/views/dashboard/partials/pegawai-tugasan/chart-status-pegawai.blade.php`

```blade
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Status Tiket Saya</h5>
            <small class="text-muted">Breakdown mengikut status</small>
        </div>
        <div class="card-body">
            <canvas id="chartStatusPegawai" height="300"></canvas>
        </div>
    </div>
</div>

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartStatusPegawai');
    if (ctx) {
        const myTicketsSummary = @json($my_tickets_summary ?? []);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Baru', 'Dalam Proses', 'Selesai', 'Dibatalkan'],
                datasets: [{
                    label: 'Bilangan Tiket',
                    data: [
                        myTicketsSummary.new || 0,
                        myTicketsSummary.in_progress || 0,
                        myTicketsSummary.completed || 0,
                        myTicketsSummary.cancelled || 0
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',   // Blue - New
                        'rgba(255, 206, 86, 0.8)',   // Yellow - In Progress
                        'rgba(75, 192, 192, 0.8)',   // Green - Completed
                        'rgba(255, 99, 132, 0.8)'     // Red - Cancelled
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' tiket';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
```

---

### 4. Category Chart (Pegawai)

**File**: `resources/views/dashboard/partials/pegawai-tugasan/chart-category-pegawai.blade.php`

```blade
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Kategori Tiket Saya</h5>
            <small class="text-muted">Distribution mengikut kategori</small>
        </div>
        <div class="card-body">
            <canvas id="chartCategoryPegawai" height="300"></canvas>
        </div>
    </div>
</div>

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load category data via AJAX
    fetch('/api/dashboard/my-categories')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('chartCategoryPegawai');
            if (ctx && data.length > 0) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(item => item.category_name),
                        datasets: [{
                            data: data.map(item => item.count),
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', 
                                '#f6c23e', '#e74a3b', '#858796'
                            ],
                            hoverBackgroundColor: [
                                '#2e59d9', '#17a673', '#2c9faf',
                                '#dda20a', '#be2617', '#60616f'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
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
            } else {
                ctx.parentElement.innerHTML = '<p class="text-muted text-center mt-5">Tiada data kategori</p>';
            }
        })
        .catch(error => {
            console.error('Error loading category data:', error);
        });
});
</script>
@endpush
```

---

### 5. Overdue Tickets Table

**File**: `resources/views/dashboard/partials/pegawai-tugasan/table-ticket-overdue-pegawai.blade.php`

```blade
<div class="col-12">
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0 text-white">
                <i class='bx bx-error-circle'></i>
                Tiket Lewat SLA - Perhatian Segera!
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableOverduePegawai" class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Tajuk</th>
                            <th>Kategori</th>
                            <th>SLA Due Date</th>
                            <th>Lewat (Jam)</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTable via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('page-script')
<script>
if ($('#tableOverduePegawai').length) {
    $('#tableOverduePegawai').DataTable({
        ajax: {
            url: '/api/dashboard/my-overdue-tickets',
            type: 'GET'
        },
        columns: [
            { data: 'ticket_no' },
            { data: 'title' },
            { data: 'category' },
            { 
                data: 'sla_duedate',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY HH:mm');
                }
            },
            { 
                data: 'hours_overdue',
                render: function(data) {
                    return `<span class="badge bg-danger">${data} jam</span>`;
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <a href="/tickets/${row.id}" class="btn btn-sm btn-danger">
                            <i class='bx bx-edit'></i> Tindakan
                        </a>
                    `;
                }
            }
        ],
        order: [[4, 'desc']], // Sort by hours overdue (most urgent first)
        pageLength: 10
    });
}
</script>
@endpush
```

---

## JavaScript Implementation

### Dashboard.js (Pegawai Sections)

**File**: `resources/assets/js/dashboard.js`

```javascript
// ==========================================
// PEGAWAI TUGASAN DASHBOARD
// ==========================================

/**
 * Initialize Pegawai Dashboard Components
 */
function initPegawaiDashboard() {
    if (!$('#myTasksTable').length && !$('#tableOverduePegawai').length) {
        return; // Not pegawai section
    }

    console.log('Initializing Pegawai Dashboard...');

    // Initialize My Tasks Table
    initMyTasksTable();

    // Initialize Overdue Table
    initOverdueTablePegawai();

    // Initialize Charts
    // (Charts initialized via Blade @push scripts)

    // Auto-refresh every 5 minutes
    setInterval(refreshPegawaiDashboard, 300000);
}

/**
 * Initialize My Tasks DataTable
 */
function initMyTasksTable() {
    if ($('#myTasksTable').length) {
        window.myTasksDT = $('#myTasksTable').DataTable({
            ajax: {
                url: '/api/dashboard/my-tasks',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                error: function(xhr, error, thrown) {
                    console.error('Error loading my tasks:', error);
                    showNotification('error', 'Gagal memuatkan data tugasan');
                }
            },
            columns: [
                { 
                    data: 'ticket_no',
                    render: function(data, type, row) {
                        return `<a href="/tickets/${row.id}">${data}</a>`;
                    }
                },
                { 
                    data: 'title',
                    render: function(data) {
                        return truncateText(data, 50);
                    }
                },
                { data: 'category' },
                { 
                    data: 'status',
                    render: function(data) {
                        return renderStatusBadge(data);
                    }
                },
                { 
                    data: 'sla_duedate',
                    render: function(data, type, row) {
                        return renderSLAStatus(data, row.is_overdue);
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <a href="/tickets/${row.id}" class="btn btn-sm btn-primary">
                                <i class='bx bx-show'></i> Lihat
                            </a>
                        `;
                    }
                }
            ],
            order: [[4, 'asc']], // Sort by SLA
            pageLength: 10,
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ms.json'
            }
        });
    }
}

/**
 * Initialize Overdue Tickets Table
 */
function initOverdueTablePegawai() {
    if ($('#tableOverduePegawai').length) {
        window.overdueDT = $('#tableOverduePegawai').DataTable({
            ajax: {
                url: '/api/dashboard/my-overdue-tickets',
                type: 'GET'
            },
            columns: [
                { data: 'ticket_no' },
                { data: 'title' },
                { data: 'category' },
                { 
                    data: 'sla_duedate',
                    render: function(data) {
                        return moment(data).format('DD/MM/YYYY HH:mm');
                    }
                },
                { 
                    data: 'hours_overdue',
                    render: function(data) {
                        return `<span class="badge bg-danger">${data} jam</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <a href="/tickets/${row.id}" class="btn btn-sm btn-danger">
                                <i class='bx bx-edit'></i> Tindakan
                            </a>
                        `;
                    }
                }
            ],
            order: [[4, 'desc']],
            pageLength: 5
        });
    }
}

/**
 * Render Status Badge
 */
function renderStatusBadge(status) {
    const statusMap = {
        '1': { text: 'Baru', class: 'bg-primary' },
        '2': { text: 'Dalam Proses', class: 'bg-info' },
        '3': { text: 'Menunggu', class: 'bg-warning' },
        '4': { text: 'Sedang Dikerjakan', class: 'bg-info' },
        '5': { text: 'Dibatalkan', class: 'bg-secondary' },
        '6': { text: 'Selesai', class: 'bg-success' }
    };
    
    const s = statusMap[status] || { text: 'Unknown', class: 'bg-secondary' };
    return `<span class="badge ${s.class}">${s.text}</span>`;
}

/**
 * Render SLA Status
 */
function renderSLAStatus(dueDate, isOverdue) {
    if (!dueDate) {
        return '<span class="text-muted">-</span>';
    }
    
    const formatted = moment(dueDate).format('DD/MM/YYYY HH:mm');
    
    if (isOverdue) {
        const hoursOverdue = moment().diff(moment(dueDate), 'hours');
        return `
            <span class="text-danger">
                <i class='bx bx-error-circle'></i> ${formatted}
                <br><small>(Lewat ${hoursOverdue}h)</small>
            </span>
        `;
    }
    
    const hoursRemaining = moment(dueDate).diff(moment(), 'hours');
    if (hoursRemaining < 24) {
        return `<span class="text-warning">${formatted}</span>`;
    }
    
    return `<span class="text-success">${formatted}</span>`;
}

/**
 * Refresh Pegawai Dashboard Data
 */
function refreshPegawaiDashboard() {
    console.log('Refreshing pegawai dashboard...');
    
    // Reload DataTables
    if (window.myTasksDT) {
        window.myTasksDT.ajax.reload(null, false);
    }
    if (window.overdueDT) {
        window.overdueDT.ajax.reload(null, false);
    }
    
    // Reload card counts
    refreshPegawaiCards();
}

/**
 * Refresh Card Counts
 */
function refreshPegawaiCards() {
    fetch('/api/dashboard/my-tasks-summary')
        .then(response => response.json())
        .then(data => {
            // Update card values
            updateCardValue('assigned_to_me', data.assigned_to_me);
            updateCardValue('pending', data.pending);
            updateCardValue('completed_this_week', data.completed_this_week);
            updateCardValue('overdue', data.overdue);
        })
        .catch(error => {
            console.error('Error refreshing cards:', error);
        });
}

/**
 * Utility: Update card value
 */
function updateCardValue(selector, value) {
    $(`[data-card="${selector}"]`).text(value);
}

/**
 * Utility: Truncate text
 */
function truncateText(text, maxLength) {
    if (text.length > maxLength) {
        return text.substring(0, maxLength) + '...';
    }
    return text;
}

// Initialize on document ready
$(document).ready(function() {
    initPegawaiDashboard();
});
```

---

## API Endpoints (Controller)

### Required Endpoints

```php
// In DashboardController.php

/**
 * Get my tasks data for DataTable
 */
public function getMyTasks(Request $request)
{
    $user = Auth::user();
    
    $tasks = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->join('ref_ticket_statuses as rts', 'tc.tick_status', '=', 'rts.id')
        ->where('tc.escalate_to', $user->id)
        ->whereNull('t.deleted_at')
        ->whereNull('tc.deleted_at')
        ->select(
            't.id',
            't.ticket_no',
            't.title',
            'rc.cat_name as category',
            'rts.name as status',
            'tc.tick_status',
            'tc.sla_duedate',
            DB::raw('CASE WHEN tc.sla_duedate < NOW() THEN 1 ELSE 0 END as is_overdue')
        )
        ->get();
    
    return response()->json(['data' => $tasks]);
}

/**
 * Get overdue tickets for pegawai
 */
public function getMyOverdueTickets(Request $request)
{
    $user = Auth::user();
    
    $overdue = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->where('tc.escalate_to', $user->id)
        ->where('tc.tick_status', '!=', 6)
        ->whereNotNull('tc.sla_duedate')
        ->where('tc.sla_duedate', '<', now())
        ->whereNull('t.deleted_at')
        ->select(
            't.id',
            't.ticket_no',
            't.title',
            'rc.cat_name as category',
            'tc.sla_duedate',
            DB::raw('TIMESTAMPDIFF(HOUR, tc.sla_duedate, NOW()) as hours_overdue')
        )
        ->orderBy('hours_overdue', 'DESC')
        ->get();
    
    return response()->json(['data' => $overdue]);
}

/**
 * Get my categories breakdown
 */
public function getMyCategories(Request $request)
{
    $user = Auth::user();
    
    $categories = DB::table('tickets as t')
        ->join('tickets_chronologies as tc', 't.id', '=', 'tc.ticket_id')
        ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
        ->where('tc.escalate_to', $user->id)
        ->whereNull('t.deleted_at')
        ->groupBy('rc.id', 'rc.cat_name')
        ->select(
            'rc.cat_name as category_name',
            DB::raw('COUNT(DISTINCT t.id) as count')
        )
        ->get();
    
    return response()->json($categories);
}
```

---

## Best Practices

### 1. Consistent Naming
```blade
{{-- Use -pegawai suffix for IDs --}}
<canvas id="chartStatusPegawai"></canvas>
<table id="myTasksTable"></table>
```

### 2. Error Display
```blade
@if(isset($error))
    <div class="alert alert-danger">{{ $error }}</div>
@endif
```

### 3. Empty State
```blade
@if(($my_tasks['assigned_to_me'] ?? 0) == 0)
    <div class="text-center py-5">
        <i class='bx bx-task bx-lg text-muted'></i>
        <p class="text-muted mt-2">Tiada tugasan</p>
    </div>
@endif
```

### 4. Loading States
```javascript
// Show spinner while loading
$('#myTasksTable').DataTable({
    processing: true,
    // ...
});
```

---

## Dokumentasi Berkaitan

- [03-services.md](03-services.md) - Backend services
- [05-database.md](05-database.md) - Database queries
- [06-testing.md](06-testing.md) - Testing

---

**Versi**: 1.0.0  
**Dikemaskini**: 5 Mac 2026
