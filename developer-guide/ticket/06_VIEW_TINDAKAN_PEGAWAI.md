# Dokumentasi: View Maklumat Tindakan Pegawai

## 📋 Ringkasan
Feature ini membolehkan pegawai tugasan dan admin menguruskan tiket dengan mengubah status, assign kepada pegawai lain, menambah catatan, dan menutup tiket. Section ini hanya visible untuk authorized officers.

## 🎯 Fungsi Utama
- Update ticket status (Diproses, Dirujuk, Selesai, DiBatal)
- **Multi-officer assignment** - Assign kepada multiple pegawai sekaligus
- Auto-load officers berdasarkan category/subcategory dengan level_tugasan filtering
- **SLA severity selection** (High/Medium/Low) untuk calculate SLA days
- **Automatic SLA calculation** - Lookup dari `sla` table berdasarkan cat_id, subcat_id, level_pegtugas, dan severity
- **Smart SLA defaults** - Guna existing ticket category jika tidak diubah
- Calculate working days (skip weekends & public holidays) untuk SLA due date
- Add action notes/comments
- Form validation sebelum submit
- Create chronology entry untuk setiap perubahan dengan complete SLA info
- Send notifications kepada stakeholders
- Conditional display based on user role

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketAssignmentController.php
├── updateTicketAssignment(Request $request, $uuid)
│   ├── Find ticket by UUID
│   ├── Validate input (dynamic validation based on status)
│   ├── Get existing ticket cat_id/subcat_id as fallback
│   ├── Get first officer's level_pegtugas from officers table
│   ├── Lookup SLA from sla table (cat_id, subcat_id, level, severity)
│   ├── Calculate sla_days and sla_duedate using Helpers::addWorkingDays()
│   ├── Update ticket status
│   ├── Update/Create chronology entry with SLA info
│   ├── Handle multiple officer assignments (additional officers)
│   ├── Add comment/notes if provided
│   ├── Send notifications
│   └── Redirect with success message
│
├── getAvailableAssigneesBySLA(Request $request)
│   ├── Get category, subcategory, and severity
│   ├── Query officers with matching criteria
│   ├── Filter by level_tugasan
│   └── Return officers list with load info
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
│   │   ├── Kategori dropdown (for Aduan Dirujuk status)
│   │   ├── Sub Kategori dropdown (for Aduan Dirujuk status)
│   │   ├── Tahap Severity dropdown (High/Medium/Low)
│   │   ├── Pegawai Tugasan dropdown (multiple select, dynamic)
│   │   ├── Tarikh Tindakan (datetime-local)
│   │   ├── Tindakan Oleh (auto-display level 1 officers)
│   │   ├── Catatan textarea
│   │   ├── Kemaskini button
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
│   ├── cat_id (only for Aduan Dirujuk status)
│   ├── subcat_id (only for Aduan Dirujuk status)
│   └── updated_by
│
app/Models/TicketChronology.php
├── Created/Updated when status changes:
│   ├── ticket_id
│   ├── frm_user_id (current user)
│   ├── tick_status (new status)
│   ├── sla_severity (High/Medium/Low)
│   ├── sla_days (from sla table lookup)
│   ├── sla_duedate (calculated with working days)
│   ├── level_pegtugas_id (from officers table)
│   ├── escalate_to (first assigned officer)
│   ├── created_by
│   └── updated_by
│
├── Multiple chronologies created for:
│   └── Additional officers (2nd, 3rd, etc.)
│
app/Models/TicketComment.php
├── Created if notes provided:
│   ├── ticket_id
│   ├── user_id
│   ├── comment_text
│   └── created_by
│
app/Models/Sla.php
├── Used for SLA lookup:
│   ├── cat_id
│   ├── subcat_id
│   ├── sla_level (level_pegtugas)
│   ├── sla_severity (High/Medium/Low)
│   └── sla_days (working days)
```

### Routes
```
routes/web.php
├── PUT  /ticket-assignments/{uuid}                      # updateTicketAssignment()
├── GET  /api/ticket-assignments/assignees/sla          # getAvailableAssigneesBySLA()
├── GET  /api/ticket-assignments/assignees              # getAvailableAssignees()
└── Middleware: auth, verified

Form Action:
├── action="{{ route('ticket-assignments.update', $ticket->uuid) }}"
└── method="POST" with @method('PUT')
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

### 3. Category & Subcategory Selection (Required for Aduan Dirujuk)
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Kategori</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="cat_id" id="cat_id">
            <option value="">Pilih Kategori</option>
            @foreach ($allCategories as $category)
                <option value="{{ $category->id }}"
                        {{ $ticket->cat_id == $category->id ? 'selected' : '' }}>
                    {{ $category->cat_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Sub Kategori</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="subcat_id" id="subcat_id">
            <option value="">Pilih Sub Kategori</option>
            @foreach ($allSubCategories as $subCategory)
                <option value="{{ $subCategory->id }}"
                        data-cat-id="{{ $subCategory->cat_id }}"
                        {{ $ticket->subcat_id == $subCategory->id ? 'selected' : '' }}>
                    {{ $subCategory->subcat_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
```

**Note**: Category & Subcategory wajib diisi untuk status "Aduan Dirujuk". Untuk status lain, boleh kosong (akan guna existing ticket's category).

### 4. SLA Severity Selection
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Tahap Severity</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="sla_severity" id="sla_severity">
            <option value="">Pilih Tahap Severity</option>
            <option value="High">High</option>
            <option value="Medium">Medium</option>
            <option value="Low">Low</option>
        </select>
        <small class="text-muted d-block mt-1">
            Tahap severity akan menentukan bilangan hari SLA dari sla table.
        </small>
    </div>
</div>
```

### 5. Officer Assignment Dropdown (Multiple Select)
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Pegawai Tugasan</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <select class="form-select" name="assigned_officers[]" id="assigned_officers" multiple>
            {{-- Officers loaded dynamically based on category/subcategory/level --}}
            @php
                // Get currently assigned officers from latest chronologies
                $assignedOfficerIds = $ticket->chronologies()
                    ->whereNotNull('escalate_to')
                    ->where('tick_status', $ticket->tick_status)
                    ->latest()
                    ->pluck('escalate_to')
                    ->unique()
                    ->toArray();

                // Get officers based on level filtering
                $currentAssignedOfficerData = DB::table('officers')
                    ->whereIn('user_id', $assignedOfficerIds)
                    ->whereNull('deleted_at')
                    ->get(['user_id', 'level_tugasan']);

                // Determine what level officers to show
                $showLevelTugasan = null;
                foreach ($currentAssignedOfficerData as $officerData) {
                    if ($officerData->level_tugasan == 1) {
                        $showLevelTugasan = 2; // Show level 2 if level 1 assigned
                        break;
                    }
                }

                // Get officers from officers table with filtering
                $officersQuery = DB::table('officers')
                    ->join('users', 'officers.user_id', '=', 'users.id')
                    ->whereNull('officers.deleted_at');

                if ($ticket->cat_id) {
                    $officersQuery->where('officers.cat_id', $ticket->cat_id);
                }
                if ($ticket->subcat_id) {
                    $officersQuery->where('officers.subcat_id', $ticket->subcat_id);
                }
                if ($showLevelTugasan !== null) {
                    $officersQuery->where('officers.level_tugasan', $showLevelTugasan);
                }

                $filteredOfficers = $officersQuery
                    ->select('users.id', 'users.nama', 'users.email')
                    ->get();
            @endphp

            @foreach ($filteredOfficers as $officer)
                <option value="{{ $officer->id }}"
                        {{ in_array($officer->id, $assignedOfficerIds) ? 'selected' : '' }}>
                    {{ $officer->nama }}
                </option>
            @endforeach
        </select>
        <small class="text-muted mt-1 d-block">
            Pegawai filtered based on category, subcategory, and level.
            Multiple selection allowed.
        </small>
    </div>
</div>
```

**Key Features**:
- **Multiple selection**: Boleh assign ke multiple officers sekaligus
- **Level-based filtering**: Jika level 1 assigned, show level 2 officers
- **Category/subcategory filtering**: Hanya show officers dengan matching category
- **Pre-selected**: Current assigned officers akan auto-selected

### 6. Tarikh Tindakan
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Tarikh Tindakan</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <input type="datetime-local" class="form-control" name="action_date"
               id="action_date" value="{{ now()->format('Y-m-d\TH:i') }}" />
    </div>
</div>
```

### 7. Tindakan Oleh (Display Only)
```blade
<div class="row mb-3">
    <div class="col-sm-3">
        <h6 class="mb-0">Tindakan Oleh</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        @php
            $assignedOfficerNames = [];
            if ($ticket->cat_id && $ticket->subcat_id) {
                $assignedOfficerNames = DB::table('officers')
                    ->join('users', 'officers.user_id', '=', 'users.id')
                    ->where('officers.cat_id', $ticket->cat_id)
                    ->where('officers.subcat_id', $ticket->subcat_id)
                    ->where('officers.level_tugasan', 1) // Level 1 only
                    ->whereNull('officers.deleted_at')
                    ->pluck('users.nama')
                    ->unique()
                    ->toArray();
            }
            $actionByText = !empty($assignedOfficerNames)
                ? implode(', ', $assignedOfficerNames)
                : '-';
        @endphp
        <span id="action_by_display">{{ $actionByText }}</span>
    </div>
</div>
```
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

### 9. Form Submission
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

### 10. Backend Processing
```php
// TicketAssignmentController@updateTicketAssignment

public function updateTicketAssignment(Request $request, $uuid)
{
    // Find ticket by UUID
    $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
    
    // Dynamic validation based on status
    $selectedStatus = RefTicketStatus::find($request->tick_status);
    $isAduanDirujuk = $selectedStatus && $selectedStatus->st_name === 'Aduan Dirujuk';
    
    $validationRules = [
        'tick_status' => 'required|exists:ref_ticket_statuses,id',
        'sla_severity' => 'nullable|string',
        'assigned_officers' => 'nullable|array',
        'assigned_officers.*' => 'exists:users,id',
        'action_date' => 'nullable|date',
        'notes' => 'nullable|string|max:1000'
    ];
    
    // Add kategori validation only for Aduan Dirujuk
    if ($isAduanDirujuk) {
        $validationRules['cat_id'] = 'required|exists:ref_categories,id';
        $validationRules['subcat_id'] = 'required|exists:ref_sub_categories,id';
    }
    
    $request->validate($validationRules);
    
    try {
        // Store old status
        $oldStatus = $ticket->tick_status;
        
        // IMPORTANT: Use existing ticket cat_id/subcat_id if not provided
        $catId = $request->cat_id ?? $ticket->cat_id;
        $subcatId = $request->subcat_id ?? $ticket->subcat_id;
        
        // Get first officer ID and their level_pegtugas
        $firstOfficerId = null;
        $levelPetugas = 1; // Default
        $slaSeverity = $request->sla_severity;
        
        if ($request->has('assigned_officers') && !empty($request->assigned_officers)) {
            $firstOfficerId = $request->assigned_officers[0];
            
            // Get level_pegtugas from officers table
            $officerData = DB::table('officers')
                ->where('user_id', $firstOfficerId)
                ->where('cat_id', $catId)
                ->where('subcat_id', $subcatId)
                ->whereNull('deleted_at')
                ->first();
            
            if ($officerData) {
                $levelPetugas = $officerData->level_tugasan ?? 1;
            }
        }
        
        // Lookup SLA from database
        $slaDays = null;
        $slaDueDate = null;
        
        if ($catId && $subcatId) {
            // Build SLA query with level_pegtugas
            $slaQuery = Sla::where('cat_id', $catId)
                ->where('subcat_id', $subcatId)
                ->where('sla_level', $levelPetugas);
            
            // Add severity if provided
            if ($slaSeverity) {
                $slaQuery->where('sla_severity', $slaSeverity);
            }
            
            $slaInfo = $slaQuery->first();
            
            // Fallback: try without severity
            if (!$slaInfo && $slaSeverity) {
                $slaInfo = Sla::where('cat_id', $catId)
                    ->where('subcat_id', $subcatId)
                    ->where('sla_level', $levelPetugas)
                    ->first();
            }
            
            if ($slaInfo) {
                $slaDays = $slaInfo->sla_days;
                $slaSeverity = $slaSeverity ?? $slaInfo->sla_severity;
                $levelPetugas = $slaInfo->sla_level ?? $levelPetugas;
                
                // Calculate due date using working days (skip weekends & holidays)
                $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);
                
                Log::info('SLA Calculation SUCCESS', [
                    'cat_id' => $catId,
                    'subcat_id' => $subcatId,
                    'sla_severity' => $slaSeverity,
                    'sla_days' => $slaDays,
                    'sla_duedate' => $slaDueDate->format('Y-m-d H:i:s'),
                    'level_pegtugas' => $levelPetugas
                ]);
            } else {
                // Default: 3 working days
                $slaDays = 3;
                $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);
                
                Log::warning('No SLA found, using defaults', [
                    'default_sla_days' => $slaDays
                ]);
            }
        }
        
        // Update ticket
        $updateData = [
            'tick_status' => $request->tick_status,
            'updated_by' => Auth::id()
        ];
        
        // Update category only for Aduan Dirujuk
        if ($isAduanDirujuk && $request->cat_id && $request->subcat_id) {
            $updateData['cat_id'] = $request->cat_id;
            $updateData['subcat_id'] = $request->subcat_id;
        }
        
        $ticket->update($updateData);
        
        // Check for existing chronology with same status
        $existingChronology = TicketChronology::where('ticket_id', $ticket->id)
            ->where('tick_status', $request->tick_status)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($existingChronology) {
            // Update existing
            $existingChronology->update([
                'frm_user_id' => Auth::id(),
                'sla_severity' => $slaSeverity,
                'sla_days' => $slaDays,
                'sla_duedate' => $slaDueDate,
                'level_pegtugas_id' => $levelPetugas,
                'escalate_to' => $firstOfficerId,
                'updated_by' => Auth::id(),
                'updated_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
            ]);
            
            $chronology = $existingChronology;
        } else {
            // Create new chronology
            $chronology = TicketChronology::create([
                'ticket_id' => $ticket->id,
                'frm_user_id' => Auth::id(),
                'tick_status' => $request->tick_status,
                'sla_severity' => $slaSeverity,
                'sla_days' => $slaDays,
                'sla_duedate' => $slaDueDate,
                'level_pegtugas_id' => $levelPetugas,
                'escalate_to' => $firstOfficerId,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
                'updated_at' => now()
            ]);
        }
        
        // Handle additional officers (2nd, 3rd, etc.)
        if ($request->has('assigned_officers') && count($request->assigned_officers) > 1) {
            $additionalOfficers = array_slice($request->assigned_officers, 1);
            
            foreach ($additionalOfficers as $officerId) {
                TicketChronology::create([
                    'ticket_id' => $ticket->id,
                    'frm_user_id' => Auth::id(),
                    'tick_status' => $request->tick_status,
                    'escalate_to' => $officerId,
                    'sla_severity' => $slaSeverity,
                    'sla_days' => $slaDays,
                    'sla_duedate' => $slaDueDate,
                    'level_pegtugas_id' => $levelPetugas,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => $request->action_date ? Carbon::parse($request->action_date) : now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Add notes as comment
        if ($request->notes) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'comment_text' => $request->notes,
                'created_by' => Auth::id(),
            ]);
        }
        
        // Reload ticket
        $ticket->refresh();
        $ticket->load(['status', 'category', 'subCategory', 'chronologies']);
        
        // Send notifications
        $this->notificationService->sendStatusChangeNotification(
            $ticket,
            $oldStatus,
            $request->tick_status,
            Auth::user(),
            Auth::id()
        );
        
        return redirect()->route('tickets.show', $ticket->uuid)
            ->with('success', 'Tiket berjaya dikemaskini!');
        
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

### How SLA Works

**SITUASI 1 - Create Ticket (Initial chronology)**:
- sla_severity: NULL (not set on creation)
- sla_days: 3 (default 3 working days)
- sla_duedate: created_at + 3 working days

**SITUASI 2 - Update Ticket (Subsequent chronologies)**:
- sla_severity: High/Medium/Low (from user selection)
- sla_days: Retrieved from `sla` table based on:
  - cat_id (ticket category)
  - subcat_id (ticket subcategory)
  - sla_level (from officers.level_tugasan)
  - sla_severity (High/Medium/Low)
- sla_duedate: current time + sla_days working days

### SLA Lookup Query
```php
// Build query to get SLA days
$slaQuery = Sla::where('cat_id', $catId)
    ->where('subcat_id', $subcatId)
    ->where('sla_level', $levelPetugas);

// Add severity if provided
if ($slaSeverity) {
    $slaQuery->where('sla_severity', $slaSeverity);
}

$slaInfo = $slaQuery->first();

// Fallback: If not found with severity, try without
if (!$slaInfo && $slaSeverity) {
    $slaInfo = Sla::where('cat_id', $catId)
        ->where('subcat_id', $subcatId)
        ->where('sla_level', $levelPetugas)
        ->first();
}

if ($slaInfo) {
    $slaDays = $slaInfo->sla_days;
    $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);
} else {
    // Default: 3 working days
    $slaDays = 3;
    $slaDueDate = Helpers::addWorkingDays(Carbon::now(), $slaDays);
}
```

### Calculate Working Days (Skip Weekends & Public Holidays)
```php
// app/Helpers/Helpers.php

public static function addWorkingDays($startDate, $days)
{
    $currentDate = Carbon::parse($startDate);
    $addedDays = 0;
    
    while ($addedDays < $days) {
        $currentDate->addDay();
        
        // Skip weekends (Saturday & Sunday)
        if ($currentDate->isWeekend()) {
            continue;
        }
        
        // Skip public holidays
        $isPublicHoliday = PublicHoliday::where('date', $currentDate->format('Y-m-d'))
            ->exists();
        
        if ($isPublicHoliday) {
            continue;
        }
        
        $addedDays++;
    }
    
    return $currentDate;
}
```

### SLA Table Structure
```sql
CREATE TABLE `sla` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cat_id` bigint unsigned NOT NULL,
    `subcat_id` bigint unsigned NOT NULL,
    `sla_level` int NOT NULL COMMENT 'Level pegtugas (1,2,3,etc)',
    `sla_severity` varchar(20) DEFAULT NULL COMMENT 'High, Medium, Low',
    `sla_days` int NOT NULL COMMENT 'Number of working days',
    `created_by` bigint unsigned DEFAULT NULL,
    `updated_by` bigint unsigned DEFAULT NULL,
    `deleted_by` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `sla_cat_id_foreign` (`cat_id`),
    KEY `sla_subcat_id_foreign` (`subcat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Example SLA Data
```sql
-- Category 2, Subcategory 3
INSERT INTO sla (cat_id, subcat_id, sla_level, sla_severity, sla_days) VALUES
(2, 3, 1, 'High', 5),
(2, 3, 1, 'Medium', 2),
(2, 3, 1, 'Low', 1),
(2, 3, 2, 'High', 3),
(2, 3, 2, 'Medium', 2),
(2, 3, 2, 'Low', 1);
```

### Display Due Date Status
```blade
@if ($ticket->sla_duedate)
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
@endif
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

## 🔔 Badge Notification untuk Catatan Tindakan (NEW - March 18, 2026)

### 📋 Overview
Feature badge notification yang dipaparkan pada button "Catatan Tindakan" untuk memberitahu user tentang komen/catatan baharu yang belum dibaca. Badge ini menggunakan **cache-based persistence** untuk memastikan ia berfungsi dengan betul walaupun dalam impersonation mode.

### 🎯 Key Features
- **Real-time badge display** dengan count komen yang belum dibaca
- **Role-based logic**: Pengadu nampak badge untuk komen pegawai, Pegawai Tugasan nampak badge untuk komen pengadu
- **Cache persistence**: Badge persistence survive across page refreshes dan impersonation mode
- **Automatic badge removal**: Badge hilang selepas user buka modal dan di-refresh
- **Pulse animation**: Visual feedback untuk menarik perhatian user
- **Comments sorted by latest**: Catatan terkini dipaparkan di atas

### 🔐 Badge Visibility Logic

#### Who Sees the Badge?

**1. Pengadu (Ticket Creator)**
- Sees badge for comments from **Pegawai Tugasan** only
- Pegawai roles: 'Pegawai Tugasan', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Administrator', 'Pentadbir Sistem'
- Does NOT see badge for their own comments

**2. Pegawai Tugasan (Assigned Officer)**
- ONLY assigned officers see the badge
- Sees badge for comments from **Pengadu (ticket creator)** only
- Does NOT see badge if not assigned to the ticket

**3. Other Users**
- NO badge displayed
- Users yang tidak berkaitan dengan ticket tidak nampak badge

### 📂 Files Modified

```
app/Http/Controllers/Ticket/TicketController.php
├── show() method
│   ├── Calculate badge count based on user role
│   ├── Check if user is Pengadu or Assigned Officer
│   ├── Query comments from relevant users only
│   ├── Filter by last viewed time from cache
│   └── Pass $newCommentsCount to view

app/Http/Controllers/Ticket/TicketAssignmentController.php
├── markCommentsAsViewed($ticketUuid)
│   ├── Store viewed timestamp in cache (30-day expiry)
│   ├── Cache key: "ticket_comments_viewed_{ticket_id}_user_{user_id}"
│   └── Return success response
│
├── getHistoryComment($ticketUuid)
│   ├── Retrieve all ticket comments
│   ├── Sort by created_at DESC (newest first)
│   └── Return comments with user info and status

resources/views/tickets/show.blade.php
├── Button with badge display
│   └── Lines ~383-391: Badge HTML with conditional display
│
└── CSS animations (Lines ~79-97)
    ├── pulse-badge animation
    └── shake animation

resources/views/tickets/partials/action-comment-modal.blade.php
├── Modal structure for displaying comments
├── markCommentsAsViewed() JavaScript function
│   └── Calls POST endpoint when modal shown
└── loadActionComments() function
    └── Fetches comments via AJAX

routes/web.php
└── POST /ticket-assignments/{uuid}/mark-comments-viewed
```

### 💻 Code Implementation

#### 1. Badge Count Calculation (TicketController.php)

```php
// Calculate badge count in show() method
$newCommentsCount = 0;

if (Auth::check()) {
    $currentUser = Auth::user();
    $currentUserId = $currentUser->id;
    
    // Get last viewed time from cache
    $cacheKey = "ticket_comments_viewed_{$ticket->id}_user_{$currentUserId}";
    $lastViewedAt = Cache::get($cacheKey);

    // Determine if current user is Pengadu
    $isPengadu = ($currentUserId === $ticket->user_id);

    // Check if user is assigned officer
    $isAssignedOfficer = $ticket->chronologies()
        ->whereNotNull('escalate_to')
        ->where('escalate_to', $currentUserId)
        ->exists();

    if ($isPengadu) {
        // Pengadu: Count comments from officers
        $officerIds = User::whereHas('roles', function ($query) {
            $query->whereIn('name', [
                'Pegawai Tugasan',
                'Penyelia Helpdesk',
                'Helpdesk On Duty',
                'Administrator',
                'Pentadbir Sistem'
            ]);
        })->pluck('id')->toArray();

        $commentsQuery = $ticket->comments()->whereIn('user_id', $officerIds);

        if ($lastViewedAt) {
            $newCommentsCount = $commentsQuery->where('created_at', '>', $lastViewedAt)->count();
        } else {
            // Fallback: Use last user interaction time
            $lastUserInteraction = $ticket->chronologies()
                ->where('frm_user_id', $currentUserId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastUserInteraction) {
                $newCommentsCount = $commentsQuery
                    ->where('created_at', '>', $lastUserInteraction->created_at)
                    ->count();
            } else {
                $newCommentsCount = $commentsQuery->count();
            }
        }
    } elseif ($isAssignedOfficer) {
        // Assigned Officer: Count comments from pengadu only
        $commentsQuery = $ticket->comments()->where('user_id', $ticket->user_id);

        if ($lastViewedAt) {
            $newCommentsCount = $commentsQuery->where('created_at', '>', $lastViewedAt)->count();
        } else {
            // Fallback: Use last user interaction time
            $lastUserInteraction = $ticket->chronologies()
                ->where('frm_user_id', $currentUserId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastUserInteraction) {
                $newCommentsCount = $commentsQuery
                    ->where('created_at', '>', $lastUserInteraction->created_at)
                    ->count();
            } else {
                $newCommentsCount = $commentsQuery->count();
            }
        }
    } else {
        // Other users: No badge
        $newCommentsCount = 0;
    }
}

// Pass to view
return view('tickets.show', compact(
    'ticket', 
    'location', 
    'ticketHistory', 
    'allStatuses', 
    'allCategories', 
    'allSubCategories', 
    'officers', 
    'currentSLA', 
    'newCommentsCount'
));
```

#### 2. Mark Comments as Viewed (TicketAssignmentController.php)

```php
/**
 * Mark comments as viewed for current user
 * Uses Cache for persistence (30-day expiry)
 */
public function markCommentsAsViewed($ticketUuid)
{
    try {
        $ticket = Ticket::where('uuid', $ticketUuid)->firstOrFail();
        $userId = Auth::id();
        
        // Store viewed timestamp in cache (30-day expiry)
        $cacheKey = "ticket_comments_viewed_{$ticket->id}_user_{$userId}";
        $viewedAt = now();
        
        Cache::put($cacheKey, $viewedAt, now()->addDays(30));
        
        return response()->json([
            'success' => true,
            'message' => 'Catatan ditanda sebagai dibaca',
            'viewed_at' => $viewedAt->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        Log::error('Error marking comments as viewed', [
            'error' => $e->getMessage(),
            'ticket_uuid' => $ticketUuid
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Ralat: ' . $e->getMessage()
        ], 500);
    }
}
```

#### 3. Get Comment History with Latest First (TicketAssignmentController.php)

```php
public function getHistoryComment($ticketUuid)
{
    try {
        $ticket = Ticket::where('uuid', $ticketUuid)
            ->with(['comments.user', 'comments.createdBy', 'status'])
            ->firstOrFail();

        // Get chronologies for status tracking
        $chronologies = $ticket->chronologies()
            ->with('status')
            ->orderBy('created_at', 'asc')
            ->get()
            ->unique(function ($item) {
                return $item->tick_status;
            });

        // Get comments sorted by newest first
        $comments = $ticket->comments
            ->sortByDesc('created_at')
            ->map(function ($comment) use ($ticket, $chronologies) {
                $commentStatus = $this->getStatusAtTime($comment->created_at, $chronologies);

                return [
                    'id' => $comment->id,
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                    'user_name' => $comment->user ? $comment->user->nama : 'Tidak diketahui',
                    'user_unit' => $comment->user ? ($comment->user->unit->nama_unit ?? $comment->user->kod_unit ?? '-') : '-',
                    'user_type' => $this->getUserType($comment->user, $ticket),
                    'comment' => $comment->comment_text,
                    'created_by' => $comment->createdBy ? $comment->createdBy->nama : 'Tidak diketahui',
                    'ticket_status' => $commentStatus['status_name'],
                    'status_class' => $commentStatus['status_class']
                ];
            })
            ->values(); // Reset array keys

        return response()->json([
            'success' => true,
            'comments' => $comments
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Ralat semasa mendapatkan catatan tindakan: ' . $e->getMessage()
        ], 500);
    }
}
```

#### 4. Badge Display HTML (show.blade.php)

```blade
{{-- Button Catatan Tindakan with Badge --}}
<button type="button" 
        class="btn btn-primary position-relative" 
        data-bs-toggle="modal" 
        data-bs-target="#actionComment">
    <i class="bx bx-message-dots me-1"></i>Catatan Tindakan
    
    @if(isset($newCommentsCount) && $newCommentsCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger pulse-badge">
            {{ $newCommentsCount }}
            <span class="visually-hidden">unread comments</span>
        </span>
    @endif
</button>
```

#### 5. Badge Animation CSS (show.blade.php)

```css
/* Badge pulse animation */
@keyframes pulse-badge {
    0%, 100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.1);
        opacity: 0.8;
    }
}

.pulse-badge {
    animation: pulse-badge 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
    20%, 40%, 60%, 80% { transform: translateX(2px); }
}

.badge.pulse-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
```

#### 6. JavaScript - Mark as Viewed (action-comment-modal.blade.php)

```javascript
function markCommentsAsViewed() {
    const ticketUuid = window.ticketData?.uuid || null;
    if (!ticketUuid) {
        console.error('Ticket UUID not found');
        return;
    }

    // Call API to mark comments as viewed
    fetch('/ticket-assignments/' + ticketUuid + '/mark-comments-viewed', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('❌ Failed to mark comments as viewed:', data.message);
        }
    })
    .catch(error => {
        console.error('❌ Error marking comments as viewed:', error);
    });
}

// Trigger when modal is shown
$('#actionComment').on('shown.bs.modal', function () {
    loadActionComments();
    markCommentsAsViewed(); // Mark as viewed immediately
});
```

#### 7. Route Definition (web.php)

```php
// Mark comments as viewed (Cache-based)
Route::post('/{uuid}/mark-comments-viewed', [
    TicketAssignmentController::class, 
    'markCommentsAsViewed'
])->name('ticket-assignments.mark-comments-viewed');
```

### 🔍 Cache Key Structure

```
Format: ticket_comments_viewed_{ticket_id}_user_{user_id}

Examples:
- ticket_comments_viewed_44_user_48
- ticket_comments_viewed_123_user_5

Cache Duration: 30 days
Storage: Laravel Cache (file/redis/memcached based on config)
```

### ⚡ Why Cache Instead of Session?

**Problem with Session**:
- Session tidak persist dalam Laravel impersonation mode
- Session data hilang selepas context switch
- Log evidence: `viewed_at` set pada 13:36:01, tetapi jadi `null` pada 13:36:05

**Solution: Cache-based Storage**:
```php
// Session (FAILED in impersonation mode)
session(['ticket_comments_viewed_' . $ticket->id => now()]);
session()->save(); // ❌ Not persistent

// Cache (WORKS in all modes)
Cache::put($cacheKey, now(), now()->addDays(30)); // ✅ Persistent
```

### 🧪 Testing Instructions

#### Test Case 1: Pengadu sees Officer Comments
1. Login sebagai Pengadu (ticket creator)
2. Navigate ke ticket detail page
3. Officer add comment dalam ticket
4. Reload page → Badge dengan count muncul pada button "Catatan Tindakan"
5. Click button → Modal opens
6. Close modal → Refresh page
7. **Expected**: Badge hilang (no longer displayed)

#### Test Case 2: Assigned Officer sees Pengadu Comments
1. Login sebagai Pegawai Tugasan yang di-assign ke ticket
2. Navigate ke ticket detail page
3. Pengadu add comment
4. Reload page → Badge dengan count muncul
5. Click button → Modal opens
6. Close modal → Refresh page
7. **Expected**: Badge hilang

#### Test Case 3: Non-Assigned Officer
1. Login sebagai Pegawai Tugasan yang TIDAK di-assign
2. Navigate ke ticket detail page
3. **Expected**: NO badge displayed (even if there are new comments)

#### Test Case 4: Impersonation Mode
1. Admin impersonate sebagai Pengadu
2. Navigate ke ticket dengan new comments
3. Badge muncul dengan count yang betul
4. Click button → Modal opens
5. Stop impersonation → Impersonate again
6. Refresh page
7. **Expected**: Badge TIDAK muncul (still marked as viewed)

#### Test Case 5: Multiple Users
1. User A (Pengadu) and User B (Officer) on same ticket
2. Both have new comments to see
3. User A opens modal → Badge hilang untuk User A only
4. User B still sees badge (independent tracking)
5. User B opens modal → Badge hilang untuk User B
6. **Expected**: Each user has independent badge state

### 🐛 Troubleshooting

#### Issue: Badge masih appear selepas view
**Check**:
```php
// Verify cache is set
$cacheKey = "ticket_comments_viewed_{$ticket_id}_user_{$user_id}";
$cached = Cache::get($cacheKey);
dd($cached); // Should return Carbon instance
```

#### Issue: Badge tidak muncul untuk new comments
**Check**:
1. Comment created_at > last viewed time?
2. User role betul (Pengadu atau Assigned Officer)?
3. Comment user_id matching role logic?

```sql
-- Check comments
SELECT id, ticket_id, user_id, created_at 
FROM ticket_comments 
WHERE ticket_id = ?;

-- Check chronology assignments
SELECT escalate_to, created_at
FROM ticket_chronologies
WHERE ticket_id = ? AND escalate_to IS NOT NULL;
```

#### Issue: Cache not working
**Check cache configuration**:
```bash
# .env file
CACHE_DRIVER=file # or redis, memcached

# Clear cache
php artisan cache:clear

# Test cache manually
Cache::put('test_key', 'test_value', now()->addMinutes(5));
dd(Cache::get('test_key')); // Should return 'test_value'
```

### 📊 Badge Count Formula

```
For Pengadu:
  Badge Count = Comments WHERE 
    - user_id IN (officer role IDs)
    - created_at > last_viewed_at (from cache)
    - ticket_id = current ticket

For Assigned Officer:
  Badge Count = Comments WHERE
    - user_id = ticket.user_id (pengadu)
    - created_at > last_viewed_at (from cache)
    - ticket_id = current ticket

For Other Users:
  Badge Count = 0 (no badge)
```

### 🎯 Business Logic Summary

| User Type | Sees Badge For | Badge Count Logic |
|-----------|----------------|-------------------|
| Pengadu (Creator) | Officer comments | Count comments from officer roles after last view |
| Assigned Officer | Pengadu comments | Count comments from ticket creator after last view |
| Non-Assigned Officer | Nothing | Always 0 |
| Other Users | Nothing | Always 0 |

### 📝 Notes & Considerations

- **Cache expiry**: 30 days (can be adjusted)
- **Per-user tracking**: Each user has independent badge state
- **Impersonation safe**: Cache persists across impersonation sessions
- **Performance**: Minimal queries (cached data, indexed lookups)
- **Real-time**: Badge updates on page load (not websocket/polling)
- **Sorting**: Comments displayed newest first for better UX
- **Animation**: Pulse effect draws user attention to new comments

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

### Issue 1: sla_days and sla_duedate masih NULL dalam ticket_chronologies
**Root Cause**: 
- cat_id dan subcat_id tidak dihantar dalam form request (NULL)
- SLA lookup gagal kerana missing parameters

**Solution**: 
```php
// Code sudah fixed - akan guna existing ticket's cat_id/subcat_id
$catId = $request->cat_id ?? $ticket->cat_id;
$subcatId = $request->subcat_id ?? $ticket->subcat_id;
```

**Verification**:
1. Check logs: `storage/logs/laravel-YYYY-MM-DD.log`
2. Search for: `SLA Lookup Input` and `SLA Calculation SUCCESS`
3. Ensure SLA data exists dalam `sla` table:
```sql
SELECT * FROM sla 
WHERE cat_id = ? AND subcat_id = ? AND sla_level = ?;
```

### Issue 2: Officer dropdown kosong
**Solution**: 
- Ensure officers configured dalam `officers` table dengan correct cat_id dan subcat_id
- Check level_tugasan matching

```sql
SELECT o.*, u.nama 
FROM officers o
JOIN users u ON o.user_id = u.id
WHERE o.cat_id = ? 
  AND o.subcat_id = ?
  AND o.deleted_at IS NULL;
```

### Issue 3: SLA due date tidak calculate correctly
**Solution**: 
- Check `Helpers::addWorkingDays()` function
- Verify public holidays table has data
- Ensure weekends are being skipped

```php
// Test working days calculation
$startDate = Carbon::now();
$dueDate = Helpers::addWorkingDays($startDate, 5);
Log::info('Test', ['start' => $startDate, 'due' => $dueDate]);
```

### Issue 4: Multiple chronologies created untuk same status
**Solution**: 
- Code already checks for existing chronology
- Will update instead of create duplicate

```php
$existingChronology = TicketChronology::where('ticket_id', $ticket->id)
    ->where('tick_status', $request->tick_status)
    ->orderBy('created_at', 'desc')
    ->first();
```

### Issue 5: Notification tidak sent
**Solution**: 
- Check queue worker running: `php artisan queue:work`
- Check notification service is injected properly
- Verify email configuration in `.env`

### Issue 6: Form submit tapi tidak update
**Solution**: 
- Check browser console untuk JavaScript errors
- Check validation errors dalam response
- Verify route exists: `php artisan route:list | grep ticket-assignment`

### Issue 7: Level pegtugas tidak betul
**Solution**: 
- Ensure officers table has correct level_tugasan value
- Check SLA table has matching sla_level

```sql
-- Check officer level
SELECT u.nama, o.level_tugasan, o.cat_id, o.subcat_id
FROM officers o
JOIN users u ON o.user_id = u.id
WHERE o.user_id = ?;

-- Check SLA for that level
SELECT * FROM sla
WHERE cat_id = ? AND subcat_id = ? AND sla_level = ?;
```

## 📝 Notes & Best Practices

### Critical Points
- Section only visible untuk authorized officers (based on role and assignment)
- **Multi-officer assignment**: Boleh assign multiple officers untuk 1 tiket
- Status change creates chronology entry automatically dengan complete SLA info
- **Smart category fallback**: Jika cat_id/subcat_id tidak diubah, akan guna existing ticket's values
- **SLA lookup priority**: 
  1. Try with cat_id + subcat_id + level_pegtugas + severity
  2. Fallback: Try without severity
  3. Default: 3 working days
- SLA calculated berdasarkan:
  - `sla` table lookup
  - level_pegtugas from officers table
  - Working days calculation (skip weekends & public holidays)
- Officers filtered based on:
  - Category/subcategory match
  - Level filtering (if level 1 assigned, show level 2)
- First officer stored dalam main chronology `escalate_to`
- Additional officers get separate chronology entries
- Notes optional tapi recommended untuk audit trail
- **Dynamic validation**: Category & subcategory wajib untuk "Aduan Dirujuk" status only
- Notifications sent to all stakeholders automatically
- Form uses UUID routing instead of ID

### Logging for Debugging
```php
// Key log entries to monitor:
Log::info('updateTicketAssignment - SLA Lookup Input', [
    'used_cat_id' => $catId,
    'used_subcat_id' => $subcatId,
    'sla_severity' => $slaSeverity,
    'level_pegtugas' => $levelPetugas
]);

Log::info('SLA Calculation SUCCESS', [
    'sla_days' => $slaDays,
    'sla_duedate' => $slaDueDate->format('Y-m-d H:i:s')
]);
```

### Testing Checklist
- [ ] Test with existing category (tidak ubah category) - should use ticket's cat_id
- [ ] Test dengan Aduan Dirujuk status - category wajib
- [ ] Test assign single officer
- [ ] Test assign multiple officers
- [ ] Test different severity levels (High/Medium/Low)
- [ ] Verify sla_days dan sla_duedate saved correctly
- [ ] Check working days calculation (skip weekends)
- [ ] Verify chronology created with all SLA fields
- [ ] Test notes/comments saved properly
- [ ] Verify notifications sent
- [ ] Check logs for SLA calculation

### Performance Considerations
- Officers query filtered early untuk reduce results
- Chronology check prevents duplicate entries
- Batch insert for multiple officers
- Use existing ticket data as fallback (mengurangkan database queries)
- Eager loading relationships before sending notifications
