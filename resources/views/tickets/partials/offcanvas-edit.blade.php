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
