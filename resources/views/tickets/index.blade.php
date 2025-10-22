@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    <style>
        /* Custom Offcanvas Styles - Responsive Percentage Widths */
        .offcanvas-50 {
            width: 50% !important;
        }

        /* .offcanvas-60 {
                                        width: 60% !important;
                                    } */

        /* Responsive breakpoints for offcanvas */
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
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/config.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/ticket-list.js',
        'resources/js/ticket.js',
        // 'resources/assets/js/ui-toasts.js',
        // 'resources/assets/js/extended-ui-sweetalert2.js',
    ])


@endsection

@section('content')
    <h4 class="mb-1">Senarai Tiket</h4>

    <p class="mb-6">Papar, tambah, edit atau padam tiket.</p>
    <!-- Ticket Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-tickets table border-top">
                <thead class="border-top">
                    <tr>
                        <th>Bil</th>
                        <th>Tarikh Aduan</th>
                        <th>Nama Pengadu</th>
                        <th>Kategori</th>
                        <th>Sub Kategori</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Offcanvas to add new ticket -->
        <div class="offcanvas offcanvas-end offcanvas-50" tabindex="-1" id="offcanvasAddTicket"
            aria-labelledby="offcanvasAddTicketLabel">
            <div class="offcanvas-header border-bottom">
                <h5 id="offcanvasAddTicketLabel">Tambah Log Tiket</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
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
                                <label class="form-label">Emel</label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ auth()->user()->email }}" readonly>
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
                        </div>
                    </div>

                    <!-- Papar hanya jika Bagi Pihak -->
                    <div id="otherInfo" class="d-none mb-4">
                        <h6>Maklumat Bagi Pihak</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Pengadu</label>
                                <input type="text" class="form-control" placeholder="Nama Pengadu" name="other_name">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Emel</label>
                                <input type="email" class="form-control" placeholder="Emel" name="other_email">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Sektor</label>
                                <select class="form-select" name="other_sektor">
                                    <option value="">-- Pilih Sektor --</option>
                                    {{-- @foreach ($sektors as $sek) --}}
                                    {{-- <option value="{{ $sek->id }}">{{ $sek->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bahagian</label>
                                <select class="form-select" name="other_agency">
                                    <option value="">-- Pilih Bahagian --</option>
                                    {{-- @foreach ($agencies as $ag) --}}
                                    {{-- <option value="{{ $ag->id }}">{{ $ag->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Seksyen</label>
                                <select class="form-select" name="other_seksyen">
                                    <option value="">-- Pilih Seksyen --</option>
                                    {{-- @foreach ($seksyens as $sx) --}}
                                    {{-- <option value="{{ $sx->id }}">{{ $sx->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Unit</label>
                                <select class="form-select" name="other_unit">
                                    <option value="">-- Pilih Unit --</option>
                                    {{-- @foreach ($units as $u) --}}
                                    {{-- <option value="{{ $u->id }}">{{ $u->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                        </div>
                    </div>

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
                    <div class="mb-3">
                        <label class="form-label">Lampiran</label>
                        <input type="file" name="attachments[]" class="form-control" multiple
                            accept=".pdf,.doc,.jpg,.png">
                        <small class="text-muted">Saiz maksimum setiap fail 2MB</small>
                    </div>

                    <!-- Butang -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan & Hantar</button>
                        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
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
                            <input type="text" class="form-control" id="edit_name" name="name"
                                value="{{ auth()->user()->nama }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Emel</label>
                            <input type="email" class="form-control" id="edit_email" name="email"
                                value="{{ auth()->user()->email }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sektor</label>
                            <input type="text" class="form-control" id="edit_sektor" name="sektor"
                                value="{{ auth()->user()->sektor->nama_sektor ?? (auth()->user()->kod_sektor ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bahagian</label>
                            <input type="text" class="form-control" id="edit_agency" name="agency"
                                value="{{ auth()->user()->bahagian->nama_bah ?? (auth()->user()->kod_bah ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Seksyen</label>
                            <input type="text" class="form-control" id="edit_seksyen" name="seksyen"
                                value="{{ auth()->user()->seksyen->nama_sec ?? (auth()->user()->kod_sec ?? '-') }}"
                                readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" id="edit_unit" name="unit"
                                value="{{ auth()->user()->unit->nama_unit ?? (auth()->user()->kod_unit ?? '-') }}"
                                readonly>
                        </div>
                    </div>
                </div>

                <!-- Papar hanya jika Bagi Pihak -->
                <div id="edit_otherInfo" class="d-none mb-4">
                    <h6>Maklumat Bagi Pihak</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Pengadu</label>
                            <input type="text" class="form-control" id="edit_other_name" name="other_name"
                                placeholder="Nama Pengadu">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Emel</label>
                            <input type="email" class="form-control" id="edit_other_email" name="other_email"
                                placeholder="Emel">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sektor</label>
                            <select class="form-select" id="edit_other_sektor" name="other_sektor">
                                <option value="">-- Pilih Sektor --</option>
                                {{-- @foreach ($sektors as $sek) --}}
                                {{-- <option value="{{ $sek->id }}">{{ $sek->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bahagian</label>
                            <select class="form-select" id="edit_other_agency" name="other_agency">
                                <option value="">-- Pilih Bahagian --</option>
                                {{-- @foreach ($agencies as $ag) --}}
                                {{-- <option value="{{ $ag->id }}">{{ $ag->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Seksyen</label>
                            <select class="form-select" id="edit_other_seksyen" name="other_seksyen">
                                <option value="">-- Pilih Seksyen --</option>
                                {{-- @foreach ($seksyens as $sx) --}}
                                {{-- <option value="{{ $sx->id }}">{{ $sx->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Unit</label>
                            <select class="form-select" id="edit_other_unit" name="other_unit">
                                <option value="">-- Pilih Unit --</option>
                                {{-- @foreach ($units as $u) --}}
                                {{-- <option value="{{ $u->id }}">{{ $u->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                    </div>
                </div>

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
                <div class="mb-3">
                    <label class="form-label">Lampiran (jika ingin gantikan)</label>
                    <input type="file" name="attachments[]" class="form-control" multiple
                        accept=".pdf,.doc,.jpg,.png">
                    <small class="text-muted">Saiz maksimum setiap fail 2MB</small>
                </div>

                <!-- Butang -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Kemaskini Tiket</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!--/ Ticket Table -->

@endsection
