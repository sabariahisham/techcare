<!-- Modal to add new knowledge base -->
<div class="modal fade" id="knowledgeBaseModal" tabindex="-1" aria-labelledby="knowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="knowledgeBaseModalLabel">Tambah Artikel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="add-new-knowledgebase" id="addNewKnowledgeBaseForm" onsubmit="return false">
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
              <select id="add-kb-subcategory" class="form-select" name="subcat_id" required>
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
            {{-- <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-tag">Tags</label>
              <input type="text" class="form-control" id="add-kb-tag" placeholder="Enter tags separated by commas" name="kb_tag" />
              <small class="text-muted">Use commas to separate multiple tags</small>
            </div> --}}
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-status">Status <span class="text-danger">*</span></label>
              <select id="add-kb-status" class="form-select" name="kb_status" required>
                <option value="aktif">Active</option>
                <option value="tidak aktif">Inactive</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="addNewKnowledgeBaseForm" class="btn btn-primary">
          <i class="bx bx-plus me-2"></i>Tambah Artikel
        </button>
      </div>
    </div>
  </div>
</div>
