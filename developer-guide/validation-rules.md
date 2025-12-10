# Validation Rules & Form Request Standards

## 1. Overview
All validation must be implemented using Laravel Form Request classes.  
No inline validation inside controllers unless for extremely simple cases.

---

## 2. Location
All Form Request files must be stored in:

```
app/Http/Requests/
```

Modules should group their own:

```
app/Http/Requests/Ticket/
app/Http/Requests/User/
app/Http/Requests/Category/
```

---

## 3. Required Methods

### 3.1 rules()
Defines field validation:

```php
public function rules()
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'message' => ['nullable', 'string', 'max:5000'],
        'status' => ['required', 'in:open,closed,pending'],
    ];
}
```

---

### 3.2 messages()
Optional but recommended:

```php
public function messages()
{
    return [
        'title.required' => 'Sila masukkan tajuk.',
        'status.in' => 'Status tidak sah, sila cuba semula.',
    ];
}
```

---

### 3.3 authorize()
Always return true unless access control required:

```php
public function authorize()
{
    return true;
}
```

---

## 4. Sanitization (Important)

Use `prepareForValidation()`:

```php
protected function prepareForValidation()
{
    $this->merge([
        'title'   => strip_tags($this->title),
        'message' => htmlspecialchars($this->message),
    ]);
}
```

Sanitization layers:
- client-side (AJAX)
- server-side (FormRequest)
- Blade escaping

---

## 5. Controller Usage Pattern

```php
public function store(StoreTicketRequest $request)
{
    Ticket::create($request->validated());

    return response()->json([
        'status' => true,
        'message' => 'Ticket berjaya disimpan.'
    ]);
}
```

---

## 6. Validation Error Response (AJAX)

Laravel will auto return JSON:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["Sila masukkan tajuk."]
  }
}
```

Frontend example:

```js
if (err.status === 422) {
    let errors = err.responseJSON.errors;
    $('#title_error').text(errors.title);
}
```

---

## 7. Recommended Rules for Helpdesk System

### Ticket:
- title: required|string|max:255
- message: nullable|string|max:5000
- priority: required|in:low,medium,high
- category_id: required|integer|exists:categories,id

### User:
- name: required|string|max:255
- email: required|email|unique:users,email
- role: required|string|in:admin,staff,user

### Category:
- name: required|string|max:100

---

## 8. Validation Language Standard (Bahasa Melayu)

- Semua mesej validation WAJIB menggunakan Bahasa Melayu.
- Gunakan mesej mudah difahami oleh pengguna.
- Gunakan `messages()` dalam FormRequest untuk mesej spesifik modul.
- Gunakan `lang/ms/validation.php` untuk mesej umum Laravel.

Contoh:
```php
public function messages()
{
    return [
        'title.required' => 'Sila masukkan tajuk tiket.',
        'email.email'    => 'Sila masukkan emel yang sah.',
        'priority.in'    => 'Pilihan keutamaan tidak sah.',
    ];
}
```

---
