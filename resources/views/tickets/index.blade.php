@extends('layouts/layoutMaster')

@section('title', $pageTitle ?? 'Senarai Tiket')

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
    <script>
        // Pass viewType to JavaScript for DataTables configuration
        window.ticketViewType = '{{ $viewType ?? 'all' }}';
    </script>
@endsection

@section('content')

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-1">{{ $pageTitle ?? 'Senarai Tiket' }}</h4>
            <p class="mb-0 text-muted">
                @if (($viewType ?? 'all') === 'user')
                    Senarai semua tiket yang direkod oleh anda.
                @elseif(($viewType ?? 'all') === 'assigned')
                    Senarai tiket yang ditugaskan kepada anda sebagai Pegawai Tugasan.
                @else
                    Paparan keseluruhan semua tiket yang direkodkan.
                @endif
            </p>
        </div>
    </div>

    @php
        $viewType = $viewType ?? 'all';
        $showMyTickets = in_array($viewType, ['user']); // Only show "Tiket Saya" for 'user' viewType
        $showManageTickets =
            in_array($viewType, ['admin', 'assigned']) &&
            auth()
                ->user()
                ->hasAnyRole([
                    'Administrator',  // admin
                    'Pentadbir Sistem', // admin
                    'Penyelia Helpdesk', // admin
                    'Helpdesk On Duty', // admin
                    'Pegawai Tugasan', // assigned
                ]);
    @endphp

    @if ($showMyTickets)
        <!-- My Tickets Table -->
        <div class="card {{ $showManageTickets ? 'mb-4' : '' }}">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="card-title mb-0">
                            Tiket Saya
                            [<strong class="text-primary">
                                Direkod (Log) oleh: {{ auth()->user()->nama }}
                            </strong>]
                        </h5>
                        <p class="text-muted small mb-0">
                            Anda boleh menambah aduan baharu dan melihat status tiket semasa.
                        </p>
                    </div>
                    <div>
                        <a href="javascript:;" class="btn btn-primary add-new" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasAddTicket" aria-controls="offcanvasAddTicket">
                            <i class="bx bx-plus me-1"></i> Aduan Baharu
                        </a>
                    </div>

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
    @endif

    @if ($showManageTickets)
        <!-- Tickets to Manage Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    @if ($viewType === 'assigned')
                        Tiket Tugasan Anda [<strong class="text-warning">Ditugaskan kepada: {{ auth()->user()->nama }}</strong>]
                    @else
                    Status Tiket Untuk Tindakan     
                    {{-- [<strong class="text-warning">{{ auth()->user()->roles->first()->name }}</strong>] --}}
                    @endif
                </h5>
                <p class="text-muted small mb-0">
                    @if ($viewType === 'assigned')
                        Kemaskini maklumat tiket dan tindakan yang diambil bagi tiket yang ditugaskan kepada pegawai.
                    @else
                        Senarai semua tiket yang memerlukan pemantauan dan tindakan selanjutnya.
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
