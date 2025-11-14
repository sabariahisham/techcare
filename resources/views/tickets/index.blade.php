@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    <style>
        /* Custom Offcanvas Styles - Responsive Percentage Widths */
        .offcanvas-50 {
            width: 30% !important;
        }

        /* .offcanvas-60 {
                                      width: 60% !important;
                                      } */

        /* Responsive breakpoints for offcanvas */

        /* Between 1200px and 1600px (typical laptops and medium desktops) */
        @media (max-width: 1600px) and (min-width: 1201px) {
            .offcanvas-50 {
                width: 45% !important;
            }
        }

        /* Responsive breakpoints for smaller screens */
        @media (max-width: 1200px) {

            /* .offcanvas-60, */
            .offcanvas-50 {
                width: 70% !important;
            }
        }

        @media (max-width: 992px) {

            /* .offcanvas-60, */
            .offcanvas-50 {
                width: 80% !important;
            }
        }

        @media (max-width: 768px) {

            /* .offcanvas-60, */
            .offcanvas-50 {
                width: 90% !important;
            }
        }

        @media (max-width: 576px) {

            /* .offcanvas-60, */
            .offcanvas-50 {
                width: 95% !important;
            }
        }

        /* Ensure offcanvas content is scrollable on smaller screens */
        .offcanvas-body {
            overflow-y: auto;
            padding: 1rem;
        }

        /* Fix Select2 dropdown z-index in offcanvas */
        .select2-container {
            z-index: 9999 !important;
        }

        .select2-dropdown {
            z-index: 9999 !important;
        }

        /* Ensure Select2 container has proper width */
        .select2-container--bootstrap-5 {
            width: 100% !important;
        }

        /* Fix Select2 in offcanvas */
        .offcanvas .select2-container {
            width: 100% !important;
        }

        /* Make form-select border styling match form-control */
        .form-select {
            display: block;
            width: 100%;
            padding: 0.4375rem 2.25rem 0.4375rem 0.875rem;
            font-size: 0.9375rem;
            font-weight: 400;
            line-height: 1.53;
            color: #697a8d;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23697a8d' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.875rem center;
            background-size: 16px 12px;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            appearance: none;
        }

        .form-select:focus {
            border-color: #696cff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
        }

        .form-select:disabled,
        .form-select[readonly] {
            background-color: #f5f5f9;
        }

        /* Ensure Select2 elements also match form-control styling */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
            padding: 0.4375rem 0.875rem !important;
            font-size: 0.9375rem !important;
            line-height: 1.53 !important;
            border: 1px solid #d9dee3 !important;
            border-radius: 0.375rem !important;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection--single {
            border-color: #696cff !important;
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25) !important;
        }

        /* Remove focus styling from Select2 elements - normal state */
        .select2-container--bootstrap-5 .select2-selection--single:not(.select2-container--focus) {
            border-color: #d9dee3 !important;
            box-shadow: none !important;
        }

        /* Override focus state when user wants normal styling */
        .select2-normal-state .select2-container--bootstrap-5 .select2-selection--single,
        .select2-normal-state .select2-container--bootstrap-5.select2-container--focus .select2-selection--single {
            border-color: #d9dee3 !important;
            box-shadow: none !important;
        }

        /* Select2 Error State */
        .select2-container.is-invalid .select2-selection--single {
            border-color: #ea5455 !important;
            box-shadow: 0 0 0 0.2rem rgba(234, 84, 85, 0.25) !important;
        }

        /* Style for invalid feedback message next to Select2 */
        .select2-container+.invalid-feedback {
            display: block !important;
            color: #ea5455;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* File input error state styling - match Bootstrap form-control styling */
        input[type="file"].is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4m0-1.4-1.4 1.4'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
        }

        input[type="file"].is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Make sure file input shows error state clearly - Bootstrap style */
        .form-field-wrapper input[type="file"].is-invalid,
        input[name="attachments[]"].is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff !important;
        }

        /* File input focus state when invalid */
        .form-field-wrapper input[type="file"].is-invalid:focus,
        input[name="attachments[]"].is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
            background-color: #fff !important;
        }

        /* Ensure invalid feedback is visible for all field types */
        .invalid-feedback {
            display: block !important;
            color: #ea5455;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Search user section styling */
        .user-search-section {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }

        .search-loading {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Remove the green box styling - just regular styling for found user info */
        #foundUserInfo,
        #editFoundUserInfo {
            margin-top: 1rem;
        }

        /* Subtle styling for readonly fields */
        input[readonly].form-control {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #6c757d;
        }

        .input-group .btn {
            white-space: nowrap;
        }

        /* Standard smaller font size for form elements */
        .offcanvas .form-label {
            font-size: 0.8125rem !important;
            /* 13px */
            font-weight: 500;
            margin-bottom: 0.375rem;
        }

        .offcanvas .form-control,
        .offcanvas .form-select {
            font-size: 0.8125rem !important;
            /* 13px */
            padding: 0.375rem 0.75rem !important;
            line-height: 1.4 !important;
        }

        .offcanvas textarea.form-control {
            font-size: 0.8125rem !important;
            /* 13px */
            line-height: 1.4 !important;
        }

        .offcanvas .btn {
            font-size: 0.8125rem !important;
            /* 13px */
            padding: 0.375rem 0.75rem !important;
        }

        .offcanvas h6 {
            font-size: 0.9375rem !important;
            /* 15px */
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .offcanvas h5 {
            font-size: 1rem !important;
            /* 16px */
            font-weight: 600;
        }

        .offcanvas .text-muted,
        .offcanvas small {
            font-size: 0.75rem !important;
            /* 12px */
        }

        /* Ensure Select2 elements also use smaller font */
        .offcanvas .select2-container--bootstrap-5 .select2-selection--single {
            font-size: 0.8125rem !important;
            /* 13px */
            padding: 0.375rem 0.75rem !important;
            line-height: 1.4 !important;
        }

        .offcanvas .select2-container--bootstrap-5 .select2-selection__rendered {
            font-size: 0.8125rem !important;
            /* 13px */
            line-height: 1.4 !important;
        }

        /* Adjust input group button to match smaller inputs */
        .offcanvas .input-group .btn {
            font-size: 0.8125rem !important;
            /* 13px */
            padding: 0.375rem 0.75rem !important;
        }

        /* Make sure file input error text is also smaller */
        .offcanvas .error-text {
            font-size: 0.75rem !important;
            /* 12px */
        }

        /* Spinner and loading text smaller */
        .offcanvas .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .offcanvas .search-loading,
        .offcanvas .alert {
            font-size: 0.8125rem !important;
            /* 13px */
        }

        /* Custom purple badge for status */
        /* .badge.bg-purple {
                                    background-color: #7b68ee !important;
                                    color: #fff !important;
                                } */
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/config.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/ticket-list.js',
        'resources/assets/js/ticket.js',
        // 'resources/assets/js/ui-toasts.js',
        // 'resources/assets/js/extended-ui-sweetalert2.js',
    ])


@endsection

@section('content')
    <h4 class="mb-1">Senarai Tiket</h4>

    <p class="mb-6">Papar, tambah, edit atau padam tiket berdasarkan peranan pengguna.</p>

    <!-- My Tickets Table -->
    <div class="card mb-4">
        <div class="card-header">
            <div>
                <h4 class="card-title mb-0">Tiket Aduan</h4>
                <p class="text-muted mb-0">Senarai tiket yang berkaitan dengan {{ auth()->user()->nama }}</p>
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

    <!-- Offcanvas to add new ticket -->
    <div class="offcanvas offcanvas-end offcanvas-50" tabindex="-1" id="offcanvasAddTicket"
        aria-labelledby="offcanvasAddTicketLabel">
        <div class="offcanvas-header border-bottom align-items-start">
        <div class="me-3">
            <h5 id="offcanvasAddTicketLabel" class="mb-0">Tambah Log Tiket</h5>
            <small class="text-muted fst-italic d-block mt-1">
                ***Maluman:<br>Data dalam ruangan ini diselaraskan daripada <strong>Sistem Pergerakan Pegawai (SPP)</strong>. Sebarang kemas kini hendaklah dibuat di sistem tersebut.
            </small>
        </div>
        <button type="button" class="btn-close text-reset mt-1" data-bs-dismiss="offcanvas"></button>
    </div>

        <div class="offcanvas-body">
            <form id="addTicketForm" enctype="multipart/form-data">
                @csrf

                <!-- Jenis Aduan -->
                <div class="mb-3">
                    <label class="form-label">Jenis Aduan</label>
                    <select id="ticketType" name="ticket_type" class="form-select" required>
                        <option value="1">Diri Sendiri</option>
                        <option value="0">Bagi Pihak</option>
                    </select>
                </div>

                <!-- Auto papar jika Diri Sendiri -->
                <div id="selfInfo" class="mb-4">
                    <h6>Maklumat Pengadu</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Pengadu</label>
                            <input type="text" class="form-control" name="name" value="{{ auth()->user()->nama }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Jawatan</label>
                            <input type="text" class="form-control" name="jawatan"
                                value="{{ auth()->user()->jawatan_name }}" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Skim Perkhidmatan</label>
                            <input type="text" class="form-control" name="skim"
                                value="{{ auth()->user()->skim?->nama_skim ? auth()->user()->skim->nama_skim . ' (' . auth()->user()->kod_skim . ')' : auth()->user()->kod_skim ?? '-' }}"
                                readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Gred</label>
                            <input type="text" class="form-control" name="gred"
                                value="{{ auth()->user()->nama_gred ?? '-' }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Emel</label>
                            <input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sektor</label>
                            <input type="text" class="form-control" name="sektor"
                                value="{{ auth()->user()->sektor->nama_sektor ?? (auth()->user()->kod_sektor ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bahagian</label>
                            <input type="text" class="form-control" name="agency"
                                value="{{ auth()->user()->bahagian->nama_bah ?? (auth()->user()->kod_bah ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Seksyen</label>
                            <input type="text" class="form-control" name="seksyen"
                                value="{{ auth()->user()->seksyen->nama_sec ?? (auth()->user()->kod_sec ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" name="unit"
                                value="{{ auth()->user()->unit->nama_unit ?? (auth()->user()->kod_unit ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon Pejabat</label>
                            <input type="text" class="form-control" name="telefon_pejabat"
                                value="{{ auth()->user()->tel_no_p ?? '-' }}" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon Bimbit</label>
                            <input type="text" class="form-control" name="telefon_bimbit"
                                value="{{ auth()->user()->tel_no_hp ?? '-' }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Papar hanya jika Bagi Pihak -->
                <div id="otherInfo" class="d-none mb-4">
                    <h6>Maklumat Bagi Pihak</h6>
                    <div class="row g-3">
                        <!-- Email search field -->
                        <div class="col-12">
                            <label class="form-label">Cari Emel Pengguna</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search_email"
                                    placeholder="Contoh: user atau user@treasury.gov.my">
                                <button class="btn btn-outline-primary" type="button" id="searchUserBtn">
                                    <i class="bx bx-search"></i> Cari
                                </button>
                            </div>
                            <small class="text-muted">Masukkan nama pengguna (tanpa @treasury.gov.my) atau alamat emel
                                penuh</small>
                        </div>

                        <!-- Alert container for search errors -->
                        <div class="col-12" id="searchAlert" style="display: none;">
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <span id="searchAlertMessage"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>

                        <!-- Loading indicator -->
                        <div class="col-12" id="searchLoading" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Mencari...</span>
                                </div>
                                <span class="ms-2">Mencari maklumat pengguna...</span>
                            </div>
                        </div>

                        <!-- User info fields (hidden by default) -->
                        <div id="foundUserInfo" style="display: none;">
                            <div class="row g-3">
                                <!-- Button to clear and search again -->
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearchBtn">
                                        <i class="bx bx-refresh"></i> Cari Pengguna Lain
                                    </button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nama Pengadu</label>
                                    <input type="text" class="form-control" id="other_name" name="other_name"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Jawatan</label>
                                    <input type="text" class="form-control" id="other_jawatan" name="other_jawatan"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Skim Perkhidmatan</label>
                                    <input type="text" class="form-control" id="other_skim" name="other_skim"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Gred</label>
                                    <input type="text" class="form-control" id="other_gred" name="other_gred"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Emel</label>
                                    <input type="email" class="form-control" id="other_email" name="other_email"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Sektor</label>
                                    <input type="text" class="form-control" id="other_sektor" name="other_sektor"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bahagian</label>
                                    <input type="text" class="form-control" id="other_agency" name="other_agency"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Seksyen</label>
                                    <input type="text" class="form-control" id="other_seksyen" name="other_seksyen"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="other_unit" name="other_unit"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Telefon Pejabat</label>
                                    <input type="text" class="form-control" id="other_p" name="telefon_pejabat"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Telefon Bimbit</label>
                                    <input type="text" class="form-control" id="other_hp" name="telefon_bimbit"
                                        readonly>
                                </div>
                                <!-- Button to clear and search again -->
                                {{-- <div class="col-12 mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        id="editClearSearchBtn1">
                                        <i class="bx bx-refresh"></i> Cari Pengguna Lain
                                    </button>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Form Fields (hidden when Bagi Pihak is selected but no user found) -->
                <div id="ticketFormFields">
                    <!-- Tajuk -->
                    <div class="mb-3">
                        <label class="form-label">Tajuk</label>
                        <input type="text" name="tick_title" class="form-control" required>
                    </div>

                    <!-- Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="cat_id" id="cat_id" class="form-select" data-validation="custom">
                            <option value="">-- Pilih Kategori --</option>
                            @if (isset($categories))
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Sub Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Sub Kategori</label>
                        <select name="subcat_id" id="subcat_id" class="form-select" data-validation="custom">
                            <option value="">-- Pilih Sub Kategori --</option>
                        </select>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="tick_desc" class="form-control" rows="4" required></textarea>
                    </div>

                    <!-- Lampiran -->
                    <div class="form-field-wrapper mb-3">
                        <label class="form-label">Lampiran</label>
                        <input type="file" name="attachments[]" class="form-control" multiple
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Format yang dibenarkan: PDF, DOC, DOCX, JPG, JPEG, PNG. Saiz maksimum
                            setiap fail 2MB</small>
                        <div class="error-text text-danger mt-1" style="display:none;"></div>
                    </div>

                    <!-- Butang -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan & Hantar</button>
                        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    </div>

    <!-- Offcanvas to edit ticket -->
    <div class="offcanvas offcanvas-end offcanvas-50" tabindex="-1" id="offcanvasEditTicket"
        aria-labelledby="offcanvasEditTicketLabel">
        <div class="offcanvas-header border-bottom">
            <h5 id="offcanvasEditTicketLabel">Kemaskini Log Tiket</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <form id="editTicketForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="ticket_id" id="edit_ticket_id">

                <!-- Jenis Aduan -->
                <div class="mb-3">
                    <label class="form-label">Jenis Aduan</label>
                    <select id="edit_ticketType" name="ticket_type" class="form-select" required>
                        <option value="1">Diri Sendiri</option>
                        <option value="0">Bagi Pihak</option>
                    </select>
                </div>

                <!-- Auto papar jika Diri Sendiri -->
                <div id="edit_selfInfo" class="mb-4">
                    <h6>Maklumat Pengadu</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Pengadu</label>
                            <input type="text" class="form-control" name="name"
                                value="{{ auth()->user()->nama }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Jawatan</label>
                            <input type="text" class="form-control" name="jawatan"
                                value="{{ auth()->user()->jawatan_name }}" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Skim Perkhidmatan</label>
                            <input type="text" class="form-control" name="skim"
                                value="{{ auth()->user()->skim?->nama_skim ? auth()->user()->skim->nama_skim . ' (' . auth()->user()->kod_skim . ')' : auth()->user()->kod_skim ?? '-' }}"
                                readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Gred</label>
                            <input type="text" class="form-control" name="gred"
                                value="{{ auth()->user()->nama_gred ?? '-' }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Emel</label>
                            <input type="email" class="form-control"name="email" value="{{ auth()->user()->email }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sektor</label>
                            <input type="text" class="form-control" name="sektor"
                                value="{{ auth()->user()->sektor->nama_sektor ?? (auth()->user()->kod_sektor ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bahagian</label>
                            <input type="text" class="form-control" name="agency"
                                value="{{ auth()->user()->bahagian->nama_bah ?? (auth()->user()->kod_bah ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Seksyen</label>
                            <input type="text" class="form-control" name="seksyen"
                                value="{{ auth()->user()->seksyen->nama_sec ?? (auth()->user()->kod_sec ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" name="unit"
                                value="{{ auth()->user()->unit->nama_unit ?? (auth()->user()->kod_unit ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon Pejabat</label>
                            <input type="text" class="form-control" name="telefon_pejabat"
                                value="{{ auth()->user()->tel_no_p ?? '-' }}" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon Bimbit</label>
                            <input type="text" class="form-control" name="telefon_bimbit"
                                value="{{ auth()->user()->tel_no_hp ?? '-' }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Papar hanya jika Bagi Pihak -->
                <div id="edit_otherInfo" class="d-none mb-4">
                    <h6>Maklumat Bagi Pihak</h6>
                    <div class="row g-3">
                        <!-- Email search field -->
                        <div class="col-12">
                            <label class="form-label">Cari Emel Pengguna</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit_search_email"
                                    placeholder="Contoh: user atau user@treasury.gov.my">
                                <button class="btn btn-outline-primary" type="button" id="editSearchUserBtn">
                                    <i class="bx bx-search"></i> Cari
                                </button>
                            </div>
                            <small class="text-muted">Masukkan nama pengguna (tanpa @treasury.gov.my) atau alamat emel
                                penuh</small>
                        </div>

                        <!-- Alert container for search errors -->
                        <div class="col-12" id="editSearchAlert" style="display: none;">
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <span id="editSearchAlertMessage"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>

                        <!-- Loading indicator -->
                        <div class="col-12" id="editSearchLoading" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Mencari...</span>
                                </div>
                                <span class="ms-2">Mencari maklumat pengguna...</span>
                            </div>
                        </div>

                        <!-- User info fields (hidden by default) -->
                        <div id="editFoundUserInfo" style="display: none;">
                            <div class="row g-3">
                                <!-- Button to clear and search again -->
                                <div class="col-12 mb-3 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        id="editClearSearchBtn">
                                        <i class="bx bx-refresh"></i> Cari Pengguna Lain
                                    </button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nama Pengadu</label>
                                    <input type="text" class="form-control" id="edit_other_name" name="other_name"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Jawatan</label>
                                    <input type="text" class="form-control" id="edit_other_jawatan"
                                        name="other_jawatan" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Skim Perkhidmatan</label>
                                    <input type="text" class="form-control" id="edit_other_skim" name="other_skim"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Gred</label>
                                    <input type="text" class="form-control" id="edit_other_gred" name="other_gred"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Emel</label>
                                    <input type="email" class="form-control" id="edit_other_email" name="other_email"
                                        readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Sektor</label>
                                    <input type="text" class="form-control" id="edit_other_sektor"
                                        name="other_sektor" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bahagian</label>
                                    <input type="text" class="form-control" id="edit_other_agency"
                                        name="other_agency" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Seksyen</label>
                                    <input type="text" class="form-control" id="edit_other_seksyen"
                                        name="other_seksyen" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="edit_other_unit" name="other_unit"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Telefon Pejabat</label>
                                    <input type="text" class="form-control" id="edit_other_p" name="telefon_pejabat"
                                        readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Telefon Bimbit</label>
                                    <input type="text" class="form-control" id="edit_other_hp" name="telefon_bimbit"
                                        readonly>
                                </div>

                                <!-- Button to clear and search again -->
                                {{-- <div class="col-12 mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        id="editClearSearchBtn1">
                                        <i class="bx bx-refresh"></i> Cari Pengguna Lain
                                    </button>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Ticket Form Fields (hidden when Bagi Pihak is selected but no user found) -->
                <div id="editTicketFormFields">
                    <!-- Tajuk -->
                    <div class="mb-3">
                        <label class="form-label">Tajuk</label>
                        <input type="text" name="tick_title" id="edit_tick_title" class="form-control" required>
                    </div>

                    <!-- Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="cat_id" id="edit_cat_id" class="form-select" data-validation="custom">
                            <option value="">-- Pilih Kategori --</option>
                            @if (isset($categories))
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->cat_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Sub Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Sub Kategori</label>
                        <select name="subcat_id" id="edit_subcat_id" class="form-select" data-validation="custom">
                            <option value="">-- Pilih Sub Kategori --</option>
                        </select>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="tick_desc" id="edit_tick_desc" class="form-control" rows="4" required></textarea>
                    </div>

                    <!-- Lampiran -->
                    <div class="form-field-wrapper mb-3">
                        <label class="form-label">Lampiran (jika ingin gantikan)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Format yang dibenarkan: PDF, DOC, DOCX, JPG, JPEG, PNG. Saiz maksimum
                            setiap fail 2MB</small>
                        <div class="error-text text-danger mt-1" style="display:none;"></div>
                    </div>

                    <!-- Butang -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Kemaskini Tiket</button>
                        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!--/ Ticket Table -->

@endsection
