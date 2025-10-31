/**
 * Knowledge Base Modal Edit
 */

'use strict';

// Knowledge Base Edit Modal Initialization
(function () {

  // Handle edit button/link click to open modal and populate data
  $(document).on('click', '.edit-record', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var id = $(this).data('id');
    console.log('Edit button clicked, ID:', id); // Debug log
    console.log('Element:', this); // Debug log

    // Use the centralized function
    loadKnowledgeBaseData(id);
  });

  // Handle category change to populate subcategories in edit modal
  $(document).on('change', '#edit-kb-category', function() {
    var categoryId = $(this).val();
    loadSubcategories(categoryId, null);
  });

  // Function to load subcategories
  function loadSubcategories(categoryId, selectedSubcatId = null) {
    var subcategorySelect = $('#edit-kb-subcategory');

    // Clear subcategory options
    subcategorySelect.html('<option value="">Select Sub Category</option>');

    if (categoryId) {
      // Fetch subcategories for the selected category
      $.ajax({
        url: `/categories/${categoryId}/subcategories`,
        type: 'GET',
        success: function(response) {
          if (response.success) {
            response.data.forEach(function(subcategory) {
              var selected = selectedSubcatId == subcategory.id ? 'selected' : '';
              subcategorySelect.append(`<option value="${subcategory.id}" ${selected}>${subcategory.subcat_name}</option>`);
            });
          }
        },
        error: function(xhr) {
          console.error('Error fetching subcategories:', xhr);
          toastr.error('Failed to load subcategories. Please try again.');
        }
      });
    }
  }

  // Handle edit form submission
  $(document).on('submit', '#editKnowledgeBaseForm', function(e) {
    e.preventDefault();

    // Validate form first
    if (!validateEditForm()) {
      return false;
    }

    var formData = new FormData(this);
    var submitButton = $('button[form="editKnowledgeBaseForm"]');
    var originalText = submitButton.html();
    var id = $('#edit-kb-id').val();

    // Add _method for Laravel PUT request
    formData.append('_method', 'PUT');

    // Disable submit button and show loading state
    submitButton.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

    $.ajax({
      url: `/knowledgebase/${id}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          // Close the modal
          $('#editKnowledgeBaseModal').modal('hide');

          // Reset the form
          $('#editKnowledgeBaseForm')[0].reset();
          $('#edit-kb-subcategory').html('<option value="">Select Sub Category</option>');

          // Reload the DataTable
          if (typeof window.dt_knowledgebase !== 'undefined') {
            window.dt_knowledgebase.ajax.reload(null, false);
          } else if ($.fn.DataTable.isDataTable('.datatables-knowledgebase')) {
            $('.datatables-knowledgebase').DataTable().ajax.reload(null, false);
          }

          // Show success message
          toastr.success(response.message || 'Knowledge Base article has been updated successfully.');
        }
      },
      error: function(xhr) {
        var errors = xhr.responseJSON?.errors;
        if (errors) {
          Object.keys(errors).forEach(function(key) {
            toastr.error(errors[key][0]);
          });
        } else {
          toastr.error(xhr.responseJSON?.message || 'An error occurred while updating the knowledge base article.');
        }
      },
      complete: function() {
        // Re-enable submit button
        submitButton.prop('disabled', false).html(originalText);
      }
    });
  });

  // Handle modal close event to reset form
  $(document).on('hidden.bs.modal', '#editKnowledgeBaseModal', function () {
    $('#editKnowledgeBaseForm')[0].reset();
    $('#edit-kb-subcategory').html('<option value="">Select Sub Category</option>');

    // Remove any validation error classes
    $(this).find('.is-invalid').removeClass('is-invalid');
    $(this).find('.invalid-feedback').remove();
  });

  // Handle modal show event to focus on first input
  $(document).on('shown.bs.modal', '#editKnowledgeBaseModal', function () {
    $('#edit-kb-title').focus();
  });

  // Form validation enhancement
  function validateEditForm() {
    var isValid = true;
    var form = $('#editKnowledgeBaseForm');

    // Remove previous validation styles
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    // Validate required fields
    var requiredFields = [
      { id: 'edit-kb-title', message: 'Title is required' },
      { id: 'edit-kb-category', message: 'Category is required' },
      { id: 'edit-kb-subcategory', message: 'Sub category is required' },
      { id: 'edit-kb-desc', message: 'Description is required' }
    ];

    requiredFields.forEach(function(field) {
      var element = $('#' + field.id);
      var value = element.val().trim();

      if (!value) {
        element.addClass('is-invalid');
        element.after('<div class="invalid-feedback">' + field.message + '</div>');
        isValid = false;
      }
    });

    return isValid;
  }



  // Real-time validation on input change
  $(document).on('input change', '#editKnowledgeBaseForm input, #editKnowledgeBaseForm select, #editKnowledgeBaseForm textarea', function() {
    var element = $(this);
    if (element.hasClass('is-invalid') && element.val().trim()) {
      element.removeClass('is-invalid');
      element.next('.invalid-feedback').remove();
    }
  });

  // Handle submit button click
  $(document).on('click', 'button[form="editKnowledgeBaseForm"]', function(e) {
    e.preventDefault();
    $('#editKnowledgeBaseForm').trigger('submit');
  });

  // Additional event handlers for edit buttons (fallback)
  $(document).on('click', '[data-bs-target="#editKnowledgeBaseModal"], .btn-edit, .edit-btn, a[title="Edit"]', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    console.log('Fallback edit handler triggered, ID:', id);
    if (id && !$(this).hasClass('edit-record')) {
      console.log('Adding edit-record class and retriggering');
      $(this).addClass('edit-record').trigger('click');
    }
  });

  // Debug: Check for any elements with bx-edit icon
  $(document).on('click', '.bx-edit', function(e) {
    console.log('Edit icon clicked:', this);
    var parent = $(this).closest('a, button');
    if (parent.length) {
      console.log('Parent element:', parent[0]);
      var id = parent.data('id');
      if (id && !parent.hasClass('edit-record')) {
        console.log('Triggering edit for ID:', id);
        parent.addClass('edit-record').trigger('click');
      }
    }
  });

  // Listen for custom edit event
  $(document).on('editKnowledgeBase', function(e, data) {
    console.log('Custom edit event triggered with ID:', data.id);
    loadKnowledgeBaseData(data.id);
  });

  // Function to load knowledge base data
  function loadKnowledgeBaseData(id) {
    if (!id) {
      toastr.error('Invalid record ID.');
      return;
    }

    // Show loading message
    toastr.info('Loading knowledge base data...', '', { timeOut: 1000 });

    // Get knowledge base data
    $.ajax({
      url: `/knowledgebase/${id}/edit`,
      type: 'GET',
      success: function(response) {
        console.log('AJAX response:', response); // Debug log
        if (response.success) {
          var data = response.data;

          // Populate form fields
          $('#edit-kb-id').val(data.id);
          $('#edit-kb-title').val(data.kb_title);
          $('#edit-kb-category').val(data.cat_id);
          $('#edit-kb-desc').val(data.kb_desc);
          $('#edit-kb-tag').val(data.kb_tag);
          $('#edit-kb-status').val(data.kb_status);

          // Load subcategories for selected category
          if (data.cat_id) {
            loadSubcategories(data.cat_id, data.subcat_id);
          }

          // Show the edit modal
          $('#editKnowledgeBaseModal').modal('show');
        } else {
          toastr.error('Failed to load knowledge base data: ' + (response.message || 'Unknown error'));
        }
      },
      error: function(xhr) {
        console.error('AJAX Error:', xhr); // Debug log
        var errorMsg = 'Failed to load knowledge base data. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        toastr.error(errorMsg);
      }
    });
  }

  // Debug: Log when the script loads
  console.log('Knowledge Base Edit Modal script loaded');

})();
