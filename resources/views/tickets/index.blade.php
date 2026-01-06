@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/scss/components/offcanvas-tickets.scss', 'resources/assets/scss/pages/tickets.scss', 'resources/assets/css/ticket.css'])
@endsection

@section('page-style')
    {{-- Include ticket-specific styles partial for any dynamic or PHP-generated styles --}}
    @include('tickets.partials.styles')
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/config.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/ticket/list.js', 'resources/assets/js/ticket/config.js', 'resources/assets/js/ticket/create.js', 'resources/assets/js/ticket/edit.js', 'resources/assets/js/ticket/search-user.js', 'resources/assets/js/ticket/attachment.js'])
@endsection

@section('content')
    <h4 class="mb-1">Senarai Tiket</h4>
    <p class="mb-6">Papar dan tambah tiket berdasarkan peranan pengguna. Tiket yang telah dihantar tidak boleh diedit.</p>

    <!-- My Tickets Table -->
    <div class="card mb-4">
        <div class="card-header">
            <div>
                <h5 class="card-title mb-0">Tiket Saya</h5>
                <p class="text-muted small mb-0">Senarai tiket yang berkaitan dengan {{ auth()->user()->nama }}</p>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-my-tickets table border-top">
                <thead class="border-top">
                    <tr>
                        <th>Bil</th>
                        <th>No. Log Tiket</th>
                        <th>Tarikh Aduan</th>
                        <th>Jenis Tiket</th>
                        <th>Kategori</th>
                        <th>Sub Kategori</th>
                        <th>Status</th>
                        <th>Pegawai Tugasan</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan']))
        <!-- Tickets to Manage Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    @if (auth()->user()->hasRole('Administrator'))
                        Semua Tiket (Administrator)
                    @elseif(auth()->user()->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']))
                        Tiket untuk Diurus ({{ auth()->user()->roles->first()->name }})
                    @elseif(auth()->user()->hasRole('Pegawai Tugasan'))
                        Tiket Tugasan Saya
                    @endif
                </h5>
                <p class="text-muted small mb-0">
                    @if (auth()->user()->hasRole('Administrator'))
                        Senarai semua tiket dalam sistem
                    @elseif(auth()->user()->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']))
                        Senarai semua tiket untuk diproses dan diurus
                    @elseif(auth()->user()->hasRole('Pegawai Tugasan'))
                        Senarai tiket yang diserahkan kepada anda
                    @endif
                </p>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-manage-tickets table border-top">
                    <thead class="border-top">
                        <tr>
                            <th>Bil</th>
                            <th>No. Log Tiket</th>
                            <th>Tarikh Aduan</th>
                            <th>Nama Pengadu</th>
                            <th>Kategori</th>
                            <th>Sub Kategori</th>
                            <th>Status</th>
                            <th>Pegawai Tugasan</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    @endif

    <!-- Offcanvas Add Ticket -->
    @include('tickets.partials.offcanvas-add')

    <!-- Offcanvas Edit Ticket -->
    @include('tickets.partials.offcanvas-edit')

@endsection
