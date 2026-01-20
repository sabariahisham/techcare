# Dokumentasi: Ticket Attachment (Lampiran Fail)

## 📋 Ringkasan
Feature ini menguruskan upload, download, view, dan delete attachment/lampiran fail untuk tiket. Setiap tiket boleh ada maksimum 5 fail dengan saiz maksimum 2MB setiap satu.

## 🎯 Fungsi Utama
- Upload multiple files (drag & drop atau click to browse)
- Real-time file preview dengan file type icons
- Download attachment
- View attachment dalam browser (PDF, images)
- Delete attachment (with permission check)
- File validation (size, type, count)
- Progress indication during upload

## 📂 Fail-fail Yang Terlibat

### Backend (Controllers)
```
app/Http/Controllers/Ticket/TicketAttachmentController.php
├── upload(StoreAttachmentRequest $request)
│   ├── Validate files
│   ├── Generate unique filename (ticket_nolog-count.ext)
│   ├── Store files to storage/ticket_attachments
│   ├── Create Document records
│   └── Update ticket.doc_id (first attachment)
│
├── download(DownloadAttachmentRequest $request, Document $document)
│   ├── Authorize user access
│   ├── Check if view or download (query param)
│   └── Return file response
│
└── destroy(DeleteAttachmentRequest $request, Document $document)
    ├── Authorize user access
    ├── Delete file from storage
    ├── Delete Document record
    └── Update ticket.doc_id if necessary
```

### Request Validation
```
app/Http/Requests/Ticket/StoreAttachmentRequest.php
├── authorize() # Check user can upload to this ticket
└── rules()
    ├── ticket_id: required|exists:tickets,id
    ├── attachments: required|array|max:5
    └── attachments.*: file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048
```

```
app/Http/Requests/Ticket/DownloadAttachmentRequest.php
├── authorize() # Check user can download this attachment
└── rules() # No specific rules, auth only
```

```
app/Http/Requests/Ticket/DeleteAttachmentRequest.php
├── authorize() # Check user can delete (must be creator)
└── rules() # No specific rules, auth only
```

### Frontend (Views)
```
resources/views/tickets/show.blade.php (Line ~280-320)
├── Attachment display section
│   ├── Loop through $ticket->attachments
│   ├── Show file icon based on extension
│   ├── Show file name & upload date
│   ├── View button (opens in new tab)
│   └── Download button
│
resources/views/tickets/partials/offcanvas-add.blade.php (Line ~280-340)
├── Drag & drop zone (#attachment-drop-zone)
├── Hidden file input (#ticket-attachments)
├── Preview area (#attachments-preview)
└── Max file notice
│
resources/views/tickets/partials/offcanvas-edit.blade.php
└── Similar structure untuk edit form
```

### JavaScript Files
```
resources/assets/js/ticket/attachment.js
├── initializeFileUpload(dropZoneId, fileInputId, previewId, offcanvasId)
│   ├── Setup drag & drop events
│   ├── File input change handler
│   └── Click to browse functionality
│
├── handleFiles(files)
│   ├── Validate file count (max 5)
│   ├── Validate each file (size, type)
│   ├── Add to selectedAttachments array
│   └── Generate preview
│
├── validateFile(file)
│   ├── Check file size (max 2MB)
│   ├── Check file type (pdf,doc,docx,jpg,jpeg,png)
│   └── Return validation result
│
├── generateFilePreview(file, index)
│   ├── Create preview HTML
│   ├── Show file icon
│   ├── Show file name & size
│   └── Add remove button
│
└── removeFile(index)
    ├── Remove from selectedAttachments
    └── Update preview display
```

```
resources/assets/js/ticket/create.js
└── Form submission includes attachments from selectedAttachments array
```

### Routes
```
routes/web.php
├── POST   /ticket-attachments/upload          # upload()
├── GET    /ticket-attachments/{document}/download # download()
├── DELETE /ticket-attachments/{document}      # destroy()
└── Middleware: auth, verified

Route names:
├── ticket-attachments.upload
├── ticket-attachments.download
└── ticket-attachments.destroy
```

### Models
```
app/Models/Document.php
├── Table: documents
├── Fields:
│   ├── id, uuid
│   ├── doc_id (ticket_id)
│   ├── doc_jenis ('TICKET')
│   ├── doc_name (original filename)
│   ├── doc_storage_nm (stored filename)
│   ├── doc_mime (mime type)
│   ├── created_by, updated_by
│   └── timestamps
│
└── Relationships:
    └── ticket() - BelongsTo Ticket (via doc_id)

app/Models/Ticket.php
├── doc_id (reference to first attachment)
└── attachments() - HasMany Document
    └── where('doc_jenis', 'TICKET')
```

## 🔄 Flow Kerja

### 1. Upload Attachments (During Ticket Creation)

```javascript
// User Flow
1. User drags files to drop zone OR clicks to browse
2. Files validated (count, size, type)
3. Preview generated with file details
4. User can remove files before submit
5. User clicks "Simpan" on ticket form
6. Files included in FormData
7. AJAX POST to /tickets (ticket creation)
8. Files uploaded in TicketController@store
9. Calls TicketAttachmentController for processing

// Backend Flow (in TicketController@store)
if ($request->hasFile('attachments')) {
    $attachmentRequest = new StoreAttachmentRequest([
        'ticket_id' => $ticket->id,
        'attachments' => $request->file('attachments')
    ]);
    
    app(TicketAttachmentController::class)
        ->upload($attachmentRequest);
}
```

### 2. Upload Attachments (After Ticket Created)

```javascript
// Standalone upload endpoint
POST /ticket-attachments/upload
{
    ticket_id: 123,
    attachments: [File, File, ...]
}

// Backend Processing
1. Validate request (StoreAttachmentRequest)
2. Get ticket and current attachment count
3. Check if total <= 5 files
4. Loop through files:
   a. Generate unique filename (ticket_nolog-count.ext)
   b. Store to storage/app/public/ticket_attachments
   c. Create Document record
5. Update ticket.doc_id if first attachment
6. Return success response with file details
```

### 3. File Naming Convention

```php
// TicketAttachmentController@upload

// Example: TKT-2026-0001-1.pdf, TKT-2026-0001-2.jpg

$currentCount = Document::where('doc_id', $ticket->id)
                        ->where('doc_jenis', 'TICKET')
                        ->count();

foreach ($files as $file) {
    $attachmentCount = $currentCount + $fileIndex;
    $extension = $file->getClientOriginalExtension();
    $storageFileName = $ticket->ticket_nolog . '-' . $attachmentCount . '.' . $extension;
    
    $path = $file->storeAs('ticket_attachments', $storageFileName, 'public');
}
```

### 4. Download Attachment

```javascript
// View in browser (PDF, images)
GET /ticket-attachments/{document_id}/download?view=1

// Download to computer
GET /ticket-attachments/{document_id}/download

// Backend
public function download(Document $document, Request $request)
{
    // Authorize
    if (!$this->authorizeDownload($document)) {
        abort(403);
    }
    
    $filePath = storage_path('app/public/ticket_attachments/' . $document->doc_storage_nm);
    
    if ($request->query('view') == 1) {
        // View in browser
        return response()->file($filePath, [
            'Content-Type' => $document->doc_mime,
            'Content-Disposition' => 'inline; filename="' . $document->doc_name . '"'
        ]);
    } else {
        // Download
        return response()->download($filePath, $document->doc_name);
    }
}
```

### 5. Delete Attachment

```javascript
// Only ticket creator can delete before submit
// After submit, only admin can delete

DELETE /ticket-attachments/{document_id}

// Backend
public function destroy(Document $document)
{
    // Authorize
    if ($document->created_by !== auth()->id()) {
        abort(403, 'Hanya pencipta boleh memadam lampiran');
    }
    
    // Delete file from storage
    Storage::delete('public/ticket_attachments/' . $document->doc_storage_nm);
    
    // Delete DB record
    $document->delete();
    
    // Update ticket.doc_id if this was the first attachment
    if ($ticket->doc_id == $document->id) {
        $nextAttachment = $ticket->attachments()->first();
        $ticket->update([
            'doc_id' => $nextAttachment ? $nextAttachment->id : null
        ]);
    }
    
    return response()->json(['success' => true]);
}
```

## 📝 File Constraints

### Allowed File Types
| Type | Extensions | Icon | Color |
|------|-----------|------|-------|
| PDF | .pdf | bxs-file-pdf | text-danger |
| Word | .doc, .docx | bxs-file-doc | text-primary |
| Image | .jpg, .jpeg, .png | bxs-file-image | text-success |

### File Size Limits
```javascript
// attachment.js
const maxFileSize = 2 * 1024 * 1024; // 2MB
const maxFiles = 5;

function validateFile(file) {
    // Check size
    if (file.size > maxFileSize) {
        return {
            valid: false,
            message: 'Saiz fail melebihi 2MB'
        };
    }
    
    // Check type
    const allowedTypes = {
        'application/pdf': ['.pdf'],
        'application/msword': ['.doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
        'image/jpeg': ['.jpg', '.jpeg'],
        'image/png': ['.png']
    };
    
    if (!allowedTypes[file.type]) {
        return {
            valid: false,
            message: 'Jenis fail tidak dibenarkan'
        };
    }
    
    return { valid: true };
}
```

## 🎨 Drag & Drop Zone

### HTML Structure
```html
<div id="attachment-drop-zone" class="file-drop-zone">
    <input type="file" id="ticket-attachments" 
           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
           multiple hidden>
    
    <div class="drop-zone-content">
        <i class="bx bx-cloud-upload"></i>
        <p>Seret fail ke sini atau klik untuk pilih</p>
        <small>Maksimum 5 fail (PDF, DOC, DOCX, JPG, PNG), 2MB setiap satu</small>
    </div>
</div>

<div id="attachments-preview"></div>
```

### CSS Styling
```css
.file-drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.file-drop-zone.dragover {
    border-color: #007bff;
    background-color: #f0f8ff;
}

.file-drop-zone.error {
    border-color: #dc3545;
    background-color: #fff5f5;
}
```

### JavaScript Events
```javascript
// attachment.js

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

// Highlight on drag over
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

// Remove highlight on drag leave/drop
['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

// Handle drop
dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const files = e.dataTransfer.files;
    handleFiles(files);
}
```

## 📊 File Preview Display

### Preview HTML Structure
```html
<div class="file-preview-item" data-index="0">
    <div class="file-icon">
        <i class="bx bxs-file-pdf text-danger"></i>
    </div>
    <div class="file-info">
        <div class="file-name">document.pdf</div>
        <div class="file-size">1.5 MB</div>
    </div>
    <button type="button" class="btn-remove-file">
        <i class="bx bx-x"></i>
    </button>
</div>
```

### Generated by JavaScript
```javascript
function generateFilePreview(file, index) {
    const extension = file.name.split('.').pop().toLowerCase();
    let iconClass = 'bx-file';
    let iconColor = 'text-muted';
    
    if (extension === 'pdf') {
        iconClass = 'bxs-file-pdf';
        iconColor = 'text-danger';
    } else if (['doc', 'docx'].includes(extension)) {
        iconClass = 'bxs-file-doc';
        iconColor = 'text-primary';
    } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
        iconClass = 'bxs-file-image';
        iconColor = 'text-success';
    }
    
    return `
        <div class="file-preview-item" data-index="${index}">
            <i class="bx ${iconClass} ${iconColor} fs-4"></i>
            <span class="file-name">${file.name}</span>
            <span class="file-size">(${formatFileSize(file.size)})</span>
            <button type="button" class="btn-remove" onclick="removeFile(${index})">
                <i class="bx bx-x"></i>
            </button>
        </div>
    `;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
```

## 🔐 Authorization Rules

### Upload Permission
```php
// StoreAttachmentRequest@authorize()

public function authorize()
{
    $ticket = Ticket::find($this->ticket_id);
    
    // User must be:
    // 1. Ticket creator (created_by)
    // 2. Ticket owner (user_id)
    // 3. Assigned officer
    // 4. Admin/Helpdesk
    
    return $ticket && (
        $ticket->created_by === auth()->id() ||
        $ticket->user_id === auth()->id() ||
        $this->isAssignedOfficer($ticket) ||
        auth()->user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Helpdesk On Duty'])
    );
}
```

### Download Permission
```php
// DownloadAttachmentRequest@authorize()

public function authorize()
{
    $document = $this->route('document');
    $ticket = Ticket::find($document->doc_id);
    
    // Anyone involved with the ticket can download
    return $ticket && (
        $ticket->created_by === auth()->id() ||
        $ticket->user_id === auth()->id() ||
        $this->isAssignedOfficer($ticket) ||
        auth()->user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Helpdesk On Duty', 'Penyelia Helpdesk'])
    );
}
```

### Delete Permission
```php
// DeleteAttachmentRequest@authorize()

public function authorize()
{
    $document = $this->route('document');
    
    // Only creator can delete their own attachment
    // OR admin can delete any attachment
    return $document->created_by === auth()->id() ||
           auth()->user()->hasRole('Administrator');
}
```

## 🎯 Display Attachments (View Page)

```blade
{{-- resources/views/tickets/show.blade.php --}}

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
                            <div class="flex-grow-1">
                                <span class="fw-medium">{{ $attachment->doc_name }}</span>
                                <small class="text-muted d-block">
                                    Dimuat naik: {{ $attachment->created_at->format('d M Y, H:i') }}
                                </small>
                            </div>
                            
                            {{-- View button --}}
                            <a href="{{ route('ticket-attachments.download', ['document' => $attachment->id, 'view' => 1]) }}"
                               class="btn btn-sm btn-outline-info me-1"
                               target="_blank"
                               data-bs-toggle="tooltip"
                               title="Lihat {{ $attachment->doc_name }}">
                                <i class="bx bx-show"></i>
                            </a>
                            
                            {{-- Download button --}}
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

## 🔧 Storage Configuration

### Laravel Storage Setup
```php
// config/filesystems.php

'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
]
```

### Symbolic Link
```bash
# Create storage link (run once)
php artisan storage:link
```

### File Path Structure
```
storage/
└── app/
    └── public/
        └── ticket_attachments/
            ├── TKT-2026-0001-1.pdf
            ├── TKT-2026-0001-2.jpg
            ├── TKT-2026-0002-1.docx
            └── ...
```

## 🐛 Common Issues & Solutions

### Issue 1: Files tidak upload
**Solution**: 
- Check `php.ini`: `upload_max_filesize`, `post_max_size`
- Check storage permissions: `chmod -R 775 storage/`
- Ensure `storage:link` executed

### Issue 2: File preview tidak show icon
**Solution**: Check file extension matching dalam `generateFilePreview()`

### Issue 3: Drag & drop tidak working
**Solution**: Ensure `preventDefaults()` called untuk semua drag events

### Issue 4: Download fail 404
**Solution**: Check file exists dalam storage dan `storage:link` configured

### Issue 5: Max file count exceeded
**Solution**: Client-side validation + server-side validation (max 5)

## 📱 Responsive Design

### Mobile View
- File preview items stack vertically
- Touch-friendly buttons (larger tap targets)
- Simplified icon display
- Scroll for multiple attachments

### Desktop View
- File preview in grid layout
- Hover effects on preview items
- Larger icons and spacing

## 🚀 Performance Optimization

1. **Chunked Upload** (for large files) - Not implemented yet
2. **Image Thumbnails** - Generate thumbnails for images
3. **Lazy Loading** - Load attachments on demand
4. **CDN** - Serve static files from CDN
5. **Compression** - Compress images before upload

## 📝 Notes
- File naming convention: `{ticket_nolog}-{count}.{extension}`
- First attachment ID stored in `ticket.doc_id`
- `doc_jenis` always 'TICKET' untuk ticket attachments
- Soft delete tidak implemented - permanent delete
- No versioning - latest upload overwrites
- MIME type stored untuk proper Content-Type headers
