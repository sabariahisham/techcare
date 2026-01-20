# Dokumentasi: View Maklumat Tindakan Pegawai

## 📋 Ringkasan
Feature ini membolehkan pegawai tugasan dan admin menguruskan tiket dengan mengubah status, assign kepada pegawai lain, menambah catatan, dan menutup tiket. Section ini hanya visible untuk authorized officers.

## 🎯 Fungsi Utama
- Update ticket status (Diproses, Dirujuk, Selesai, DiBatal)
- Assign/reassign ticket kepada pegawai lain
- Auto-load officers berdasarkan category/subcategory
- Calculate dan display SLA due date
- Add action notes/comments
- Form validation sebelum submit
- Create chronology entry untuk setiap perubahan
- Send notifications kepada stakeholders
- Conditional display based on user role

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketAssignmentController.php
├── update(Request $request, Ticket $ticket)
│   ├── Validate input
│   ├── Check authorization
│   ├── Update ticket status
│   ├── Reassign officer if needed
│   ├── Create chronology entry
│   ├── Add comment/notes
│   ├── Send notifications
│   └── Return success response
│
├── getOfficersByCategory(Request $request)
│   ├── Get category and subcategory
│   ├── Query officers with matching cat_id & subcat_id
│   └── Return officers list
```

### Frontend (Views)
```
resources/views/tickets/show.blade.php (Line ~370-600)
├── Permission check block
│   └── $canSeeTindakanPegawai logic
│
├── Maklumat Tindakan Pegawai Card (conditional)
│   ├── Card header
│   ├── Update form
│   │   ├── Status Tiket dropdown
│   │   ├── Pegawai Tugasan dropdown (dynamic)
│   │   ├── SLA Due Date display (auto-calculated)
│   │   ├── Catatan Tindakan textarea
│   │   ├── Simpan button
│   │   └── Batal button
```

### JavaScript
```
resources/assets/js/ticket/assignment.js
├── Status change handler
│   ├── Load officers for selected status
│   └── Show/hide officer dropdown
│
├── Category/subcategory change handler
│   └── Reload officers based on selection
│
├── Form submission handler
│   ├── Validate required fields
│   ├── Prepare form data
│   ├── AJAX PUT request
│   └── Handle response (success/error)
│
└── SLA calculation display
    └── Update due date based on status
```

### Models
```
app/Models/Ticket.php
├── Fields updated:
│   ├── tick_status (current status)
│   ├── sla_duedate (calculated due date)
│   └── updated_by
│
app/Models/TicketChronology.php
├── Created when status changes:
│   ├── ticket_id
│   ├── tick_status (new status)
│   ├── frm_user_id (current user)
│   ├── escalate_to (assigned officer)
│   ├── level_pegtugas_id
│   ├── sla_duedate
│   └── created_by
```

### Routes
```
routes/web.php
├── PUT  /ticket-assignments/{ticket}           # update()
├── GET  /api/officers-by-category-severity    # getOfficersByCategory()
└── Middleware: auth, verified
```

## 🔐 Permission & Authorization

### Who Can See This Section?
```php
// show.blade.php - Permission Check

// Get currently assigned officers
$assignedOfficerIdsForPermission = $ticket->chronologies()
    ->whereNotNull('escalate_to')
    ->where('tick_status', $ticket->tick_status)
    ->latest()
    ->pluck('escalate_to')
    ->unique()
    ->toArray();

// Check if user can see this section
$canSeeTindakanPegawai = false;

// Admins, Pentadbir Sistem, Penyelia Helpdesk, Helpdesk On Duty can always see
if (auth()->user()->hasAnyRole([
    'Administrator', 
    'Pentadbir Sistem', 
    'Penyelia Helpdesk', 
    'Helpdesk On Duty'
])) {
    $canSeeTindakanPegawai = true;
}
// Pegawai Tugasan can only see if they are assigned to this ticket
elseif (
    auth()->user()->hasRole('Pegawai Tugasan') &&
    in_array(auth()->user()->id, $assignedOfficerIdsForPermission)
) {
    $canSeeTindakanPegawai = true;
}
```

### Authorization Matrix
| Role | View Section | Update Status | Reassign Officer | Close Ticket |
|------|--------------|---------------|------------------|--------------|
| Administrator | ✓ | ✓ | ✓ | ✓ |
| Pentadbir Sistem | ✓ | ✓ | ✓ | ✓ |
| Penyelia Helpdesk | ✓ | ✓ | ✓ | ✓ |
| Helpdesk On Duty | ✓ | ✓ | ✓ | ✓ |
| Pegawai Tugasan (Assigned) | ✓ | ✓ | ✓ | ✓ |
| Pegawai Tugasan (Not Assigned) | ✗ | ✗ | ✗ | ✗ |
| Pengadu | ✗ | ✗ | ✗ | ✗ |

## 🔄 Flow Kerja

### 1. Display Form
```blade
@if ($canSeeTindakanPegawai)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bx bx-cog me-2"></i>Maklumat Tindakan Pegawai
            </h5>
        </div>
        <div class="card-body">
            <form id="updateTicketForm" 
                  action="{{ route('ticket-assignments.update', $ticket) }}" 
                  method="POST">
                @csrf
                @method('PUT')
                
                {{-- Form fields --}}
            </form>
        </div>
    </div>
@endif
```

### 2. Status Dropdown
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Status Tiket</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="tick_status" id="tick_status" required>
            <option value="">Pilih Status</option>
            @foreach ($allStatuses as $status)
                <option value="{{ $status->id }}"
                        {{ $ticket->tick_status == $status->id ? 'selected' : '' }}>
                    {{ $status->st_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
```

**Available Statuses**:
- Aduan Baru (initial, auto-set)
- Aduan Diproses
- Serahan Kepada
- Aduan Dirujuk
- Aduan DiBatal
- Aduan Selesai

### 3. Officer Assignment Dropdown (Dynamic)
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Pegawai Tugasan</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="escalate_to" id="escalate_to" required>
            <option value="">Pilih Pegawai</option>
            {{-- Officers loaded via AJAX based on category/subcategory --}}
        </select>
        <small class="text-muted mt-1 d-block">
            Pegawai yang tersedia berdasarkan kategori dan sub kategori aduan.
        </small>
    </div>
</div>
```

### 4. Load Officers Dynamically
```javascript
// assignment.js

$('#tick_status, #cat_id, #subcat_id').on('change', function() {
    loadOfficers();
});

function loadOfficers() {
    const catId = $('#cat_id').val() || ticketCategoryId;
    const subcatId = $('#subcat_id').val() || ticketSubcategoryId;
    const statusId = $('#tick_status').val();
    
    if (!catId || !subcatId || !statusId) {
        return;
    }
    
    $.ajax({
        url: '/api/officers-by-category-severity',
        data: {
            cat_id: catId,
            subcat_id: subcatId,
            status_id: statusId
        },
        success: function(response) {
            const $select = $('#escalate_to');
            $select.empty();
            $select.append('<option value="">Pilih Pegawai</option>');
            
            $.each(response.officers, function(index, officer) {
                const loadInfo = officer.active_tickets_count 
                    ? ` (${officer.active_tickets_count} tiket aktif)` 
                    : '';
                
                $select.append(
                    `<option value="${officer.user_id}">
                        ${officer.user.nama} - ${officer.user.jawatan_name}${loadInfo}
                    </option>`
                );
            });
        }
    });
}
```

### 5. SLA Due Date Display
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">SLA Due Date</h6>
    </div>
    <div class="col-sm-9">
        @if ($ticket->sla_duedate)
            @php
                $now = \Carbon\Carbon::now();
                $dueDate = \Carbon\Carbon::parse($ticket->sla_duedate);
                $isOverdue = $now->greaterThan($dueDate);
                $diffForHumans = $isOverdue 
                    ? 'Overdue: ' . $dueDate->diffForHumans() 
                    : 'Due in: ' . $dueDate->diffForHumans();
            @endphp
            
            <div class="alert {{ $isOverdue ? 'alert-danger' : 'alert-info' }} mb-0">
                <i class="bx {{ $isOverdue ? 'bx-error' : 'bx-info-circle' }} me-1"></i>
                <strong>{{ $dueDate->format('d M Y, H:i') }}</strong>
                <br>
                <small>{{ $diffForHumans }}</small>
            </div>
        @else
            <p class="text-muted mb-0">SLA belum ditetapkan</p>
        @endif
    </div>
</div>
```

### 6. Notes/Catatan Textarea
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Catatan Tindakan</h6>
    </div>
    <div class="col-sm-9">
        <textarea class="form-control" 
                  name="notes" 
                  id="action_notes" 
                  rows="4"
                  placeholder="Masukkan catatan tindakan (optional)"></textarea>
        <small class="text-muted">
            Catatan ini akan direkodkan dalam kronologi tiket.
        </small>
    </div>
</div>
```

### 7. Form Submission
```javascript
// assignment.js

$('#updateTicketForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate
    if (!$('#tick_status').val()) {
        toastr.error('Sila pilih status tiket');
        return;
    }
    
    if (!$('#escalate_to').val()) {
        toastr.error('Sila pilih pegawai tugasan');
        return;
    }
    
    // Confirm if closing ticket
    const selectedStatus = $('#tick_status option:selected').text();
    if (selectedStatus === 'Aduan Selesai' || selectedStatus === 'Aduan DiBatal') {
        if (!confirm(`Adakah anda pasti untuk ${selectedStatus}?`)) {
            return;
        }
    }
    
    // Submit
    $.ajax({
        url: $(this).attr('action'),
        type: 'PUT',
        data: $(this).serialize(),
        success: function(response) {
            toastr.success('Tiket berjaya dikemaskini');
            
            // Reload page to show updated data
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            $.each(errors, function(field, messages) {
                toastr.error(messages[0]);
            });
        }
    });
});
```

### 8. Backend Processing
```php
// TicketAssignmentController@update

public function update(Request $request, Ticket $ticket)
{
    // Validate
    $request->validate([
        'tick_status' => 'required|exists:ref_ticket_statuses,id',
        'escalate_to' => 'required|exists:users,id',
        'notes' => 'nullable|string'
    ]);
    
    // Check authorization
    $this->authorize('update', $ticket);
    
    DB::beginTransaction();
    try {
        // Get new status details
        $newStatus = RefTicketStatus::find($request->tick_status);
        $escalateTo = User::find($request->escalate_to);
        
        // Calculate SLA due date based on status and severity
        $slaDueDate = $this->calculateSLADueDate(
            $ticket->cat_id, 
            $ticket->subcat_id,
            $newStatus->id
        );
        
        // Update ticket
        $ticket->update([
            'tick_status' => $request->tick_status,
            'sla_duedate' => $slaDueDate,
            'updated_by' => auth()->id()
        ]);
        
        // Create chronology entry
        TicketChronology::create([
            'ticket_id' => $ticket->id,
            'frm_user_id' => auth()->id(),
            'tick_status' => $request->tick_status,
            'escalate_to' => $request->escalate_to,
            'sla_duedate' => $slaDueDate,
            'created_by' => auth()->id()
        ]);
        
        // Add comment if notes provided
        if ($request->notes) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment_text' => $request->notes,
                'created_by' => auth()->id()
            ]);
        }
        
        // Send notifications
        $this->sendNotifications($ticket, $newStatus, $escalateTo);
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Tiket berjaya dikemaskini'
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Ralat: ' . $e->getMessage()
        ], 500);
    }
}
```

## 📊 SLA Calculation

### Calculate Due Date
```php
// TicketAssignmentController

protected function calculateSLADueDate($catId, $subcatId, $statusId)
{
    // Get SLA configuration
    $sla = Sla::where('cat_id', $catId)
              ->where('subcat_id', $subcatId)
              ->first();
    
    if (!$sla) {
        // Default: 3 working days
        return $this->addWorkingDays(Carbon::now(), 3);
    }
    
    // Get days based on severity
    $days = match($sla->severity) {
        'critical' => 1,
        'high' => 3,
        'medium' => 5,
        'low' => 7,
        default => 3
    };
    
    return $this->addWorkingDays(Carbon::now(), $days);
}

protected function addWorkingDays(Carbon $date, $days)
{
    $addedDays = 0;
    $currentDate = $date->copy();
    
    while ($addedDays < $days) {
        $currentDate->addDay();
        
        // Skip weekends
        if (!$currentDate->isWeekend()) {
            $addedDays++;
        }
    }
    
    return $currentDate;
}
```

### Display Due Date Status
```blade
@php
    $now = Carbon::now();
    $dueDate = Carbon::parse($ticket->sla_duedate);
    $hoursRemaining = $now->diffInHours($dueDate, false);
    
    $statusClass = 'alert-info';
    $statusIcon = 'bx-info-circle';
    $statusMessage = 'On Track';
    
    if ($hoursRemaining < 0) {
        // Overdue
        $statusClass = 'alert-danger';
        $statusIcon = 'bx-error';
        $statusMessage = 'OVERDUE';
    } elseif ($hoursRemaining < 24) {
        // Due soon (less than 24 hours)
        $statusClass = 'alert-warning';
        $statusIcon = 'bx-time';
        $statusMessage = 'DUE SOON';
    }
@endphp

<div class="alert {{ $statusClass }}">
    <i class="bx {{ $statusIcon }} me-1"></i>
    <strong>{{ $statusMessage }}</strong>: {{ $dueDate->format('d M Y, H:i') }}
    <br>
    <small>{{ abs($hoursRemaining) }} jam {{ $hoursRemaining < 0 ? 'terlepas' : 'lagi' }}</small>
</div>
```

## 🔔 Notifications

### Send After Status Update
```php
// TicketAssignmentController

protected function sendNotifications($ticket, $newStatus, $escalateTo)
{
    // 1. Notify newly assigned officer
    if ($escalateTo) {
        $escalateTo->notify(new TicketAssignedNotification($ticket));
    }
    
    // 2. Notify pengadu if status changed to Selesai or DiBatal
    if (in_array($newStatus->st_name, ['Aduan Selesai', 'Aduan DiBatal'])) {
        $ticket->user->notify(new TicketStatusChangedNotification($ticket, $newStatus));
    }
    
    // 3. Notify wakil if ticket type is "Bagi Pihak"
    if ($ticket->ticket_type == 2 && $ticket->created_by != $ticket->user_id) {
        $ticket->createdBy->notify(new TicketStatusChangedNotification($ticket, $newStatus));
    }
    
    // 4. Notify previous officer (if reassigned)
    $previousOfficer = $ticket->chronologies()
        ->where('tick_status', $ticket->tick_status)
        ->orderBy('created_at', 'desc')
        ->skip(1)
        ->first();
    
    if ($previousOfficer && $previousOfficer->escalate_to != $escalateTo->id) {
        $previousOfficer->escalatedTo->notify(
            new TicketReassignedNotification($ticket)
        );
    }
}
```

## 🎨 Form Styling

### Card Layout
```css
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.card-header h5 {
    color: white;
    font-weight: 600;
}

.form-select, .form-control {
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
}

.form-select:focus, .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}
```

### Buttons
```blade
<div class="row">
    <div class="col-sm-9 offset-sm-3">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Simpan
        </button>
        <button type="button" class="btn btn-outline-secondary" 
                onclick="window.location.reload()">
            <i class="bx bx-x me-1"></i>Batal
        </button>
    </div>
</div>
```

## 🐛 Common Issues & Solutions

### Issue 1: Officer dropdown kosong
**Solution**: Ensure officers configured dengan correct cat_id dan subcat_id

### Issue 2: SLA due date tidak calculate
**Solution**: Check SLA configuration exists untuk category/subcategory

### Issue 3: Notification tidak sent
**Solution**: Check queue worker running (`php artisan queue:work`)

### Issue 4: Form submit tapi tidak update
**Solution**: Check authorization dan validation rules

### Issue 5: Pegawai tidak assigned
**Solution**: Check officer exists dalam Officer table dengan matching category

## 📝 Notes
- Section only visible untuk authorized officers
- Status change creates chronology entry automatically
- SLA calculated berdasarkan severity dan working days (skip weekends)
- Officers filtered based on category/subcategory match
- Load balancing considers active tickets count
- Notes optional tapi recommended untuk audit trail
- Confirm dialog shown untuk Selesai/DiBatal status
- Notifications sent to all stakeholders
- Form validation both client-side dan server-side
