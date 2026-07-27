# 2. Struktur Fail & Kod Sumber (Files)

Berikut adalah senarai fail penting untuk sistem pengurusan cuti umum ini.

> **Nota Seni Bina:** Sistem ini menggunakan **JSON columns** (`states`, `states_except`) terus pada jadual `public_holidays`. Tiada jadual pivot atau Many-to-Many relationship.

---

## A. Model (`app/Models/PublicHoliday.php`)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PublicHoliday extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'holiday_name',
        'holiday_date',
        'day',
        'states',
        'states_except',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'holiday_date' => 'datetime',
        'states'       => 'json',
        'states_except' => 'json',
    ];

    public function generateTags(): array
    {
        return ['public_holidays'];
    }
}
```

### Logik `states` Column
| Nilai DB | Maksud |
|---|---|
| `NULL` | Cuti Nasional (semua negeri) |
| `["Johor","Selangor"]` | Cuti spesifik negeri tersebut sahaja |

### Logik `states_except` Column
| Nilai DB | Maksud |
|---|---|
| `NULL` | Tiada pengecualian |
| `["Sabah","Sarawak"]` | Kecualikan negeri ini dari skop |

---

## B. Controller (`app/Http/Controllers/Configuration/PublicHolidayController.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuration;

use App\Http\Requests\StorePublicHolidayRequest;
use App\Http\Requests\UpdatePublicHolidayRequest;
use App\Models\PublicHoliday;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicHolidayController
{
    public function index(): View { /* ... */ }

    public function store(StorePublicHolidayRequest $request): JsonResponse
    {
        // Jika 'national' dalam states array → simpan states = null
        $states = in_array('national', $request->states ?? []) ? null : $request->states;
        PublicHoliday::create([..., 'states' => $states, 'created_by' => auth()->id()]);
        return response()->json(['success' => true, ...], 201);
    }

    public function edit(PublicHoliday $publicHoliday): JsonResponse
    {
        return response()->json(['success' => true, 'holiday' => $publicHoliday]);
    }

    public function update(UpdatePublicHolidayRequest $request, PublicHoliday $publicHoliday): JsonResponse
    {
        $states = in_array('national', $request->states ?? []) ? null : $request->states;
        $publicHoliday->update([..., 'states' => $states, 'updated_by' => auth()->id()]);
        return response()->json(['success' => true, ...]);
    }

    public function destroy(PublicHoliday $publicHoliday): JsonResponse
    {
        $publicHoliday->update(['deleted_by' => auth()->id()]);
        $publicHoliday->delete(); // soft delete
        return response()->json(['success' => true, ...]);
    }
}
```

---

## C. Form Requests

### `StorePublicHolidayRequest` & `UpdatePublicHolidayRequest`

```php
public function rules(): array
{
    return [
        'holiday_date'    => ['required', 'date_format:Y-m-d'],
        'holiday_name'    => ['required', 'string', 'min:3', 'max:255'],
        'states'          => ['required', 'array', 'min:1'],
        'states.*'        => ['string', 'in:national,Johor,Kedah,...,Wilayah Persekutuan Putrajaya'],
        'states_except'   => ['nullable', 'array'],
        'states_except.*' => ['string', 'in:Johor,Kedah,...,Wilayah Persekutuan Putrajaya'], // no 'national'
    ];
}
```

> `UpdatePublicHolidayRequest` adalah identik kecuali method boleh dikembangkan untuk logik tambahan pada masa hadapan.

---

## D. Frontend JavaScript

### Fail-fail JS (`resources/assets/js/public-holidays/`)

| Fail | Tujuan |
|---|---|
| `app-public-holidays-add.js` | Borang tambah: Select2, FormValidation, AJAX POST |
| `app-public-holidays-edit.js` | Borang edit: load data, Select2, FormValidation, AJAX PUT |
| `app-public-holidays-delete.js` | SweetAlert2 konfirmasi, AJAX DELETE |

### Penting — Select2 API

```javascript
// BETUL: Guna Select2 API
const selectedStates = $('#add-holiday-states').val() || [];

// SALAH: Native DOM tidak reliable dengan Select2
const selectedStates = Array.from(select.selectedOptions).map(opt => opt.value);
```

### Populate Edit Form dengan Select2

```javascript
// Tarikh: extract YYYY-MM-DD dari datetime string backend
document.getElementById('edit-holiday-date').value = holiday.holiday_date.substring(0, 10);

// Negeri: states null = nasional
if (holiday.states && Array.isArray(holiday.states)) {
    $('#edit-holiday-states').val(holiday.states).trigger('change');
} else {
    $('#edit-holiday-states').val(['national']).trigger('change');
}
```

### FormValidation + Select2 Revalidation

```javascript
// Trigger revalidation bila Select2 berubah
$(document).on('change', '#add-holiday-states', function () {
    fv.revalidateField('states[]');
});
```

### Reset Form Selepas Berjaya Simpan

```javascript
// BETUL: Reset FormValidation state + form fields sekaligus
fv.resetForm(true);
$('#add-holiday-states').val(null).trigger('change');
$('#add-holiday-states-except').val(null).trigger('change');

// SALAH: form.reset() sahaja menyebabkan FormValidation tunjuk error palsu
addNewPublicHolidayForm.reset();
```

---

## E. Artisan Command Scraper (`app/Console/Commands/ScrapeHolidays.php`)

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;
use App\Models\PublicHoliday;
use Carbon\Carbon;

class ScrapeHolidays extends Command
{
    protected $signature = 'scrape:holidays {year?}';

    public function handle(): int
    {
        $year = $this->argument('year') ?? date('Y');
        $url  = "https://publicholidays.com.my/{$year}-dates/";

        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 ...'])->get($url);

        $dom  = HtmlDomParser::str_get_html($response->body());
        $rows = $dom->find('table.publicholidays tbody tr');

        foreach ($rows as $row) {
            $dateText  = $row->find('td', 0)?->text();
            $nameText  = $row->find('td', 2)?->text();
            $stateText = strtolower(trim($row->find('td', 3)?->text() ?? ''));

            $date = Carbon::parse(trim($dateText) . ' ' . $year)->format('Y-m-d');

            // Logik negeri: National = states null, selain itu = array nama negeri
            $isNational = str_contains($stateText, 'national') || str_contains($stateText, 'semua');
            $states     = $isNational ? null : /* parse state names */ [];

            PublicHoliday::updateOrCreate(
                ['holiday_date' => $date, 'holiday_name' => trim($nameText)],
                ['states' => $states, 'day' => Carbon::parse($date)->format('l')]
            );
        }

        return Command::SUCCESS;
    }
}
```

> **Perbezaan dari dokumentasi lama:** Tiada `states()->sync()`. States disimpan terus sebagai JSON column. `states = null` bermakna Nasional.

---

## F. Senarai Negeri Malaysia (Whitelist)

```
national, Johor, Kedah, Kelantan, Melaka, Negeri Sembilan, Pahang,
Pulau Pinang, Perak, Perlis, Selangor, Terengganu, Sabah, Sarawak,
Wilayah Persekutuan Kuala Lumpur, Wilayah Persekutuan Labuan,
Wilayah Persekutuan Putrajaya
```

> `states_except` tidak boleh mengandungi `national`.


---

## A. Lapisan Pangkalan Data (Migration & Seeder)

### 1. Migration Jadual Negeri (`database/migrations/xxxx_create_states_table.php`)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2)->unique(); // Kod MyKad JPN (01 - 16)
            $table->string('state_name')->unique();    // Nama Negeri Lengkap
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
```

### 2. Migration Jadual Cuti Am (`database/migrations/xxxx_create_public_holidays_table.php`)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('holiday_name');
            $table->date('holiday_date');
            $table->timestamps();

            // Kombinasi unik untuk mengelakkan isu duplikasi data
            $table->unique(['holiday_name', 'holiday_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
```

### 3. Migration Jadual Perantara (`database/migrations/xxxx_create_public_holiday_state_table.php`)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_holiday_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_holiday_id')->constrained('public_holidays')->onDelete('cascade');
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['public_holiday_id', 'state_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holiday_state');
    }
};
```

### 4. Fail Seeder Data Negeri (`database/seeders/StateSeeder.php`)
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['state_code' => '01', 'state_name' => 'Johor'],
            ['state_code' => '02', 'state_name' => 'Kedah'],
            ['state_code' => '03', 'state_name' => 'Kelantan'],
            ['state_code' => '04', 'state_name' => 'Melaka'],
            ['state_code' => '05', 'state_name' => 'Negeri Sembilan'],
            ['state_code' => '06', 'state_name' => 'Pahang'],
            ['state_code' => '07', 'state_name' => 'Pulau Pinang'],
            ['state_code' => '08', 'state_name' => 'Perak'],
            ['state_code' => '09', 'state_name' => 'Perlis'],
            ['state_code' => '10', 'state_name' => 'Selangor'],
            ['state_code' => '11', 'state_name' => 'Terengganu'],
            ['state_code' => '12', 'state_name' => 'Sabah'],
            ['state_code' => '13', 'state_name' => 'Sarawak'],
            ['state_code' => '14', 'state_name' => 'Wilayah Persekutuan Kuala Lumpur'],
            ['state_code' => '15', 'state_name' => 'Wilayah Persekutuan Labuan'],
            ['state_code' => '16', 'state_name' => 'Wilayah Persekutuan Putrajaya'],
        ];

        foreach ($states as $state) {
            State::updateOrCreate(
                ['state_name' => $state['state_name']],
                ['state_code' => $state['state_code']]
            );
        }
    }
}
```

---

## B. Lapisan Model Eloquent

### 5. Model Cuti Am (`app/Models/PublicHoliday.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PublicHoliday extends Model
{
    protected $fillable = ['holiday_name', 'holiday_date'];

    public function states(): BelongsToMany
    {
        return $this->belongsToMany(State::class);
    }

    /**
     * Jika rekod perantara tiada, ia adalah Cuti Nasional (Semua Negeri)
     */
    public function isNational(): bool
    {
        return $this->states()->count() === 0;
    }
}
```

---

## C. Lapisan Logik Core & Scheduler

### 6. Artisan Console Command (`app/Console/Commands/ScrapeHolidays.php`)
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;
use App\Models\PublicHoliday;
use App\Models\State;
use Carbon\Carbon;

class ScrapeHolidays extends Command
{
    protected $signature = 'scrape:holidays {year?}';
    protected $description = 'Scrape public holidays data from publicholidays.com.my';

    public function handle()
    {
        $year = $this->argument('year') ?? date('Y');
        $url = "https://publicholidays.com.my/{$year}-dates/"; 
        
        $this->info("Memulakan proses scraping dari: {$url}");

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->get($url);

        if ($response->failed()) {
            $this->error("Gagal mengakses laman web. Status: " . $response->status());
            return Command::FAILURE;
        }

        $dom = HtmlDomParser::str_get_html($response->body());
        $rows = $dom->find('table.publicholidays tbody tr');

        if (count($rows) === 0) {
            $rows = $dom->find('table tr');
        }

        $count = 0;
        $allStates = State::all();

        foreach ($rows as $row) {
            if ($row->find('th', 0)) continue;

            $dateText  = $row->find('td', 0)?->text(); 
            $nameText  = $row->find('td', 2)?->text(); 
            $stateText = $row->find('td', 3)?->text(); 

            if ($dateText && $nameText) {
                $cleanedDate = trim(preg_replace('/\s+/', ' ', $dateText));
                
                try {
                    $formattedDate = Carbon::parse($cleanedDate . ' ' . $year)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue; 
                }

                $holiday = PublicHoliday::updateOrCreate(
                    ['holiday_date' => $formattedDate, 'holiday_name' => trim($nameText)],
                    ['updated_at'   => now()]
                );

                $cleanedState = strtolower(trim($stateText));
                $stateIds = [];

                // Validasi Condition: Jika teks tidak mengandungi unsur kata 'Semua' atau 'National'
                if (!empty($cleanedState) && !str_contains($cleanedState, 'national') && !str_contains($cleanedState, 'semua')) {
                    foreach ($allStates as $state) {
                        if (str_contains($cleanedState, strtolower($state->state_name))) {
                            $stateIds[] = $state->id;
                        }
                    }
                }

                // Sync data hubungan ke pivot table
                $holiday->states()->sync($stateIds);
                $count++;
            }
        }

        $this->info("Scraping selesai! {$count} rekod bagi tahun {$year} dikemaskini.");
        return Command::SUCCESS;
    }
}
```
