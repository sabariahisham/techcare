# Modul Senarai Laporan - Fail-Fail

## Controllers

### ReportLogTicketController
**Path**: `app/Http/Controllers/Reports/ReportLogTicketController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Senarai Log Tiket

**Methods**:
- `index()` - Halaman senarai semua laporan (`/report/index`)
- `data(Request $request)` - API: DataTable data untuk halaman indeks
- `logTicket(Request $request)` - Halaman laporan Senarai Log Tiket
- `logTicketData(Request $request)` - API: DataTable server-side untuk log tiket
- `exportExcel(Request $request)` - Export laporan ke Excel
- `exportPdf(Request $request)` - Export laporan ke PDF

**Dependencies**:
- `Officer`, `RefBahagian`, `RefCategory`, `RefSubCategory`, `RefTicketStatus`, `Report`, `Ticket`
- `Yajra\DataTables\Facades\DataTables`
- `Carbon\Carbon`

---

### AduanTahunanController
**Path**: `app/Http/Controllers/Reports/AduanTahunanController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Laporan Aduan Tahunan

**Methods**:
- `index()` - Halaman laporan aduan tahunan (hantar `$categories`, `$years`)
- `data(Request $request)` - API: Data carta dan jadual mengikut tahun (POST)
- `exportExcel(Request $request)` - Export ke Excel
- `exportPdf(Request $request)` - Export ke PDF

**Dependencies**:
- `RefCategory`
- `DB` facade untuk query terus

---

### StatistikLogAduanController
**Path**: `app/Http/Controllers/Reports/StatistikLogAduanController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Statistik Log Aduan

**Methods**:
- `index()` - Halaman statistik log aduan (hantar `$years`)
- `getData(Request $request)` - API: DataTable data statistik
- `getChart(Request $request)` - API: Data carta mengikut tahun
- `exportExcel(Request $request)` - Export ke Excel
- `exportPdf(Request $request)` - Export ke PDF

**Dependencies**:
- `RefCategory`, `RefTicketStatus`
- `DB` facade, `DataTables`, `Excel`, `PDF`

---

### LaporanStatusAduanTahunanController
**Path**: `app/Http/Controllers/Reports/LaporanStatusAduanTahunanController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Laporan Bulanan Aduan Mengikut Status Aduan

**Methods**:
- `index()` - Halaman laporan (hantar `$statuses`, `$categories`, `$years`)
- `data(Request $request)` - API: DataTable dengan status terkini (POST)
- `exportExcel(Request $request)` - Export ke Excel
- `exportPdf(Request $request)` - Export ke PDF

**Pattern Penting**: Menggunakan subquery latest status pattern untuk mendapatkan status terkini tiket.

**Dependencies**:
- `DB` facade, `DataTables`

---

### PrestasiPegtugasController
**Path**: `app/Http/Controllers/Reports/PrestasiPegtugasController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Prestasi Pegawai Tugasan

**Methods**:
- `index(Request $request)` - Halaman laporan (dengan statistik ringkasan)
- `data(Request $request)` - API: DataTable prestasi pegawai
- `exportExcel(Request $request)` - Export ke Excel
- `exportPdf(Request $request)` - Export ke PDF

**Data Utama (hantar ke view)**:
- `$officers` - Senarai pegawai
- `$bahagians` - Senarai bahagian
- `$totalOfficers` - Jumlah pegawai aktif
- `$totalTickets` - Jumlah tiket diterima
- `$totalTicketSelesai` - Tiket selesai
- `$totalTicketDalamTindakan` - Tiket dalam tindakan
- `$totalTicketLewatSla` - Tiket lewat SLA
- `$totalTicketSelesaiDalamSla` - Tiket selesai dalam SLA

**Dependencies**:
- `RefBahagian`, `DB`, `DataTables`

---

### RekodLaporanController
**Path**: `app/Http/Controllers/Reports/RekodLaporanController.php`

**Namespace**: `App\Http\Controllers\Reports`

**Laporan**: Senarai Rekod Laporan

**Methods**:
- `index()` - Halaman laporan (hantar `$categories`, `$statuses`)
- `ringkasan(Request $request)` - API: Data Tab 1 - Ringkasan tiket selesai mengikut kategori
- `perincian(Request $request)` - API: Data Tab 2 - Perincian tiket individual

**Ciri Istimewa**: Mempunyai 2 tab berbeza (Ringkasan & Perincian) dalam satu halaman.

**Dependencies**:
- `DB` facade, `DataTables`

---

## JavaScript Files

### app-report-list.js
**Path**: `resources/assets/js/report/app-report-list.js`

**Tujuan**: DataTable untuk halaman indeks senarai laporan

**Data**: Statik (hardcoded) - 6 baris laporan dengan nama, deskripsi, tarikh
- Row 1: Senarai Log Tiket → `route('report-log-ticket')`
- Row 2: Prestasi Pegawai Tugasan → `route('report-prestasi-pegtugas')`
- Row 3: Laporan Aduan Tahunan → `route('report-aduan-tahunan')`
- Row 4: Senarai Rekod Laporan → `route('report-rekod-laporan')`
- Row 5: Statistik Log Aduan → `route('report-statistik-log')`
- Row 6: Laporan Bulanan Aduan Mengikut Status Aduan → `route('report-laporan-status-aduan-tahunan')`

---

### app-log-ticket.js
**Path**: `resources/assets/js/report/app-log-ticket.js`

**Tujuan**: Logik JavaScript untuk laporan Senarai Log Tiket (penapisan, export)

---

### app-prestasi-pegtugas.js
**Path**: `resources/assets/js/report/app-prestasi-pegtugas.js`

**Tujuan**: Logik JavaScript untuk laporan Prestasi Pegawai Tugasan

---

### app-aduan-tahunan.js
**Path**: `resources/assets/js/report/app-aduan-tahunan.js`

**Tujuan**: Logik JavaScript untuk laporan Aduan Tahunan (carta + penapisan)

---

### app-rekod-laporan.js
**Path**: `resources/assets/js/report/app-rekod-laporan.js`

**Tujuan**: Logik JavaScript untuk Senarai Rekod Laporan (2 tab)

---

### app-statistik-log.js
**Path**: `resources/assets/js/report/app-statistik-log.js`

**Tujuan**: Logik JavaScript untuk laporan Statistik Log Aduan (carta + DataTable)

---

### app-laporan-status-aduan.js
**Path**: `resources/assets/js/report/app-laporan-status-aduan.js`

**Tujuan**: Logik JavaScript untuk Laporan Bulanan Aduan Mengikut Status

---

## Blade Views

### Halaman Indeks
**Path**: `resources/views/report/index.blade.php`

**Tujuan**: Halaman utama senarai semua laporan (DataTable dengan 6 baris)

**Komponen**:
- Kad ringkasan: "Jumlah Laporan: 6 laporan"
- DataTable: Bil., Nama Laporan, Deskripsi, Tarikh Kemaskini, Lihat Laporan

---

### Log Tiket
**Path**: `resources/views/report/log-ticket.blade.php`

**Tujuan**: Paparan laporan Senarai Log Tiket

**Fail PDF**: `resources/views/report/log-ticket-pdf.blade.php`

---

### Aduan Tahunan
**Path**: `resources/views/report/aduan-tahunan.blade.php`

**Tujuan**: Paparan laporan Aduan Tahunan dengan carta dan jadual

**Fail PDF**: `resources/views/report/aduan-tahunan-pdf.blade.php`

---

### Laporan Status Aduan
**Path**: `resources/views/report/laporan-status-aduan.blade.php`

**Tujuan**: Paparan Laporan Bulanan Aduan Mengikut Status

---

### Statistik Log
**Path**: `resources/views/report/statistik-log.blade.php`

**Tujuan**: Paparan Statistik Log Aduan dengan carta interaktif

**Fail Excel**: `resources/views/report/statistik-log-excel.blade.php`

**Fail PDF**: `resources/views/report/statistik-log-pdf.blade.php`

---

### Prestasi Pegawai Tugasan
**Path**: `resources/views/report/prestasi-pegtugas.blade.php`

**Tujuan**: Paparan laporan Prestasi Pegawai Tugasan

**Fail PDF**: `resources/views/report/prestasi-pegtugas-pdf.blade.php`

---

### Rekod Laporan
**Path**: `resources/views/report/rekod-laporan.blade.php`

**Tujuan**: Paparan Senarai Rekod Laporan (2 tab: Ringkasan & Perincian)

---

## Routes

**Fail**: `routes/web.php`

**Middleware**: `auth` (authentication)

**Prefix**: `/report`

### Senarai Route

```php
// Halaman indeks senarai laporan
GET /report/index → ReportLogTicketController@index [report-index]

// Laporan 1: Senarai Log Tiket
GET /report/senarai-log-tiket         → ReportLogTicketController@logTicket         [report-log-ticket]
GET /report/senarai-log-tiket/data    → ReportLogTicketController@logTicketData     [report-log-ticket-data]
GET /report/senarai-log-tiket/export-excel → ReportLogTicketController@exportExcel  [report-log-ticket-export-excel]
GET /report/senarai-log-tiket/export-pdf   → ReportLogTicketController@exportPdf    [report-log-ticket-export-pdf]

// Laporan 2: Prestasi Pegawai Tugasan
GET /report/prestasi-pegawai-tugasan        → PrestasiPegtugasController@index        [report-prestasi-pegtugas]
GET /report/prestasi-pegawai-tugasan/data   → PrestasiPegtugasController@data         [report-prestasi-pegtugas-data]
GET /report/prestasi-pegawai-tugasan/export-excel → PrestasiPegtugasController@exportExcel [report-prestasi-pegtugas-export-excel]
GET /report/prestasi-pegawai-tugasan/export-pdf   → PrestasiPegtugasController@exportPdf   [report-prestasi-pegtugas-export-pdf]

// Laporan 3: Laporan Aduan Tahunan
GET  /report/aduan-tahunan              → AduanTahunanController@index          [report-aduan-tahunan]
POST /report/aduan-tahunan/data         → AduanTahunanController@data           [report-aduan-tahunan-data]
GET  /report/aduan-tahunan/export-excel → AduanTahunanController@exportExcel    [report-aduan-tahunan-export-excel]
GET  /report/aduan-tahunan/export-pdf   → AduanTahunanController@exportPdf      [report-aduan-tahunan-export-pdf]

// Laporan 4: Senarai Rekod Laporan
GET /report/rekod-laporan           → RekodLaporanController@index       [report-rekod-laporan]
GET /report/rekod-laporan/ringkasan → RekodLaporanController@ringkasan   [report-rekod-laporan-ringkasan]
GET /report/rekod-laporan/perincian → RekodLaporanController@perincian   [report-rekod-laporan-perincian]

// Laporan 5: Statistik Log Aduan
GET /report/statistik-log              → StatistikLogAduanController@index        [report-statistik-log]
GET /report/statistik-log/data         → StatistikLogAduanController@getData      [report-statistik-log-data]
GET /report/statistik-log/chart        → StatistikLogAduanController@getChart     [report-statistik-log-chart]
GET /report/statistik-log/export-excel → StatistikLogAduanController@exportExcel  [report-statistik-log-export-excel]
GET /report/statistik-log/export-pdf   → StatistikLogAduanController@exportPdf    [report-statistik-log-export-pdf]

// Laporan 6: Laporan Bulanan Aduan Mengikut Status
GET  /report/laporan-status-aduan-tahunan              → LaporanStatusAduanTahunanController@index        [report-laporan-status-aduan-tahunan]
POST /report/laporan-status-aduan-tahunan/data         → LaporanStatusAduanTahunanController@data         [report-laporan-status-aduan-tahunan-data]
GET  /report/laporan-status-aduan-tahunan/export-excel → LaporanStatusAduanTahunanController@exportExcel  [report-laporan-status-aduan-tahunan-export-excel]
GET  /report/laporan-status-aduan-tahunan/export-pdf   → LaporanStatusAduanTahunanController@exportPdf    [report-laporan-status-aduan-tahunan-export-pdf]
```

---

## Models Digunakan

**Ticket**
- **Path**: `app/Models/Ticket.php`
- **Table**: `tickets`
- **Digunakan oleh**: ReportLogTicketController, AduanTahunanController

**RefCategory**
- **Path**: `app/Models/RefCategory.php`
- **Table**: `ref_categories`
- **Digunakan oleh**: AduanTahunanController, LaporanStatusAduanTahunanController, RekodLaporanController

**RefTicketStatus**
- **Path**: `app/Models/RefTicketStatus.php`
- **Table**: `ref_ticket_statuses`
- **Digunakan oleh**: ReportLogTicketController, LaporanStatusAduanTahunanController, RekodLaporanController

**RefBahagian**
- **Path**: `app/Models/RefBahagian.php`
- **Table**: `ref_bahagians`
- **Digunakan oleh**: PrestasiPegtugasController

**Officer**
- **Path**: `app/Models/Officer.php`
- **Table**: `officers`
- **Digunakan oleh**: ReportLogTicketController

---

## Database Tables

### tickets
Rekod utama tiket aduan.

**Lajur Utama**: `id`, `ticket_no`, `created_at`, `updated_at`, `deleted_at`

---

### tickets_chronologies
Jejak perubahan status tiket (paling penting untuk laporan).

**Lajur Utama**: `id`, `ticket_id`, `tick_status`, `escalate_to`, `sla_duedate`, `created_at`, `deleted_at`

**Pattern Penting**: Gunakan `MAX(id)` untuk mendapat status terkini bagi setiap tiket.

---

### prestasi_pegtugas *(Database View)*
View khas untuk laporan prestasi pegawai.

**Lajur Utama**: `assignee_id`, `tiket_diterima`, `tiket_selesai`, `tiket_dalam_tindakan`, `tiket_lewat_sla`, `tiket_selesai_dalam_sla`, `high`, `medium`, `low`, `bulan`, `tahun`

---

### ref_categories
Jadual rujukan kategori aduan.

**Lajur Utama**: `id`, `cat_name`, `cat_code`, `parent_id`, `deleted_at`

---

### ref_ticket_statuses
Jadual rujukan status tiket.

**Lajur Utama**: `id`, `st_name`, `st_code`, `deleted_at`

---

### ref_bahagians
Jadual rujukan bahagian/jabatan.

**Lajur Utama**: `id`, `nama_bah`, `status`, `deleted_at`

---

## Export Files

### Excel
- **Library**: `Maatwebsite\Excel`
- **Export Class**: `app/Exports/StatistikLogAduanExport.php` (untuk Statistik Log Aduan)
- Format: `.xlsx`

### PDF
- **Library**: `Barryvdh\DomPDF`
- **Views PDF**: Fail Blade berasingan (`*-pdf.blade.php`)
- Format: `.pdf`

---

## Struktur Fail

```
app/
└── Http/Controllers/Reports/
    ├── ReportLogTicketController.php     ← Laporan 1 + Halaman Indeks
    ├── PrestasiPegtugasController.php    ← Laporan 2
    ├── AduanTahunanController.php        ← Laporan 3
    ├── RekodLaporanController.php        ← Laporan 4
    ├── StatistikLogAduanController.php   ← Laporan 5
    └── LaporanStatusAduanTahunanController.php ← Laporan 6

resources/
├── views/report/
│   ├── index.blade.php
│   ├── log-ticket.blade.php
│   ├── log-ticket-pdf.blade.php
│   ├── prestasi-pegtugas.blade.php
│   ├── prestasi-pegtugas-pdf.blade.php
│   ├── aduan-tahunan.blade.php
│   ├── aduan-tahunan-pdf.blade.php
│   ├── rekod-laporan.blade.php
│   ├── statistik-log.blade.php
│   ├── statistik-log-excel.blade.php
│   ├── statistik-log-pdf.blade.php
│   └── laporan-status-aduan.blade.php
└── assets/js/report/
    ├── app-report-list.js
    ├── app-log-ticket.js
    ├── app-prestasi-pegtugas.js
    ├── app-aduan-tahunan.js
    ├── app-rekod-laporan.js
    ├── app-statistik-log.js
    └── app-laporan-status-aduan.js

app/Exports/
└── StatistikLogAduanExport.php

routes/
└── web.php  (bahagian report management)

report_md/
├── overview.md     ← Gambaran keseluruhan modul
├── files.md        ← Fail ini
└── flow.md         ← Diagram aliran data
```
