# Modul Senarai Laporan - Aliran Data

## Aliran Halaman Indeks

```
┌─────────────────────────────────────────────────────────┐
│  1. Pengguna akses: GET /report/index                   │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. Route: web.php                                       │
│     Route::middleware(['auth'])                         │
│           ->get('/report/index', ...)                   │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. Middleware: auth                                     │
│     - Semak pengguna log masuk                          │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. ReportLogTicketController::index()                  │
│     - return view('report.index')                       │
│     (tiada data dinamik - semua data dalam JS)          │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  5. Blade: resources/views/report/index.blade.php       │
│     - Render kad "Jumlah Laporan: 6 laporan"            │
│     - Render struktur DataTable (kosong)                │
│     - Load app-report-list.js                           │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  6. JavaScript: app-report-list.js                      │
│     - Inisialisasi DataTable dengan data statik (6 baris)│
│     - Paparkan butang "Lihat Laporan" untuk setiap baris │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran Setiap Laporan (Corak Umum)

Setiap laporan mengikut aliran yang sama:

```
┌─────────────────────────────────────────────────────────┐
│  1. Pengguna klik "Lihat Laporan" dari Senarai Laporan  │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  2. GET /report/{nama-laporan}                          │
│     → Controller@index()                                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  3. Controller::index()                                  │
│     - Dapatkan data rujukan (kategori, tahun, status)   │
│     - return view(...) dengan data rujukan              │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  4. Blade View Dirender                                  │
│     - Form penapisan (filter) dipaparkan                 │
│     - Struktur DataTable/Chart (kosong)                 │
│     - JavaScript dimuatkan                              │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  5. JavaScript memulakan AJAX / DataTable               │
│     fetch('/report/{nama-laporan}/data')                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  6. Controller@data() / Controller@getData()            │
│     - Terima parameter penapisan                        │
│     - Query database dengan DB facade                   │
│     - Return JSON / DataTables response                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  7. JavaScript renders data                              │
│     - DataTable paparkan data dalam jadual              │
│     - Carta dirender (jika ada)                         │
└──────────────────────────────────────────────────────────┘
```

---

## Aliran Khusus Setiap Laporan

### Laporan 1: Senarai Log Tiket

```
GET /report/senarai-log-tiket
    │
    ▼
ReportLogTicketController::logTicket()
    │ Hantar: $categories, $statuses, $officers, $years, dll.
    ▼
Blade: log-ticket.blade.php
    │ Render form penapisan (status, kategori, subkategori, pegawai)
    ▼
JavaScript: app-log-ticket.js
    │ Init DataTable dengan server-side processing
    ▼
GET /report/senarai-log-tiket/data?{filters}
    │
    ▼
ReportLogTicketController::logTicketData()
    │ Query: tickets JOIN tickets_chronologies
    │ Filter: status, kategori, subkategori, pegawai, tarikh
    │ Return: DataTables JSON response
    ▼
Jadual dipaparkan kepada pengguna

Export:
    GET /report/senarai-log-tiket/export-excel → .xlsx
    GET /report/senarai-log-tiket/export-pdf   → .pdf
```

---

### Laporan 2: Prestasi Pegawai Tugasan

```
GET /report/prestasi-pegawai-tugasan
    │
    ▼
PrestasiPegtugasController::index()
    │ Query: prestasi_pegtugas (view) untuk statistik ringkasan
    │ Hantar: $officers, $bahagians, $totalOfficers,
    │         $totalTickets, $totalTicketSelesai, dll.
    ▼
Blade: prestasi-pegtugas.blade.php
    │ Render kad statistik ringkasan (4 kad)
    │ Render form penapisan (pegawai, bahagian, bulan, tahun)
    │ Render struktur DataTable
    ▼
JavaScript: app-prestasi-pegtugas.js
    │ Init DataTable server-side
    ▼
GET /report/prestasi-pegawai-tugasan/data?{filters}
    │
    ▼
PrestasiPegtugasController::data()
    │ Query: prestasi_pegtugas JOIN users
    │ Filter: bulan, tahun, pegawai, bahagian
    │ Return: DataTables JSON dengan kolum prestasi
    ▼
Jadual dipaparkan

Export:
    GET /report/prestasi-pegawai-tugasan/export-excel → .xlsx
    GET /report/prestasi-pegawai-tugasan/export-pdf   → .pdf
```

---

### Laporan 3: Laporan Aduan Tahunan

```
GET /report/aduan-tahunan
    │
    ▼
AduanTahunanController::index()
    │ Query: tahun unik dari tickets_chronologies
    │ Hantar: $categories, $years
    ▼
Blade: aduan-tahunan.blade.php
    │ Render dropdown tahun dan butang Jana Laporan
    │ Render kawasan carta (Chart.js)
    │ Render struktur jadual
    ▼
JavaScript: app-aduan-tahunan.js
    │ Apabila pengguna klik Jana / tukar tahun:
    ▼
POST /report/aduan-tahunan/data
    │ Body: { year: 2025 }
    ▼
AduanTahunanController::data()
    │ Query: tickets GROUP BY MONTH(created_at)
    │ Inisialisasi 12 bulan (0 untuk bulan tiada data)
    │ Return: JSON { labels: [...], data: [...] }
    ▼
Chart.js merender carta bar bulanan (Jan-Dis)
Jadual data dipaparkan

Export:
    GET /report/aduan-tahunan/export-excel → .xlsx
    GET /report/aduan-tahunan/export-pdf   → .pdf
```

---

### Laporan 4: Senarai Rekod Laporan

```
GET /report/rekod-laporan
    │
    ▼
RekodLaporanController::index()
    │ Query: ref_categories, ref_ticket_statuses
    │ Hantar: $categories, $statuses
    ▼
Blade: rekod-laporan.blade.php
    │ Render form penapisan (tarikh dari-hingga, kategori, status)
    │ Render 2 tab: [Ringkasan] [Perincian]
    │ Render struktur DataTable untuk setiap tab
    ▼
JavaScript: app-rekod-laporan.js
    │ Init kedua-dua DataTable

TAB 1: Ringkasan
    ▼
GET /report/rekod-laporan/ringkasan?from=&to=&category=
    │
    ▼
RekodLaporanController::ringkasan()
    │ Subquery: tickets_chronologies status SELESAI (status 6)
    │ Filter: tarikh ticket_created, kategori
    │ Group by: kategori/subkategori
    │ Return: DataTables JSON (ringkasan kiraan)

TAB 2: Perincian
    ▼
GET /report/rekod-laporan/perincian?from=&to=&category=&status=
    │
    ▼
RekodLaporanController::perincian()
    │ Query: tickets JOIN tickets_chronologies (latest status)
    │ Filter: tarikh, kategori, status
    │ Return: DataTables JSON (senarai tiket individual)
```

---

### Laporan 5: Statistik Log Aduan

```
GET /report/statistik-log
    │
    ▼
StatistikLogAduanController::index()
    │ Query: tahun unik dari tickets
    │ Hantar: $years
    ▼
Blade: statistik-log.blade.php
    │ Render dropdown tahun
    │ Render kawasan carta (Chart.js)
    │ Render kawasan DataTable
    ▼
JavaScript: app-statistik-log.js
    │ Apabila halaman load / tahun berubah:

[CARTA]
    ▼
GET /report/statistik-log/chart?year=2025
    │
    ▼
StatistikLogAduanController::getChart()
    │ Query: tickets GROUP BY MONTH(created_at) untuk tahun berkenaan
    │ Inisialisasi array 12 bulan
    │ Return: JSON { labels: [Jan...Dis], data: [n1...n12] }
    ▼
Chart.js merender carta

[JADUAL]
    ▼
GET /report/statistik-log/data?year=2025
    │
    ▼
StatistikLogAduanController::getData()
    │ Query: tickets dengan kiraan mengikut kategori
    │ Return: DataTables JSON

Export:
    GET /report/statistik-log/export-excel → .xlsx
    GET /report/statistik-log/export-pdf   → .pdf
```

---

### Laporan 6: Laporan Bulanan Aduan Mengikut Status Aduan

```
GET /report/laporan-status-aduan-tahunan
    │
    ▼
LaporanStatusAduanTahunanController::index()
    │ Query: ref_ticket_statuses, ref_categories, tahun unik
    │ Hantar: $statuses, $categories, $years
    ▼
Blade: laporan-status-aduan.blade.php
    │ Render dropdown tahun, pilihan bulan
    │ Render struktur DataTable
    ▼
JavaScript: app-laporan-status-aduan.js
    │ Apabila Jana Laporan diklik:
    ▼
POST /report/laporan-status-aduan-tahunan/data
    │ Body: { year: 2025, months: [1,2,3,...] }
    │
    ▼
LaporanStatusAduanTahunanController::data()
    │
    ├──→ SUBQUERY: Dapatkan STATUS TERKINI sahaja
    │    SELECT tc1.* FROM tickets_chronologies tc1
    │    JOIN (
    │        SELECT ticket_id, MAX(id) AS latest_id
    │        FROM tickets_chronologies
    │        WHERE deleted_at IS NULL
    │        GROUP BY ticket_id
    │    ) latest ON latest.latest_id = tc1.id
    │
    ├──→ Query utama:
    │    SELECT tickets.*, tc_latest.tick_status, statuses.st_name
    │    FROM tickets
    │    JOIN {latest_status_subquery} tc_latest ...
    │    WHERE YEAR(tickets.created_at) = $year
    │    AND MONTH(tickets.created_at) IN ($months)
    │
    └──→ Return: DataTables JSON (dengan status terkini tiket)
    ▼
Jadual dipaparkan mengikut status semasa

Export:
    GET /report/laporan-status-aduan-tahunan/export-excel → .xlsx
    GET /report/laporan-status-aduan-tahunan/export-pdf   → .pdf
```

---

## Aliran Export

### Export Excel

```
Pengguna klik butang "Export Excel"
    │
    ▼
GET /report/{nama-laporan}/export-excel?{filters}
    │
    ▼
Controller::exportExcel(Request $request)
    │ Ambil semua parameter penapisan
    │ Buat Export class (implements FromQuery/FromCollection)
    │ return Excel::download(new ExportClass($filters), 'filename.xlsx')
    ▼
Browser muat turun fail .xlsx
```

### Export PDF

```
Pengguna klik butang "Export PDF"
    │
    ▼
GET /report/{nama-laporan}/export-pdf?{filters}
    │
    ▼
Controller::exportPdf(Request $request)
    │ Ambil semua parameter penapisan
    │ Query data dari database
    │ $pdf = PDF::loadView('report.{nama}-pdf', compact('data'))
    │ return $pdf->download('filename.pdf')
    │           OR $pdf->stream('filename.pdf')
    ▼
Browser muat turun / papar fail .pdf
```

---

## Pattern Subquery Latest Status

**Digunakan oleh**: Rekod Laporan, Laporan Status Aduan Tahunan, dan lain-lain

**Masalah**: Setiap tiket boleh ada banyak baris dalam `tickets_chronologies`. Kita hanya mahu status yang TERKINI.

**Penyelesaian**: Subquery `MAX(id)` per tiket

```sql
-- Subquery: Dapatkan ID terkini bagi setiap tiket
SELECT tc1.*
FROM tickets_chronologies tc1
INNER JOIN (
    SELECT ticket_id, MAX(id) AS latest_id
    FROM tickets_chronologies
    WHERE deleted_at IS NULL
    GROUP BY ticket_id
) AS latest ON latest.latest_id = tc1.id
WHERE tc1.deleted_at IS NULL
```

**Dalam Laravel Query Builder**:
```php
$latestStatus = DB::table('tickets_chronologies as tc1')
    ->select('tc1.*')
    ->join(DB::raw('(
        SELECT ticket_id, MAX(id) AS latest_id
        FROM tickets_chronologies
        WHERE deleted_at IS NULL
        GROUP BY ticket_id
    ) latest'), function ($join) {
        $join->on('latest.latest_id', '=', 'tc1.id');
    })
    ->whereNull('tc1.deleted_at');

// Kemudian join dengan tickets:
$query = DB::table('tickets as t')
    ->joinSub($latestStatus, 'tc', function ($join) {
        $join->on('t.id', '=', 'tc.ticket_id');
    })
    ->whereNull('t.deleted_at');
```

**Kenapa MAX(id) bukan MAX(created_at)?**
- ID adalah berurutan (AUTO_INCREMENT) — tiada duplikasi
- Lebih terpercaya berbanding timestamp (elak race condition)
- Prestasi index lebih baik

---

## Urutan Muatan Halaman

```
Timeline muat halaman laporan (contoh: Prestasi Pegawai Tugasan):

0ms    → GET /report/prestasi-pegawai-tugasan
50ms   → Controller::index() query data rujukan
100ms  → Blade dirender dan dihantar ke browser
150ms  → Browser paparkan halaman (kad statistik, form, jadual kosong)
200ms  → JavaScript memulakan DataTable
250ms  → AJAX: GET /report/prestasi-pegawai-tugasan/data
500ms  → Response DataTables diterima
550ms  → Jadual diisi dengan data
```

---

## Pengendalian Ralat

```
try {
    // Logic query
    return DataTables::of($query)->make(true);
} catch (\Exception $e) {
    Log::error('Report error', [
        'report' => 'prestasi-pegtugas',
        'message' => $e->getMessage(),
        'user' => Auth::id()
    ]);

    return response()->json([
        'error' => true,
        'message' => 'Ralat berlaku. Sila cuba semula.'
    ], 500);
}
```

---

## Hubungan Antara Fail

```
routes/web.php
    └── /report/index
              └── ReportLogTicketController@index
                        └── view: report/index.blade.php
                                  └── JS: app-report-list.js
                                            │
                          ┌─────────────────┼──────────────────────┐
                          │                 │                      │
                          ▼                 ▼                      ▼
              /senarai-log-tiket    /prestasi-pegawai-tugasan    /aduan-tahunan
              ReportLogTicket       PrestasiPegtugas             AduanTahunan
              Controller            Controller                   Controller
                   │                     │                          │
              log-ticket.blade       prestasi-pegtugas.blade    aduan-tahunan.blade
              app-log-ticket.js      app-prestasi-pegtugas.js   app-aduan-tahunan.js
                                            │
                          ┌─────────────────┼──────────────────────┐
                          │                 │                      │
                          ▼                 ▼                      ▼
              /rekod-laporan          /statistik-log        /laporan-status-aduan-tahunan
              RekodLaporan            StatistikLogAduan     LaporanStatusAduan
              Controller              Controller            TahunanController
                   │                     │                          │
              rekod-laporan.blade    statistik-log.blade    laporan-status-aduan.blade
              app-rekod-laporan.js   app-statistik-log.js   app-laporan-status-aduan.js
```
