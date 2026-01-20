# Dokumentasi: Senarai Tiket (DataTable)

## 📋 Ringkasan
Feature ini memaparkan senarai tiket dalam format DataTable yang interaktif dengan server-side processing. Terdapat 2 jenis paparan:
1. **Tiket Saya** - Tiket yang dilog oleh pengguna semasa
2. **Tiket Untuk Diurus** - Tiket untuk pentadbir dan pegawai tugasan

## 🎯 Fungsi Utama
- Paparan tiket dalam format jadual yang boleh di-sort dan di-search
- Server-side processing untuk performance yang baik
- Filter tiket mengikut jenis (Semua/Diri Sendiri/Bagi Pihak)
- Export data (Print, CSV, Excel, PDF, Copy)
- Responsive design untuk mobile dan desktop
- Real-time status updates

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketController.php
├── index()                    # Paparan halaman senarai tiket
├── data(Request $request)     # Endpoint untuk DataTables AJAX
└── show($id)                  # Paparan detail tiket
```

```
app/Http/Controllers/Ticket/TicketSearchController.php
├── getData(Request $request)           # Main routing untuk data request
├── getMyTicketsData(Request $request)  # Data untuk "Tiket Saya"
└── getTicketsToManageData(Request $request) # Data untuk "Tiket Untuk Diurus"
```

### Frontend (Views)
```
resources/views/tickets/index.blade.php
└── Halaman utama senarai tiket dengan 2 DataTables:
    ├── .datatables-my-tickets       # Table untuk tiket pengguna
    └── .datatables-manage-tickets   # Table untuk pentadbir/pegawai
```

```
resources/views/tickets/partials/styles.blade.php
└── Custom CSS untuk ticket listing
```

### JavaScript Files
```
resources/assets/js/ticket/list.js
├── Initialization DataTable untuk "Tiket Saya"
│   ├── Server-side processing configuration
│   ├── Column definitions
│   ├── Custom buttons (Export, Aduan Baru)
│   └── Bahasa Melayu translations
│
├── Initialization DataTable untuk "Tiket Untuk Diurus"
│   └── Similar configuration untuk pentadbir
│
├── Filter dropdown functionality
│   └── Tapis berdasarkan jenis tiket
│
└── View record handler
    └── Redirect ke halaman detail tiket
```

```
resources/assets/js/ticket/config.js
├── Global configuration untuk ticket module
├── DataTable reference management
└── Reload functions untuk semua tables
```

### Routes
```
routes/web.php
├── GET  /tickets                    # index() - Halaman senarai
├── GET  /tickets/data              # data() - AJAX endpoint untuk DataTables
├── GET  /tickets/{ticket}          # show() - Detail tiket
└── Middleware: auth, verified
```

### Models
```
app/Models/Ticket.php
├── Relationships:
│   ├── user() - Belongsto User (pengadu)
│   ├── createdBy() - BelongsTo User (pembuat tiket)
│   ├── category() - BelongsTo RefCategory
│   ├── subCategory() - BelongsTo RefSubCategory
│   ├── status() - BelongsTo RefTicketStatus
│   ├── chronologies() - HasMany TicketChronology
│   └── attachments() - HasMany Document
```

## 🔄 Flow Kerja

### 1. Load Halaman
```
User → GET /tickets
     → TicketController@index()
     → View: tickets/index.blade.php
     → Load JS: list.js, config.js
```

### 2. Initialize DataTable (My Tickets)
```javascript
// list.js
dt_my_tickets = $('.datatables-my-tickets').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/tickets/data',
        data: { 
            type: 'my',
            ticket_filter: $('#ticketFilterSelect').val() 
        }
    }
});
```

### 3. Server-Side Data Processing
```
AJAX Request → GET /tickets/data?type=my
             → TicketController@data()
             → Query tickets dengan filter
             → Format data untuk DataTables
             → Return JSON response
```

### 4. Render DataTable
```
JSON Response → DataTable processing
             → Render rows dengan badges, buttons
             → Apply Malay translations
             → Show action buttons (Lihat)
```

## 📊 Columns Configuration

### Tiket Saya
| Column | Field | Searchable | Sortable | Render |
|--------|-------|------------|----------|--------|
| Bil | DT_RowIndex | ✗ | ✗ | Auto number |
| No. Log Tiket | ticket_nolog | ✓ | ✓ | Plain text |
| Tarikh Aduan | created_at | ✓ | ✓ | d-m-Y format |
| Jenis Tiket | ticket_type | ✗ | ✗ | Badge + tooltip |
| Kategori | cat_name | ✓ | ✓ | Plain text |
| Sub Kategori | subcat_name | ✓ | ✓ | Plain text |
| Status | st_name | ✓ | ✓ | Color badge |
| Pegawai Tugasan | escalate_to | ✗ | ✗ | Officer name |
| Tindakan | - | ✗ | ✗ | View button |

### Tiket Untuk Diurus
| Column | Field | Searchable | Sortable | Render |
|--------|-------|------------|----------|--------|
| Bil | DT_RowIndex | ✗ | ✗ | Auto number |
| No. Log Tiket | ticket_nolog | ✓ | ✓ | Plain text |
| Tarikh Aduan | created_at | ✓ | ✓ | d-m-Y format |
| Nama Pengadu | user.nama | ✓ | ✓ | Plain text |
| Kategori | cat_name | ✓ | ✓ | Plain text |
| Sub Kategori | subcat_name | ✓ | ✓ | Plain text |
| Status | st_name | ✓ | ✓ | Color badge |
| Pegawai Tugasan | escalate_to | ✗ | ✗ | Officer name |
| Tindakan | - | ✗ | ✗ | View button |

## 🎨 Filter Feature

### Filter Dropdown (Tiket Saya sahaja)
```html
<select id="ticketFilterSelect">
    <option value="all">Semua Tiket</option>
    <option value="diri_sendiri">Diri Sendiri</option>
    <option value="bagi_pihak">Bagi Pihak</option>
</select>
```

### Filter Logic (Backend)
```php
// TicketSearchController@getMyTicketsData

switch ($ticketFilter) {
    case 'diri_sendiri':
        // Tiket yang saya buat untuk diri sendiri
        $query->where('user_id', $user->id)
              ->where('created_by', $user->id)
              ->where('ticket_type', 1);
        break;
        
    case 'bagi_pihak':
        // 1. Tiket saya buat untuk orang lain (Wakil)
        // 2. Tiket orang buat untuk saya (Pengadu)
        $query->where('ticket_type', 2)
              ->where(function($q) use ($user) {
                  $q->where('created_by', $user->id)
                    ->orWhere('user_id', $user->id);
              });
        break;
        
    default: // 'all'
        // Semua tiket berkaitan dengan saya
        $query->where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('created_by', $user->id);
        });
}
```

## 🔐 Permission & Access Control

### Role-Based Table Display
```php
// index.blade.php

// Semua user boleh lihat "Tiket Saya"
<table class="datatables-my-tickets">

// Hanya role tertentu boleh lihat "Tiket Untuk Diurus"
@if (auth()->user()->hasAnyRole([
    'Administrator', 
    'Pentadbir Sistem', 
    'Penyelia Helpdesk', 
    'Helpdesk On Duty',
    'Pegawai Tugasan'
]))
    <table class="datatables-manage-tickets">
@endif
```

### Data Access Rules
1. **Pengadu/Wakil** - Lihat tiket sendiri sahaja
2. **Pegawai Tugasan** - Lihat tiket yang assigned kepada mereka
3. **Helpdesk/Penyelia** - Lihat semua tiket dalam scope mereka
4. **Administrator** - Lihat semua tiket dalam sistem

## 🎯 Action Buttons

### View Button
```javascript
// list.js
$(document).on('click', '.view-record', function() {
    var ticketId = $(this).data('id');
    window.location.href = '/tickets/' + ticketId;
});
```

### Add New Ticket Button
```javascript
{
    text: 'ADUAN BARU',
    className: 'add-new btn btn-primary',
    attr: {
        'data-bs-toggle': 'offcanvas',
        'data-bs-target': '#offcanvasAddTicket'
    }
}
```

## 📱 Responsive Design

### Mobile View
- Table menggunakan DataTables responsive extension
- Columns collapse automatically pada skrin kecil
- Buttons stack vertically
- Search and filter di-optimize untuk touch

### Desktop View
- Full table dengan semua columns visible
- Horizontal scrolling jika perlu
- Filter dropdown inline dengan search

## 🔄 Real-time Updates

### Reload DataTable
```javascript
// config.js - Global reload function
window.reloadAllTables = function() {
    if (window.dt_my_tickets) {
        window.dt_my_tickets.ajax.reload(null, false);
    }
    if (window.dt_manage_tickets) {
        window.dt_manage_tickets.ajax.reload(null, false);
    }
};

// Dipanggil selepas:
// - Create new ticket
// - Update ticket status
// - Delete ticket
```

## 🌐 Translations (Bahasa Melayu)

```javascript
language: {
    search: '',
    searchPlaceholder: 'Carian Tiket Saya',
    emptyTable: 'Tiada data tersedia dalam jadual',
    info: 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ entri',
    infoEmpty: 'Menunjukkan 0 hingga 0 daripada 0 entri',
    infoFiltered: '(ditapis daripada _MAX_ jumlah entri)',
    lengthMenu: 'Tunjuk _MENU_ entri',
    loadingRecords: 'Memuatkan...',
    processing: 'Memproses...',
    zeroRecords: 'Tiada rekod yang sepadan dijumpai'
}
```

## 🚀 Performance Optimization

1. **Server-Side Processing**
   - Hanya load data yang diperlukan (pagination)
   - Database query optimization dengan indexes
   - Lazy loading untuk relationships

2. **Caching**
   - Static assets cached di browser
   - DataTables state saved di localStorage

3. **Lazy Loading**
   - JS files loaded on demand
   - Images loaded progressively

## 🐛 Common Issues & Solutions

### Issue 1: DataTable tidak initialize
**Solution**: Ensure jQuery dan DataTables library loaded sebelum list.js

### Issue 2: Server-side processing lambat
**Solution**: Add database indexes pada columns yang selalu di-search/sort

### Issue 3: Filter tidak working
**Solution**: Check AJAX data parameter dan backend filter logic

### Issue 4: Buttons tidak kelihatan
**Solution**: Import DataTables buttons extension dan CSS

## 📝 Notes
- DataTable menggunakan Yajra DataTables package untuk Laravel
- Server-side processing mandatory untuk large datasets (>1000 records)
- Filter state persisted menggunakan localStorage
- Export functions require DataTables buttons extension
