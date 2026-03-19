{{-- /* TUGASAN ANDA! - Pegawai Tugasan Dashboard */ --}}

{{-- Auto-Refresh Controls --}}
@include('dashboard/partials/pegawai-tugasan/refresh-controls')

{{-- Aduan Hari Ini / Lewat SLA / Tiket Belum Selesai / Selesai Minggu Ini --}}
@include('dashboard/partials/pegawai-tugasan/cards-status-pegawai')

{{-- Status Tiket (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/chart-status-pegawai')

{{-- close this chart after UAT(04032026)  --}}
{{-- Kategori Tiket (Tugasan Anda) --}}
{{-- @include('dashboard/partials/pegawai-tugasan/chart-category-pegawai') --}}

{{-- Sub Kategori Tiket (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/chart-subcategory-pegawai')

{{-- Jadual Tiket Lewat (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/table-ticket-overdue-pegawai')
