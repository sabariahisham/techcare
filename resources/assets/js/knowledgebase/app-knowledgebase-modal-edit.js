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

    // Check if user has a role
    if (typeof window.userHasRole !== 'undefined' && !window.userHasRole) {
      Swal.fire({
        title: 'Akses Ditolak',
        html: 'Anda tidak mempunyai kebenaran untuk mengedit artikel.<br><small class="text-muted">Hanya pengguna dengan peranan yang ditetapkan boleh mengedit artikel.</small>',
        icon: 'error',
        confirmButtonColor: '#696cff',
        confirmButtonText: 'Faham'
      });
      return;
    }

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
          toastr.error('Gagal memuatkan sub kategori. Sila cuba lagi.');
        }
      });
    }
  }

  // Handle edit form submission
  $(document).on('submit', '#editKnowledgeBaseForm', function(e) {
    e.preventDefault();
    console.log('Edit form submission started'); // Debug log

    // Validate form first
    if (!validateEditForm()) {
      console.log('Form validation failed'); // Debug log
      return false;
    }

    var formData = new FormData(this);
    var submitButton = $('button[form="editKnowledgeBaseForm"]');
    var originalText = submitButton.html();
    var id = $('#edit-kb-id').val();

    console.log('Form ID:', id); // Debug log
    console.log('Submit button found:', submitButton.length > 0); // Debug log

    // Add new files if any are selected
    if (typeof window.editSelectedFiles !== 'undefined' && window.editSelectedFiles.length > 0) {
      // Validate files before submission
      let validationErrors = [];
      const maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
      const maxFiles = 5;

      // Check file count
      if (window.editSelectedFiles.length > maxFiles) {
        validationErrors.push(`Maksimum ${maxFiles} fail dibenarkan. Anda telah memilih ${window.editSelectedFiles.length} fail.`);
      }

      // Check each file
      window.editSelectedFiles.forEach((file, index) => {
        // Check file type
        const isValidPDF = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        if (!isValidPDF) {
          validationErrors.push(`Fail "${file.name}" bukanlah fail PDF yang sah. Sila pilih fail PDF sahaja.`);
        }

        // Check file size (2MB limit)
        if (file.size > maxFileSize) {
          const fileSize = (file.size / (1024 * 1024)).toFixed(2);
          validationErrors.push(`Fail "${file.name}" (${fileSize}MB) melebihi had saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`);
        }
      });

      // If there are validation errors, stop submission
      if (validationErrors.length > 0) {
        validationErrors.forEach(error => {
          toastr.error(error, 'Ralat Validasi!', {
            closeButton: true,
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        });
        return false;
      }

      // Add valid files to FormData
      window.editSelectedFiles.forEach(file => {
        formData.append('pdf_files[]', file);
      });
      console.log(`Adding ${window.editSelectedFiles.length} new files to FormData`);
    }

    // Add _method for Laravel PUT request
    formData.append('_method', 'PUT');

    // Disable submit button and show loading state
    submitButton.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

    console.log('Sending AJAX request to:', `/knowledgebase/${id}`); // Debug log

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
        console.log('Edit Success Response:', response); // Debug log

        if (response.success) {
          // Show success message first with proper styling
          if (typeof toastr !== 'undefined') {
            toastr.success(response.message || 'Artikel Knowledge Base telah berjaya dikemas kini.', 'Berjaya!', {
              closeButton: true,
              timeOut: 3000,
              progressBar: true,
              positionClass: 'toast-top-right',
              showClass: "animate__animated animate__bounceInRight",
              hideClass: "animate__animated animate__fadeOut",
              "toastClass": "toast ngx-toastr bg-success text-white",
              "closeClass": "toast-close-button",
              "titleClass": "toast-title",
              "messageClass": "toast-message",
              "progressClass": "toast-progress"
            });
          } else {
            toastr.success(response.message || 'Artikel Knowledge Base telah berjaya dikemas kini.', 'Berjaya!', {
              closeButton: true,
              timeOut: 3000,
              progressBar: true,
              positionClass: 'toast-top-right'
            });
          }

          // Close the modal
          $('#editKnowledgeBaseModal').modal('hide');

          // Reset the form and file selections
          $('#editKnowledgeBaseForm')[0].reset();
          $('#edit-kb-subcategory').html('<option value="">Pilih Sub Kategori</option>');

          // Clear file selections
          if (typeof window.removeAllEditFiles !== 'undefined') {
            window.removeAllEditFiles();
          }

          // Reload the DataTable with callback to ensure it's refreshed
          setTimeout(function() {
            if (typeof window.dt_knowledgebase !== 'undefined') {
              window.dt_knowledgebase.ajax.reload(function() {
                console.log('DataTable refreshed after update');
              }, false);
            } else if ($.fn.DataTable.isDataTable('.datatables-knowledgebase')) {
              $('.datatables-knowledgebase').DataTable().ajax.reload(function() {
                console.log('DataTable refreshed after update (fallback)');
              }, false);
            } else {
              console.warn('DataTable not found, attempting to reload page');
              // Last resort: reload the page if DataTable is not found
              window.location.reload();
            }
          }, 500); // Small delay to ensure modal is closed first
        } else {
          toastr.error(response.message || 'Gagal mengemaskini artikel Knowledge Base.', 'Ralat!', {
            closeButton: true,
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function(xhr) {
        console.log('Edit Error Response:', xhr); // Debug log

        var errors = xhr.responseJSON?.errors;
        if (errors) {
          Object.keys(errors).forEach(function(key) {
            toastr.error(errors[key][0], 'Ralat Validasi!', {
              closeButton: true,
              timeOut: 5000,
              progressBar: true,
              positionClass: 'toast-top-right'
            });
          });
        } else {
          var errorMessage = xhr.responseJSON?.message || 'Ralat berlaku semasa mengemaskini artikel Knowledge Base. Sila cuba lagi.';
          toastr.error(errorMessage, 'Ralat!', {
            closeButton: true,
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      complete: function() {
        console.log('Edit AJAX request completed'); // Debug log
        // Re-enable submit button
        submitButton.prop('disabled', false).html(originalText);
      }
    });
  });

  // Handle delete document button click
  $(document).on('click', '.delete-document-btn', function(e) {
    e.preventDefault();
    var documentId = $(this).data('doc-id');
    var documentItem = $(this).closest('.document-item');
    var fileName = documentItem.find('.file-name').text();

    if (confirm(`Adakah anda pasti untuk memadamkan dokumen "${fileName}"?`)) {
      // Disable the button
      $(this).prop('disabled', true);

      $.ajax({
        url: `/knowledgebase/document/${documentId}`,
        type: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            // Remove the document item from the display
            documentItem.fadeOut(300, function() {
              $(this).remove();

              // Check if no documents left
              var remainingDocs = $('#edit-existing-documents .document-item');
              if (remainingDocs.length === 0) {
                $('#edit-existing-documents').html('<p class="text-muted mb-0">Tiada dokumen</p>');
              }
            });

            // Show success message
            toastr.success(response.message || 'Dokumen telah berjaya dipadamkan.');

            // Refresh DataTable to update any document counts or indicators
            if (typeof window.dt_knowledgebase !== 'undefined') {
              window.dt_knowledgebase.ajax.reload(null, false);
            } else if ($.fn.DataTable.isDataTable('.datatables-knowledgebase')) {
              $('.datatables-knowledgebase').DataTable().ajax.reload(null, false);
            }
          }
        },
        error: function(xhr) {
          toastr.error(xhr.responseJSON?.message || 'Gagal memadamkan dokumen. Sila cuba lagi.');
          // Re-enable the button on error
          $(this).prop('disabled', false);
        }
      });
    }
  });

  // Handle modal close event to reset form
  $(document).on('hidden.bs.modal', '#editKnowledgeBaseModal', function () {
    $('#editKnowledgeBaseForm')[0].reset();
    $('#edit-kb-subcategory').html('<option value="">Select Sub Category</option>');

    // Clear file selections
    if (typeof window.removeAllEditFiles !== 'undefined') {
      window.removeAllEditFiles();
    }

    // Clear existing documents display
    $('#edit-existing-documents').html('<p class="text-muted mb-0">Tiada dokumen</p>');

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
      toastr.error('ID rekod tidak sah.');
      return;
    }

    // Show loading message
    toastr.info('Memuatkan data Knowledge Base...', '', { timeOut: 1000 });

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

          // Display existing documents
          displayExistingDocuments(data.documents || []);

          // Initialize file upload functionality for edit modal
          initializeEditFileUpload();

          // Show the edit modal
          $('#editKnowledgeBaseModal').modal('show');
        } else {
          toastr.error('Gagal memuatkan data Knowledge Base: ' + (response.message || 'Ralat tidak diketahui'));
        }
      },
      error: function(xhr) {
        console.error('AJAX Error:', xhr); // Debug log
        var errorMsg = 'Gagal memuatkan data Knowledge Base. Sila cuba lagi.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        toastr.error(errorMsg);
      }
    });
  }

  // Function to display existing documents
  function displayExistingDocuments(documents) {
    var container = $('#edit-existing-documents');

    if (!documents || documents.length === 0) {
      container.html('<p class="text-muted mb-0">Tiada dokumen</p>');
      return;
    }

    var html = '';
    documents.forEach(function(doc) {
      var iconClass = getFileIcon(doc.doc_mime);
      var downloadUrl = `/knowledgebase/document/${doc.id}/download`;

      html += `
        <div class="document-item">
          <i class="${iconClass} file-icon"></i>
          <span class="file-name">${doc.doc_name}</span>
          <div class="file-actions">
            <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary" title="Muat turun" target="_blank">
              <i class="bx bx-download"></i>
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger delete-document-btn" data-doc-id="${doc.id}" title="Padam">
              <i class="bx bx-trash"></i>
            </button>
          </div>
        </div>
      `;
    });

    container.html(html);
  }

  // Function to get file icon based on MIME type
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

  // Function to initialize file upload functionality for edit modal
  function initializeEditFileUpload() {
    const dropZone = document.getElementById('edit-pdf-drop-zone');
    const fileInput = document.getElementById('edit-kb-file');
    const filesPreview = document.getElementById('edit-files-preview');

    if (!dropZone || !fileInput || !filesPreview) {
      console.log('Edit file upload elements not found');
      return;
    }

    // Maximum file size (2MB) and maximum number of files (5)
    const maxFileSize = 2 * 1024 * 1024;
    const maxFiles = 5;
    let selectedFiles = [];

    // Make selectedFiles available globally for form submission
    window.editSelectedFiles = selectedFiles;

    // Toastr helper object
    const showToastr = {
      success: function(message) {
        toastr.success(message, '', {
          closeButton: true,
          timeOut: 3000,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      },
      error: function(message) {
        toastr.error(message, 'Ralat!', {
          closeButton: true,
          timeOut: 5000,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      },
      warning: function(message) {
        toastr.warning(message, 'Amaran!', {
          closeButton: true,
          timeOut: 4000,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      },
      info: function(message) {
        toastr.info(message, '', {
          closeButton: true,
          timeOut: 2000,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      }
    };

    // Event listeners for drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
      dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
      dropZone.classList.remove('dragover');
    }

    // Handle drop
    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', function(e) {
      handleFiles(e.target.files);
    });

    function handleDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      handleFiles(files);
    }

    function handleFiles(fileList) {
      if (!fileList || fileList.length === 0) return;

      // Convert FileList to Array for easier manipulation
      const files = Array.from(fileList);

      // Reset previous states
      dropZone.classList.remove('error');
      clearValidationError();

      const filesToAdd = [];
      const errors = [];
      let hasErrors = false;

      // Check if adding these files would exceed the limit
        if (selectedFiles.length + files.length > maxFiles) {
        const message = `Maksimum ${maxFiles} fail dibenarkan. Anda sudah mempunyai ${selectedFiles.length} fail dipilih. Sila buang beberapa fail sebelum menambah fail baru.`;
        showError(message);
        showToastr.error(message);
        return;
      }      // Validate each file
      files.forEach(file => {
        // Check if file is already selected
        const isDuplicate = selectedFiles.some(selectedFile =>
          selectedFile.name === file.name &&
          selectedFile.size === file.size &&
          selectedFile.lastModified === file.lastModified
        );

        if (isDuplicate) {
          errors.push(`Fail "${file.name}" sudah dipilih sebelum ini`);
          showToastr.warning(`Fail "${file.name}" sudah dipilih sebelum ini. Sila pilih fail yang berbeza.`);
          hasErrors = true;
          return;
        }

        // Validate file type (check both MIME type and file extension)
        const isValidPDF = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        if (!isValidPDF) {
          errors.push(`"${file.name}" bukanlah fail PDF yang sah`);
          showToastr.error(`"${file.name}" bukanlah fail PDF yang sah. Hanya fail PDF dibenarkan untuk dimuat naik.`);
          hasErrors = true;
          return;
        }

        // Validate file size (2MB = 2 * 1024 * 1024 bytes)
        if (file.size > maxFileSize) {
          const fileSize = formatFileSize(file.size);
          errors.push(`"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB`);
          showToastr.error(`"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`);
          hasErrors = true;
          return;
        }

        // File is valid, add to list
        filesToAdd.push(file);
      });

      // Add valid files to selectedFiles array
      if (filesToAdd.length > 0) {
        selectedFiles.push(...filesToAdd);
        updateFilesPreview();

        if (filesToAdd.length === 1) {
          showToastr.success(`1 fail berjaya ditambah`);
        } else {
          showToastr.success(`${filesToAdd.length} fail berjaya ditambah`);
        }
      }

      // Clear file input
      fileInput.value = '';
    }

    function updateFilesPreview() {
      if (selectedFiles.length === 0) {
        filesPreview.innerHTML = '';
        return;
      }

      let previewHTML = '';
      selectedFiles.forEach((file, index) => {
        const fileSize = formatFileSize(file.size);
        previewHTML += `
          <div class="file-item d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-grow-1">
              <i class="bx bxs-file-pdf text-danger me-2" style="font-size: 1.5rem;"></i>
              <div>
                <div class="fw-medium">${file.name}</div>
                <small class="text-muted">${fileSize}</small>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEditFile(${index})" title="Buang fail">
              <i class="bx bx-x"></i>
            </button>
          </div>
        `;
      });
      filesPreview.innerHTML = previewHTML;
    }

    // Make removeEditFile globally accessible
    window.removeEditFile = function(index) {
      selectedFiles.splice(index, 1);
      updateFilesPreview();
      showToastr.info('Fail telah dibuang');
    };

    // Make removeAllEditFiles globally accessible
    window.removeAllEditFiles = function() {
      selectedFiles.length = 0;
      updateFilesPreview();
      dropZone.classList.remove('dragover', 'error');
      clearValidationError();
    };

    function showError(message) {
      dropZone.classList.add('error');
      const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
      feedback.textContent = message;
      feedback.style.display = 'block';
    }

    function clearValidationError() {
      const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
      feedback.style.display = 'none';
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
  }

  // Debug: Log when the script loads
  console.log('Knowledge Base Edit Modal script loaded');

})();
