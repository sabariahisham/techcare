<!-- Modal to edit knowledge base -->
<div class="modal fade" id="editKnowledgeBaseModal" tabindex="-1" aria-labelledby="editKnowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editKnowledgeBaseModalLabel">Edit Knowledge Base Article</h5>
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
              <label class="form-label" for="edit-kb-category">Category <span class="text-danger">*</span></label>
              <select id="edit-kb-category" class="form-select" name="cat_id" required>
                <option value="">Select Category</option>
                @foreach(\App\Models\RefCategory::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-subcategory">Sub Category <span class="text-danger">*</span></label>
              <select id="edit-kb-subcategory" class="form-select" name="subcat_id" required>
                <option value="">Select Sub Category</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="edit-kb-desc">Description <span class="text-danger">*</span></label>
              <textarea class="form-control" id="edit-kb-desc" rows="4" placeholder="Enter detailed article description" name="kb_desc" required></textarea>
            </div>
          </div>

          <div class="row">
            {{-- <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-tag">Tags</label>
              <input type="text" class="form-control" id="edit-kb-tag" placeholder="Enter tags separated by commas" name="kb_tag" />
              <small class="text-muted">Use commas to separate multiple tags</small>
            </div> --}}
            <div class="col-md-6 mb-4">
              <label class="form-label" for="edit-kb-status">Status <span class="text-danger">*</span></label>
              <select id="edit-kb-status" class="form-select" name="kb_status" required>
                <option value="aktif">Active</option>
                <option value="tidak aktif">Inactive</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="editKnowledgeBaseForm" class="btn btn-primary">
          <i class="bx bx-save me-2"></i>Update Article
        </button>
      </div>
    </div>
  </div>
</div>
