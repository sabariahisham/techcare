# 01 - Dashboard: Gambaran Keseluruhan

## Tujuan
Dashboard sistem eHelp menyediakan paparan ringkas aktiviti, statistik dan prestasi semasa untuk rujukan pantas pengguna berdasarkan peranan mereka.

## Sokongan Peranan (Roles)
Dashboard menyokong 6 peranan utama:
1. **Administrator** - Paparan pentadbiran penuh
2. **Pentadbir Sistem** - Paparan helpdesk dan sistem
3. **Penyelia Helpdesk** - Paparan penyeliaan helpdesk
4. **Helpdesk On Duty** - Paparan operasi helpdesk
5. **Pegawai Tugasan** - Paparan tugasan yang diperuntukkan kepada pegawai
6. **Pengurusan MOF** - Paparan pengurusan dan laporan eksekutif

## Ciri-ciri Utama
- **Role-Based Dashboard**: Setiap peranan mempunyai pandangan yang disesuaikan
- **Real-time Statistics**: Statistik tiket terkini
- **Interactive Charts**: Carta dan graf interaktif menggunakan Chart.js
- **SLA Monitoring**: Pemantauan dan amaran SLA
- **Session Timer**: Penunjuk masa sesi yang tinggal
- **Impersonation Support**: Sokongan untuk pentadbir impersonate pengguna lain

## Komponen Dashboard
1. **Welcome Card** - Kad alu-aluan dengan nama pengguna
2. **Session Timer** - Widget masa sesi
3. **Status Cards** - Kad statistik ringkas (4 kad utama)
4. **Charts & Graphs** - Carta status, kategori, trend bulanan
5. **Tables** - Jadual tiket lewat, tugasan pegawai
6. **Impersonation Banner** - Banner amaran semasa impersonate

## Struktur Fail
```
resources/views/dashboard/
├── dashboard.blade.php              # Main dashboard view
└── partials/
    ├── impersonate-card.blade.php   # Impersonation banner
    ├── cards-status.blade.php       # Status cards (admin/helpdesk)
    ├── card-status-user.blade.php   # User-specific status
    ├── chart-status.blade.php       # Status chart
    ├── chart-category.blade.php     # Category chart
    ├── chart-total.blade.php        # Total tickets chart
    ├── chart-trend.blade.php        # Monthly trend chart
    ├── chart-assigned.blade.php     # Assigned tickets chart
    ├── table-ticket-overdue.blade.php # Overdue tickets table
    └── pegawai-tugasan/
        ├── cards-status-pegawai.blade.php
        ├── chart-status-pegawai.blade.php
        ├── chart-category-pegawai.blade.php
        ├── table-ticket-overdue-pegawai.blade.php
        └── my-tasks.blade.php

app/Http/Controllers/Dashboard/
└── DashboardController.php          # Main dashboard controller

app/Services/Dashboard/
├── DashboardService.php             # Service dispatcher
├── Role/
│   ├── AdminDashboard.php
│   ├── HelpdeskDashboard.php
│   ├── PegawaiTugasanDashboard.php
│   └── PengurusanDashboard.php
└── Widgets/
    ├── TugasanAndaWidget.php
    ├── TrendBulananWidget.php
    ├── TicketSummaryWidget.php
    ├── SLAWidget.php
    ├── PegawaiPerformanceWidget.php
    └── KategoriWidget.php

resources/assets/js/
└── dashboard.js                     # Dashboard JavaScript logic
```

## Teknologi Digunakan
- **Backend**: Laravel 11, PHP 8.x
- **Frontend**: Blade Templates, Bootstrap 5
- **Charts**: Chart.js
- **DataTables**: For interactive tables
- **JavaScript**: Vanilla JS with modular approach

## Prinsip Reka Bentuk
1. **Separation of Concerns**: Controller, Service, dan View dipisahkan dengan jelas
2. **Role-based Access**: Logik peranan diuruskan di backend, bukan di Blade
3. **Reusable Components**: Widget dan partial boleh diguna semula
4. **Responsive Design**: Dashboard responsif untuk semua saiz skrin
5. **Performance**: Lazy loading dan caching untuk prestasi optimum

## Aliran Data
```
User Request
    ↓
DashboardController::index()
    ↓
DashboardService::getDashboardData($user)
    ↓
[Role-specific Dashboard Service]
    ↓
[Widget Services]
    ↓
Return data array to Blade
    ↓
dashboard.blade.php renders with partials
```

## Keselamatan
- Semua route dilindungi dengan authentication middleware
- Role checking dilakukan di backend (DashboardService)
- Input sanitization dan validation
- CSRF protection pada semua form
- XSS prevention dalam Blade templates

## Dokumentasi Berkaitan
- Lihat `02-architecture.md` untuk butiran arkitektur
- Lihat `03-controller.md` untuk maklumat controller
- Lihat `04-services.md` untuk maklumat services
- Lihat `05-widgets.md` untuk maklumat widgets
- Lihat `06-frontend.md` untuk maklumat frontend
