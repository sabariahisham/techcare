/**
 * Knowledge Base Modal Add
 */

'use strict';

// Knowledge Base Add Modal Initialization
(function () {
  // Global variable for FormValidation instance
  let fv;

  // ─── Add File Upload State ─────────────────────────────────────────────────
  const ADD_MAX_FILES = 5;
  const ADD_MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB

  // Use a stable array so window.selectedFiles always points to the same object
  const addSelectedFiles = [];
  window.selectedFiles = addSelectedFiles;

  // Toastr helper (matches edit modal style)
  const addToastr = {
    success: (msg) => toastr.success(msg, '', {
      closeButton: true,
      timeOut: 3000,
      progressBar: true,
      positionClass: 'toast-top-right'
    }),
    error: (msg) => toastr.error(msg, 'Ralat!', {
      closeButton: true,
      timeOut: 5000,
      progressBar: true,
      positionClass: 'toast-top-right'
    }),
    warning: (msg) => toastr.warning(msg, 'Amaran!', {
      closeButton: true,
      timeOut: 4000,
      progressBar: true,
      positionClass: 'toast-top-right'
    }),
    info: (msg) => toastr.info(msg, '', {
      closeButton: true,
      timeOut: 2000,
      progressBar: true,
      positionClass: 'toast-top-right'
    })
  };

  function addFormatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024,
      sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function addShowError(dropZone, message) {
    dropZone.classList.add('error');
    var wrapper = dropZone.closest('.file-upload-wrapper');
    var fb = wrapper ? wrapper.querySelector('.invalid-feedback') : null;
    if (fb) {
      fb.textContent = message;
      fb.style.display = 'block';
    }
  }

  function addClearError() {
    const dropZone = document.getElementById('pdf-drop-zone');
    if (!dropZone) return;
    var wrapper = dropZone.closest('.file-upload-wrapper');
    var fb = wrapper ? wrapper.querySelector('.invalid-feedback') : null;
    if (fb) fb.style.display = 'none';
  }

  function addUpdatePreview() {
    const filesPreview = document.getElementById('files-preview');
    if (!filesPreview) return;
    filesPreview.innerHTML = '';
    if (addSelectedFiles.length === 0) return;

    const header = document.createElement('div');
    header.className = 'mb-2 d-flex justify-content-between align-items-center';
    header.innerHTML = `
      <small class="text-muted fw-semibold">Fail dipilih (${addSelectedFiles.length}/${ADD_MAX_FILES})</small>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.removeAllFiles()">
        <i class="bx bx-x me-1"></i>Buang Semua
      </button>`;
    filesPreview.appendChild(header);

    addSelectedFiles.forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'file-item';
      item.innerHTML = `
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="bx bxs-file-pdf text-danger me-3 fs-4"></i>
            <div>
              <div class="file-name fw-semibold">${file.name}</div>
              <small class="file-size text-muted">${addFormatFileSize(file.size)}</small>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-add-file" data-idx="${index}">
            <i class="bx bx-x"></i>
          </button>
        </div>`;
      item.querySelector('.remove-add-file').addEventListener('click', function () {
        const idx = parseInt(this.dataset.idx, 10);
        const removedFile = addSelectedFiles[idx];
        const removedName = removedFile ? removedFile.name : null;
        addSelectedFiles.splice(idx, 1);
        addUpdatePreview();
        addClearError();
        if (removedName) addToastr.info(`Fail "${removedName}" telah dibuang`);
      });
      filesPreview.appendChild(item);
    });
  }

  // Global removeAllFiles — called from success handler and "Buang Semua" button
  window.removeAllFiles = function () {
    const fileInput = document.getElementById('add-kb-file');
    const filesPreview = document.getElementById('files-preview');
    const dropZone = document.getElementById('pdf-drop-zone');
    const fileCount = addSelectedFiles.length;
    addSelectedFiles.length = 0; // clear in-place; window.selectedFiles stays in sync
    if (fileInput) fileInput.value = '';
    if (filesPreview) filesPreview.innerHTML = '';
    if (dropZone) dropZone.classList.remove('dragover', 'error');
    addClearError();
    if (fileCount > 0) addToastr.info(`Semua fail (${fileCount}) telah dibuang`);
  };

  // Called on each modal open — resets state and (first time only) attaches listeners
  function initializeAddFileUpload() {
    const dropZone = document.getElementById('pdf-drop-zone');
    const fileInput = document.getElementById('add-kb-file');
    const filesPreview = document.getElementById('files-preview');
    if (!dropZone || !fileInput || !filesPreview) return;

    // Reset state every open
    addSelectedFiles.length = 0;
    filesPreview.innerHTML = '';
    fileInput.value = '';
    dropZone.classList.remove('dragover', 'error');
    addClearError();

    // Attach DOM event listeners only once (flag on the element)
    if (dropZone._addFileUploadInitialized) return;
    dropZone._addFileUploadInitialized = true;

    function handleFiles(fileList) {
      if (!fileList || fileList.length === 0) return;
      const files = Array.from(fileList);
      dropZone.classList.remove('error');
      addClearError();

      if (addSelectedFiles.length + files.length > ADD_MAX_FILES) {
        const msg = `Maksimum ${ADD_MAX_FILES} fail dibenarkan. Anda sudah mempunyai ${addSelectedFiles.length} fail dipilih. Sila buang beberapa fail sebelum menambah fail baru.`;
        addShowError(dropZone, msg);
        addToastr.error(msg);
        return;
      }

      const filesToAdd = [];
      files.forEach(file => {
        if (addSelectedFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified)) {
          addToastr.warning(`Fail "${file.name}" sudah dipilih sebelum ini. Sila pilih fail yang berbeza.`);
          return;
        }
        if (!(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
          addToastr.error(`"${file.name}" bukanlah fail PDF yang sah. Hanya fail PDF dibenarkan untuk dimuat naik.`);
          return;
        }
        if (file.size > ADD_MAX_FILE_SIZE) {
          addToastr.error(`"${file.name}" (${addFormatFileSize(file.size)}) melebihi saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`);
          return;
        }
        filesToAdd.push(file);
      });

      if (filesToAdd.length > 0) {
        addSelectedFiles.push(...filesToAdd);
        addUpdatePreview();
        addToastr.success(filesToAdd.length === 1 ? `1 fail berjaya ditambah` : `${filesToAdd.length} fail berjaya ditambah`);
      }
      fileInput.value = '';
    }

    // Click anywhere on the drop zone opens the file dialog
    dropZone.addEventListener('click', function (e) {
      if (e.target !== fileInput) fileInput.click();
    });

    // Drag-and-drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt =>
      dropZone.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
      }, false)
    );
    ['dragenter', 'dragover'].forEach(evt =>
      dropZone.addEventListener(evt, () => dropZone.classList.add('dragover'), false)
    );
    ['dragleave', 'drop'].forEach(evt =>
      dropZone.addEventListener(evt, () => dropZone.classList.remove('dragover', 'error'), false)
    );
    dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);

    // File input change (direct click on input or programmatic trigger)
    fileInput.addEventListener('change', function () {
      if (this.files && this.files.length > 0) handleFiles(this.files);
    });
  }
  // ─── End Add File Upload ───────────────────────────────────────────────────

  // Handle category change to populate subcategories
  $(document).on('change', '#add-kb-category', function () {
    var categoryId = $(this).val();
    var subcategorySelect = $('#add-kb-subcategory');

    // Reset subcategory validation state
    if (fv) {
      fv.resetField('subcat_id');
      subcategorySelect.removeClass('is-invalid is-valid');
      subcategorySelect.closest('.mb-4').find('.invalid-feedback:not(.fv-plugins-message-container)').remove();
    }

    // Reset subcategory and disable until category chosen
    subcategorySelect.html('<option value="">Pilih Sub Kategori</option>').val('').prop('disabled', true);

    if (categoryId) {
      // Show loading state
      subcategorySelect.html('<option value="">Memuatkan...</option>');

      $.ajax({
        url: `/categories/${categoryId}/subcategories`,
        type: 'GET',
        success: function (response) {
          subcategorySelect.html('<option value="">Pilih Sub Kategori</option>');
          if (response.success && response.data && response.data.length > 0) {
            response.data.forEach(function (subcategory) {
              subcategorySelect.append(`<option value="${subcategory.id}">${subcategory.subcat_name}</option>`);
            });
            subcategorySelect.prop('disabled', false);
          } else {
            subcategorySelect.html('<option value="">Tiada sub kategori tersedia</option>');
          }
        },
        error: function (xhr) {
          console.error('Error fetching subcategories:', xhr);
          toastr.error('Gagal memuatkan sub kategori. Sila cuba lagi.');
          subcategorySelect.html('<option value="">Gagal memuatkan sub kategori</option>');
        }
      });
    }
  });

  // Initialize FormValidation
  const addNewKnowledgeBaseForm = document.getElementById('addNewKnowledgeBaseForm');

  if (addNewKnowledgeBaseForm) {
    fv = FormValidation.formValidation(addNewKnowledgeBaseForm, {
      fields: {
        kb_title: {
          validators: {
            notEmpty: {
              message: 'Tajuk artikel diperlukan'
            },
            stringLength: {
              min: 3,
              max: 255,
              message: 'Tajuk artikel mestilah antara 3 hingga 255 aksara'
            }
          }
        },
        cat_id: {
          validators: {
            notEmpty: {
              message: 'Sila pilih kategori'
            }
          }
        },
        subcat_id: {
          validators: {
            notEmpty: {
              message: 'Sila pilih sub kategori'
            }
          }
        },
        kb_desc: {
          validators: {
            notEmpty: {
              message: 'Deskripsi artikel diperlukan'
            },
            stringLength: {
              min: 10,
              message: 'Deskripsi mestilah sekurang-kurangnya 10 aksara'
            }
          }
        },
        kb_status: {
          validators: {
            notEmpty: {
              message: 'Sila pilih status artikel'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          eleInvalidClass: 'is-invalid',
          rowSelector: function (field, ele) {
            return '.mb-4';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    });

    // Handle form submission when all fields are valid
    fv.on('core.form.valid', function () {
      const form = fv.form;

      // Temporarily enable disabled fields so FormData captures their values
      const subcatSelect = $('#add-kb-subcategory');
      const wasDisabled = subcatSelect.prop('disabled');
      subcatSelect.prop('disabled', false);

      const formData = new FormData(form);

      // Re-disable if it was disabled
      if (wasDisabled) subcatSelect.prop('disabled', true);

      const submitButton = $('#btnAddKnowledgeBase');
      const originalText = submitButton.html();

      // Remove any existing pdf_files from FormData (in case the input field has old data)
      formData.delete('pdf_files[]');

      // Get selected files from the global window object (set by the file upload script)
      if (typeof window.selectedFiles !== 'undefined' && window.selectedFiles.length > 0) {
        // Validate files before submission
        let validationErrors = [];
        const maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
        const maxFiles = 5;

        // Check file count
        if (window.selectedFiles.length > maxFiles) {
          validationErrors.push(`Maksimum ${maxFiles} fail dibenarkan. Anda telah memilih ${window.selectedFiles.length} fail.`);
        }

        // Check each file
        window.selectedFiles.forEach((file, index) => {
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
          return;
        }

        // Add valid files to FormData
        window.selectedFiles.forEach(file => {
          formData.append('pdf_files[]', file);
        });
      }

      // Disable submit button and show loading state
      submitButton.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Menyimpan...');

      $.ajax({
        url: '/knowledgebase',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            toastr.success('Artikel Knowledge Base berjaya ditambah.', 'Berjaya!', {
              closeButton: true,
              timeOut: 3000,
              progressBar: true,
              positionClass: 'toast-top-right',
              showClass: 'animate__animated animate__bounceInRight',
              hideClass: 'animate__animated animate__fadeOut',
              'toastClass': 'toast ngx-toastr bg-success text-white',
              'closeClass': 'toast-close-button',
              'titleClass': 'toast-title',
              'messageClass': 'toast-message',
              'progressClass': 'toast-progress'
            });

            // Close the modal if it exists
            var modalEl = document.getElementById('knowledgeBaseModal');
            if (modalEl) {
              bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            // Close the offcanvas if it exists
            var offcanvasEl = document.getElementById('offcanvasAddKnowledgeBase');
            if (offcanvasEl) {
              bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
            }

            // Reset the form and validation
            form.reset();
            fv.resetForm();
            $('#add-kb-subcategory').html('<option value="">Pilih Sub Kategori</option>').prop('disabled', true);

            // Clear file selections
            if (typeof window.removeAllFiles !== 'undefined') {
              window.removeAllFiles();
            }

            // Reload the DataTable if it exists
            setTimeout(function () {
              if (typeof window.dt_knowledgebase !== 'undefined') {
                window.dt_knowledgebase.ajax.reload(null, false);
              } else if ($.fn.DataTable.isDataTable('.datatables-knowledgebase')) {
                $('.datatables-knowledgebase').DataTable().ajax.reload(null, false);
              }
            }, 500);
          } else {
            toastr.error(response.message || 'Gagal menambah artikel Knowledge Base.', 'Ralat!', {
              closeButton: true,
              timeOut: 5000,
              progressBar: true,
              positionClass: 'toast-top-right'
            });
          }
        },
        error: function (xhr) {
          var responseJson = xhr.responseJSON || {};
          var errors = responseJson.errors;
          var generalMessage = responseJson.message;

          if (errors) {
            // Show general message as toastr if available
            if (generalMessage) {
              toastr.error(generalMessage, 'Ralat!', {
                closeButton: true,
                timeOut: 5000,
                progressBar: true,
                positionClass: 'toast-top-right'
              });
            }

            // Display server validation errors under each field
            Object.keys(errors).forEach(function (key) {
              const fieldName = key;
              const errorMessage = errors[key][0];

              // Find the field element
              const fieldElement = $(`[name="${fieldName}"]`);

              if (fieldElement.length > 0) {
                // Add Bootstrap invalid class
                fieldElement.addClass('is-invalid');

                // Remove existing manual validation messages (not FormValidation ones)
                fieldElement.closest('.mb-4').find('.invalid-feedback:not(.fv-plugins-message-container)').remove();

                // Add new validation message after the field
                fieldElement.after(`<div class="invalid-feedback d-block">${errorMessage}</div>`);
              }
            });
          } else {
            toastr.error(generalMessage || 'Ralat berlaku semasa menyimpan artikel.', 'Ralat!', {
              closeButton: true,
              timeOut: 5000,
              progressBar: true,
              positionClass: 'toast-top-right'
            });
          }
        },
        complete: function () {
          // Re-enable submit button
          submitButton.prop('disabled', false).html(originalText);
        }
      });
    });

    // Show toastr when form cannot be submitted due to validation errors
    fv.on('core.form.invalid', function () {
      toastr.error('Sila perbaiki ralat dalam borang sebelum menyimpan.', 'Ralat Validasi!', {
        closeButton: true,
        timeOut: 5000,
        progressBar: true,
        positionClass: 'toast-top-right'
      });
    });
  }

  // Trigger FormValidation when the footer submit button is clicked
  $(document).on('click', '#btnAddKnowledgeBase', function () {
    if (fv) {
      fv.validate();
    }
  });

  // Handle modal/offcanvas close event to reset form
  $(document).on('hidden.bs.modal hidden.bs.offcanvas', '#knowledgeBaseModal, #offcanvasAddKnowledgeBase', function () {
    const form = $('#addNewKnowledgeBaseForm')[0];

    // Clear file selections
    if (typeof window.removeAllFiles === 'function') {
      window.removeAllFiles();
    }

    // Reset form data
    form.reset();
    $('#add-kb-subcategory').html('<option value="">Pilih Sub Kategori</option>').prop('disabled', true);

    // Reset FormValidation if it exists
    if (fv) {
      fv.resetForm();
    }

    // Remove any validation error classes and messages
    $(this).find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
    $(this).find('.invalid-feedback').remove();

    // Reset all form fields validation state
    $(this).find('input, select, textarea').each(function () {
      $(this).removeClass('is-invalid is-valid');
      if (fv) {
        fv.resetField($(this).attr('name'));
      }
    });
  });

  // Handle modal show event to focus on first input and initialize file upload
  $(document).on('shown.bs.modal shown.bs.offcanvas', '#knowledgeBaseModal, #offcanvasAddKnowledgeBase', function () {
    // Initialize / reset file upload on each open
    initializeAddFileUpload();
    // Focus on first input after a small delay to ensure modal is fully rendered
    setTimeout(function () {
      $('#add-kb-title').focus();
    }, 100);
  });

  // Handle subcategory change for validation
  $(document).on('change', '#add-kb-subcategory', function () {
    // Clear only manually-added server-side error messages
    $(this).closest('.mb-4').find('.invalid-feedback:not(.fv-plugins-message-container)').remove();

    if (fv) {
      fv.revalidateField('subcat_id');
    }
  });

  // Real-time validation revalidation for FormValidation
  $(document).on('input change blur', '#addNewKnowledgeBaseForm input, #addNewKnowledgeBaseForm select, #addNewKnowledgeBaseForm textarea', function () {
    const $field = $(this);
    const fieldName = $field.attr('name');

    // Remove only manually-added server-side error messages (not FormValidation ones)
    $field.closest('.mb-4').find('.invalid-feedback:not(.fv-plugins-message-container)').remove();

    if (fv && fieldName) {
      fv.revalidateField(fieldName);
    }
  });

})();
