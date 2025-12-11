# Module Guidelines

Each module must include:
- Model
- Controller
- Migration
- Blade views (index, create/edit)
- AJAX scripts

## Example Modules:
1. Dashboard
2. Tiket
3. Knowledge Base
4. Laporan
5. Pentadbir: Pengurusan Pengguna (Pengguna, Modul & Capaian, Peranan), Konfigurasi (Kategori, Sub-kategori, SLA, Pegawai Tugasan)
6. Audit Trail

---

## Form Request Rules for Each Module

Each CRUD module must include:

### **store request**  
Located in:
```
app/Http/Requests/Module/StoreXxxRequest.php
```

### **update request**  
Located in:
```
app/Http/Requests/Module/UpdateXxxRequest.php
```

### Structure:

```php
public function rules()
{
    return [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:5000'
    ];
}

public function messages()
{
    return [
        'name.required' => 'Medan nama diperlukan.'
    ];
}

protected function prepareForValidation()
{
    $this->merge([
        'name' => strip_tags($this->name),
        'description' => htmlspecialchars($this->description),
    ]);
}
```

### Controller must use:
```php
public function store(StoreCategoryRequest $request)
{
    Category::create($request->validated());
}
```
