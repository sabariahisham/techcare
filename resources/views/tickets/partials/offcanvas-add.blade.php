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
                            value="{{ auth()->user()->unit->nama_unit ?? (auth()->user()->kod_unit ?? '-') }}" readonly>
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
                    <!-- Username search field -->
                    <div class="col-12">
                        <label class="form-label">Cari Nama Pengguna</label>
                        <small class="text-muted d-block mb-2">Masukkan nama penuh atau sebahagian nama pengguna</small>
                        <div class="position-relative">
                            <div class="input-group">
                                <input type="text" class="form-control username-search-input" id="search_name"
                                    placeholder="Contoh: Ahmad Bin Ali atau ahmad.ali" autocomplete="off">
                                <button class="btn btn-outline-primary" type="button" id="searchUserBtn">
                                    <i class="bx bx-search"></i> Cari
                                </button>
                            </div>

                            <!-- Autocomplete dropdown with Bootstrap 5 styling -->
                            <div id="autocompleteDropdown" class="autocomplete-dropdown position-absolute w-100 mt-1"
                                style="display: none; z-index: 1050;">
                                <div class="card shadow-sm border">
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush" id="autocompleteResults"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
