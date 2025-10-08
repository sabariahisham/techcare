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
      success: function(response) {
        if (response.success) {
          var data = response.data;

          // Populate modal with data
          $('#show-kb-title').text(data.kb_title || 'N/A');
          $('#show-kb-category').text(data.category_name || 'N/A');
          $('#show-kb-subcategory').text(data.subcategory_name || 'N/A');
          $('#show-kb-description').text(data.kb_desc || 'N/A');
          // $('#show-kb-tags').text(data.kb_tag || 'N/A');

          // Set status with badge
          var statusBadge = data.kb_status === 'aktif'
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-warning">Inactive</span>';
          $('#show-kb-status').html(statusBadge);

          $('#show-kb-created-by').text(data.created_by || 'N/A');
          // $('#show-kb-created-date').text(data.created_at ? new Date(data.created_at).toLocaleDateString() : 'N/A');

          // Show the modal
          $('#showKnowledgeBaseModal').modal('show');
        }
      },
      error: function(xhr) {
        toastr.error('Failed to load knowledge base details. Please try again.');
        console.error(xhr);
      }
    });
  });

})();
