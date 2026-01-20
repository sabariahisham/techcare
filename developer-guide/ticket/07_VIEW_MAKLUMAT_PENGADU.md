# Dokumentasi: View Maklumat Pengadu Tiket

## 📋 Ringkasan
Feature ini memaparkan maklumat lengkap pengadu (complainant) tiket termasuk maklumat peribadi, organisasi, dan jenis aduan (Diri Sendiri atau Bagi Pihak). Data ini auto-populated dari database SPP (Sistem Pergerakan Pegawai) dan ditampilkan secara read-only.

## 🎯 Fungsi Utama
- Display maklumat peribadi pengadu (nama, jawatan, gred, skim)
- Display maklumat organisasi (sektor, bahagian, seksyen, unit)
- Display maklumat kontak (email, telefon)
- Show ticket type (Diri Sendiri atau Bagi Pihak)
- Display wakil information jika Bagi Pihak
- Avatar dengan initials
- Responsive card layout
- Print-friendly format

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketController.php
├── show($id)
│   ├── Load ticket dengan user relationship
│   ├── Load ticket.user (pengadu)
│   ├── Load ticket.createdBy (wakil, jika Bagi Pihak)
│   └── Return view dengan ticket data
```

### Frontend (Views)
```
resources/views/tickets/show.blade.php (Line ~115-210)
├── Maklumat Pengadu Card
│   ├── Card header dengan icon dan title
│   │   ├── "Maklumat Pengadu" (jika Diri Sendiri)
│   │   └── "Maklumat Pengadu (Bagi Pihak)" (jika Bagi Pihak)
│   │
│   ├── Card body - Three column layout
│   │   ├── Left Column: Maklumat Peribadi
│   │   │   ├── Avatar dengan initials
│   │   │   ├── Nama
│   │   │   ├── Kod Jawatan
│   │   │   └── Email
│   │   │
│   │   ├── Vertical divider (hidden on mobile)
│   │   │
│   │   └── Right Column: Maklumat Organisasi
│   │       ├── Sektor (with icon)
│   │       ├── Bahagian (with icon)
│   │       ├── Seksyen (with icon)
│   │       └── Unit (with icon)
│   │
│   └── Wakil information banner (if Bagi Pihak)
│       ├── Display wakil name
│       ├── Display wakil email
│       └── Badge "BAGI PIHAK"
```

### Models
```
app/Models/Ticket.php
├── user_id → user relationship (Pengadu)
├── created_by → createdBy relationship (Wakil)
└── ticket_type (1 = Diri Sendiri, 2 = Bagi Pihak)

app/Models/User.php
├── Personal Info:
│   ├── nama
│   ├── email
│   ├── kod_jwtn (jawatan code)
│   ├── jawatan_name
│   ├── kod_skim
│   ├── nama_gred
│   ├── tel_no_p (telefon pejabat)
│   └── tel_no_hp (telefon bimbit)
│
├── Organization Info:
│   ├── kod_sektor → sektor relationship
│   ├── kod_bah → bahagian relationship
│   ├── kod_sec → seksyen relationship
│   └── kod_unit → unit relationship
│
└── Relationships:
    ├── skim() - BelongsTo RefSkim
    ├── sektor() - BelongsTo RefSektor
    ├── bahagian() - BelongsTo RefBahagian
    ├── seksyen() - BelongsTo RefSeksyen
    └── unit() - BelongsTo RefUnit
```

### Routes
```
routes/web.php
├── GET /tickets/{ticket}   # show() - Display ticket detail dengan pengadu info
└── Middleware: auth, verified
```

## 🔄 Flow Kerja

### 1. Load Ticket with User Data
```php
// TicketController@show

public function show($id)
{
    $ticket = Ticket::with([
        'user',                    // Pengadu
        'user.skim',
        'user.sektor',
        'user.bahagian',
        'user.seksyen',
        'user.unit',
        'createdBy',               // Wakil (if Bagi Pihak)
        'createdBy.skim',
        'createdBy.sektor',
        'createdBy.bahagian',
        // ... other relationships
    ])->findOrFail($id);
    
    return view('tickets.show', compact('ticket'));
}
```

### 2. Display Card Header
```blade
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bx bx-user me-2"></i>
            @if ($ticket->ticket_type == 1)
                Maklumat Pengadu
            @else
                Maklumat Pengadu (Bagi Pihak)
            @endif
        </h5>
    </div>
    <div class="card-body">
        {{-- Content --}}
    </div>
</div>
```

## 📝 Display Fields Details

### A. Maklumat Peribadi (Left Column)
```blade
<div class="col-12 col-md-4 col-lg-4 mb-3 mb-md-0">
    {{-- Avatar + Basic Info --}}
    <div class="d-flex align-items-center mb-3">
        <div class="avatar avatar-lg me-3">
            <span class="avatar-initial rounded-circle bg-label-primary">
                {{ strtoupper(substr($ticket->user->nama ?? 'U', 0, 2)) }}
            </span>
        </div>
        <div>
            <h6 class="mb-0">{{ $ticket->user->nama ?? 'Tidak Diketahui' }}</h6>
            <h6 class="mb-0">{{ $ticket->user->kod_jwtn ?? '-' }}</h6>
            <small class="text-muted">{{ $ticket->user->email ?? '-' }}</small>
        </div>
    </div>
</div>
```

**Fields Displayed**:
| Field | Source | Format | Fallback |
|-------|--------|--------|----------|
| Avatar | `nama` first 2 chars | Uppercase initials | 'U' |
| Nama | `user.nama` | Full name | 'Tidak Diketahui' |
| Jawatan | `user.kod_jwtn` | Code (e.g., W41) | '-' |
| Email | `user.email` | Email address | '-' |

### B. Maklumat Organisasi (Right Column)
```blade
<div class="col-12 col-md-7 col-lg-7">
    {{-- Row 1: Sektor & Bahagian --}}
    <div class="row mb-3">
        @if ($ticket->user->sektor || $ticket->user->kod_sektor)
            <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                <h6 class="text-primary mb-1">
                    <i class="bx bx-buildings me-1"></i>Sektor
                </h6>
                <p class="mb-0 small">
                    {{ $ticket->user->sektor->nama_sektor ?? 
                       ($ticket->user->kod_sektor ?? '-') }}
                </p>
            </div>
        @endif
        
        @if ($ticket->user->bahagian || $ticket->user->kod_bah)
            <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                <h6 class="text-primary mb-1">
                    <i class="bx bx-sitemap me-1"></i>Bahagian
                </h6>
                <p class="mb-0 small">
                    {{ $ticket->user->bahagian->nama_bah ?? 
                       ($ticket->user->kod_bah ?? '-') }}
                </p>
            </div>
        @endif
    </div>
    
    {{-- Row 2: Seksyen & Unit --}}
    <div class="row">
        @if ($ticket->user->seksyen || $ticket->user->kod_sec)
            <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                <h6 class="text-primary mb-1">
                    <i class="bx bx-git-branch me-1"></i>Seksyen
                </h6>
                <p class="mb-0 small">
                    {{ $ticket->user->seksyen->nama_sec ?? 
                       ($ticket->user->kod_sec ?? '-') }}
                </p>
            </div>
        @endif
        
        @if ($ticket->user->unit || $ticket->user->kod_unit)
            <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                <h6 class="text-primary mb-1">
                    <i class="bx bx-group me-1"></i>Unit
                </h6>
                <p class="mb-0 small">
                    {{ $ticket->user->unit->nama_unit ?? 
                       ($ticket->user->kod_unit ?? '-') }}
                </p>
            </div>
        @endif
    </div>
</div>
```

**Organization Icons**:
| Field | Icon | Color |
|-------|------|-------|
| Sektor | bx-buildings | text-primary |
| Bahagian | bx-sitemap | text-primary |
| Seksyen | bx-git-branch | text-primary |
| Unit | bx-group | text-primary |

### C. Additional Fields (Not Displayed in Main View)

These fields available but typically shown in offcanvas/modal:
```
- Skim Perkhidmatan: user.skim.nama_skim + kod_skim
- Gred: user.nama_gred
- Telefon Pejabat: user.tel_no_p
- Telefon Bimbit: user.tel_no_hp
```

## 🎯 Ticket Type Handling

### 1. Diri Sendiri (ticket_type = 1)
```blade
{{-- Pengadu = Wakil (same person) --}}
@if ($ticket->ticket_type == 1)
    <div class="alert alert-info mt-3">
        <i class="bx bx-info-circle me-2"></i>
        <strong>Jenis Aduan:</strong> Diri Sendiri
        <br>
        <small>Aduan ini dibuat oleh pengadu untuk dirinya sendiri.</small>
    </div>
@endif
```

**Logic**:
- `user_id == created_by`
- Pengadu dan Wakil adalah orang sama
- Single person display

### 2. Bagi Pihak (ticket_type = 2)
```blade
{{-- Pengadu ≠ Wakil (different persons) --}}
@if ($ticket->ticket_type == 2)
    <div class="alert alert-warning mt-3">
        <span class="badge bg-warning text-dark me-2">BAGI PIHAK</span>
        <br>
        <strong>Pengadu:</strong> {{ $ticket->user->nama }}
        <br>
        <strong>Dilog oleh (Wakil):</strong> {{ $ticket->createdBy->nama }}
        <br>
        <small class="text-muted">{{ $ticket->createdBy->email }}</small>
    </div>
@endif
```

**Logic**:
- `user_id != created_by`
- Pengadu = Yang buat complaint
- Wakil = Yang log complaint (on behalf)
- Display both persons

## 🎨 Layout & Styling

### Responsive Grid
```blade
<div class="row">
    {{-- Left: Personal Info (4 cols) --}}
    <div class="col-12 col-md-4 col-lg-4 mb-3 mb-md-0">
        {{-- Avatar + Name + Position + Email --}}
    </div>
    
    {{-- Divider (1 col, hidden on mobile) --}}
    <div class="col-1 d-none d-md-flex justify-content-center">
        <div class="vr h-100"></div>
    </div>
    
    {{-- Right: Organization Info (7 cols) --}}
    <div class="col-12 col-md-7 col-lg-7">
        {{-- Sektor, Bahagian, Seksyen, Unit --}}
    </div>
</div>
```

### CSS Classes
```css
/* Avatar */
.avatar-lg {
    width: 3.5rem;
    height: 3.5rem;
}

.avatar-initial {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.125rem;
}

.bg-label-primary {
    background-color: rgba(105, 108, 255, 0.16) !important;
    color: #696cff !important;
}

/* Vertical divider */
.vr {
    width: 1px;
    background-color: rgba(0, 0, 0, 0.1);
    opacity: 1;
}

/* Organization info */
.text-primary {
    color: #696cff !important;
}

.mb-0 {
    margin-bottom: 0 !important;
}

.small, small {
    font-size: 0.875rem;
}
```

## 📱 Responsive Behavior

### Desktop (≥768px)
```
+------------------+---|------------------------+
| Avatar           |   | Sektor     | Bahagian  |
| Name             | | | Seksyen    | Unit      |
| Position         |   |                        |
| Email            |   |                        |
+------------------+---|------------------------+
```

### Mobile (<768px)
```
+--------------------------------------------+
| Avatar                                     |
| Name                                       |
| Position                                   |
| Email                                      |
+--------------------------------------------+
| Sektor                                     |
| Bahagian                                   |
| Seksyen                                    |
| Unit                                       |
+--------------------------------------------+
```

### Breakpoints
```css
/* Small devices (landscape phones, 576px and up) */
@media (min-width: 576px) {
    .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
}

/* Medium devices (tablets, 768px and up) */
@media (min-width: 768px) {
    .col-md-4 { flex: 0 0 33.333333%; }
    .col-md-7 { flex: 0 0 58.333333%; }
    .d-none.d-md-flex { display: flex !important; }
}
```

## 🔐 Data Source & Integration

### SPP Database Integration
Data pengadu di-sync dari SPP (Sistem Pergerakan Pegawai):

**Sync Fields**:
```php
// User model attributes from SPP
protected $fillable = [
    // Personal
    'nama', 'email', 'no_kp',
    
    // Position
    'kod_jwtn', 'jawatan_name', 
    'kod_skim', 'nama_gred',
    
    // Organization
    'kod_sektor', 'kod_bah', 'kod_sec', 'kod_unit',
    
    // Contact
    'tel_no_p', 'tel_no_hp',
    
    // ... other fields
];
```

**Update Notice Display**:
```blade
<div class="alert alert-secondary">
    <i class="bx bx-info-circle me-1"></i>
    <strong>Makluman:</strong><br>
    Data dalam ruangan ini diselaraskan daripada 
    <strong>Sistem Pergerakan Pegawai (SPP)</strong>.
    Sebarang kemas kini hendaklah dibuat melalui sistem tersebut.
</div>
```

## 🔍 Fallback Handling

### Missing Data Handling
```blade
{{-- Name fallback --}}
{{ $ticket->user->nama ?? 'Tidak Diketahui' }}

{{-- Organization fallback (try name, then code, then dash) --}}
{{ $ticket->user->sektor->nama_sektor ?? 
   ($ticket->user->kod_sektor ?? '-') }}

{{-- Email fallback --}}
{{ $ticket->user->email ?? 'email@tidak.tersedia' }}
```

### Conditional Display
```blade
{{-- Only show if data exists --}}
@if ($ticket->user->sektor || $ticket->user->kod_sektor)
    <div class="col-12 col-sm-6">
        {{-- Sektor info --}}
    </div>
@endif
```

## 🖨️ Print-Friendly Format

### Print CSS
```css
@media print {
    .card {
        border: 1px solid #000;
        box-shadow: none;
        page-break-inside: avoid;
    }
    
    .card-header {
        background: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Hide vertical divider on print */
    .vr {
        display: none;
    }
    
    /* Full width columns on print */
    .col-md-4, .col-md-7 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 1rem;
    }
    
    /* Avatar print styling */
    .avatar {
        border: 1px solid #000;
    }
}
```

## 🐛 Common Issues & Solutions

### Issue 1: Avatar tidak show initials
**Solution**: Check `nama` field tidak null dan ada minimum 1 character

### Issue 2: Organization data tidak display
**Solution**: Ensure relationships loaded dalam controller: `->with(['user.sektor', ...])`

### Issue 3: "-" show semua fields
**Solution**: Check SPP sync berjalan dan user data updated

### Issue 4: Vertical divider tidak show
**Solution**: Check Bootstrap vr class dan `d-none d-md-flex` classes

### Issue 5: Bagi Pihak banner tidak show
**Solution**: Check `ticket_type == 2` dan `created_by != user_id`

## 📊 Data Privacy & Security

### Display Rules
1. **Show only to authorized users**:
   - Pengadu dapat lihat info sendiri
   - Wakil dapat lihat pengadu + diri sendiri
   - Officers dapat lihat assigned tickets
   - Admin dapat lihat semua

2. **Sensitive Data**:
   - IC Number: Hidden dalam view (ada dalam database)
   - Telefon: Shown tapi tidak editable
   - Email: Shown untuk communication purposes

3. **Audit Trail**:
   - Log setiap view access (optional)
   - Track who viewed ticket details
   - Compliance dengan PDPA

## 🎯 Best Practices

1. **Always check data exists**: Use `?? operator atau @if` checks
2. **Use fallback values**: Display '-' atau default text jika null
3. **Responsive design**: Test on mobile, tablet, desktop
4. **Print consideration**: Ensure readable when printed
5. **SPP sync notice**: Always remind users data from SPP
6. **Avatar accessibility**: Use proper alt text dan aria labels
7. **Icon consistency**: Same icons untuk same types of data
8. **Color coding**: Use consistent colors untuk organization hierarchy

## 📝 Notes
- All user data read-only (tidak boleh edit dalam ticket system)
- Data synced from SPP automatically (nightly atau real-time)
- Bagi Pihak shows both pengadu dan wakil information
- Organization hierarchy: Sektor > Bahagian > Seksyen > Unit
- Avatar initials auto-generated dari nama (first 2 chars)
- Vertical divider hidden pada mobile untuk save space
- Card positioned at top of show.blade.php (above other cards)
- Permission check not needed kerana ticket authorization sudah check
- Fallback values consistent ('-' untuk organization, 'Tidak Diketahui' untuk nama)
