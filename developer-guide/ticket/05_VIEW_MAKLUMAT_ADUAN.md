# Dokumentasi: View Maklumat Aduan Tiket

## 📋 Ringkasan
Feature ini memaparkan maklumat lengkap aduan tiket termasuk tajuk, kategori, sub kategori, keterangan, no. siri (jika Sewaan), dan lampiran fail. Section ini adalah bahagian utama yang menunjukkan detail complaint dari pengadu.

## 🎯 Fungsi Utama
- Display ticket details dalam format card
- Show category dan subcategory information
- Display serial number (conditional - untuk Sewaan sahaja)
- Show formatted ticket description
- List all attachments dengan preview icons
- View dan download attachment buttons
- Comment button untuk pengadu
- Action comment button untuk pegawai
- Print-friendly layout

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketController.php
├── show($id)
│   ├── Load ticket dengan relationships
│   ├── Check user authorization
│   ├── Load all statuses untuk dropdown
│   └── Return view dengan ticket data
```

### Frontend (Views)
```
resources/views/tickets/show.blade.php (Line ~210-350)
├── Maklumat Aduan Tiket Card
│   ├── Card header dengan ticket number badge
│   │
│   ├── Tajuk Aduan row
│   │   └── ticket_title
│   │
│   ├── Kategori row
│   │   └── category->cat_name
│   │
│   ├── Sub Kategori row
│   │   └── subCategory->subcat_name
│   │
│   ├── No. Siri row (conditional)
│   │   └── serial_number (only if category = "Sewaan")
│   │
│   ├── Keterangan Aduan row
│   │   └── ticket_desc (formatted with nl2br)
│   │
│   ├── Lampiran row
│   │   ├── Loop attachments
│   │   ├── File icon based on extension
│   │   ├── File name & upload date
│   │   ├── View button (new tab)
│   │   └── Download button
│   │
│   └── Action buttons row
│       ├── Komen button (for pengadu only)
│       └── Catatan Tindakan button (for officers)
```

### Models
```
app/Models/Ticket.php
├── Displayed fields:
│   ├── ticket_nolog (shown in badge)
│   ├── ticket_title
│   ├── cat_id → category relationship
│   ├── subcat_id → subCategory relationship
│   ├── serial_number (conditional)
│   ├── ticket_desc
│   └── doc_id → attachments relationship
│
└── Relationships:
    ├── category() - BelongsTo RefCategory
    ├── subCategory() - BelongsTo RefSubCategory
    └── attachments() - HasMany Document
```

### Routes
```
routes/web.php
├── GET /tickets/{ticket}   # show() - Display ticket detail page
└── Middleware: auth, verified
```

## 🔄 Flow Kerja

### 1. Load Ticket Detail Page
```
User clicks "Lihat" button dari list
→ GET /tickets/{ticket_id}
→ TicketController@show($id)
→ Load ticket dengan eager loading:
    - category
    - subCategory
    - user (pengadu)
    - createdBy (wakil)
    - status
    - chronologies
    - attachments
→ Check authorization
→ Return view: tickets.show
```

### 2. Controller Logic
```php
// TicketController@show

public function show($id)
{
    $ticket = Ticket::with([
        'user',
        'createdBy',
        'category',
        'subCategory',
        'status',
        'chronologies.status',
        'chronologies.createdBy',
        'chronologies.escalatedTo',
        'attachments'
    ])->findOrFail($id);
    
    // Check if user can view this ticket
    $this->authorize('view', $ticket);
    
    // Get all statuses for dropdown (officer section)
    $allStatuses = RefTicketStatus::all();
    
    return view('tickets.show', compact('ticket', 'allStatuses'));
}
```

### 3. Authorization Check
```php
// TicketPolicy@view

public function view(User $user, Ticket $ticket)
{
    // Can view if:
    // 1. User is pengadu (user_id)
    // 2. User is wakil who created ticket (created_by)
    // 3. User is assigned officer
    // 4. User is admin/helpdesk
    
    if ($ticket->user_id === $user->id) {
        return true;
    }
    
    if ($ticket->created_by === $user->id) {
        return true;
    }
    
    if ($user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Helpdesk On Duty', 'Penyelia Helpdesk'])) {
        return true;
    }
    
    // Check if user is assigned officer
    $isAssignedOfficer = $ticket->chronologies()
        ->where('escalate_to', $user->id)
        ->exists();
    
    if ($isAssignedOfficer) {
        return true;
    }
    
    return false;
}
```

## 📝 Display Fields Details

### A. Tajuk Aduan
```blade
<div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Tajuk Aduan</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        {{ $ticket->ticket_title }}
    </div>
</div>
<hr>
```
- **Field**: `ticket_title`
- **Type**: String (max 255 chars)
- **Format**: Plain text
- **Required**: Yes

### B. Kategori
```blade
<div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Kategori</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        {{ $ticket->category->cat_name ?? '-' }}
    </div>
</div>
<hr>
```
- **Field**: `cat_id` → `category.cat_name`
- **Relationship**: BelongsTo RefCategory
- **Format**: Category name (e.g., "Perisian", "Perkakasan", "Sewaan")
- **Required**: Yes

### C. Sub Kategori
```blade
<div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Sub Kategori</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        {{ $ticket->subCategory->subcat_name ?? '-' }}
    </div>
</div>
<hr>
```
- **Field**: `subcat_id` → `subCategory.subcat_name`
- **Relationship**: BelongsTo RefSubCategory
- **Format**: Subcategory name
- **Required**: Yes

### D. No. Siri (Conditional)
```blade
@if ($ticket->category && $ticket->category->cat_name === 'Sewaan')
    <hr>
    <div class="row">
        <div class="col-sm-3">
            <h6 class="mb-0">No. Siri <br><small>(Sewaan)</small></h6>
        </div>
        <div class="col-sm-9 text-secondary">
            {{ $ticket->serial_number ?? '-' }}
        </div>
    </div>
@endif
```
- **Field**: `serial_number`
- **Type**: String (max 100 chars)
- **Visibility**: Hanya jika `category.cat_name === 'Sewaan'`
- **Format**: Serial number (e.g., "SN-2024-001234")
- **Required**: Yes (jika Sewaan), No (otherwise)

### E. Keterangan Aduan
```blade
<div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Keterangan Aduan</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        {!! nl2br(e(ucfirst($ticket->ticket_desc))) !!}
    </div>
</div>
<hr>
```
- **Field**: `ticket_desc`
- **Type**: Text (unlimited)
- **Format**: 
  - `e()` - Escape HTML untuk security
  - `ucfirst()` - Capitalize first letter
  - `nl2br()` - Convert line breaks to `<br>` tags
- **Required**: Yes

**Example Output**:
```
Input: "Komputer tidak boleh connect ke printer.\nCuba restart tapi masih sama."

Display:
Komputer tidak boleh connect ke printer.
Cuba restart tapi masih sama.
```

### F. Lampiran
```blade
<div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Lampiran</h6>
    </div>
    <div class="col-sm-9">
        @if ($ticket->attachments && $ticket->attachments->count() > 0)
            <div class="attachment-list">
                @foreach ($ticket->attachments as $attachment)
                    <div class="attachment-item mb-2">
                        <div class="d-flex align-items-center">
                            {{-- File Icon --}}
                            @php
                                $extension = strtolower(pathinfo($attachment->doc_name, PATHINFO_EXTENSION));
                                $iconClass = 'bx-file';
                                $iconColor = 'text-muted';
                                
                                if ($extension === 'pdf') {
                                    $iconClass = 'bxs-file-pdf';
                                    $iconColor = 'text-danger';
                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                    $iconClass = 'bxs-file-doc';
                                    $iconColor = 'text-primary';
                                } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                                    $iconClass = 'bxs-file-image';
                                    $iconColor = 'text-success';
                                }
                            @endphp
                            
                            <i class="bx {{ $iconClass }} {{ $iconColor }} me-2 fs-5"></i>
                            
                            {{-- File Info --}}
                            <div class="flex-grow-1">
                                <span class="fw-medium">{{ $attachment->doc_name }}</span>
                                <small class="text-muted d-block">
                                    Dimuat naik: {{ $attachment->created_at->format('d M Y, H:i') }}
                                </small>
                            </div>
                            
                            {{-- View Button --}}
                            <a href="{{ route('ticket-attachments.download', ['document' => $attachment->id, 'view' => 1]) }}"
                               class="btn btn-sm btn-outline-info me-1"
                               target="_blank"
                               data-bs-toggle="tooltip"
                               title="Lihat {{ $attachment->doc_name }}">
                                <i class="bx bx-show"></i>
                            </a>
                            
                            {{-- Download Button --}}
                            <a href="{{ route('ticket-attachments.download', $attachment->id) }}"
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="Muat turun {{ $attachment->doc_name }}">
                                <i class="bx bx-download"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mb-0 text-muted">Tiada lampiran tersedia.</p>
        @endif
    </div>
</div>
```

**File Icon Mapping**:
| Extension | Icon Class | Color |
|-----------|-----------|-------|
| .pdf | bxs-file-pdf | text-danger (red) |
| .doc, .docx | bxs-file-doc | text-primary (blue) |
| .jpg, .jpeg, .png | bxs-file-image | text-success (green) |
| Others | bx-file | text-muted (gray) |

## 🎯 Action Buttons

### A. Komen Button (Pengadu Only)
```blade
@if ($ticket->user_id == auth()->user()->id)
    <button type="button" class="btn btn-warning btn-sm"
            onclick="$('#commentModal').modal('show')">
        <i class="bx bx-message me-1"></i>Komen
    </button>
@else
    <button type="button" class="btn btn-secondary btn-sm" disabled>
        <i class="bx bx-message me-1"></i>Komen
    </button>
@endif
```

**Logic**:
- Enabled: Jika user adalah pengadu (`ticket.user_id == auth()->user()->id`)
- Disabled: Jika user bukan pengadu
- Action: Opens comment modal untuk pengadu add feedback/follow-up

### B. Catatan Tindakan Button (Officers)
```blade
<button type="button" class="btn btn-primary btn-sm float-end"
        onclick="$('#actionComment').modal('show')">
    <i class="bx bx-save me-1"></i>Catatan Tindakan
</button>
```

**Logic**:
- Always visible untuk semua yang boleh view ticket
- Opens action comment modal untuk pegawai add internal notes
- Notes will be attached to chronology

## 🎨 Card Layout

### HTML Structure
```blade
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bx bx-file-blank me-2"></i>Maklumat Aduan Tiket
            <span class="badge bg-primary fs-6 float-end">
                {{ $ticket->ticket_nolog }}
            </span>
        </h5>
    </div>
    <div class="card-body">
        {{-- All rows here --}}
        
        {{-- Action buttons at bottom --}}
        <hr>
        <div class="row mt-6 mb-1">
            <div class="col-sm">
                {{-- Komen button --}}
                {{-- Catatan Tindakan button --}}
            </div>
        </div>
    </div>
</div>
```

### CSS Styling
```css
/* Card header */
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-header .badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

/* Row spacing */
.card-body .row {
    margin-bottom: 1rem;
}

.card-body hr {
    margin: 1.5rem 0;
}

/* Label column */
.card-body .col-sm-3 h6 {
    font-weight: 600;
    color: #6c757d;
}

/* Value column */
.card-body .col-sm-9 {
    word-wrap: break-word;
}
```

## 📱 Responsive Design

### Desktop View (col-sm-3 / col-sm-9)
```
+------------------+---------------------------------------+
| Tajuk Aduan      | Sistem tidak boleh dibuka            |
+------------------+---------------------------------------+
| Kategori         | Perisian                              |
+------------------+---------------------------------------+
```

### Mobile View (Stacked)
```
+-----------------------------------------------------+
| Tajuk Aduan                                         |
+-----------------------------------------------------+
| Sistem tidak boleh dibuka                           |
+-----------------------------------------------------+
| Kategori                                            |
+-----------------------------------------------------+
| Perisian                                            |
+-----------------------------------------------------+
```

### Media Queries
```css
@media (max-width: 576px) {
    .card-body .col-sm-3,
    .card-body .col-sm-9 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .card-body .col-sm-3 h6 {
        margin-bottom: 0.5rem;
    }
    
    .card-header .badge {
        display: block;
        margin-top: 0.5rem;
        float: none !important;
    }
}
```

## 🖨️ Print-Friendly Layout

### Print CSS
```css
@media print {
    /* Hide unnecessary elements */
    .btn, .navbar, .sidebar {
        display: none !important;
    }
    
    /* Full width card */
    .card {
        border: 1px solid #000;
        page-break-inside: avoid;
    }
    
    /* Black text for better printing */
    .text-muted, .text-secondary {
        color: #000 !important;
    }
    
    /* Remove card shadow */
    .card {
        box-shadow: none;
    }
}
```

### Print Button
```blade
<button type="button" class="btn btn-primary" onclick="window.print()">
    <i class="bx bx-printer me-3"></i>Cetak
</button>
```

## 🔐 Data Security

### HTML Escaping
```blade
{{-- Safe: Auto-escaped --}}
{{ $ticket->ticket_title }}

{{-- Dangerous: No escaping --}}
{!! $ticket->ticket_desc !!}  ❌ DON'T DO THIS

{{-- Safe: Escaped + nl2br --}}
{!! nl2br(e($ticket->ticket_desc)) !!}  ✓ CORRECT
```

### XSS Prevention
```php
// Always escape user input before display
$title = e($ticket->ticket_title);
$desc = e($ticket->ticket_desc);

// Then apply formatting
$formattedDesc = nl2br($desc);
```

## 🐛 Common Issues & Solutions

### Issue 1: Line breaks tidak show dalam description
**Solution**: Use `nl2br(e($ticket->ticket_desc))` instead of just `{{ $ticket->ticket_desc }}`

### Issue 2: Serial number show untuk semua kategori
**Solution**: Check conditional `@if ($ticket->category->cat_name === 'Sewaan')`

### Issue 3: Attachment icon tidak betul
**Solution**: Check file extension extraction: `pathinfo($attachment->doc_name, PATHINFO_EXTENSION)`

### Issue 4: Download button 404 error
**Solution**: Ensure `storage:link` executed dan file exists dalam storage

### Issue 5: Comment button enabled untuk bukan pengadu
**Solution**: Check condition `@if ($ticket->user_id == auth()->user()->id)`

## 🎯 Best Practices

1. **Always escape user input**: Use `e()` or `{{ }}` untuk prevent XSS
2. **Check relationships exist**: Use `??` operator atau `@if` sebelum access
3. **Format dates consistently**: Use `format('d M Y, H:i')` untuk consistency
4. **Use tooltips**: Add tooltips pada buttons untuk better UX
5. **Responsive layout**: Use Bootstrap grid system properly
6. **Print-friendly**: Consider print stylesheet untuk documents
7. **Error handling**: Show fallback text jika data tidak wujud (e.g., "Tiada lampiran tersedia")

## 📝 Notes
- Maklumat Aduan adalah read-only display (no editing in this section)
- Serial number field conditional based on category name exact match "Sewaan"
- Attachment buttons open in new tab untuk preview
- Description formatted dengan nl2br untuk preserve line breaks
- Card positioned dalam left column (col-xl-7) dalam show.blade.php
- Komen button hanya untuk pengadu (user_id matches)
- Catatan Tindakan button untuk officers dan admin
- All text properly escaped untuk security
