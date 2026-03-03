# Dokumentasi: Ticket Form Validation & Auto-Scroll

## 📋 Ringkasan
Dokumentasi lengkap tentang form validation untuk Add Ticket dan Edit Ticket termasuk auto-scroll ke error field, error display, dan client/server-side validation.

**Last Updated**: March 3, 2026

## 🎯 Key Features
- ✅ Client-side validation (real-time)
- ✅ Server-side validation (Laravel Request)
- ✅ Auto-scroll to first validation error
- ✅ Visual error indicators (red borders, error text)
- ✅ Select2 field validation support
- ✅ Dynamic error messages in Bahasa Melayu
- ✅ Consistent error display for both Add and Edit forms

---

## 📂 Fail-fail Yang Terlibat

### JavaScript Files
```
resources/assets/js/ticket/
├── create.js        # Add ticket form validation
├── edit.js          # Edit ticket form validation
├── config.js        # Global validation functions
└── attachment.js    # File upload validation
```

### PHP Request Validation
```
app/Http/Requests/Ticket/
├── StoreTicketRequest.php   # Validation for creating tickets
└── UpdateTicketRequest.php  # Validation for editing tickets
```

### Blade Templates
```
resources/views/tickets/partials/
├── offcanvas-add.blade.php   # Add ticket form
└── offcanvas-edit.blade.php  # Edit ticket form
```

---

## 🔄 Validation Flow

### 1. Client-Side Validation (JavaScript)

#### Add Ticket Form (`create.js`)
```javascript
function validateAddForm() {
    clearValidationErrors();
    var isValid = true;
    var errors = {};
    var ticketType = $('#ticketType').val();

    // Validate based on ticket type
    if (ticketType == '1') {
        // Diri Sendiri - validate authenticated user fields
        var nameField = $('[name="name"]');
        var emailField = $('[name="email"]');
        
        if (!nameField.prop('readonly') && nameField.is(':visible')) {
            var name = nameField.val();
            if (!name || name.trim() === '') {
                errors['name'] = ['Nama diperlukan.'];
                isValid = false;
            }
        }
    } else if (ticketType == '2') {
        // Bagi Pihak - validate other person fields
        if ($('#foundUserInfo').is(':visible')) {
            var otherName = $('#other_name').val();
            var otherEmail = $('#other_email').val();
            
            if (!otherName || otherName.trim() === '') {
                errors['other_name'] = ['Nama pengadu diperlukan.'];
                isValid = false;
            }
        } else {
            errors['user_search'] = ['Sila cari dan pilih pengguna terlebih dahulu.'];
            isValid = false;
        }
    }

    // Common validations
    if (!$('[name="tick_title"]').val()?.trim()) {
        errors['tick_title'] = ['Tajuk aduan diperlukan.'];
        isValid = false;
    }

    if (!$('[name="cat_id"]').val()) {
        errors['cat_id'] = ['Sila pilih kategori.'];
        isValid = false;
    }

    if (!$('[name="subcat_id"]').val()) {
        errors['subcat_id'] = ['Sila pilih sub kategori.'];
        isValid = false;
    }

    if (!$('[name="tick_desc"]').val()?.trim()) {
        errors['tick_desc'] = ['Keterangan aduan diperlukan.'];
        isValid = false;
    }

    // Location validations
    if (!$('[name="building_loc"]').val()) {
        errors['building_loc'] = ['Sila pilih lokasi bangunan.'];
        isValid = false;
    }

    if (!$('[name="block_loc"]').val()) {
        errors['block_loc'] = ['Sila pilih blok.'];
        isValid = false;
    }

    if (!$('[name="level_loc"]').val()) {
        errors['level_loc'] = ['Sila pilih aras.'];
        isValid = false;
    }

    // File attachment validation
    var attachmentValidation = validateAttachments();
    if (!attachmentValidation.isValid) {
        Object.assign(errors, attachmentValidation.errors);
        isValid = false;
    }

    if (!isValid) {
        displayValidationErrors(errors);
    }

    return isValid;
}
```

#### Edit Ticket Form (`edit.js`)
```javascript
function validateEditForm() {
    var isValid = true;
    var errors = {};

    // Clear previous validation
    clearValidationErrors();

    // Validate required fields
    var requiredFields = [
        { name: 'tick_title', label: 'Tajuk aduan' },
        { name: 'tick_desc', label: 'Keterangan aduan' },
        { name: 'cat_id', label: 'Kategori' },
        { name: 'subcat_id', label: 'Sub kategori' },
        { name: 'building_loc', label: 'Lokasi bangunan' },
        { name: 'block_loc', label: 'Blok' },
        { name: 'level_loc', label: 'Aras' }
    ];

    // Add conditional required fields based on ticket type
    var ticketType = $('#editTicketType').val();
    if (ticketType == '2') {
        requiredFields.push({ name: 'other_email', label: 'Emel pengadu' });
    }

    requiredFields.forEach(function (field) {
        var value = $('#editTicketForm [name="' + field.name + '"]').val();
        if (!value || value.trim() === '') {
            errors[field.name] = [field.label + ' diperlukan.'];
            isValid = false;
        }
    });

    // Validate email format if provided
    var emailField = ticketType == '2' ? 'other_email' : 'email';
    var emailValue = $('#editTicketForm [name="' + emailField + '"]').val();
    if (emailValue && !isValidEmail(emailValue)) {
        errors[emailField] = ['Format emel tidak sah.'];
        isValid = false;
    }

    if (!isValid) {
        displayValidationErrors(errors);
    }

    return isValid;
}
```

---

## 🎨 Auto-Scroll to Validation Error

### Implementation (Global Function - `config.js`)

```javascript
window.displayValidationErrors = function (errors) {
    console.log('displayValidationErrors called with:', errors);
    clearValidationErrors();

    Object.keys(errors).forEach(function (fieldName) {
        var field;
        var errorMessage = errors[fieldName][0];

        // Handle array-based field names like attachments.0, attachments.1
        if (fieldName.includes('.')) {
            var baseFieldName = fieldName.split('.')[0];
            field = $('[name="' + baseFieldName + '[]"]');
            if (field.length === 0) {
                field = $('[name="' + baseFieldName + '"]');
            }
        } else {
            field = $('[name="' + fieldName + '"]');
        }

        // Skip validation display for readonly fields
        if (field.prop('readonly')) {
            return;
        }

        // Skip if field not found
        if (field.length === 0) {
            return;
        }

        // Handle Select2 fields (Kategori and Sub Kategori)
        if (field.hasClass('select2-hidden-accessible')) {
            field.next('.select2-container').addClass('is-invalid');
            field.next('.select2-container').after(
                '<div class="invalid-feedback d-block">' + errorMessage + '</div>'
            );
        } else {
            // Handle regular fields and file inputs
            field.addClass('is-invalid');

            // Check if field is in a form-field-wrapper (for file inputs)
            var wrapper = field.closest('.form-field-wrapper');
            if (wrapper.length > 0) {
                var errorContainer = wrapper.find('.error-text');
                if (errorContainer.length > 0) {
                    errorContainer.text(errorMessage).show();
                } else {
                    field.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
                }
            } else {
                field.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
            }
        }
    });

    // AUTO-SCROLL TO FIRST ERROR
    setTimeout(function () {
        var firstInvalidField = $('.is-invalid').first();
        if (firstInvalidField.length > 0) {
            var scrollTarget = firstInvalidField;
            
            // If it's a Select2 field, scroll to the container instead
            if (firstInvalidField.hasClass('select2-hidden-accessible')) {
                scrollTarget = firstInvalidField.siblings('.select2-container').first();
            }
            
            // Determine which offcanvas is open
            var offcanvasBody = $('#offcanvasAddTicket .offcanvas-body');
            if (!offcanvasBody.is(':visible')) {
                offcanvasBody = $('#offcanvasEditTicket .offcanvas-body');
            }
            
            // Scroll within offcanvas if available
            if (offcanvasBody.length > 0 && offcanvasBody.is(':visible') && scrollTarget.length > 0) {
                var fieldTop = scrollTarget.offset().top;
                var offcanvasTop = offcanvasBody.offset().top;
                var scrollPosition = offcanvasBody.scrollTop() + (fieldTop - offcanvasTop) - 100;
                
                offcanvasBody.animate({
                    scrollTop: scrollPosition
                }, 500);
            } else if (scrollTarget.length > 0) {
                // Fallback to page scroll
                $('html, body').animate({
                    scrollTop: scrollTarget.offset().top - 100
                }, 500);
            }
            
            // Focus on the field
            if (!firstInvalidField.hasClass('select2-hidden-accessible') && 
                firstInvalidField.attr('type') !== 'file') {
                firstInvalidField.focus();
            } else if (firstInvalidField.hasClass('select2-hidden-accessible')) {
                // Briefly open Select2 dropdown to draw attention
                try {
                    firstInvalidField.select2('open');
                    setTimeout(function () {
                        firstInvalidField.select2('close');
                    }, 100);
                } catch (e) {
                    console.log('Could not open Select2:', e);
                }
            }
        }
    }, 150);
};
```

### Form-Specific Auto-Scroll

#### Edit Form (`edit.js`)
```javascript
function displayValidationErrors(errors) {
    clearValidationErrors();

    Object.keys(errors).forEach(function (fieldName) {
        var fieldElement = $('#editTicketForm [name="' + fieldName + '"]');
        var errorMessage = errors[fieldName][0];

        if (fieldElement.length > 0) {
            // Skip readonly fields
            if (fieldElement.prop('readonly')) {
                return;
            }

            fieldElement.addClass('is-invalid');

            // Handle Select2 fields
            if (fieldElement.hasClass('select2-hidden-accessible')) {
                fieldElement.siblings('.select2-container').addClass('is-invalid');
                var feedbackElement = fieldElement.siblings('.invalid-feedback');
                if (feedbackElement.length > 0) {
                    feedbackElement.addClass('d-block').text(errorMessage).show();
                } else {
                    fieldElement.siblings('.select2-container').after(
                        '<div class="invalid-feedback d-block">' + errorMessage + '</div>'
                    );
                }
            } else {
                // Handle regular fields
                var feedbackElement = fieldElement.siblings('.invalid-feedback');
                if (feedbackElement.length > 0) {
                    feedbackElement.addClass('d-block').text(errorMessage).show();
                } else {
                    fieldElement.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
                }
            }
        }
    });

    // Auto-scroll to first invalid field
    setTimeout(function () {
        var firstInvalidField = $('#editTicketForm .is-invalid').first();
        if (firstInvalidField.length > 0) {
            var scrollTarget = firstInvalidField;
            
            if (firstInvalidField.hasClass('select2-hidden-accessible')) {
                scrollTarget = firstInvalidField.siblings('.select2-container').first();
            }
            
            var offcanvasBody = $('#offcanvasEditTicket .offcanvas-body');
            if (offcanvasBody.length > 0 && scrollTarget.length > 0) {
                var fieldTop = scrollTarget.offset().top;
                var offcanvasTop = offcanvasBody.offset().top;
                var scrollPosition = offcanvasBody.scrollTop() + (fieldTop - offcanvasTop) - 100;
                
                offcanvasBody.animate({
                    scrollTop: scrollPosition
                }, 500);
            }
            
            // Focus on the field
            if (!firstInvalidField.hasClass('select2-hidden-accessible')) {
                firstInvalidField.focus();
            } else {
                firstInvalidField.select2('open');
                setTimeout(function () {
                    firstInvalidField.select2('close');
                }, 100);
            }
        }
    }, 150);
}
```

---

## 🎯 Visual Error Indicators

### CSS Styling (`offcanvas-add.blade.php` & `offcanvas-edit.blade.php`)

```css
.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.invalid-feedback.d-block {
    display: block !important;
}

/* For file inputs */
.file-drop-zone.error {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.1);
}

.file-drop-zone.error + .files-preview + .invalid-feedback {
    display: block !important;
}

/* Select2 validation styling */
.select2-container.is-invalid + .invalid-feedback {
    display: block !important;
}
```

### Error Display Examples

1. **Regular Input Field**
```html
<input type="text" name="tick_title" class="form-control is-invalid">
<div class="invalid-feedback d-block">Tajuk aduan diperlukan.</div>
```

2. **Select2 Field**
```html
<select name="cat_id" class="form-select select2-hidden-accessible is-invalid">
    <option value="">Pilih Kategori</option>
</select>
<span class="select2-container is-invalid">...</span>
<div class="invalid-feedback d-block">Sila pilih kategori.</div>
```

3. **File Input Field**
```html
<input type="file" name="attachments[]" class="form-control is-invalid">
<div class="error-text" style="display: block;">
    Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.
</div>
```

---

## 🔐 Server-Side Validation

### Store Ticket Request (`StoreTicketRequest.php`)

```php
public function rules(): array
{
    return [
        'ticket_type' => 'required|in:1,2',
        'name' => 'required_if:ticket_type,1|nullable|string|max:255',
        'email' => 'required_if:ticket_type,1|nullable|email|max:255',
        'other_name' => 'required_if:ticket_type,2|nullable|string|max:255',
        'other_email' => 'required_if:ticket_type,2|nullable|email|max:255',
        'tick_title' => 'required|string|max:255',
        'cat_id' => 'required|exists:ref_categories,id',
        'subcat_id' => 'required|exists:ref_sub_categories,id',
        'tick_desc' => 'required|string|max:5000',
        'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        'building_loc' => 'required|in:P1,P2',
        'block_loc' => 'required|in:U,TU,T,TS,S',
        'level_loc' => 'required|in:G,1,2,3,4,5,6,7,8,8.5,9,10,11,12',
    ];
}

public function messages(): array
{
    return [
        'tick_title.required' => 'Tajuk aduan diperlukan.',
        'cat_id.required' => 'Sila pilih kategori.',
        'subcat_id.required' => 'Sila pilih sub kategori.',
        'tick_desc.required' => 'Keterangan aduan diperlukan.',
        'building_loc.required' => 'Sila pilih lokasi bangunan.',
        'block_loc.required' => 'Sila pilih blok.',
        'level_loc.required' => 'Sila pilih aras.',
        'attachments.*.mimes' => 'Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
        'attachments.*.max' => 'Saiz lampiran tidak boleh melebihi 2MB.',
        // ... other messages
    ];
}
```

### Update Ticket Request (`UpdateTicketRequest.php`)

```php
public function rules(): array
{
    return [
        'tick_title' => 'required|string|max:255',
        'cat_id' => 'required|exists:ref_categories,id',
        'subcat_id' => 'required|exists:ref_sub_categories,id',
        'tick_desc' => 'required|string|max:5000',
        'no_siri' => 'nullable|string|max:255',
        'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        'building_loc' => 'required|in:P1,P2',
        'block_loc' => 'required|in:U,TU,T,TS,S',
        'level_loc' => 'required|in:G,1,2,3,4,5,6,7,8,8.5,9,10,11,12',
        
        // These fields are readonly but accepted to avoid errors
        'ticket_type' => 'sometimes',
        'name' => 'sometimes',
        'email' => 'sometimes',
    ];
}
```

### AJAX Error Handling

```javascript
// create.js
$.ajax({
    url: '/tickets',
    type: 'POST',
    data: formData,
    success: function(response) {
        toastr.success('Tiket berjaya dicipta!');
        $('#offcanvasAddTicket').offcanvas('hide');
        reloadAllTables();
    },
    error: function(xhr) {
        var response = xhr.responseJSON;
        
        if (response && response.errors) {
            // Display server validation errors
            displayValidationErrors(response.errors);
            toastr.error('Sila betulkan ralat dalam borang.');
        } else {
            toastr.error('Ralat tidak dijangka. Sila cuba lagi.');
        }
    }
});
```

---

## ⚡ Real-Time Validation

### Input Field Validation
```javascript
$(document).on('input blur', '#addTicketForm input[required], #addTicketForm textarea[required]', 
function () {
    var field = $(this);
    var value = field.val().trim();

    field.removeClass('is-invalid is-valid');
    field.siblings('.invalid-feedback').hide();

    if (value === '') {
        field.addClass('is-invalid');
        field.siblings('.invalid-feedback').text('Medan ini diperlukan.').show();
    } else {
        field.addClass('is-valid');
    }
});
```

### Select2 Field Validation
```javascript
$(document).on('change', '#cat_id, #subcat_id', function () {
    var field = $(this);
    var value = field.val();

    field.removeClass('is-invalid is-valid');
    if (field.hasClass('select2-hidden-accessible')) {
        field.siblings('.select2-container').removeClass('is-invalid');
    }
    field.siblings('.invalid-feedback').hide();

    if (!value || value === '') {
        field.addClass('is-invalid');
        if (field.hasClass('select2-hidden-accessible')) {
            field.siblings('.select2-container').addClass('is-invalid');
        }
        field.siblings('.invalid-feedback').text('Sila pilih satu pilihan.').show();
    } else {
        field.addClass('is-valid');
    }
});
```

---

## 📋 Validation Rules Summary

### Add Ticket Form

| Field | Type | Required | Condition | Validation Rules |
|-------|------|----------|-----------|------------------|
| ticket_type | Select | ✓ | - | in:1,2 |
| name | Input | ✓ | ticket_type=1 | string, max:255 |
| email | Input | ✓ | ticket_type=1 | email, max:255 |
| other_name | Input | ✓ | ticket_type=2 | string, max:255 |
| other_email | Input | ✓ | ticket_type=2 | email, max:255 |
| tick_title | Textarea | ✓ | - | string, max:255 |
| cat_id | Select | ✓ | - | exists:ref_categories |
| subcat_id | Select | ✓ | - | exists:ref_sub_categories |
| no_siri | Input | ○ | category=Sewaan | string, max:255 |
| tick_desc | Textarea | ✓ | - | string, max:5000 |
| building_loc | Select | ✓ | - | in:P1,P2 |
| block_loc | Select | ✓ | - | in:U,TU,T,TS,S |
| level_loc | Select | ✓ | - | in:G,1-12,8.5 |
| attachments[] | File | ○ | - | mimes:pdf,doc,docx,jpg,jpeg,png, max:2048KB, max_files:5 |

### Edit Ticket Form

| Field | Editable | Required | Validation Rules |
|-------|----------|----------|------------------|
| ticket_type | ✗ | - | Display only |
| name / other_name | ✗ | - | Display only |
| email / other_email | ✗ | - | Display only |
| tick_title | ✓ | ✓ | string, max:255 |
| cat_id | ✓ | ✓ | exists:ref_categories |
| subcat_id | ✓ | ✓ | exists:ref_sub_categories |
| no_siri | ✓ | ○ | string, max:255 |
| tick_desc | ✓ | ✓ | string, max:5000 |
| building_loc | ✓ | ✓ | in:P1,P2 |
| block_loc | ✓ | ✓ | in:U,TU,T,TS,S |
| level_loc | ✓ | ✓ | in:G,1-12,8.5 |
| attachments[] | ✓ | ○ | mimes:pdf,doc,docx,jpg,jpeg,png, max:2048KB |

---

## 🐛 Common Validation Issues & Solutions

### Issue 1: Error text tidak muncul
**Penyebab**: Class `.d-block` tidak ditambah pada `.invalid-feedback`

**Solution**:
```javascript
// Wrong
$('.invalid-feedback').text(errorMessage).show();

// Correct
$('.invalid-feedback').addClass('d-block').text(errorMessage).show();
```

### Issue 2: Auto-scroll tidak berfungsi dalam offcanvas
**Penyebab**: Scroll target salah (scroll page instead of offcanvas body)

**Solution**:
```javascript
// Wrong
$('html, body').animate({ scrollTop: ... });

// Correct
$('#offcanvasAddTicket .offcanvas-body').animate({ scrollTop: ... });
```

### Issue 3: Select2 validation tidak kelihatan
**Penyebab**: Invalid class tidak ditambah pada `.select2-container`

**Solution**:
```javascript
if (field.hasClass('select2-hidden-accessible')) {
    field.siblings('.select2-container').addClass('is-invalid');
}
```

### Issue 4: Validation error untuk file upload tidak muncul
**Penyebab**: Error container tidak dijumpai

**Solution**:
```javascript
var wrapper = field.closest('.form-field-wrapper');
var errorContainer = wrapper.find('.error-text');
if (errorContainer.length > 0) {
    errorContainer.text(errorMessage).show();
}
```

### Issue 5: Server validation errors tidak ditangani
**Penyebab**: Array-based field names (e.g., `attachments.0`) tidak dihandle

**Solution**:
```javascript
if (fieldName.includes('.')) {
    var baseFieldName = fieldName.split('.')[0];
    field = $('[name="' + baseFieldName + '[]"]');
}
```

---

## 🚀 Performance Optimization

### 1. Debounce Real-Time Validation
```javascript
var validationTimeout;
$(document).on('input', 'input[name], textarea[name]', function() {
    clearTimeout(validationTimeout);
    var field = $(this);
    
    validationTimeout = setTimeout(function() {
        // Validate field
    }, 300); // Wait 300ms after user stops typing
});
```

### 2. Clear Validation on Form Reset
```javascript
function resetForm() {
    $('#addTicketForm')[0].reset();
    clearValidationErrors();
    // Reset other states
}
```

### 3. Batch Validation Updates
```javascript
// Process all errors at once instead of one-by-one
Object.keys(errors).forEach(function(fieldName) {
    // Mark all invalid fields
});

// Then scroll to first error (not during loop)
setTimeout(function() {
    scrollToFirstError();
}, 150);
```

---

## 📝 Best Practices

1. **Always validate on both client and server side**
   - Client-side: Better UX, instant feedback
   - Server-side: Security, data integrity

2. **Use `.d-block` class for Bootstrap invalid-feedback**
   - Ensures error text is visible

3. **Scroll to first error after validation**
   - Helps user find and fix errors quickly

4. **Clear validation errors before re-validating**
   - Prevents duplicate error messages

5. **Handle readonly fields properly**
   - Skip validation display for readonly fields

6. **Support Select2 field validation**
   - Add `.is-invalid` to both select and container

7. **Use consistent error messages**
   - Client and server messages should match

8. **Provide helpful error messages in Bahasa Melayu**
   - Clear, actionable, user-friendly

---

## 📚 Related Documentation

- [02_TICKET_FORM_LOG_BARU.md](02_TICKET_FORM_LOG_BARU.md) - Add ticket form
- [03_TICKET_ATTACHMENT.md](03_TICKET_ATTACHMENT.md) - File upload validation
- [../validation-rules.md](../validation-rules.md) - General validation rules
- [../frontend-guidelines.md](../frontend-guidelines.md) - Frontend best practices

---

## 🔄 Changelog

### March 3, 2026
- ✅ Added auto-scroll to first validation error
- ✅ Enhanced error display with `.d-block` class
- ✅ Improved Select2 field validation
- ✅ Added support for array-based field names (attachments)
- ✅ Consistent validation across Add and Edit forms
- ✅ Updated documentation with code examples

### Previous Updates
- Client-side validation implementation
- Server-side validation with Laravel Request
- Real-time validation for better UX
- File upload validation
