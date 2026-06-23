<!-- Modal: Tambah ke Knowledge Base daripada Senarai Aduan Selesai -->
<div class="modal fade" id="addFromAduanModal" tabindex="-1" aria-labelledby="addFromAduanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addFromAduanModalLabel">Tambah ke Knowledge Base</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Maklumat Tiket (read-only reference) -->
        <div class="alert alert-info mb-4 p-3">
          <h6 class="mb-2 fw-semibold"><i class="bx bx-info-circle me-1"></i>Maklumat Tiket Rujukan</h6>
          <div class="row g-2">
            <div class="col-sm-6">
              <small class="text-muted">No. Log Tiket</small>
              <div class="fw-semibold" id="ref-no-log-tiket">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted">Nama Pengadu</small>
              <div class="fw-semibold" id="ref-nama-pengadu">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted">Tarikh Aduan</small>
              <div id="ref-tarikh-aduan">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted">Status</small>
              <div><span class="badge bg-success">Aduan Selesai</span></div>
            </div>
            <div class="col-12 mt-2" id="ref-catatan-tindakan-wrapper" style="display:none;">
              <hr class="my-2">
              <div class="d-flex align-items-center mb-2">
                <input class="form-check-input me-2" type="checkbox" id="checkbox-pilih-semua">
                <label class="form-check-label small fw-semibold" for="checkbox-pilih-semua">Pilih Semua</label>
              </div>
              <div id="ref-catatan-tindakan"></div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="checkbox-salin-catatan">
                <label class="form-check-label small" for="checkbox-salin-catatan">
                  Salin catatan terpilih ke Deskripsi
                </label>
              </div>
            </div>
          </div>
        </div>

        <form class="add-from-aduan-kb" id="addFromAduanForm" onsubmit="return false" novalidate>
          <!-- Hidden fields -->
          <input type="hidden" name="kb_jenis" value="doc_kb">
          <input type="hidden" name="ticket_uuid" id="from-aduan-ticket-uuid" value="">

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="from-aduan-kb-title">Tajuk <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="from-aduan-kb-title" placeholder="Masukkan tajuk artikel" name="kb_title" required />
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">Kategori <span class="text-danger">*</span></label>
              <div id="from-aduan-kb-category-display" class="form-control bg-light text-body">-</div>
              <input type="hidden" name="cat_id" id="from-aduan-hidden-cat-id">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Sub Kategori <span class="text-danger">*</span></label>
              <div id="from-aduan-kb-subcategory-display" class="form-control bg-light text-body">-</div>
              <input type="hidden" name="subcat_id" id="from-aduan-hidden-subcat-id">
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="from-aduan-kb-desc">Deskripsi <span class="text-danger">*</span></label>
              <textarea class="form-control" id="from-aduan-kb-desc" rows="4" placeholder="Masukkan deskripsi artikel yang terperinci" name="kb_desc" required></textarea>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="from-aduan-kb-file">Muat Naik Fail PDF</label>
              <div class="file-upload-wrapper">
                <div id="from-aduan-pdf-drop-zone" class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center position-relative">
                  <input type="file" id="from-aduan-kb-file" name="pdf_files[]" accept=".pdf,application/pdf" multiple class="file-input position-absolute w-100 h-100 opacity-0" style="cursor: pointer; z-index: 1;">
                  <div class="drop-zone-content">
                    <i class="bx bx-file-find display-4 text-muted mb-3"></i>
                    <h6 class="text-muted mb-2">Seret & Letak fail PDF di sini</h6>
                    <p class="text-muted mb-2">atau <span class="text-primary fw-semibold" style="cursor: pointer;">telusuri (browse) fail</span></p>
                    <small class="text-muted">Hanya fail PDF dibenarkan (Maks: 2MB setiap fail, maksimum 5 fail)</small>
                  </div>
                </div>
                <div id="from-aduan-files-preview" class="files-preview mt-3"></div>
                <div class="invalid-feedback"></div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="from-aduan-kb-tag">Tags</label>
              <input type="text" class="form-control" id="from-aduan-kb-tag" placeholder="Masukkan tag dipisahkan koma" name="kb_tag" />
              <small class="text-muted">Guna koma(,) untuk memisahkan pelbagai tag (contoh: tag1, tag2, tag3)</small>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="from-aduan-kb-status">Status <span class="text-danger">*</span></label>
              <select id="from-aduan-kb-status" class="form-select" name="kb_status" required>
                <option value="aktif">Aktif</option>
                <option value="tidak aktif">Tidak Aktif</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">BATAL</button>
        <button type="button" id="btnAddFromAduan" class="btn btn-primary">
          <i class="bx bx-plus me-2"></i>TAMBAH ARTIKEL
        </button>
      </div>
    </div>
  </div>
</div>
