# Dokumentasi: Form Log Tiket Baru

## 📋 Ringkasan
Feature ini membolehkan pengguna membuat/log tiket aduan baru dalam sistem. Terdapat 2 jenis tiket:
1. **Diri Sendiri** - Pengguna buat aduan untuk diri sendiri
2. **Bagi Pihak** - Pengguna (Wakil) buat aduan untuk pengguna lain (Pengadu)

## 🎯 Fungsi Utama
- Form validation (client-side & server-side)
- Auto-populate maklumat pengguna dari database SPP
- Search user untuk "Bagi Pihak" ticket
- Dynamic subcategory loading based on category
- Conditional serial number field (untuk kategori "Sewaan")
- File attachment support
- Real-time form validation feedback

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketController.php
├── store(StoreTicketRequest $request)  # Create new ticket
│   ├── Validate input
│   ├── Generate ticket_nolog (unique number)
│   ├── Auto-assign officer based on category/subcategory
│   ├── Create ticket record
│   ├── Create initial chronology ("Aduan Baru")
│   ├── Handle file attachments
│   └── Send notifications
```

```
app/Http/Controllers/Ticket/TicketSearchController.php
├── searchUser(Request $request)  # Search user for "Bagi Pihak"
│   └── Return user details from SPP database
```

### Request Validation
```
app/Http/Requests/Ticket/StoreTicketRequest.php
├── authorize() # Check user authentication
└── rules()     # Validation rules
    ├── ticket_type: required|in:1,2
    ├── ticket_title: required|string|max:255
    ├── ticket_desc: required|string
    ├── cat_id: required|exists:ref_categories
    ├── subcat_id: required|exists:ref_sub_categories
    ├── no_siri: required_if:category,Sewaan
    ├── user_id: required_if:ticket_type,2
    └── attachments.*: nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048
```

### Frontend (Views)
```
resources/views/tickets/partials/offcanvas-add.blade.php
├── Offcanvas panel (50% width)
├── Jenis Aduan selector (Diri Sendiri/Bagi Pihak)
│
├── Section: Maklumat Pengadu (Diri Sendiri)
│   ├── Auto-filled dari auth()->user()
│   ├── Read-only fields
│   └── Fields: nama, jawatan, skim, gred, email, sektor, 
│       bahagian, seksyen, unit, telefon
│
├── Section: Maklumat Pengadu (Bagi Pihak)
│   ├── User search dengan autocomplete
│   ├── Display selected user info
│   └── Same fields as above
│
├── Section: Maklumat Aduan (#ticketFormFields)
│   ├── Category selector (Select2)
│   ├── Subcategory selector (Select2, dynamic)
│   ├── Serial Number (conditional - Sewaan only)
│   ├── Tajuk Aduan (textarea)
│   └── Keterangan Aduan (textarea)
│
└── Section: Lampiran
    ├── Drag & drop zone
    ├── File preview
    └── Max 5 files, 2MB each
```

### JavaScript Files
```
resources/assets/js/ticket/create.js
├── Form initialization
│   ├── Select2 configuration
│   └── Reset form on offcanvas show/hide
│
├── Ticket type handler
│   └── Toggle Diri Sendiri / Bagi Pihak sections
│
├── Category change handler
│   ├── Load subcategories dynamically
│   └── Show/hide Serial Number field
│
├── Form validation
│   ├── validateAddForm()
│   ├── Check required fields based on ticket type
│   └── Display validation errors
│
└── Form submission
    ├── Prepare FormData with files
    ├── AJAX POST to /tickets
    ├── Handle success/error response
    └── Reload DataTable
```

```
resources/assets/js/ticket/search-user.js
├── User search functionality
│   ├── Autocomplete dropdown
│   ├── AJAX search request
│   └── Populate user fields
│
├── Auto-search on typing (debounced)
├── Clear search function
└── Error handling
```

```
resources/assets/js/ticket/attachment.js
├── File upload functionality (covered in separate doc)
└── Referenced in offcanvas-add.blade.php
```

### Routes
```
routes/web.php
├── POST   /tickets                         # store() - Create ticket
├── GET    /get-subcategory/{categoryId}   # Get subcategories
├── POST   /tickets/search-user            # searchUser() - Search for "Bagi Pihak"
└── Middleware: auth, verified
```

### Models
```
app/Models/Ticket.php
├── Fillable fields:
│   ├── uuid, ticket_nolog, ticket_type
│   ├── user_id, created_by
│   ├── cat_id, subcat_id, serial_number
│   ├── ticket_title, ticket_desc
│   ├── tick_status, sla_duedate
│   └── doc_id
│
└── Relationships:
    ├── user() - Pengadu
    ├── createdBy() - Pembuat tiket (Wakil)
    └── category(), subCategory(), status()
```

## 🔄 Flow Kerja

### 1. Open Form
```
User click "ADUAN BARU" button
→ Offcanvas slides in from right
→ Initialize Select2 dropdowns
→ Default to "Diri Sendiri"
→ Auto-populate authenticated user info
```

### 2A. Create Ticket (Diri Sendiri)
```javascript
// User Flow
1. Select "Diri Sendiri"
2. Maklumat Pengadu auto-filled (readonly)
3. Select Category
4. Select Subcategory (loaded dynamically)
5. [Optional] Enter Serial Number (if Sewaan)
6. Enter Tajuk Aduan
7. Enter Keterangan Aduan
8. [Optional] Upload attachments (max 5 files)
9. Click "Simpan"

// Backend Processing
POST /tickets
├── Validate: StoreTicketRequest
├── ticket_type = 1
├── user_id = auth()->user()->id
├── created_by = auth()->user()->id
├── Generate ticket_nolog (e.g., TKT-2026-0001)
├── Assign officer (auto based on cat + subcat)
├── Calculate SLA due date
├── Create Ticket record
├── Create TicketChronology (Status: "Aduan Baru")
├── Upload attachments to storage
├── Send notification to assigned officer
└── Return success response
```

### 2B. Create Ticket (Bagi Pihak)
```javascript
// User Flow
1. Select "Bagi Pihak"
2. Search for user (type name/email)
3. Select user from autocomplete dropdown
4. Maklumat Pengadu filled with selected user
5. [Continue same as Diri Sendiri from step 3]

// Backend Processing (Difference)
├── ticket_type = 2
├── user_id = selected_user_id (Pengadu)
├── created_by = auth()->user()->id (Wakil)
└── [Rest same as above]
```

### 3. Dynamic Subcategory Loading
```javascript
// create.js
$('#cat_id').change(function() {
    var catID = $(this).val();
    var catText = $('#cat_id option:selected').text();
    
    // Show Serial Number field if "Sewaan"
    if (catText === 'Sewaan') {
        showSerialNumberField();
    } else {
        hideSerialNumberField();
    }
    
    // Load subcategories
    $.ajax({
        url: '/get-subcategory/' + catID,
        success: function(response) {
            $('#subcat_id').empty();
            $.each(response.data, function(key, subcat) {
                $('#subcat_id').append(
                    '<option value="' + subcat.id + '">' + 
                    subcat.subcat_name + '</option>'
                );
            });
        }
    });
});
```

## 📝 Form Fields Details

### A. Jenis Aduan
```html
<select id="ticketType" name="ticket_type" required>
    <option value="1">Diri Sendiri</option>
    <option value="2">Bagi Pihak</option>
</select>
```

### B. Maklumat Pengadu (Auto-populated)
| Field | Source | Editable |
|-------|--------|----------|
| Nama | auth()->user()->nama | ✗ |
| Jawatan | auth()->user()->jawatan_name | ✗ |
| Skim | auth()->user()->skim->nama_skim | ✗ |
| Gred | auth()->user()->nama_gred | ✗ |
| Email | auth()->user()->email | ✗ |
| Sektor | auth()->user()->sektor->nama_sektor | ✗ |
| Bahagian | auth()->user()->bahagian->nama_bah | ✗ |
| Seksyen | auth()->user()->seksyen->nama_sec | ✗ |
| Unit | auth()->user()->unit->nama_unit | ✗ |
| Telefon Pejabat | auth()->user()->tel_no_p | ✗ |
| Telefon Bimbit | auth()->user()->tel_no_hp | ✗ |

### C. Maklumat Aduan
| Field | Type | Required | Max Length | Notes |
|-------|------|----------|------------|-------|
| Kategori | Select | ✓ | - | From ref_categories |
| Sub Kategori | Select | ✓ | - | Loaded dynamically |
| No. Siri | Text | Conditional | 100 | Only if category = "Sewaan" |
| Tajuk Aduan | Textarea | ✓ | 255 | Brief title |
| Keterangan | Textarea | ✓ | - | Detailed description |

### D. Lampiran
| Constraint | Value |
|------------|-------|
| Max Files | 5 |
| Max Size per File | 2MB |
| Allowed Types | PDF, DOC, DOCX, JPG, JPEG, PNG |
| Storage Path | storage/ticket_attachments/ |
| Naming | {ticket_nolog}-{count}.{extension} |

## 🔐 Validation Rules

### Client-Side Validation
```javascript
// create.js - validateAddForm()

function validateAddForm() {
    var errors = {};
    var ticketType = $('#ticketType').val();
    
    if (ticketType == '1') {
        // Diri Sendiri - validate auth user
        if (!$('[name="name"]').val()) {
            errors['name'] = ['Nama diperlukan'];
        }
    } else if (ticketType == '2') {
        // Bagi Pihak - validate selected user
        if (!$('[name="other_name"]').val()) {
            errors['other_name'] = ['Sila pilih pengguna'];
        }
    }
    
    // Common validation
    if (!$('#cat_id').val()) {
        errors['cat_id'] = ['Sila pilih kategori'];
    }
    if (!$('#subcat_id').val()) {
        errors['subcat_id'] = ['Sila pilih sub kategori'];
    }
    if (!$('[name="ticket_title"]').val()) {
        errors['ticket_title'] = ['Tajuk aduan diperlukan'];
    }
    if (!$('[name="ticket_desc"]').val()) {
        errors['ticket_desc'] = ['Keterangan diperlukan'];
    }
    
    // Serial number (if Sewaan)
    var catText = $('#cat_id option:selected').text();
    if (catText === 'Sewaan' && !$('[name="no_siri"]').val()) {
        errors['no_siri'] = ['No. siri diperlukan untuk Sewaan'];
    }
    
    return Object.keys(errors).length === 0;
}
```

### Server-Side Validation
```php
// StoreTicketRequest.php

public function rules()
{
    $rules = [
        'ticket_type' => 'required|in:1,2',
        'ticket_title' => 'required|string|max:255',
        'ticket_desc' => 'required|string',
        'cat_id' => 'required|exists:ref_categories,id',
        'subcat_id' => 'required|exists:ref_sub_categories,id',
    ];
    
    // Conditional: Serial number for "Sewaan"
    $category = \App\Models\RefCategory::find($this->cat_id);
    if ($category && $category->cat_name === 'Sewaan') {
        $rules['no_siri'] = 'required|string|max:100';
    }
    
    // Conditional: User ID for "Bagi Pihak"
    if ($this->ticket_type == 2) {
        $rules['user_id'] = 'required|exists:users,id';
    }
    
    // File attachments
    $rules['attachments'] = 'nullable|array|max:5';
    $rules['attachments.*'] = 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048';
    
    return $rules;
}
```

## 🔍 User Search Feature (Bagi Pihak)

### Search Input
```html
<input type="text" id="search_name" 
       placeholder="Contoh: Ahmad Bin Ali atau ahmad.ali">
<button id="searchUserBtn">Cari</button>
```

### Search Flow
```javascript
// search-user.js

1. User types in search box
2. Debounce 300ms
3. AJAX POST /tickets/search-user
4. Backend searches nama + email (LIKE)
5. Return matched users (max 10)
6. Display in autocomplete dropdown
7. User clicks result
8. Populate all fields
9. Show "Cari Pengguna Lain" button
```

### Backend Search
```php
// TicketSearchController@searchUser

$users = User::where('nama', 'LIKE', "%{$search}%")
             ->orWhere('email', 'LIKE', "%{$search}%")
             ->limit(10)
             ->get();

return response()->json([
    'success' => true,
    'users' => $users
]);
```

## 🎨 Select2 Configuration

```javascript
// create.js

$('#cat_id').select2({
    placeholder: 'Pilih Kategori',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#offcanvasAddTicket'),
    theme: 'bootstrap-5'
});

$('#subcat_id').select2({
    placeholder: 'Pilih Sub Kategori',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#offcanvasAddTicket'),
    theme: 'bootstrap-5'
});
```

**Important**: `dropdownParent` must be set to offcanvas untuk elakkan z-index issues.

## 📤 Form Submission

### AJAX Submit
```javascript
// create.js

$('#addTicketForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate
    if (!validateAddForm()) {
        return;
    }
    
    // Prepare FormData (for file upload)
    var formData = new FormData(this);
    
    // Add attachments from global array
    if (window.selectedAttachments) {
        window.selectedAttachments.forEach(file => {
            formData.append('attachments[]', file);
        });
    }
    
    // Submit
    $.ajax({
        url: '/tickets',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            toastr.success('Tiket berjaya dicipta!');
            $('#offcanvasAddTicket').offcanvas('hide');
            window.reloadAllTables();
        },
        error: function(xhr) {
            displayValidationErrors(xhr.responseJSON.errors);
        }
    });
});
```

## 🔔 Notifications After Create

```php
// TicketController@store

// 1. Send to assigned officer
$officer->notify(new NewTicketAssignedNotification($ticket));

// 2. Send to complainant (if Bagi Pihak)
if ($ticket->ticket_type == 2) {
    $ticket->user->notify(new TicketCreatedForYouNotification($ticket));
}

// 3. Log chronology
TicketChronology::create([
    'ticket_id' => $ticket->id,
    'tick_status' => $statusAduanBaru->id,
    'frm_user_id' => auth()->id(),
    'escalate_to' => $assignedOfficer->id,
]);
```

## 🐛 Common Issues & Solutions

### Issue 1: Select2 dropdown behind offcanvas
**Solution**: Set `dropdownParent: $('#offcanvasAddTicket')`

### Issue 2: Subcategory tidak load
**Solution**: Check AJAX URL dan ensure category ID valid

### Issue 3: File upload gagal
**Solution**: Check file size, type, dan ensure `enctype="multipart/form-data"`

### Issue 4: Serial Number tidak hide/show
**Solution**: Check category name exact match "Sewaan" (case-sensitive)

### Issue 5: User search tidak jumpa
**Solution**: Ensure user exists dalam database SPP dan nama/email betul

## 📱 Responsive Design

- Offcanvas width: 50% on desktop, 100% on mobile
- Form fields stack vertically on mobile
- Touch-friendly buttons dan inputs
- Auto-scroll to top when offcanvas opens

## 🚀 Performance Tips

1. **Debounce user search** - Avoid excessive API calls
2. **Lazy load subcategories** - Only when category selected
3. **Client-side validation first** - Reduce server load
4. **FormData for files** - Efficient binary upload
5. **Reset form on close** - Prevent memory leaks

## 📝 Notes
- Maklumat pengguna dari SPP database (read-only)
- Ticket number auto-generated (format: TKT-YYYY-9999)
- Officer auto-assigned based on category/subcategory/load balancing
- SLA due date auto-calculated based on severity
- Initial status always "Aduan Baru"
- Chronology entry created automatically
