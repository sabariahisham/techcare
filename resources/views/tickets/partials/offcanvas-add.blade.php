<!-- Offcanvas to add new ticket -->
<div class="offcanvas offcanvas-end offcanvas-50" tabindex="-1" id="offcanvasAddTicket"
    aria-labelledby="offcanvasAddTicketLabel">
    <div class="offcanvas-header border-bottom align-items-start">
        <div class="me-3">
            <h5 id="offcanvasAddTicketLabel" class="mb-0">Tambah Log Tiket</h5>
            <small class="text-muted fst-italic d-block mt-3">
                <strong>Makluman:</strong><br>
                Data dalam ruangan ini diselaraskan daripada <strong>Sistem Pergerakan Pegawai (SPP)</strong>.
                Sebarang kemas kini hendaklah dibuat melalui sistem tersebut.
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
                    <option value="2">Bagi Pihak</option>
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
                <h6>Maklumat Pengadu (Bagi Pihak)</h6>
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
                        <option value="">Pilih Kategori</option>
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
                        <option value="">Pilih Sub Kategori</option>
                    </select>
                </div>

                <!-- No Siri -->
                <div class="mb-3" id="serialNumberField" style="display: none;">
                    <label class="form-label">No. Siri <small>(Sewaan)</small></label>
                    <input type="text" name="no_siri" class="form-control">
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label">Keterangan Aduan</label>
                    <textarea name="tick_desc" class="form-control" rows="4" required></textarea>
                </div>

                <!-- Lampiran -->
                <div class="form-field-wrapper mb-3">
                    <label class="form-label">Lampiran</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="ticket-attachments" name="attachments[]"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple class="file-input d-none">
                        <div id="attachment-drop-zone"
                            class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center"
                            style="cursor: pointer;">
                            <div class="drop-zone-content">
                                <i class="bx bx-file-find display-4 text-muted mb-3 d-block"></i>
                                <h6 class="text-muted mb-2">Seret dan lepaskan fail di sini </h6>
                                <p class="text-muted mb-2">atau <span class="text-primary fw-semibold">Muat naik
                                        fail</span></p>
                                <small class="text-muted">Format yang dibenarkan: PDF, DOC, DOCX, JPG, JPEG, PNG (Maks:
                                    2MB setiap fail, maksimum 5 fail)</small>
                            </div>
                        </div>
                        <div id="attachments-preview" class="files-preview mt-3"></div>
                        <div class="invalid-feedback"></div>
                        </input>
                    </div>

                    <!-- Butang -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan & Hantar</button>
                        <button type="button" class="btn btn-label-danger"
                            data-bs-dismiss="offcanvas">Batal</button>
                    </div>
                </div>

        </form>
    </div>
</div>
</div>

<style>
    .file-drop-zone {
        transition: all 0.3s ease;
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .file-drop-zone.dragover {
        border-color: #007bff !important;
        background-color: rgba(0, 123, 255, 0.1);
    }

    .file-drop-zone.error {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.1);
    }

    .file-input {
        cursor: pointer !important;
    }

    .drop-zone-content {
        user-select: none;
    }

    .files-preview {
        max-height: 300px;
        overflow-y: auto;
    }

    .file-item {
        margin-bottom: 10px;
        padding: 12px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .file-item:last-child {
        margin-bottom: 0;
    }

    .drop-zone-content {
        pointer-events: none;
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }

    .invalid-feedback.d-block {
        display: block !important;
    }

    .file-drop-zone.error+.files-preview+.invalid-feedback {
        display: block !important;
    }
</style>
