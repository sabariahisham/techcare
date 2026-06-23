# Modul Senarai Laporan - Gambaran Keseluruhan

## Tujuan
Modul Senarai Laporan menyediakan 6 laporan yang berbeza untuk membantu pengurusan dan analisis data tiket sistem eHelp. Setiap laporan mempunyai fokus yang berbeza dan menyokong export dalam format Excel dan PDF.

## Senarai 6 Laporan

| Bil. | Nama Laporan | Controller | URL |
|------|-------------|------------|-----|
| 1 | Senarai Log Tiket | `ReportLogTicketController` | `/report/senarai-log-tiket` |
| 2 | Prestasi Pegawai Tugasan | `PrestasiPegtugasController` | `/report/prestasi-pegawai-tugasan` |
| 3 | Laporan Aduan Tahunan | `AduanTahunanController` | `/report/aduan-tahunan` |
| 4 | Senarai Rekod Laporan | `RekodLaporanController` | `/report/rekod-laporan` |
| 5 | Statistik Log Aduan | `StatistikLogAduanController` | `/report/statistik-log` |
| 6 | Laporan Bulanan Aduan Mengikut Status Aduan | `LaporanStatusAduanTahunanController` | `/report/laporan-status-aduan-tahunan` |

---

## Penerangan Setiap Laporan

### 1. Senarai Log Tiket
**Tujuan**: Laporan lengkap log aktiviti dan perubahan status tiket.

**Ciri Utama**:
- Senarai semua tiket dengan butiran penuh
- Penapisan mengikut status, kategori, subkategori, pegawai
- Export Excel dan PDF
- DataTable server-side dengan pagination

**Data Sumber**: `tickets`, `tickets_chronologies`, `ref_ticket_statuses`, `ref_categories`

---

### 2. Prestasi Pegawai Tugasan
**Tujuan**: Laporan prestasi pegawai berdasarkan tiket yang ditugaskan.

**Ciri Utama**:
- Statistik tiket diterima, selesai, dalam tindakan, lewat SLA
- Penapisan mengikut bulan, tahun, pegawai, bahagian
- Breakdown mengikut keutamaan (High, Medium, Low)
- Peratus selesai dalam SLA
- Export Excel dan PDF

**Data Sumber**: `prestasi_pegtugas` (view), `users`, `ref_bahagians`

---

### 3. Laporan Aduan Tahunan
**Tujuan**: Laporan bulanan aduan yang diterima mengikut kategori dan tahunan.

**Ciri Utama**:
- Carta bar bulanan (Jan–Dis)
- Penapisan mengikut tahun
- Pecahan mengikut kategori
- Export Excel dan PDF

**Data Sumber**: `tickets`, `tickets_chronologies`, `ref_categories`

---

### 4. Senarai Rekod Laporan
**Tujuan**: Senarai rekod laporan TechCare dengan ringkasan dan perincian.

**Ciri Utama**:
- 2 tab: **Ringkasan** dan **Perincian**
- Penapisan mengikut tarikh (dari–hingga), kategori, status
- Ringkasan: kiraan tiket selesai mengikut kategori/subkategori
- Perincian: senarai tiket individual dengan butiran lengkap

**Data Sumber**: `tickets`, `tickets_chronologies`, `ref_categories`, `ref_ticket_statuses`

---

### 5. Statistik Log Aduan
**Tujuan**: Statistik log aduan TechCare mengikut kategori dan bulan.

**Ciri Utama**:
- Carta statistik interaktif
- Penapisan mengikut tahun
- Jumlah aduan mengikut bulan
- Export Excel dan PDF

**Data Sumber**: `tickets`, `ref_categories`

---

### 6. Laporan Bulanan Aduan Mengikut Status Aduan
**Tujuan**: Laporan bulanan aduan yang diterima mengikut status aduan.

**Ciri Utama**:
- Penapisan mengikut tahun dan bulan
- Senarai status aduan: Baru, Diproses, Diserahkan, Dirujuk, Dibatal, Selesai
- Menggunakan status terkini sahaja (berdasarkan MAX id chronology)
- Export Excel dan PDF

**Data Sumber**: `tickets`, `tickets_chronologies` (latest status), `ref_ticket_statuses`, `ref_categories`

---

## Halaman Indeks Senarai Laporan

### URL
`GET /report/index`

### Controller
`ReportLogTicketController@index`

### Named Route
`report-index`

### Fungsi
- Menampilkan DataTable dengan senarai semua 6 laporan
- Data laporan adalah statik (hardcoded dalam JavaScript)
- Setiap baris mempunyai butang "Lihat Laporan" yang menghala ke laporan berkenaan
- Menampilkan kiraan "6 laporan" dan tarikh dikemaskini hari ini

---

## Prinsip Reka Bentuk

### 1. Konsistensi
Semua laporan mengikut struktur yang sama:
- Halaman indeks dengan penapisan
- AJAX untuk data dinamik
- Export Excel dan PDF

### 2. Latest Chronology Pattern
Semua laporan yang memerlukan status semasa tiket menggunakan:
```sql
tc.id = (
    SELECT MAX(tc2.id)
    FROM tickets_chronologies tc2
    WHERE tc2.ticket_id = t.id
    AND tc2.deleted_at IS NULL
)
```

### 3. Soft Delete
Semua query menyemak `deleted_at IS NULL` untuk mengelak rekod yang dihapus.

### 4. Export Format
Semua laporan menyokong:
- **Excel** - menggunakan `Maatwebsite\Excel`
- **PDF** - menggunakan `Barryvdh\DomPDF`

---

## Status Tiket (Rujukan)

| ID | Nama Status | Kod |
|----|-------------|-----|
| 1 | Aduan Baru | NEW |
| 2 | Aduan Diproses | PROCESSING |
| 3 | Serahan Kepada | ASSIGNED |
| 4 | Aduan Dirujuk | REFERRED |
| 5 | Aduan DiBatal | CANCELLED |
| 6 | Aduan Selesai | COMPLETED |

---

## Stack Teknologi
- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Blade, Bootstrap 5, Vanilla JS
- **DataTables**: Yajra DataTables (server-side)
- **Export Excel**: Maatwebsite Excel
- **Export PDF**: Barryvdh DomPDF
- **AJAX**: Native `fetch()` API dan DataTables AJAX

---

## Keselamatan
- Semua route dilindungi oleh middleware `auth`
- Input divalidasi menggunakan Form Request / `$request->validate()`
- Soft delete diperiksa dalam semua query
- CSRF protection pada semua form

---

## Dokumentasi Berkaitan
- [Fail-Fail](files.md) - Senarai lengkap semua fail
- [Aliran Data](flow.md) - Diagram aliran request dan data
