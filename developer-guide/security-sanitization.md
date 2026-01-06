# Security & Sanitization Standards

## Overview
All input/output must be sanitized to prevent:
- XSS
- SQL Injection
- CSRF
- Data corruption

---

## 1. Input Sanitization
Before saving:
```php
strip_tags()
htmlspecialchars()
trim()
```

Example:
```php
$data['title'] = strip_tags($data['title']);
$data['message'] = htmlspecialchars($data['message']);
```

---

## 2. Output Sanitization
Use Blade escaping by default:
```blade
{{ $ticket->title }}
```

Avoid:
```blade
{!! $ticket->title !!}
```

---

## 3. AJAX Sanitization
Use jQuery:
```js
let title = $.trim($('#title').val());
let message = $('<div>').text($('#message').val()).html();
```

---

## 4. SQL Injection
Avoid raw SQL unless required.

---

## 5. CSRF Protection
Ensure every AJAX request includes:
```js
data: {
    _token: '{{ csrf_token() }}',
}
```

---

## 6. Model Protection
Always use fillable:
```php
protected $fillable = ['title', 'message'];
```

---

## 7. Sanitization Within Form Request Classes

Sanitization can be applied directly inside a Form Request using the `prepareForValidation()` method.

### Example:

```php
protected function prepareForValidation()
{
    $this->merge([
        'title'   => strip_tags($this->title),
        'message' => htmlspecialchars($this->message),
    ]);
}
```

### Flow:
1. User submits form  
2. AJAX sanitizes (optional but recommended)  
3. FormRequest sanitizes again (server-side, required)  
4. Validation rules apply  
5. Controller receives only clean & validated data  
6. Saved to DB securely  

This ensures **double-layer sanitization** for maximum security.
