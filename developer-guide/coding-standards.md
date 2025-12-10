# Coding Standards (Laravel 12)

## 1. General Rules
- Follow PSR-12 formatting.
- Use meaningful variable names.
- Avoid long functions. Max ~50 lines.
- Avoid nested logic more than 3 levels deep.
- Prefer early return to reduce indentation.

---

## 2. Controllers
- Controllers must remain thin and only handle flow control.
- All validation MUST use dedicated FormRequest classes.
- Sanitization done inside FormRequest using prepareForValidation().
- Controller should only handle:
  - calling models
  - returning response
  - simple data passing

### Example (Clean & Best Practice)

```php
public function store(StoreTicketRequest $request)
{
    Ticket::create($request->validated());

    return response()->json([
        'status' => true,
        'message' => 'Saved'
    ]);
}
```

---

## 3. Models
- Use fillable
- Add casts when needed
- Add relationship methods at bottom

---

## 4. Blade
- Use {{ }} for output
- Avoid inline queries
- Structure:

```
@extends('layout')

@section('content')
@endsection
```

---

## 5. JavaScript
- Use jQuery
- Keep scripts in separate files or @push('scripts')
- Keep naming consistent

---

## 6. File Naming
- Controllers: TicketController.php
- Models: Ticket.php
- Views: tickets/index.blade.php
- Migrations: yyyy_mm_dd_create_tickets_table.php

---

## 7. Form Request Validation (Recommended)

All validation must use Laravel Form Request classes located in:

```
app/Http/Requests/
```

### Naming Convention:
- Ticket: StoreTicketRequest.php, UpdateTicketRequest.php  
- User: StoreUserRequest.php, UpdateUserRequest.php  

### Example Form Request:

```php
public function rules()
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'message' => ['nullable', 'string', 'max:5000'],
        'priority' => ['required', 'in:low,medium,high'],
    ];
}
```

### Custom Messages:

```php
public function messages()
{
    return [
        'title.required' => 'Sila masukkan tajuk tiket.',
        'priority.in' => 'Keutamaan tidak sah.',
    ];
}
```

### Authorization Rule:
Always return true unless restricted:

```php
public function authorize()
{
    return true;
}
```

### Controller usage:
```php
public function store(StoreTicketRequest $request)
{
    $data = $request->validated();

    $data['title'] = strip_tags($data['title']);
    $data['message'] = htmlspecialchars($data['message']);

    Ticket::create($data);

    return response()->json(['status' => true, 'message' => 'Saved']);
}
```

### Validation Messages (Bahasa Melayu)
Semua mesej validation WAJIB menggunakan Bahasa Melayu untuk konsistensi UI dan pengalaman pengguna.

Contoh:
```php
public function messages()
{
    return [
        'name.required' => 'Sila masukkan nama.',
        'email.unique'  => 'Emel ini telah digunakan.',
    ];
}
```

