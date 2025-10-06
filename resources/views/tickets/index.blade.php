@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
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

        <!-- Offcanvas to add new user -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddTicket" aria-labelledby="offcanvasAddTicketLabel">
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
                                <input type="text" class="form-control" name="name" value="{{ auth()->user()->name }}"
                                    readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Emel</label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ auth()->user()->email }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sektor</label>
                                <input type="text" class="form-control" name="sektor"
                                    value="{{ auth()->user()->sektor }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bahagian</label>
                                <input type="text" class="form-control" name="agency"
                                    value="{{ auth()->user()->agency }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Seksyen</label>
                                <input type="text" class="form-control" name="seksyen"
                                    value="{{ auth()->user()->seksyen }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" value="{{ auth()->user()->unit }}"
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
                            <div class="col-md-6">
                                <label class="form-label">Sektor</label>
                                <select class="form-select" name="other_sektor">
                                    <option value="">-- Pilih Sektor --</option>
                                    {{-- @foreach ($sektors as $sek) --}}
                                    {{-- <option value="{{ $sek->id }}">{{ $sek->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bahagian</label>
                                <select class="form-select" name="other_agency">
                                    <option value="">-- Pilih Bahagian --</option>
                                    {{-- @foreach ($agencies as $ag) --}}
                                    {{-- <option value="{{ $ag->id }}">{{ $ag->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Seksyen</label>
                                <select class="form-select" name="other_seksyen">
                                    <option value="">-- Pilih Seksyen --</option>
                                    {{-- @foreach ($seksyens as $sx) --}}
                                    {{-- <option value="{{ $sx->id }}">{{ $sx->name }}</option> --}}
                                    {{-- @endforeach --}}
                                </select>
                            </div>
                            <div class="col-md-6">
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
                        <select name="cat_id" id="cat_id" class="form-select select2" required>
                            <option value="">-- Pilih Kategori --</option>
                            @if (isset($categories))
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Sub Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Sub Kategori</label>
                        <select name="subcat_id" id="subcat_id" class="form-select select2" required></select>
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
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditTicket"
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
                                value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Emel</label>
                            <input type="email" class="form-control" id="edit_email" name="email"
                                value="{{ auth()->user()->email }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sektor</label>
                            <input type="text" class="form-control" id="edit_sektor" name="sektor"
                                value="{{ auth()->user()->sektor }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bahagian</label>
                            <input type="text" class="form-control" id="edit_agency" name="agency"
                                value="{{ auth()->user()->agency }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Seksyen</label>
                            <input type="text" class="form-control" id="edit_seksyen" name="seksyen"
                                value="{{ auth()->user()->seksyen }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" id="edit_unit" name="unit"
                                value="{{ auth()->user()->unit }}" readonly>
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
                        <div class="col-md-6">
                            <label class="form-label">Sektor</label>
                            <select class="form-select" id="edit_other_sektor" name="other_sektor">
                                <option value="">-- Pilih Sektor --</option>
                                {{-- @foreach ($sektors as $sek) --}}
                                {{-- <option value="{{ $sek->id }}">{{ $sek->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bahagian</label>
                            <select class="form-select" id="edit_other_agency" name="other_agency">
                                <option value="">-- Pilih Bahagian --</option>
                                {{-- @foreach ($agencies as $ag) --}}
                                {{-- <option value="{{ $ag->id }}">{{ $ag->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Seksyen</label>
                            <select class="form-select" id="edit_other_seksyen" name="other_seksyen">
                                <option value="">-- Pilih Seksyen --</option>
                                {{-- @foreach ($seksyens as $sx) --}}
                                {{-- <option value="{{ $sx->id }}">{{ $sx->name }}</option> --}}
                                {{-- @endforeach --}}
                            </select>
                        </div>
                        <div class="col-md-6">
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
                    <select name="cat_id" id="edit_cat_id" class="form-select select2" required>
                        <option value="">-- Pilih Kategori --</option>
                        @if (isset($categories))
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Sub Kategori -->
                <div class="mb-3">
                    <label class="form-label">Sub Kategori</label>
                    <select name="subcat_id" id="edit_subcat_id" class="form-select select2" required></select>
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
