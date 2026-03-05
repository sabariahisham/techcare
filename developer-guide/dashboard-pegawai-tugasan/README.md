# Dashboard Pegawai Tugasan - Index

Dokumentasi lengkap untuk Dashboard Pegawai Tugasan sistem eHelp.

## Kandungan

### [01 - Gambaran Keseluruhan](01-overview.md)
- Tujuan dashboard pegawai tugasan
- Fungsi dan tanggungjawab peranan
- Ciri-ciri utama
- Komponen dashboard
- Struktur fail
- Perbezaan dengan dashboard management
- Prinsip reka bentuk

### [02 - Arkitektur](02-architecture.md)
- Service architecture untuk pegawai
- PegawaiTugasanDashboard service
- Widget architecture
- Data filtering mechanism
- Data flow pattern
- Error handling

### [03 - Backend Services](03-services.md)
- PegawaiTugasanDashboard service
- Widget services:
  - TugasanAndaWidget
  - Filtered TicketSummaryWidget
- Query patterns specific to pegawai
- Data scope dan filtering
- Performance optimization

### [04 - Frontend Implementation](04-frontend.md)
- Blade templates untuk pegawai
- Partial templates:
  - cards-status-pegawai.blade.php
  - chart-status-pegawai.blade.php
  - chart-category-pegawai.blade.php
  - table-ticket-overdue-pegawai.blade.php
  - my-tasks.blade.php
- JavaScript interaction
- Auto-refresh mechanics
- Best practices

### [05 - Database & Queries](05-database.md)
- Query patterns untuk pegawai tugasan
- Filtering by escalate_to
- SLA queries for assigned tickets
- Performance tracking queries
- Indexes dan optimization

### [06 - Testing](06-testing.md)
- Unit tests untuk PegawaiTugasanDashboard
- Widget testing
- Data filtering tests
- Performance tests
- Best practices

---

## Quick Reference

### Peranan
**Pegawai Tugasan** - Staff yang diberi tugasan untuk menyelesaikan tiket yang diperuntukkan kepada mereka.

### Data Scope
Dashboard pegawai tugasan menunjukkan **HANYA tiket yang diperuntukkan** kepada pegawai tersebut (`escalate_to = user_id`).

### Komponen Utama
1. **Kad Status** - 4 kad ringkasan tugasan pegawai
2. **Senarai Tugasan Saya** - Jadual tiket yang diperuntukkan
3. **Carta Status** - Status breakdown tiket pegawai
4. **Carta Kategori** - Kategori breakdown tiket pegawai
5. **Tiket Lewat SLA** - Tiket pegawai yang lewat

---

## Struktur Dashboard Pegawai Tugasan

```
Dashboard Pegawai Tugasan
├── Service Layer
│   ├── PegawaiTugasanDashboard
│   └── Widgets
│       ├── TugasanAndaWidget
│       └── TicketSummaryWidget (filtered)
├── Views (Blade)
│   └── partials/pegawai-tugasan/
│       ├── cards-status-pegawai.blade.php
│       ├── chart-status-pegawai.blade.php
│       ├── chart-category-pegawai.blade.php
│       ├── table-ticket-overdue-pegawai.blade.php
│       └── my-tasks.blade.php
└── JavaScript
    └── dashboard.js (pegawai-specific sections)
```

---

## Navigasi Pantas

### Untuk Developer Baru
1. Mulakan dengan [01-overview.md](01-overview.md) untuk memahami dashboard pegawai
2. Baca [02-architecture.md](02-architecture.md) untuk pattern yang digunakan
3. Fahami [03-services.md](03-services.md) untuk backend implementation
4. Rujuk [04-frontend.md](04-frontend.md) untuk frontend components
5. Lihat [05-database.md](05-database.md) untuk query patterns

### Untuk Menambah Feature Baru
1. Kaji [03-services.md](03-services.md) untuk service structure
2. Ikuti widget pattern yang sedia ada
3. Ensure data filtering by `escalate_to`
4. Update frontend di [04-frontend.md](04-frontend.md)
5. Tambah tests di [06-testing.md](06-testing.md)

### Untuk Troubleshooting
1. Verify data filtering mechanism
2. Check query performance
3. Validate role assignment
4. Test with different pegawai users

---

## File Locations

### Backend
```
app/Services/Dashboard/
├── Role/
│   └── PegawaiTugasanDashboard.php
└── Widgets/
    ├── TugasanAndaWidget.php
    └── TicketSummaryWidget.php
```

### Frontend
```
resources/views/dashboard/partials/pegawai-tugasan/
├── cards-status-pegawai.blade.php
├── chart-status-pegawai.blade.php
├── chart-category-pegawai.blade.php
├── table-ticket-overdue-pegawai.blade.php
└── my-tasks.blade.php
```

### JavaScript
```
resources/assets/js/
└── dashboard.js (contains pegawai sections)
```

---

## Konvensyen Naming

### Database Filtering
- **Column**: `escalate_to` (user_id yang menerima assignment)
- **Filter**: `WHERE tc.escalate_to = :userId`

### Frontend IDs
- **Pattern**: Use `-pegawai` suffix
  - `statusCardPegawai`
  - `chartStatusPegawai`
  - `myTasksTable`

---

## Dokumentasi Berkaitan

- [Dashboard Management](../dashboard/README.md) - Dashboard untuk admin/helpdesk/pengurusan
- [Ticket System](../ticket/README.md) - Sistem tiket keseluruhan
- [Notification System](../notification/README.md) - Sistem notifikasi untuk pegawai

---

**Dokumentasi ini dikemaskini pada**: 5 Mac 2026
**Versi**: 1.0.0
**Untuk**: Dashboard Pegawai Tugasan
