# Log Viewer System Documentation

## Overview
Sistem paparan log untuk email dan SLA notifications yang telah dihantar kepada pegawai. Sistem ini membolehkan audit trail dan monitoring untuk semua komunikasi automatik sistem.

---

## 🎯 Features

### 1. **Log Email Viewer** (`/audit/logs-mail`)
- Paparan semua email notification yang telah dihantar
- Filter mengikut tarikh dan status (SENT/FAILED)
- Statistik real-time untuk semua status
- DataTables integration untuk searching dan pagination

### 2. **Log SLA Viewer** (`/audit/logs-sla`)
- Paparan SLA notification logs (REMINDER & OVERDUE)
- Filter mengikut tarikh dan kategori
- Statistik real-time untuk setiap kategori
- Maklumat lengkap pegawai dan status tiket

---

## 📁 File Structure

```
app/
├── Http/Controllers/Audit/
│   ├── MailLogController.php      # Controller untuk log email
│   └── SLALogController.php       # Controller untuk log SLA
├── Services/
│   └── LogParserService.php       # Service untuk parse log files
resources/views/audit/
├── logs-mail/
│   └── index.blade.php            # View untuk log email
└── logs-sla/
    └── index.blade.php            # View untuk log SLA
storage/logs/
├── mail-YYYY-MM-DD.log            # Daily mail logs
└── sla-YYYY-MM-DD.log             # Daily SLA logs
```

---

## 🔧 Technical Implementation

### 1. Log Parser Service

**File:** `app/Services/LogParserService.php`

#### Key Methods:

##### `parseMailLog($date)`
```php
// Parse mail log file untuk tarikh tertentu
// Returns: Array of parsed mail log entries
// Features:
// - Filters out SENDING status (only shows SENT/FAILED)
// - Fetches recipient data from database
// - Includes ticket status information
```

##### `parseSlaLog($date)`
```php
// Parse SLA log file untuk tarikh tertentu
// Returns: Array of parsed SLA log entries
// Features:
// - Filters out intermediate statuses (only shows SENT/FAILED)
// - Excludes COMMAND/QUERY/FOUND/COMPLETE statuses
// - Shows only REMINDER and OVERDUE notifications
// - Includes ticket and recipient details
```

##### `parseMailLogLine($line)` & `parseSlaLogLine($line)`
```php
// Parse single log line using regex
// Pattern: [timestamp] channel.LEVEL: [TAG1][TAG2][TAG3] message {context}
// Extracts:
// - Date and time
// - Category/Type
// - Status
// - Message
// - JSON context (ticket, email, etc.)
```

#### Important Filtering Logic:

**Mail Logs:**
```php
// Only show final status (SENT/FAILED), skip SENDING
if ($parsed['status'] !== 'SENDING') {
    $logs[] = $parsed;
}
```

**SLA Logs:**
```php
// Only show notification results (SENT/FAILED)
$relevantStatuses = ['SENT', 'FAILED'];
if (in_array($parsed['status'], $relevantStatuses)) {
    $logs[] = $parsed;
}
```

---

### 2. Controllers

#### MailLogController

**File:** `app/Http/Controllers/Audit/MailLogController.php`

**Key Features:**
- Date filter (default: empty, user must select)
- Status filter (SENT/FAILED/All)
- Statistics calculated **before** filtering
- Validates date selection before applying filters

**Important Logic:**
```php
// Calculate statistics BEFORE filtering
$allLogs = $logs; // Keep copy for statistics
$statistics = [
    'total' => count($allLogs),
    'by_status' => []
];

foreach ($allLogs as $log) {
    $status = $log['status'];
    $statistics['by_status'][$status] = ($statistics['by_status'][$status] ?? 0) + 1;
}

// NOW apply filter (only affects displayed logs, not statistics)
if (!empty($logs) && $status) {
    $logs = array_filter($logs, function ($log) use ($status) {
        return $log['status'] === $status;
    });
}
```

#### SLALogController

**File:** `app/Http/Controllers/Audit/SLALogController.php`

**Similar to MailLogController but:**
- Category filter instead of status filter
- Filters REMINDER vs OVERDUE
- Same statistics-before-filter pattern

---

### 3. Views

#### Common Features (Both Views):

**Filter Section:**
```blade
<div class="row">
    <!-- Date Input -->
    <div class="col-md-3">
        <label>Pilih Tarikh:</label>
        <input type="date" id="dateFilter" value="{{ $selectedDate ?? '' }}">
        <small>Sila pilih tarikh untuk melihat log</small>
    </div>
    
    <!-- Status/Category Filter -->
    <div class="col-md-3">
        <label>Pilih Status/Kategori:</label>
        <select id="statusFilter">...</select>
        <small>Tapis mengikut status/kategori</small>
    </div>
    
    <!-- Reset Button -->
    <div class="col-md-3">
        <label>&nbsp;</label>
        <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
            <i class="bx bx-refresh"></i> Reset Filter
        </button>
        <small>&nbsp;</small>
    </div>
    
    <!-- Statistics Badges -->
    <div class="col-md-3">
        <label>&nbsp;</label>
        <div class="d-flex gap-2 flex-wrap">
            <div class="badge bg-primary p-2 text-center">
                Jumlah: {{ $statistics['total'] }}
            </div>
            <!-- Status/Category badges -->
        </div>
        <small>&nbsp;</small>
    </div>
</div>
```

**JavaScript Functions:**
```javascript
// Date filter with validation
function filterByDate() {
    const selectedDate = document.getElementById('dateFilter').value;
    if (!selectedDate) {
        alert('Sila pilih tarikh terlebih dahulu.');
        return;
    }
    // Build URL with parameters
    let url = "?date=" + selectedDate;
    if (selectedStatus) {
        url += "&status=" + selectedStatus;
    }
    window.location.href = url;
}

// Reset to empty state
function resetFilters() {
    window.location.href = "{{ route('log-mail-index') }}";
}
```

**DataTables Initialization:**
```javascript
// Only initialize when data exists (prevents column mismatch warning)
@if(!empty($logs) && count($logs) > 0)
if (typeof jQuery !== 'undefined') {
    jQuery('#mailLogsTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']], // Sort by date descending
        language: {
            search: "Cari:",
            lengthMenu: "Papar _MENU_ rekod",
            // ... Malay translations
        }
    });
}
@endif
```

---

## 🗂️ Database Queries

### Fetching Recipient and Ticket Data

Both parsers query database untuk dapatkan maklumat lengkap:

```php
$ticketData = DB::table('tickets')
    ->leftJoin('tickets_chronologies', function ($join) {
        $join->on('tickets.id', '=', 'tickets_chronologies.ticket_id')
            ->whereNull('tickets_chronologies.deleted_at')
            ->whereRaw('tickets_chronologies.id = (
                SELECT MAX(tc.id)
                FROM tickets_chronologies tc
                WHERE tc.ticket_id = tickets.id
                AND tc.deleted_at IS NULL
            )');
    })
    ->leftJoin('users', 'tickets_chronologies.escalate_to', '=', 'users.id')
    ->leftJoin('ref_ticket_statuses', 'tickets_chronologies.tick_status', '=', 'ref_ticket_statuses.id')
    ->where('tickets.ticket_nolog', $ticket)
    ->whereNull('tickets.deleted_at')
    ->select(
        'users.nama as recipient_name',
        'users.email as recipient_email',
        'ref_ticket_statuses.st_name as ticket_status'
    )
    ->first();
```

**Mengapa Query Ini Kompleks:**
- Ambil chronology TERKINI sahaja (MAX id)
- Join dengan users untuk nama dan email pegawai
- Join dengan ref_ticket_statuses untuk status nama
- Filter deleted records

---

## 📊 Log File Format

### Mail Log Example:
```
[2026-02-09 22:52:54] local.INFO: [MAIL][SLA_REMINDER][SENDING] Preparing reminder email {"ticket":"HELP-2026-02-0005","recipient_email":"m***g@treasury.gov.my"}
[2026-02-09 22:52:56] local.INFO: [MAIL][SLA_REMINDER][SUCCESS] Email sent successfully {"ticket":"HELP-2026-02-0005","recipient_email":"m***g@treasury.gov.my"}
```

### SLA Log Example:
```
[2026-02-09 22:52:54] local.INFO: [SLA][COMMAND][START] SLA notification command started
[2026-02-09 22:52:54] local.INFO: [SLA][REMINDER][START] Starting reminder notifications process
[2026-02-09 22:52:54] local.INFO: [SLA][REMINDER][QUERY] Target SLA due date calculated
[2026-02-09 22:52:54] local.INFO: [SLA][REMINDER][FOUND] Chronologies retrieved {"count":2}
[2026-02-09 22:52:56] local.INFO: [SLA][REMINDER][SENT] Notification sent successfully {"ticket":"HELP-2026-02-0005"}
[2026-02-09 22:52:57] local.INFO: [SLA][REMINDER][COMPLETE] Process completed {"sent":2}
```

### Regex Pattern:
```php
/^\[([^\]]+)\]\s+\w+\.(\w+):\s+\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]\s+(.+?)(\s+\{.+\})?$/
```

**Captures:**
1. Timestamp
2. Log level (INFO, ERROR, etc.)
3. Tag 1 (SLA/MAIL)
4. Tag 2 (REMINDER/OVERDUE/SLA_REMINDER/etc.)
5. Tag 3 (SENT/FAILED/START/QUERY/etc.)
6. Message
7. JSON context (optional)

---

## 🎨 UI/UX Features

### Alignment & Spacing
Semua input fields, button, dan badges diselaraskan dengan:
```blade
<label class="form-label">&nbsp;</label>  <!-- Empty label for spacing -->
<button class="w-100">...</button>         <!-- Full width button -->
<small class="text-muted">&nbsp;</small>   <!-- Empty small text for spacing -->
```

### Badge Styling
```blade
<div class="badge bg-primary p-2 text-center">
    <i class="bx bx-list-ul"></i> Jumlah: {{ $statistics['total'] }}
</div>
```
- `p-2`: Padding untuk badge
- `text-center`: Text di tengah badge
- Color codes:
  - `bg-primary`: Blue (Total)
  - `bg-success`: Green (SENT)
  - `bg-danger`: Red (FAILED/OVERDUE)
  - `bg-warning`: Yellow (REMINDER)

### Empty State
```blade
<td colspan="5" class="text-center text-muted py-5">
    <i class="bx bx-calendar bx-lg"></i><br>
    <strong>
        {{ $selectedDate 
            ? 'Tiada rekod log untuk tarikh ini.' 
            : 'Sila pilih tarikh untuk melihat log email.' 
        }}
    </strong>
</td>
```

---

## 🐛 Common Issues & Solutions

### Issue 1: DataTables Warning - Column Count Mismatch
**Problem:** Warning muncul bila tiada data/tarikh tidak dipilih

**Solution:** Conditional DataTables initialization
```javascript
@if(!empty($logs) && count($logs) > 0)
    // Initialize DataTables
@endif
```

### Issue 2: Duplicate Records
**Problem:** Tiket sama muncul berkali-kali dengan status berbeza

**Solution:** Filter intermediate statuses
```php
// Mail: Skip SENDING, only show SENT/FAILED
// SLA: Skip START/QUERY/FOUND/COMPLETE, only show SENT/FAILED
```

### Issue 3: Statistics Hanya Tunjuk Data Yang Difilter
**Problem:** Bila pilih REMINDER, badge hanya tunjuk REMINDER count

**Solution:** Calculate statistics BEFORE filtering
```php
$allLogs = $logs; // Keep original
// Calculate stats from $allLogs
// Then filter $logs for display
```

### Issue 4: Filter Tidak Reset
**Problem:** Kategori/status kekal walaupun klik Reset

**Solution:** Reset function tanpa parameters
```javascript
function resetFilters() {
    window.location.href = "{{ route('log-mail-index') }}"; // No query params
}
```

---

## 🔄 Data Flow

```
1. User selects date → filterByDate()
2. Controller receives date parameter
3. LogParserService reads storage/logs/mail-YYYY-MM-DD.log
4. Parse each line with regex
5. Query database for ticket/user details
6. Filter out intermediate statuses
7. Sort by date/time descending
8. Calculate statistics from ALL logs
9. Apply user filters (status/category)
10. Return filtered logs + full statistics to view
11. View renders table + badges
12. DataTables enhances table (if data exists)
```

---

## 📝 Routes

```php
// web.php
Route::get('/logs-mail', [MailLogController::class, 'index'])->name('log-mail-index');
Route::get('/logs-sla', [SLALogController::class, 'index'])->name('log-sla-index');
```

---

## 🔐 Permissions

Pastikan user mempunyai permission untuk akses audit logs:
```php
// Middleware: auth, verified, role:superadmin|admin
```

---

## 📈 Future Enhancements

1. **Export to Excel/PDF**
2. **Date Range Filter** (from-to)
3. **Advanced Search** (by ticket number, email, officer name)
4. **Email Retry** function untuk FAILED emails
5. **Real-time Log Streaming** dengan WebSockets
6. **Log Retention Policy** (auto-delete old logs)
7. **Dashboard Widgets** untuk quick stats

---

## 🧪 Testing Checklist

- [ ] Tarikh kosong - tunjuk mesej "Sila pilih tarikh"
- [ ] Pilih tarikh - papar data untuk tarikh tersebut
- [ ] Filter status/kategori - hanya tunjuk yang dipilih
- [ ] Reset filter - kembali ke keadaan awal (tarikh kosong)
- [ ] Statistics badge - tunjuk semua count walaupun ada filter
- [ ] DataTables - search, sort, pagination berfungsi
- [ ] Tiada duplikasi - tiket sama tidak berulang
- [ ] Responsive design - berfungsi di mobile
- [ ] Empty state - mesej yang sesuai bila tiada data

---

## 📞 Support

Untuk sebarang isu atau pertanyaan, rujuk:
- **Code Location:** `app/Services/LogParserService.php`
- **Views:** `resources/views/audit/logs-mail/` & `logs-sla/`
- **Routes:** `routes/web.php` (search: `logs-mail`, `logs-sla`)

---

**Last Updated:** February 10, 2026
**Version:** 1.0.0
