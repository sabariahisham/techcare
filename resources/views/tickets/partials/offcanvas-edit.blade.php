<!-- Offcanvas to edit ticket -->
<div class="offcanvas offcanvas-end offcanvas-50" tabindex="-1" id="offcanvasEditTicket"
    aria-labelledby="offcanvasEditTicketLabel">
    <div class="offcanvas-header border-bottom align-items-start">
        <div class="me-3">
            <h5 id="offcanvasEditTicketLabel" class="mb-0">Edit Tiket</h5>
            <small class="text-muted fst-italic d-block mt-3">
                <strong>Makluman:</strong><br>
                Tiket yang telah diproses atau ditutup tidak boleh diedit.
            </small>
        </div>
        <button type="button" class="btn-close text-reset mt-1" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="editTicketForm" enctype="multipart/form-data">
            @csrf

            <input type="hidden" id="editTicketId" name="ticket_id">
            <input type="hidden" name="_method" value="PUT">

            <!-- Jenis Aduan - Disabled for edit -->
            <div class="mb-3">
                <label class="form-label">Jenis Aduan</label>
                <select id="editTicketType" name="ticket_type" class="form-select" disabled>
                    <option value="1">Diri Sendiri</option>
                    <option value="0">Bagi Pihak</option>
                </select>
                <input type="hidden" id="editTicketTypeHidden" name="ticket_type">
                <small class="text-muted d-block mt-1">
                    <i class="bx bx-info-circle"></i> Jenis aduan tidak boleh diubah selepas tiket dicipta
                </small>
            </div>

            <!-- Auto papar jika Diri Sendiri -->
            <div id="editSelfInfo" class="mb-4">
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
                </div>
            </div>

            <!-- Papar hanya jika Bagi Pihak -->
            <div id="editOtherInfo" class="d-none mb-4">
                <h6>Maklumat Pengadu (Bagi Pihak)</h6>
                <div class="row g-3">
                    <!-- Username search field -->
                    <div class="col-12">
                        <label class="form-label">Cari Nama Pengguna</label>
                        <small class="text-muted d-block mb-2">Masukkan nama penuh atau sebahagian nama pengguna</small>
                        <div class="position-relative">
                            <div class="input-group">
                                <input type="text" class="form-control username-search-input" id="editSearchName"
                                    placeholder="Contoh: Ahmad Bin Ali atau ahmad.ali" autocomplete="off">
                                <button class="btn btn-outline-primary" type="button" id="editSearchUserBtn">
                                    <i class="bx bx-search"></i> Cari
                                </button>
                            </div>

                            <!-- Autocomplete dropdown with Bootstrap 5 styling -->
                            <div id="editAutocompleteDropdown"
                                class="autocomplete-dropdown position-absolute w-100 mt-1"
                                style="display: none; z-index: 1050;">
                                <div class="card shadow-sm border">
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush" id="editAutocompleteResults"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User info fields (hidden by default) -->
                    <div id="editFoundUserInfo" style="display: none;">
                        <div class="row g-3">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="editClearSearchBtn">
                                    <i class="bx bx-refresh"></i> Cari Pengguna Lain
                                </button>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nama Pengadu</label>
                                <input type="text" class="form-control" id="editOtherName" name="other_name"
                                    readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Emel</label>
                                <input type="email" class="form-control" id="editOtherEmail" name="other_email"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Form Fields -->
            <div id="editTicketFormFields">
                <!-- Tajuk -->
                <div class="mb-3">
                    <label class="form-label">Tajuk <span class="text-danger">*</span></label>
                    <input type="text" name="tick_title" id="editTicketTitle" class="form-control" required>
                    <div class="invalid-feedback"></div>
                </div>

                <!-- Kategori -->
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="cat_id" id="editCatId" class="form-select" data-validation="custom" required>
                        <option value="">Pilih Kategori</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <!-- Sub Kategori -->
                <div class="mb-3">
                    <label class="form-label">Sub Kategori <span class="text-danger">*</span></label>
                    <select name="subcat_id" id="editSubcatId" class="form-select" data-validation="custom"
                        required>
                        <option value="">Pilih Sub Kategori</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <!-- No Siri -->
                <div class="mb-3" id="editSerialNumberField" style="display: none;">
                    <label class="form-label">No. Siri <small>(Sewaan)</small></label>
                    <input type="text" name="no_siri" id="editNoSiri" class="form-control">
                    <div class="invalid-feedback"></div>
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label">Keterangan Aduan <span class="text-danger">*</span></label>
                    <textarea name="tick_desc" id="editTicketDesc" class="form-control" rows="4" required></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <!-- Lampiran Sedia Ada -->
                <div class="mb-3" id="editExistingAttachments" style="display: none;">
                    <label class="form-label">Lampiran Sedia Ada</label>
                    <div id="editExistingAttachmentsList" class="existing-attachments-list"></div>
                </div>

                <!-- Lampiran Baharu -->
                <div class="form-field-wrapper mb-3">
                    <label class="form-label">Lampiran Baharu</label>
                    <div class="file-upload-wrapper">
                        <div id="editAttachmentDropZone"
                            class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center position-relative">
                            <input type="file" id="editTicketAttachments" name="attachments[]"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple
                                class="file-input position-absolute w-100 h-100 opacity-0"
                                style="cursor: pointer; z-index: 2; top: 0; left: 0;">
                            <div class="drop-zone-content">
                                <i class="bx bx-file-find display-4 text-muted mb-3"></i>
                                <h6 class="text-muted mb-2">Seret & Letak fail di sini</h6>
                                <p class="text-muted mb-2">atau <span class="text-primary fw-semibold"
                                        style="cursor: pointer;">telusuri (browse) fail</span></p>
                                <small class="text-muted">Format yang dibenarkan: PDF, DOC, DOCX, JPG, JPEG, PNG (Maks:
                                    2MB setiap fail, maksimum 5 fail)</small>
                            </div>
                        </div>
                        <div id="editAttachmentsPreview" class="files-preview mt-3"></div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <!-- Butang -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Kemaskini</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
                </div>
            </div>

        </form>
    </div>
</div>

<style>
    .existing-attachments-list {
        max-height: 200px;
        overflow-y: auto;
    }

    .existing-attachment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        margin-bottom: 5px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
    }

    .existing-attachment-item:last-child {
        margin-bottom: 0;
    }

    .attachment-info {
        flex-grow: 1;
    }

    .attachment-name {
        font-weight: 500;
        color: #495057;
    }

    .attachment-size {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .attachment-actions {
        display: flex;
        gap: 5px;
    }

    .btn-attachment {
        padding: 2px 6px;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .file-drop-zone {
        transition: all 0.3s ease;
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
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
        user-select: none;
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
