/**
 * Knowledge Base Modal Show
 */

'use strict';

// Knowledge Base Show Modal Initialization
(function () {

  // Handle show button click to open modal and display data
  $(document).on('click', '.show-record', function () {
    var id = $(this).data('id');

    // Get knowledge base data
    $.ajax({
      url: `/knowledgebase/${id}`,
      type: 'GET',
      success: function (response) {
        if (response.success) {
          var data = response.data;

          // Populate modal with data
          $('#show-kb-title').text(data.kb_title || 'N/A');
          $('#show-kb-category').text(data.category_name || 'N/A');
          $('#show-kb-subcategory').text(data.subcategory_name || 'N/A');
          $('#show-kb-description').text(data.kb_desc || 'N/A');
          // Format tags as badges
          if (data.kb_tag && data.kb_tag.trim() !== '') {
            var tags = data.kb_tag.split(',');
            var tagBadges = '';
            tags.forEach(function (tag) {
              tag = tag.trim();
              if (tag !== '') {
                tagBadges += '<span class="badge bg-primary me-1 mb-1">' + tag + '</span>';
              }
            });
            $('#show-kb-tags').html(tagBadges);
          } else {
            $('#show-kb-tags').html('<span class="badge bg-secondary">Tiada Tag</span>');
          }
          // $('#show-kb-created-date').text(data.kb_created_date || 'N/A');

          // Set status with badge
          var statusBadge = data.kb_status === 'aktif' ?
            '<span class="badge bg-success">Active</span>' :
            '<span class="badge bg-warning">Inactive</span>';
          $('#show-kb-status').html(statusBadge);

          // Handle attachments/documents
          var attachmentsHtml = '';
          if (data.documents && data.documents.length > 0) {
            attachmentsHtml = '<div class="attachment-list">';
            data.documents.forEach(function (doc) {
              var downloadUrl = `/knowledgebase/document/${doc.id}/download`;
              var iconClass = getFileIcon(doc.doc_mime);
              attachmentsHtml += `
                <div class="attachment-item d-flex align-items-center mb-2 p-2 border rounded">
                  <i class="${iconClass} me-2 text-primary"></i>
                  <span class="flex-grow-1">${doc.doc_name}</span>
                  <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary ms-2" title="Muat turun Artikel" target="_blank" rel="noopener">
                    <i class="bx bx-download"></i>
                  </a>
                </div>
              `;
            });
            attachmentsHtml += '</div>';
          } else {
            attachmentsHtml = '<span class="text-muted">Tiada lampiran</span>';
          }
          $('#show-kb-attachments .attachment-list').html(attachmentsHtml);

          $('#show-kb-created-by').text(data.created_by || 'N/A');
          // $('#show-kb-created-date').text(data.created_at ? new Date(data.created_at).toLocaleDateString() : 'N/A');

          // Show the modal
          bootstrap.Modal.getOrCreateInstance(document.getElementById('showKnowledgeBaseModal')).show();
        }
      },
      error: function (xhr) {
        toastr.error('Failed to load knowledge base details. Please try again.');
        console.error(xhr);
      }
    });
  });

  // Helper function to get file icon based on MIME type
  function getFileIcon(mimeType) {
    if (!mimeType) return 'bx bx-file';

    if (mimeType.includes('pdf')) {
      return 'bx bxs-file-pdf text-danger';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
      return 'bx bxs-file-doc text-primary';
    } else if (mimeType.includes('image')) {
      return 'bx bx-image text-success';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
      return 'bx bxs-file text-success';
    } else {
      return 'bx bx-file text-secondary';
    }
  }

})();
