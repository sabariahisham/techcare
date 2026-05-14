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
                    <option value="2">Bagi Pihak</option>
                </select>
                <input type="hidden" id="editTicketTypeHidden" name="ticket_type">
                <small class="text-muted d-block mt-1">
                    <i class="bx bx-info-circle"></i> Jenis aduan tidak boleh diubah selepas tiket dicipta
                </small>
            </div>

            <!-- Auto papar jika Diri Sendiri -->
            <div id="editSelfInfo" class="mb-4">

                <div class="row g-3">
                    <span class="badge badge-lg bg-secondary text-start d-inline-block py-2 px-2">Maklumat Pengadu (Diri
                        Sendiri)</span>
                    <div class="col-12">
                        <label class="form-label">Nama Pengadu</label>
                        <input type="text" class="form-control" name="name" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Jawatan</label>
                        <input type="text" class="form-control" name="jawatan" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Skim Perkhidmatan</label>
                        <input type="text" class="form-control" name="skim" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Gred</label>
                        <input type="text" class="form-control" name="gred" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Emel</label>
                        <input type="email" class="form-control" name="email" readonly>
                    </div>
                </div>
            </div>

            <!-- Papar hanya jika Bagi Pihak -->
            <div id="editOtherInfo" class="d-none mb-4">
                <span class="badge badge-lg bg-secondary text-start d-inline-block py-2 px-2">Maklumat Pengadu (Bagi
                    Pihak)</span>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nama Pengadu</label>
                        <input type="text" class="form-control" id="editOtherName" name="other_name" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Jawatan</label>
                        <input type="text" class="form-control" id="editOtherJawatan" name="other_jawatan" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Skim Perkhidmatan</label>
                        <input type="text" class="form-control" id="editOtherSkim" name="other_skim" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Gred</label>
                        <input type="text" class="form-control" id="editOtherGred" name="other_gred" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Emel</label>
                        <input type="email" class="form-control" id="editOtherEmail" name="other_email" readonly>
                    </div>
                </div>
            </div>

            <!-- Ticket Form Fields -->
            <div id="editTicketFormFields">
                <div class="row g-3">
                    <span class="badge badge-lg bg-primary text-start d-inline-block py-2 px-2">Maklumat Aduan</span>

                    <!-- Tajuk -->
                    <div class="mb-3">
                        <label class="form-label">Tajuk <span class="text-danger">*</span></label>
                        <input type="text" name="tick_title" id="editTicketTitle" class="form-control" style="text-transform: uppercase;" required>
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

                    <!-- Lokasi: Bangunan/Blok/Aras (dropdown) -->
                    <div class="col-4 mb-3">
                        <label class="form-label">Bangunan</label>
                        <select name="building_loc" id="edit_building_loc" class="form-select"
                            data-validation="custom" required>
                            <option value="">Pilih Lokasi</option>
                            <option value="P1">Perbendaharaan 1</option>
                            <option value="P2">Perbendaharaan 2</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-4 mb-3">
                        <label class="form-label">Blok</label>
                        <select name="block_loc" id="edit_block_loc" class="form-select" data-validation="custom"
                            required>
                            <option value="">Pilih Blok</option>
                            <option value="U">Utara</option>
                            <option value="TU">Tengah-Utara</option>
                            <option value="T">Tengah</option>
                            <option value="TS">Tengah-Selatan</option>
                            <option value="S">Selatan</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-4 mb-3">
                        <label class="form-label">Aras</label>
                        <select name="level_loc" id="edit_level_loc" class="form-select" data-validation="custom"
                            required>
                            <option value="">Pilih Aras</option>
                            <option value="G">G</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="8.5">8.5</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
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
                            <input type="file" id="editTicketAttachments" name="attachments[]"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple class="file-input d-none">
                            <div id="editAttachmentDropZone"
                                class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center"
                                style="cursor: pointer;">
                                <div class="drop-zone-content">
                                    <i class="bx bx-file-find display-4 text-muted mb-3 d-block"></i>
                                    <h6 class="text-muted mb-2">Seret dan lepaskan fail di sini</h6>
                                    <p class="text-muted mb-2">atau <span class="text-primary fw-semibold">Muat naik
                                            fail</span></p>
                                    <small class="text-muted">Format yang dibenarkan: PDF, DOC, DOCX, JPG, JPEG, PNG
                                        (Maks:
                                        2MB setiap fail, maksimum 5 fail)</small>
                                </div>
                            </div>
                            <div id="editAttachmentsPreview" class="files-preview mt-3"></div>
                            <div class="invalid-feedback"></div>
                            </input>
                        </div>
                    </div>

                    <!-- Butang -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Kemaskini</button>
                        <button type="button" class="btn btn-label-danger"
                            data-bs-dismiss="offcanvas">Batal</button>
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

     /* dropdown using select */
    .select2-container .select2-results__options {
    max-height: 200px;
    overflow-y: auto;
</style>
