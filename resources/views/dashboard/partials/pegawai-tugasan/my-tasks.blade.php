{{-- /* TUGASAN ANDA! - Pegawai Tugasan Dashboard */ --}}

{{-- Aduan Hari Ini / Lewat SLA / Tiket Belum Selesai / Selesai Minggu Ini --}}
@include('dashboard/partials/pegawai-tugasan/cards-status-pegawai')

{{-- Status Tiket (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/chart-status-pegawai')

{{-- Kategori Tiket (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/chart-category-pegawai')

{{-- Jadual Tiket Lewat (Tugasan Anda) --}}
@include('dashboard/partials/pegawai-tugasan/table-ticket-overdue-pegawai')
