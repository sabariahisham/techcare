<!-- Modal to add new knowledge base -->
<div class="modal fade" id="knowledgeBaseModal" tabindex="-1" aria-labelledby="knowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="knowledgeBaseModalLabel">Tambah Artikel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="add-new-knowledgebase" id="addNewKnowledgeBaseForm" onsubmit="return false" novalidate>
          <!-- Hidden field for kb_jenis -->
          <input type="hidden" name="kb_jenis" value="doc_kb">

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-title">Tajuk <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-kb-title" placeholder="Masukkan tajuk artikel" name="kb_title" aria-label="Article Title" required />
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-category">Kategori <span class="text-danger">*</span></label>
              <select id="add-kb-category" class="form-select" name="cat_id" required>
                <option value="">Pilih Kategori</option>
                @foreach(\App\Models\RefCategory::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-subcategory">Sub Kategori <span class="text-danger">*</span></label>
              <select id="add-kb-subcategory" class="form-select" name="subcat_id" required disabled>
                <option value="">Pilih Sub Kategori</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-desc">Deskripsi <span class="text-danger">*</span></label>
              <textarea class="form-control" id="add-kb-desc" rows="4" placeholder="Masukkan deskripsi artikel yang terperinci" name="kb_desc" required></textarea>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-file">Muat Naik Fail PDF</label>
              <div class="file-upload-wrapper">
                <div id="pdf-drop-zone" class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center position-relative">
                  <input type="file" id="add-kb-file" name="pdf_files[]" accept=".pdf,application/pdf" multiple class="file-input position-absolute w-100 h-100 opacity-0" style="cursor: pointer; z-index: 1;">
                  <div class="drop-zone-content">
                    <i class="bx bx-file-find display-4 text-muted mb-3"></i>
                    <h6 class="text-muted mb-2">Seret & Letak fail PDF di sini</h6>
                    <p class="text-muted mb-2">atau <span class="text-primary fw-semibold" style="cursor: pointer;">telusuri (browse) fail</span></p>
                    <small class="text-muted">Hanya fail PDF dibenarkan (Maks: 2MB setiap fail, maksimum 5 fail)</small>
                  </div>
                </div>
                <div id="files-preview" class="files-preview mt-3"></div>
                <div class="invalid-feedback"></div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-tag">Tags</label>
              <input type="text" class="form-control" id="add-kb-tag" placeholder="Enter tags separated by commas" name="kb_tag" />
              <small class="text-muted">Guna koma(,) untuk memisahkan pelbagai tag (contoh: tag1, tag2, tag3)</small>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-status">Status <span class="text-danger">*</span></label>
              <select id="add-kb-status" class="form-select" name="kb_status" required>
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
        <button type="button" id="btnAddKnowledgeBase" class="btn btn-primary">
          <i class="bx bx-plus me-2"></i>TAMBAH ARTIKEL
        </button>
      </div>
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
}
</style>

