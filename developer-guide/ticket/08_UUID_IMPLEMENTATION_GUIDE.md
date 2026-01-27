# Panduan Implementasi UUID dalam eHelp

## 1. Gambaran Keseluruhan

UUID (Universally Unique Identifier) digunakan dalam sistem eHelp sebagai pengecam unik untuk rekod-rekod penting. UUID versi 4 (random) digunakan untuk memastikan keunikan merentas sistem tanpa perlanggaran.

### Tujuan Penggunaan UUID
- **Keselamatan**: Menyembunyikan ID auto-increment yang mudah diteka
- **Route Key**: Digunakan untuk URL routing yang lebih selamat
- **Rujukan Luaran**: Untuk API dan integrasi dengan sistem lain
- **Privacy**: Mengelakkan pendedahan maklumat sensitif melalui ID berturutan

---

## 2. Model Yang Menggunakan UUID

### 2.1 Model Ticket
**Fail**: [app/Models/Ticket.php](../../../app/Models/Ticket.php)

#### Ciri-ciri:
```php
// Kolum UUID dalam fillable
protected $fillable = [
    'uuid',
    'ticket_nolog',
    // ... kolum lain
];

// UUID sebagai route key
public function getRouteKeyName()
{
    return 'uuid';
}

// Auto-generate UUID semasa creating
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = Uuid::uuid4()->toString();
        }
    });
}
```

#### Migrasi:
**Fail**: [database/migrations/2025_09_06_134850_create_tickets_table.php](../../../database/migrations/2025_09_06_134850_create_tickets_table.php)

```php
$table->uuid('uuid')->unique();
```

---

### 2.2 Model User
**Fail**: [app/Models/User.php](../../../app/Models/User.php)

#### Ciri-ciri:
```php
protected $fillable = [
    'uuid',
    // ... kolum lain
];

protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        if (!$model->uuid) {
            $model->uuid = Uuid::uuid4()->toString();
        }
    });
}
```

#### Migrasi:
**Fail**: [database/migrations/0001_01_01_000000_create_users_table.php](../../../database/migrations/0001_01_01_000000_create_users_table.php)

```php
$table->uuid('uuid');
// ...
$table->unique('uuid');
```

---

### 2.3 Model KnowledgeBase
**Fail**: [app/Models/KnowledgeBase.php](../../../app/Models/KnowledgeBase.php)

#### Ciri-ciri:
```php
protected $fillable = [
    'uuid',
    'cat_id',
    'subcat_id',
    // ... kolum lain
];
```

#### Migrasi:
**Fail**: [database/migrations/2025_09_06_133315_create_knowledge_base_table.php](../../../database/migrations/2025_09_06_133315_create_knowledge_base_table.php)

```php
$table->uuid('uuid')->unique();
```

---

### 2.4 Model Module
**Fail**: [app/Models/Module.php](../../../app/Models/Module.php)

#### Ciri-ciri:
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        $model->uuid = Uuid::uuid4()->toString();
        // ...
    });
}
```

#### Migrasi:
**Fail**: [database/migrations/2025_01_16_160556_create_module_list_table.php](../../../database/migrations/2025_01_16_160556_create_module_list_table.php)

```php
$table->uuid('uuid')->unique();
```

---

## 3. Penggunaan UUID dalam Controller

### 3.1 TicketController
**Fail**: [app/Http/Controllers/Ticket/TicketController.php](../../../app/Http/Controllers/Ticket/TicketController.php)

#### Contoh Penggunaan:

**DataTable Actions**:
```php
// Untuk view button
$actions .= '<a class="btn btn-sm view-record" 
             data-uuid="' . $ticket->uuid . '" 
             data-bs-toggle="tooltip" 
             title="Lihat">
             <i class="bx bx-show text-success"></i>
             </a>';

// Untuk manage button
$actions .= '<a class="btn btn-sm manage-ticket" 
             data-uuid="' . $ticket->uuid . '" 
             data-bs-toggle="tooltip" 
             title="Urus">
             <i class="bx bx-cog text-primary"></i>
             </a>';
```

**Response JSON**:
```php
return response()->json([
    'success' => true,
    'uuid' => $ticket->uuid,
    'ticket_nolog' => $ticket->ticket_nolog,
    // ... data lain
]);
```

---

### 3.2 TicketAttachmentController
**Fail**: [app/Http/Controllers/Ticket/TicketAttachmentController.php](../../../app/Http/Controllers/Ticket/TicketAttachmentController.php)

#### Contoh Generate UUID untuk Attachment:
```php
use Illuminate\Support\Str;

// Generate UUID baru untuk attachment
$attachment = TicketAttachment::create([
    'uuid' => Str::uuid(),
    'ticket_id' => $ticketId,
    'file_path' => $filePath,
    // ... data lain
]);
```

---

### 3.3 KnowledgeBaseController
**Fail**: [app/Http/Controllers/Knowledgebase/KnowledgeBaseController.php](../../../app/Http/Controllers/Knowledgebase/KnowledgeBaseController.php)

#### Contoh:
```php
use Illuminate\Support\Str;

$knowledgeBaseUuid = Str::uuid();

KnowledgeBase::create([
    'uuid' => $knowledgeBaseUuid,
    'cat_id' => $request->cat_id,
    // ... data lain
]);
```

---

## 4. Library UUID yang Digunakan

### 4.1 Ramsey/Uuid
Digunakan dalam Model untuk auto-generate UUID.

```php
use Ramsey\Uuid\Uuid;

$uuid = Uuid::uuid4()->toString();
```

### 4.2 Laravel Str Helper
Digunakan dalam Controller untuk kemudahan.

```php
use Illuminate\Support\Str;

$uuid = Str::uuid();
```

**Nota**: Kedua-dua kaedah ini menghasilkan UUID versi 4 yang sama.

---

## 5. Struktur Database

### Format UUID
- **Tipe Data**: `uuid` (native) atau `string` (36 karakter)
- **Format**: `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`
- **Contoh**: `550e8400-e29b-41d4-a716-446655440000`

### Constraint
```php
// Unique constraint
$table->uuid('uuid')->unique();

// Atau separate unique
$table->uuid('uuid');
$table->unique('uuid');
```

---

## 6. Best Practices

### ✅ DO (Buat)

1. **Gunakan UUID untuk Route Key**
   ```php
   public function getRouteKeyName()
   {
       return 'uuid';
   }
   ```

2. **Auto-generate dalam Model Boot**
   ```php
   static::creating(function ($model) {
       if (empty($model->uuid)) {
           $model->uuid = Uuid::uuid4()->toString();
       }
   });
   ```

3. **Set Unique Constraint dalam Migrasi**
   ```php
   $table->uuid('uuid')->unique();
   ```

4. **Gunakan UUID dalam DataTable Actions**
   ```php
   data-uuid="' . $model->uuid . '"
   ```

5. **Return UUID dalam API Response**
   ```php
   return response()->json([
       'uuid' => $model->uuid,
       // ... data lain
   ]);
   ```

### ❌ DON'T (Jangan)

1. **Jangan gunakan UUID sebagai Foreign Key**
   ```php
   // ❌ Salah
   $table->uuid('ticket_uuid');
   $table->foreign('ticket_uuid')->references('uuid')->on('tickets');
   
   // ✅ Betul
   $table->foreignId('ticket_id')->constrained('tickets');
   ```

2. **Jangan expose ID auto-increment dalam URL**
   ```php
   // ❌ Salah
   route('tickets.show', $ticket->id);
   
   // ✅ Betul
   route('tickets.show', $ticket->uuid);
   ```

3. **Jangan lupa check null sebelum generate**
   ```php
   // ✅ Betul
   if (empty($model->uuid)) {
       $model->uuid = Uuid::uuid4()->toString();
   }
   ```

4. **Jangan gunakan UUID untuk searching/filtering berat**
   - UUID lebih lambat untuk indexing berbanding integer
   - Gunakan ID untuk operasi internal/database joins

---

## 7. Routing dengan UUID

### 7.1 Route Definition
```php
// routes/web.php
Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
```

### 7.2 Route Model Binding
Laravel akan automatic gunakan `getRouteKeyName()` untuk resolve model:

```php
public function show(Ticket $ticket)
{
    // $ticket sudah di-resolve berdasarkan UUID
    return view('tickets.show', compact('ticket'));
}
```

### 7.3 Generate URL
```php
// Dalam Blade
<a href="{{ route('tickets.show', $ticket) }}">
    View Ticket
</a>

// Output: /tickets/550e8400-e29b-41d4-a716-446655440000
```

---

## 8. Frontend Implementation

### 8.1 DataTable dengan UUID
```javascript
// Dalam DataTable button action
{
    data: null,
    render: function(data, type, row) {
        let actions = '';
        actions += '<button class="btn view-record" 
                    data-uuid="' + row.uuid + '">View</button>';
        return actions;
    }
}
```

### 8.2 AJAX Request dengan UUID
```javascript
// Click handler
$(document).on('click', '.view-record', function() {
    const uuid = $(this).data('uuid');
    
    $.ajax({
        url: `/api/tickets/${uuid}`,
        method: 'GET',
        success: function(response) {
            // Handle response
        }
    });
});
```

---

## 9. Testing UUID

### 9.1 Factory dengan UUID
```php
// database/factories/TicketFactory.php
use Illuminate\Support\Str;

public function definition()
{
    return [
        'uuid' => Str::uuid(),
        // ... field lain
    ];
}
```

### 9.2 Test Case
```php
public function test_ticket_has_uuid()
{
    $ticket = Ticket::factory()->create();
    
    $this->assertNotNull($ticket->uuid);
    $this->assertEquals(36, strlen($ticket->uuid));
    $this->assertMatchesRegularExpression(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $ticket->uuid
    );
}
```

---

## 10. Ringkasan Fail-fail Terlibat

### Models
| Fail | Path | Fungsi UUID |
|------|------|-------------|
| Ticket.php | app/Models/ | Route key, auto-generate |
| User.php | app/Models/ | Auto-generate |
| KnowledgeBase.php | app/Models/ | Fillable field |
| Module.php | app/Models/ | Auto-generate |

### Controllers
| Fail | Path | Penggunaan UUID |
|------|------|----------------|
| TicketController.php | app/Http/Controllers/Ticket/ | DataTable actions, JSON response |
| TicketAttachmentController.php | app/Http/Controllers/Ticket/ | Generate UUID untuk attachment |
| TicketSearchController.php | app/Http/Controllers/Ticket/ | DataTable buttons |
| KnowledgeBaseController.php | app/Http/Controllers/Knowledgebase/ | Generate UUID baru |

### Migrations
| Fail | Path | UUID Column |
|------|------|------------|
| create_users_table.php | database/migrations/ | uuid (unique) |
| create_tickets_table.php | database/migrations/ | uuid (unique) |
| create_knowledge_base_table.php | database/migrations/ | uuid (unique) |
| create_module_list_table.php | database/migrations/ | uuid (unique) |

---

## 11. Checklist Implementation

Apabila menambah UUID ke model baru:

- [ ] Tambah kolum `uuid` dalam migration dengan constraint unique
- [ ] Tambah `'uuid'` dalam array `$fillable` model
- [ ] Implement `getRouteKeyName()` method (jika perlu untuk routing)
- [ ] Tambah auto-generate logic dalam `boot()` method
- [ ] Update factory (jika ada) untuk include UUID
- [ ] Update controller untuk return UUID dalam response
- [ ] Update DataTable untuk gunakan UUID dalam actions
- [ ] Update routes untuk gunakan UUID (jika applicable)
- [ ] Buat test case untuk UUID generation

---

## 12. Troubleshooting

### Issue: UUID tidak auto-generate
**Sebab**: Model boot method tidak dipanggil atau condition check salah

**Penyelesaian**:
```php
static::creating(function ($model) {
    // Pastikan check null/empty dengan betul
    if (empty($model->uuid)) {
        $model->uuid = Uuid::uuid4()->toString();
    }
});
```

### Issue: Duplicate UUID Error
**Sebab**: UUID generation tidak unique (sangat jarang berlaku)

**Penyelesaian**:
- UUID v4 memiliki probability collision yang sangat rendah
- Pastikan constraint unique ada dalam database
- Check jika ada manual assignment UUID

### Issue: Route Model Binding tidak berfungsi
**Sebab**: `getRouteKeyName()` tidak di-implement

**Penyelesaian**:
```php
public function getRouteKeyName()
{
    return 'uuid';
}
```

---

## 13. References

- **Laravel Route Model Binding**: https://laravel.com/docs/routing#route-model-binding
- **Ramsey UUID Package**: https://github.com/ramsey/uuid
- **UUID v4 Specification**: https://tools.ietf.org/html/rfc4122

---

**Dokumen ini disediakan untuk panduan pembangunan sistem eHelp.**

**Versi**: 1.0  
**Tarikh**: 26 Januari 2026  
**Penulis**: Development Team
