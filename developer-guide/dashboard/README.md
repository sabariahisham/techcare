# Dashboard Documentation - Index

Dokumentasi lengkap untuk Dashboard sistem eHelp.

## Kandungan

### [01 - Gambaran Keseluruhan](01-overview.md)
- Tujuan dan matlamat dashboard
- Sokongan peranan (6 roles)
- Ciri-ciri utama
- Komponen dashboard
- Struktur fail
- Teknologi yang digunakan
- Prinsip reka bentuk
- Aliran data

### [02 - Arkitektur](02-architecture.md)
- Pattern arkitektur (Service-Oriented + Dispatcher Pattern)
- Lapisan aplikasi
- Dispatcher pattern (DashboardService)
- Role-specific dashboard services
- Widget architecture
- Data flow pattern
- Separation of concerns
- Prinsip SOLID
- Caching strategy
- Error handling

### [03 - Controller](03-controller.md)
- DashboardController overview
- Prinsip thin controller
- Main method: `index()`
- API endpoints untuk cards
  - Today logs count
  - Weekly completed tickets
  - Pending tickets stats
  - Overdue SLA count
- API endpoints untuk charts
  - Status tickets
  - Category tickets
  - Monthly trends
- API endpoints untuk tables
  - Overdue tickets list
- Route registration
- Error handling strategy
- Performance optimization

### [04 - Services](04-services.md)
- DashboardService (Dispatcher)
- Role-specific services:
  - AdminDashboard
  - HelpdeskDashboard
  - PegawaiTugasanDashboard
  - PengurusanDashboard
- Widget services:
  - TugasanAndaWidget
  - TrendBulananWidget
  - TicketSummaryWidget
  - SLAWidget
  - KategoriWidget
  - PegawaiPerformanceWidget
- Service testing
- Best practices

### [05 - Frontend (Blade & JavaScript)](05-frontend.md)
- Blade templates structure
- Key principles (no role logic in Blade)
- Partial templates:
  - Impersonate card
  - Status cards
  - Chart partials
  - Table partials
  - Pegawai tugasan partials
- JavaScript (dashboard.js):
  - Load card data
  - Initialize charts (Chart.js)
  - Initialize tables (DataTables)
  - Auto refresh
- CSS styling
- Best practices
- Performance optimization

### [06 - Database & Queries](06-database.md)
- Database schema:
  - tickets
  - tickets_chronologies
  - ref_ticket_statuses
  - ref_categories
  - users
- Query patterns untuk dashboard:
  - Today's logs count
  - Weekly completed tickets
  - Pending tickets statistics
  - Overdue SLA count
  - Status tickets chart data
  - Category tickets chart data
  - Monthly trend data
  - Overdue tickets list
- Database optimization:
  - Indexes
  - Query caching
  - Database views
  - Query optimization tips
  - Monitoring

### [07 - Testing](07-testing.md)
- Testing strategy
- Unit tests:
  - Testing services
  - Testing widgets
- Feature tests:
  - Testing controller endpoints
  - Testing API endpoints
- Integration tests
- Testing best practices
- Running tests
- Continuous integration
- Test coverage goals

### [08 - Deployment & Maintenance](08-deployment.md)
- Deployment checklist
- Deployment steps
- Environment configuration
- Performance optimization:
  - Database optimization
  - Caching strategy
  - Queue configuration
  - Asset optimization
- Monitoring & logging:
  - Application monitoring
  - Error tracking
  - Log management
- Health checks
- Maintenance tasks
- Troubleshooting
- Rollback procedure
- Security considerations

---

## Quick Start

### Untuk Developer Baru

1. Baca [01-overview.md](01-overview.md) untuk gambaran keseluruhan
2. Fahami [02-architecture.md](02-architecture.md) untuk pattern yang digunakan
3. Lihat [03-controller.md](03-controller.md) dan [04-services.md](04-services.md) untuk backend
4. Rujuk [05-frontend.md](05-frontend.md) untuk frontend
5. Pelajari [06-database.md](06-database.md) untuk query patterns

### Untuk Maintenance

1. Rujuk [08-deployment.md](08-deployment.md) untuk deployment
2. Gunakan [07-testing.md](07-testing.md) untuk testing
3. Check [06-database.md](06-database.md) untuk optimization

### Untuk Troubleshooting

1. Lihat bahagian Troubleshooting di [08-deployment.md](08-deployment.md)
2. Check logs: `storage/logs/laravel.log`
3. Run health check: `php artisan dashboard:health-check`

---

## Struktur Dashboard

```
Dashboard
├── Controller (DashboardController)
│   └── API Endpoints
├── Services
│   ├── DashboardService (Dispatcher)
│   ├── Role Services
│   │   ├── AdminDashboard
│   │   ├── HelpdeskDashboard
│   │   ├── PegawaiTugasanDashboard
│   │   └── PengurusanDashboard
│   └── Widgets
│       ├── TugasanAndaWidget
│       ├── TrendBulananWidget
│       ├── TicketSummaryWidget
│       ├── SLAWidget
│       ├── KategoriWidget
│       └── PegawaiPerformanceWidget
├── Views (Blade)
│   ├── dashboard.blade.php
│   └── partials/
│       ├── impersonate-card.blade.php
│       ├── cards-status.blade.php
│       ├── chart-*.blade.php
│       └── pegawai-tugasan/
└── JavaScript
    └── dashboard.js
```

---

## Konvensyen Naming

### PHP
- **Controllers**: `DashboardController`
- **Services**: `DashboardService`, `AdminDashboard`
- **Widgets**: `TugasanAndaWidget`, `SLAWidget`
- **Methods**: camelCase (`getData`, `getTodayLogsCount`)

### Database
- **Tables**: snake_case (`tickets`, `tickets_chronologies`)
- **Columns**: snake_case (`tick_status`, `escalate_to`)
- **Indexes**: `idx_` prefix (`idx_ticket_status`)

### Frontend
- **Files**: kebab-case (`dashboard.blade.php`, `chart-status.blade.php`)
- **IDs**: camelCase (`todayLogsCount`, `chartStatus`)
- **Classes**: kebab-case or BEM (`card-body`, `avatar-initial`)

### Routes
- **Prefix**: `/dashboard`
- **Names**: dot notation (`dashboard.index`, `dashboard.status-tickets`)

---

## Environment Setup

### Development
```bash
# Clone repository
git clone https://github.com/your-org/ehelp.git
cd ehelp

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed

# Build assets
npm run dev

# Start server
php artisan serve
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/Dashboard

# Run with coverage
php artisan test --coverage
```

---

## Support & Contact

Untuk soalan atau bantuan:
- **Internal Wiki**: https://wiki.internal/ehelp/dashboard
- **Issue Tracker**: https://github.com/your-org/ehelp/issues
- **Team Contact**: ehelp-dev@example.com

---

## Changelog

### Version 1.0.0 (2026-01-28)
- Initial dashboard implementation
- 6 role support
- 4 main status cards
- Status, category, and trend charts
- Overdue tickets table
- Real-time updates
- Complete documentation

---

## License

Internal use only - Kementerian Kewangan Malaysia

---

**Dokumentasi ini dikemaskini pada**: 28 Januari 2026
**Versi**: 1.0.0
