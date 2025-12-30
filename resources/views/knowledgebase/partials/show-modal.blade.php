<!-- Knowledge Base Show Modal -->
<style>
.attachment-item {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6 !important;
  transition: background-color 0.2s ease;
}

.attachment-item:hover {
  background-color: #e9ecef;
}

.attachment-list {
  max-height: 200px;
  overflow-y: auto;
}
</style>

<div class="modal fade" id="showKnowledgeBaseModal" tabindex="-1" aria-labelledby="showKnowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="showKnowledgeBaseModalLabel">Knowledge Base Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-3"><strong>Tajuk:</strong></div>
          <div class="col-md-9" id="show-kb-title">-</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Kategori:</strong></div>
          <div class="col-md-9" id="show-kb-category">-</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Sub Kategori:</strong></div>
          <div class="col-md-9" id="show-kb-subcategory">-</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Deskripsi:</strong></div>
          <div class="col-md-9" id="show-kb-description">-</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Tags:</strong></div>
          <div class="col-md-9" id="show-kb-tags">-</div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Status:</strong></div>
          <div class="col-md-9" id="show-kb-status">-</div>
        </div>
         <div class="row mb-3">
          <div class="col-md-3"><strong>Lampiran:</strong></div>
          <div class="col-md-9" id="show-kb-attachments">
            <div class="attachment-list">
              <!-- Files will be dynamically populated here -->
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-3"><strong>Ditulis oleh:</strong></div>
          <div class="col-md-9" id="show-kb-created-by">-</div>
        </div>
        {{-- <div class="row mb-3">
          <div class="col-md-3"><strong>Tarikh Dicipta:</strong></div>
          <div class="col-md-9" id="show-kb-created-date">-</div>
        </div> --}}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
