<!-- Modal to edit knowledge base -->
<div class="modal fade" id="editKnowledgeBaseModal" tabindex="-1" aria-labelledby="editKnowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editKnowledgeBaseModalLabel">Kemaskini Artikel Knowledge Base</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="edit-knowledgebase" id="editKnowledgeBaseForm">
          <input type="hidden" id="edit-kb-id" name="id" />
          <!-- Hidden field for kb_jenis -->
          <input type="hidden" name="kb_jenis" value="doc_kb">
          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="edit-kb-title">Article Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit-kb-title" placeholder="Enter article title" name="kb_title" aria-label="Article Title" required />
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-category">Kategori <span class="text-danger">*</span></label>
              <select id="edit-kb-category" class="form-select" name="cat_id" required>
                <option value="">Pilih Kategori</option>
                @foreach(\App\Models\RefCategory::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-subcategory">Sub Kategori <span class="text-danger">*</span></label>
              <select id="edit-kb-subcategory" class="form-select" name="subcat_id" required>
                <option value="">Pilih Sub Kategori</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="edit-editor">Deskripsi <span class="text-danger">*</span></label>
              <div class="quill-editor-wrapper">
                <div id="edit-editor"></div>
              </div>
              <input type="hidden" id="edit-kb-desc" name="kb_desc" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-tag">Tags</label>
              <input type="text" class="form-control" id="edit-kb-tag" placeholder="Enter tags separated by commas" name="kb_tag" />
              <small class="text-muted">Use commas to separate multiple tags</small>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-status">Status <span class="text-danger">*</span></label>
              <select id="edit-kb-status" class="form-select" name="kb_status" required>
                <option value="aktif">Active</option>
                <option value="tidak aktif">Inactive</option>
              </select>
            </div>
          </div>

          <!-- Existing Documents Section -->
          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label">Dokumen Sedia Ada</label>
              <div id="edit-existing-documents" class="existing-documents-container p-3 bg-light rounded">
                <!-- Existing documents will be populated here -->
                <p class="text-muted mb-0">Tiada dokumen</p>
              </div>
            </div>
          </div>



          <!-- File Upload Section -->
          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="edit-kb-file">Muat Naik Fail PDF Baru (Pilihan)</label>
              <div class="file-upload-wrapper">
                <div id="edit-pdf-drop-zone" class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center position-relative">
                  <input type="file" id="edit-kb-file" name="pdf_files[]" accept=".pdf,application/pdf" multiple class="file-input position-absolute w-100 h-100 opacity-0" style="pointer-events: none; z-index: -1;">
                  <div class="drop-zone-content">
                    <i class="bx bx-file-find display-4 text-muted mb-3"></i>
                    <h6 class="text-muted mb-2">Seret & Letak fail PDF di sini</h6>
                    <p class="text-muted mb-2">atau <span class="text-primary fw-semibold" style="cursor: pointer;">telusuri (browse) fail</span></p>
                    <small class="text-muted">Hanya fail PDF dibenarkan (Maks: 2MB setiap fail, maksimum 5 fail)</small>
                    <br><small class="text-warning">Nota: Fail baru akan menggantikan semua fail sedia ada</small>
                  </div>
                </div>
                <div class="invalid-feedback" style="display: none;"></div>
                <div id="edit-files-preview" class="files-preview mt-3">
                  <!-- Selected files will be displayed here -->
                </div>
              </div>
            </div>
          </div>


        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" form="editKnowledgeBaseForm" class="btn btn-primary">
          <i class="bx bx-save me-2"></i>Kemaskini Artikel
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

.existing-documents-container {
  max-height: 200px;
  overflow-y: auto;
}

.document-item {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  margin-bottom: 8px;
  background-color: white;
  border: 1px solid #e9ecef;
  border-radius: 6px;
}

.document-item:last-child {
  margin-bottom: 0;
}

.document-item .file-icon {
  font-size: 1.2rem;
  margin-right: 10px;
}

.document-item .file-name {
  flex-grow: 1;
  font-size: 0.9rem;
}

.document-item .file-actions {
  display: flex;
  gap: 5px;
}
</style>
