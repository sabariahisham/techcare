# Dokumentasi: View Kronologi Tiket

## 📋 Ringkasan
Feature ini memaparkan timeline/history perubahan status tiket secara kronologi. Setiap perubahan status dicatat sebagai chronology entry dengan maklumat lengkap seperti status, pegawai, tarikh, dan catatan.

## 🎯 Fungsi Utama
- Paparan timeline perubahan status tiket
- Visual representation dengan icons dan colors
- Display pegawai yang bertanggungjawab setiap stage
- Catatan/notes untuk setiap perubahan status
- Auto-generate "Aduan Baru" entry jika tidak wujud
- Response time calculation (total days to complete)
- SLA due date tracking

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketChronologyController.php
├── getChronology(Ticket $ticket)
│   ├── Load ticket dengan relationships
│   ├── Build chronology history
│   └── Return JSON response
│
├── addChronologyEntry($ticketId, $statusId, ...)
│   ├── Create TicketChronology record
│   ├── Add comment if notes provided
│   └── Update ticket current status
│
└── getTicketHistory(Ticket $ticket)
    ├── Retrieve all chronologies
    ├── Sort by created_at
    └── Format for display
```

### Frontend (Views)
```
resources/views/tickets/show.blade.php (Line ~610-850)
├── Kronologi Tiket card
│   ├── Card header with icon
│   ├── Timeline container
│   │   ├── Auto-generate "Aduan Baru" if not exists
│   │   ├── Loop through chronologies
│   │   │   ├── Status badge dengan color
│   │   │   ├── Status description
│   │   │   ├── Timestamp
│   │   │   ├── Action performer (pegawai)
│   │   │   ├── Escalated to (if applicable)
│   │   │   └── Notes/comments
│   │   └── Timeline connector lines
│   └── Empty state (if no chronology)
```

### Models
```
app/Models/TicketChronology.php
├── Table: ticket_chronologies
├── Fields:
│   ├── id, uuid
│   ├── ticket_id (FK to tickets)
│   ├── tick_status (FK to ref_ticket_statuses)
│   ├── frm_user_id (user who made change)
│   ├── escalate_to (FK to users - assigned officer)
│   ├── level_pegtugas_id (officer level)
│   ├── sla_duedate
│   ├── created_by, updated_by
│   └── timestamps
│
└── Relationships:
    ├── ticket() - BelongsTo Ticket
    ├── status() - BelongsTo RefTicketStatus
    ├── fromUser() - BelongsTo User (frm_user_id)
    ├── escalatedTo() - BelongsTo User (escalate_to)
    └── comments() - HasMany TicketComment (via ticket + timestamp)

app/Models/TicketComment.php
├── Table: ticket_comments
├── Fields:
│   ├── id, ticket_id, user_id
│   ├── comment_text
│   ├── created_by, updated_by
│   └── timestamps
│
└── Used for notes/catatan in chronology
```

### Routes
```
routes/web.php
├── GET /tickets/{ticket}/chronology    # getChronology()
└── Embedded in /tickets/{ticket}       # show() includes chronology
```

## 🔄 Flow Kerja

### 1. Display Chronology on Ticket View

```blade
{{-- show.blade.php --}}

<!-- Kronologi Tiket Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bx bx-time me-2"></i>Kronologi Tiket
        </h5>
    </div>
    <div class="card-body">
        @if ($ticket->status || $ticket->chronologies->count() > 0)
            <div class="timeline">
                {{-- Timeline items --}}
            </div>
        @else
            {{-- Empty state --}}
            <div class="text-center py-5">
                <h6 class="text-muted mb-2">Tiada Kronologi Tersedia</h6>
                <p class="text-muted small mb-0">
                    Kronologi tiket akan dipaparkan di sini apabila status dikemaskini.
                </p>
            </div>
        @endif
    </div>
</div>
```

### 2. Build Chronology Timeline

```php
// show.blade.php - Inline PHP

// Get all chronologies
$allChronologies = $ticket->chronologies()
    ->with(['status', 'createdBy', 'escalatedTo'])
    ->orderBy('created_at', 'asc')
    ->get();

// Check if "Aduan Baru" exists
$hasAduanBaru = $allChronologies->contains(function($chron) {
    return $chron->status && $chron->status->st_name === 'Aduan Baru';
});

// Build timeline items
$timelineItems = collect();

// Add "Aduan Baru" if not exists
if (!$hasAduanBaru) {
    $timelineItems->push([
        'status_name' => 'Aduan Baru',
        'description' => 'Tiket baru dicipta dan dilog ke dalam sistem.',
        'created_at' => $ticket->created_at,
        'created_by' => $ticket->createdBy,
        'escalate_to' => null,
        'notes' => null
    ]);
}

// Add actual chronologies
foreach ($allChronologies as $chronology) {
    $timelineItems->push([
        'status_name' => $chronology->status->st_name,
        'description' => getStatusDescription($chronology->status->st_name),
        'created_at' => $chronology->created_at,
        'created_by' => $chronology->createdBy,
        'escalate_to' => $chronology->escalatedTo,
        'notes' => getNotesForChronology($chronology)
    ]);
}

// Sort by date
$timelineItems = $timelineItems->sortBy('created_at');
```

### 3. Create Chronology Entry (When Status Changes)

```php
// TicketChronologyController@addChronologyEntry

public static function addChronologyEntry(
    $ticketId, 
    $newStatusId, 
    $escalateTo = null, 
    $slaDueDate = null, 
    $levelPegtugasId = null, 
    $notes = null
) {
    // Create chronology record
    $chronology = TicketChronology::create([
        'ticket_id' => $ticketId,
        'frm_user_id' => Auth::id(),
        'level_pegtugas_id' => $levelPegtugasId,
        'tick_status' => $newStatusId,
        'escalate_to' => $escalateTo,
        'sla_duedate' => $slaDueDate,
        'created_by' => Auth::id(),
    ]);
    
    // Add comment if notes provided
    if ($notes) {
        TicketComment::create([
            'ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'comment_text' => $notes,
            'created_by' => Auth::id(),
        ]);
    }
    
    // Update ticket's current status
    $ticket = Ticket::find($ticketId);
    if ($ticket) {
        $ticket->update([
            'tick_status' => $newStatusId,
            'updated_by' => Auth::id()
        ]);
    }
    
    return $chronology;
}
```

## 📊 Status Types & Styling

### Status Mapping
| Status Name | Icon | Color | Description |
|-------------|------|-------|-------------|
| Aduan Baru | bx-plus-circle | bg-primary | Tiket dicipta dan dilog |
| Aduan Diproses | bx-cog | bg-warning | Pegawai sedang proses |
| Serahan Kepada | bx-user-plus | bg-info | Diserahkan kepada pegawai lain |
| Aduan Dirujuk | bx-share-alt | bg-danger | Dirujuk ke unit/pegawai lain |
| Aduan DiBatal | bx-x-circle | bg-secondary | Tiket dibatalkan |
| Aduan Selesai | bx-check-circle | bg-success | Tiket diselesaikan |

### Helper Functions (Blade)
```php
// Function to get status styling
$getStatusStyling = function($statusName) {
    switch ($statusName) {
        case 'Aduan Baru':
            return ['icon' => 'bx-plus-circle', 'color' => 'bg-primary'];
        case 'Aduan Diproses':
            return ['icon' => 'bx-cog', 'color' => 'bg-warning'];
        case 'Serahan Kepada':
            return ['icon' => 'bx-user-plus', 'color' => 'bg-info'];
        case 'Aduan Dirujuk':
            return ['icon' => 'bx-share-alt', 'color' => 'bg-danger'];
        case 'Aduan DiBatal':
            return ['icon' => 'bx-x-circle', 'color' => 'bg-secondary'];
        case 'Aduan Selesai':
            return ['icon' => 'bx-check-circle', 'color' => 'bg-success'];
        default:
            return ['icon' => 'bx-edit', 'color' => 'bg-secondary'];
    }
};

// Function to get status description
$getStatusDescription = function($statusName) {
    switch ($statusName) {
        case 'Aduan Baru':
            return 'Tiket baru dicipta dan dilog ke dalam sistem.';
        case 'Aduan Diproses':
            return 'Tiket sedang diproses oleh pegawai yang bertugas.';
        case 'Serahan Kepada':
            return 'Tiket telah diserahkan kepada pegawai yang berkaitan untuk tindakan.';
        case 'Aduan Dirujuk':
            return 'Tiket dirujuk kepada unit/pegawai lain untuk tindakan lanjut.';
        case 'Aduan DiBatal':
            return 'Tiket telah dibatalkan dan tidak akan diproses lagi.';
        case 'Aduan Selesai':
            return 'Tiket telah diselesaikan dengan jayanya dan ditutup.';
        default:
            return 'Status tiket dikemaskini.';
    }
};

// Function to get action verb
$getActionVerb = function($statusName) {
    switch ($statusName) {
        case 'Aduan Baru':
            return 'Dicipta oleh:';
        case 'Aduan Diproses':
            return 'Diproses oleh:';
        case 'Serahan Kepada':
            return 'Diserahkan oleh:';
        case 'Aduan Dirujuk':
            return 'Dirujuk oleh:';
        case 'Aduan DiBatal':
            return 'Dibatalkan oleh:';
        case 'Aduan Selesai':
            return 'Diselesaikan oleh:';
        default:
            return 'Dikemaskini oleh:';
    }
};
```

## 🎨 Timeline HTML Structure

### Single Timeline Item
```blade
<div class="timeline-item">
    {{-- Icon/Badge --}}
    <div class="timeline-icon {{ $styling['color'] }}">
        <i class="bx {{ $styling['icon'] }}"></i>
    </div>
    
    {{-- Content --}}
    <div class="timeline-content">
        {{-- Status Badge --}}
        <span class="badge {{ $styling['color'] }} mb-2">
            {{ $item['status_name'] }}
        </span>
        
        {{-- Description --}}
        <p class="mb-2 text-muted">
            {{ $item['description'] }}
        </p>
        
        {{-- Timestamp --}}
        <small class="text-muted d-block mb-2">
            <i class="bx bx-time me-1"></i>
            {{ $item['created_at']->format('d M Y, H:i') }}
        </small>
        
        {{-- Action Performer --}}
        @if ($item['created_by'])
            <div class="mt-2">
                <small class="text-muted">
                    {{ $getActionVerb($item['status_name']) }}
                </small>
                <div class="d-flex align-items-center mt-1">
                    <div class="avatar avatar-xs me-2">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($item['created_by']->nama, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $item['created_by']->nama }}</h6>
                        <small class="text-muted">{{ $item['created_by']->jawatan_name }}</small>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Escalated To (if applicable) --}}
        @if ($item['escalate_to'])
            <div class="mt-3 p-3 bg-light rounded">
                <small class="text-muted">Ditugaskan kepada:</small>
                <div class="d-flex align-items-center mt-1">
                    <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded-circle bg-label-info">
                            {{ strtoupper(substr($item['escalate_to']->nama, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $item['escalate_to']->nama }}</h6>
                        <small class="text-muted">{{ $item['escalate_to']->jawatan_name }}</small>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Notes/Comments --}}
        @if ($item['notes'])
            <div class="mt-3">
                <small class="text-muted d-block mb-1">
                    <i class="bx bx-message me-1"></i>Catatan:
                </small>
                <div class="alert alert-secondary mb-0">
                    {!! nl2br(e($item['notes'])) !!}
                </div>
            </div>
        @endif
    </div>
</div>
```

### CSS for Timeline
```css
.timeline {
    position: relative;
    padding-left: 50px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-icon {
    position: absolute;
    left: -38px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 1;
}

.timeline-content {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    border-left: 3px solid #007bff;
}
```

## 🔔 Response Time Calculation

### Calculate Days to Complete
```php
// TicketChronologyController

public function calculateResponseTime(Ticket $ticket)
{
    // Find "Aduan Baru" chronology
    $startChronology = $ticket->chronologies()
        ->whereHas('status', function($q) {
            $q->where('st_name', 'Aduan Baru');
        })
        ->first();
    
    // Find "Aduan Selesai" chronology
    $endChronology = $ticket->chronologies()
        ->whereHas('status', function($q) {
            $q->where('st_name', 'Aduan Selesai');
        })
        ->first();
    
    if ($startChronology && $endChronology) {
        $start = $startChronology->created_at;
        $end = $endChronology->created_at;
        
        $days = $start->diffInDays($end);
        $hours = $start->diffInHours($end) % 24;
        
        return [
            'days' => $days,
            'hours' => $hours,
            'total_hours' => $start->diffInHours($end),
            'formatted' => "{$days} hari, {$hours} jam"
        ];
    }
    
    return null;
}
```

### Display Response Time
```blade
@if ($ticket->tick_status == $statusSelesai->id)
    @php
        $responseTime = app(TicketChronologyController::class)
                           ->calculateResponseTime($ticket);
    @endphp
    
    @if ($responseTime)
        <div class="alert alert-success mt-3">
            <i class="bx bx-check-circle me-2"></i>
            <strong>Masa Tindakan:</strong> {{ $responseTime['formatted'] }}
        </div>
    @endif
@endif
```

## 🎯 Auto-Generate "Aduan Baru"

### Why Auto-Generate?
Untuk ensure setiap tiket ada starting point dalam timeline walaupun chronology record tidak dicipta secara manual.

### Implementation
```php
// show.blade.php

// Check if "Aduan Baru" exists
$hasAduanBaru = $allChronologies->contains(function($chron) {
    return $chron->status && $chron->status->st_name === 'Aduan Baru';
});

// If not exists, create virtual entry using ticket creation data
if (!$hasAduanBaru) {
    $aduanBaruEntry = (object)[
        'status' => (object)['st_name' => 'Aduan Baru'],
        'created_at' => $ticket->created_at,
        'createdBy' => $ticket->createdBy,
        'escalatedTo' => null
    ];
    
    // Prepend to chronologies
    $timelineItems->prepend($aduanBaruEntry);
}
```

## 🔐 Permission & Visibility

### Who Can See Chronology?
- **Pengadu** - Boleh lihat chronology tiket sendiri
- **Wakil** - Boleh lihat chronology tiket yang dibuat
- **Pegawai Tugasan** - Boleh lihat chronology tiket yang assigned
- **Helpdesk/Admin** - Boleh lihat semua chronology

### Notes Visibility
```php
// Some notes might be internal (for officers only)
@if ($comment->is_internal)
    @if (auth()->user()->hasAnyRole(['Administrator', 'Pegawai Tugasan', 'Helpdesk On Duty']))
        {{-- Show internal note --}}
        <div class="alert alert-warning">
            <i class="bx bx-lock me-1"></i>
            <strong>Catatan Dalaman:</strong> {{ $comment->comment_text }}
        </div>
    @endif
@else
    {{-- Show public note --}}
    <div class="alert alert-secondary">
        {{ $comment->comment_text }}
    </div>
@endif
```

## 📱 Responsive Design

### Mobile View
- Timeline connector line adjusted for narrow screens
- Icons smaller (32px instead of 40px)
- Content padding reduced
- Avatar sizes smaller
- Stack elements vertically

### Desktop View
- Full timeline with proper spacing
- Larger icons and avatars
- Side-by-side layout for some elements

## 🚀 Performance Optimization

1. **Eager Loading**
   ```php
   $ticket->load([
       'chronologies.status',
       'chronologies.createdBy',
       'chronologies.escalatedTo'
   ]);
   ```

2. **Caching** (if needed for heavy traffic)
   ```php
   $chronologies = Cache::remember(
       "ticket.{$ticketId}.chronologies",
       3600,
       fn() => $ticket->chronologies()->with(...)->get()
   );
   ```

3. **Pagination** (for tickets with many chronologies)
   ```php
   $chronologies = $ticket->chronologies()
       ->latest()
       ->paginate(10);
   ```

## 🐛 Common Issues & Solutions

### Issue 1: "Aduan Baru" tidak wujud
**Solution**: Auto-generate dari ticket creation data

### Issue 2: Chronology tidak sorted betul
**Solution**: Ensure `orderBy('created_at', 'asc')` dalam query

### Issue 3: Officer name tidak display
**Solution**: Check relationship loaded dengan `with(['escalatedTo'])`

### Issue 4: Notes tidak show
**Solution**: Join dengan TicketComment using ticket_id + timestamp matching

### Issue 5: Timeline line tidak sambung
**Solution**: Check CSS `::before` pseudo-element positioning

## 📝 Notes
- Chronology represents state changes only (not comments)
- Each status change creates new chronology entry
- Comments linked to chronology via timestamp correlation
- Response time calculated from "Aduan Baru" to "Aduan Selesai"
- Timeline always shows in ascending order (oldest first)
- Auto-generate "Aduan Baru" ensures complete timeline
- SLA due date tracked in chronology untuk monitoring
