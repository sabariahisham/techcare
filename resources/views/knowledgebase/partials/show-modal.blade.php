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

/* Description formatting styles */
#show-kb-description {
  line-height: 1.6;
  color: #333;
}

#show-kb-description p {
  margin-bottom: 0.75rem;
}

#show-kb-description p:last-child {
  margin-bottom: 0;
}

#show-kb-description strong {
  font-weight: 600;
  color: #212529;
}

#show-kb-description em {
  font-style: italic;
}

#show-kb-description u {
  text-decoration: underline;
}

#show-kb-description ol,
#show-kb-description ul {
  margin-bottom: 0.75rem;
  margin-left: 1.5rem;
}

#show-kb-description li {
  margin-bottom: 0.25rem;
}

#show-kb-description blockquote {
  border-left: 4px solid #dee2e6;
  padding-left: 1rem;
  margin: 0.75rem 0;
  color: #6c757d;
  font-style: italic;
}

#show-kb-description code {
  background-color: #f8f9fa;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

#show-kb-description pre {
  background-color: #f8f9fa;
  padding: 12px;
  border-radius: 4px;
  overflow-x: auto;
  margin-bottom: 0.75rem;
}

#show-kb-description a {
  color: #0d6efd;
  text-decoration: none;
}

#show-kb-description a:hover {
  text-decoration: underline;
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
