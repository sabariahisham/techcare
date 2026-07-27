# 3. Aliran Sistem & Panduan Pengoperasian (Flows)

Dokumen ini menjelaskan aliran logik pemrosesan data (data pipeline) serta tatacara mengendalikan sistem ini.

---

## A. Aliran Web Scraping

1. **Pencetusan (Trigger):** Dijalankan manual (`php artisan scrape:holidays {year}`) atau automatik via scheduler.
2. **Permintaan HTTP:** GET request ke `publicholidays.com.my` dengan header `User-Agent` untuk elak sekatan bot.
3. **Pengekstrakan DOM:** HTML dimuatkan ke engine `voku`. Loop pada `table.publicholidays tbody tr`.
4. **Penetapan Tarikh:** Teks tarikh (cth: "1 May") + argumen tahun → `Carbon::parse()` → format `Y-m-d`.
5. **Pengendalian Skop Negeri:**
    - **Condition 1 (Nasional):** String mengandungi `"National"` atau `"Semua Negeri"` → simpan `states = null` dalam DB.
    - **Condition 2 (Spesifik):** Negeri tertentu → simpan `states = ["Johor", "Kedah"]` sebagai JSON.
6. **Simpan/Kemaskini:** `PublicHoliday::updateOrCreate()` berdasarkan kombinasi `holiday_date` + `holiday_name`.

---

## B. Aliran CRUD Admin (Tambah / Edit / Padam)

### Tambah Cuti Umum
1. Klik butang "Tambah" → offcanvas dibuka, Select2 diinisialisasi.
2. Isi Tarikh, Nama, Nama Negeri (wajib), Negeri Berkecuali (opsional).
3. FormValidation.js validate semua field client-side.
4. AJAX POST ke `/public-holidays/store`.
5. `StorePublicHolidayRequest` validate server-side.
6. Controller: jika `national` dalam states → simpan `states = null`; selain itu simpan array JSON.
7. Response JSON `{success: true}` → toastr success → page reload.

### Edit Cuti Umum
1. Klik butang Edit → AJAX GET ke `/public-holidays/{id}/edit`.
2. Data dipopulate ke form: tarikh (substr 0-10 dari datetime string), nama, states via Select2 `.val().trigger('change')`.
3. Isi semula → FormValidation validate → AJAX PUT ke `/public-holidays/{id}/update`.
4. Response JSON → toastr → page reload.

### Padam Cuti Umum
1. Klik butang Padam → SweetAlert2 konfirmasi.
2. AJAX DELETE ke `/public-holidays/{id}/delete`.
3. Soft delete: set `deleted_by`, panggil `$model->delete()`.

---

## C. Routes

```php
Route::middleware(['auth'])->prefix('public-holidays')->group(function () {
    Route::get('/index',              [PublicHolidayController::class, 'index'])->name('public-holidays.index');
    Route::post('/store',             [PublicHolidayController::class, 'store'])->name('public-holidays.store');
    Route::get('/{publicHoliday}/edit',   [PublicHolidayController::class, 'edit'])->name('public-holidays.edit');
    Route::put('/{publicHoliday}/update', [PublicHolidayController::class, 'update'])->name('public-holidays.update');
    Route::delete('/{publicHoliday}/delete', [PublicHolidayController::class, 'destroy'])->name('public-holidays.destroy');
});
```

> **Penting:** Parameter `{publicHoliday}` mesti sepadan dengan nama variable dalam controller untuk Laravel implicit model binding.

---

## D. Panduan Pengoperasian & Arahan Terminal

### 1. Scraping Manual
```bash
# Tahun semasa
php artisan scrape:holidays

# Tahun spesifik
php artisan scrape:holidays 2027
```

### 2. Automasi Berjadual (`routes/console.php`)
```php
use Illuminate\Support\Facades\Schedule;

// Dijalankan automatik hari pertama setiap bulan, jam 1:00 pagi
Schedule::command('scrape:holidays')->monthlyOn(1, '01:00');
```

### 3. Cron Job (Production Server)
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```
