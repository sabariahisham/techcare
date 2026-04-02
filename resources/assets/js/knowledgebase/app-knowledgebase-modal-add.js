/**
 * Knowledge Base Modal Add
 */

'use strict';

// Knowledge Base Add Modal Initialization
(function () {
  // Global variable for FormValidation instance
  let fv;

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
            toastr.error(error);
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
            // Show success message first before closing modal
            toastr.success('Artikel Knowledge Base berjaya ditambah.');

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

            // Reload the DataTable if it exists
            if (typeof dt_knowledgebase !== 'undefined') {
              dt_knowledgebase.ajax.reload();
            } else if (typeof $('.datatables-knowledgebase').DataTable !== 'undefined') {
              $('.datatables-knowledgebase').DataTable().ajax.reload();
            }
          }
        },
        error: function (xhr) {
          var responseJson = xhr.responseJSON || {};
          var errors = responseJson.errors;
          var generalMessage = responseJson.message;

          if (errors) {
            // Show general message as toastr if available
            if (generalMessage) {
              toastr.error(generalMessage, 'TIDAK boleh disimpan');
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
            toastr.error(generalMessage || 'Ralat berlaku semasa menyimpan artikel.', 'TIDAK boleh disimpan');
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
      toastr.error('Sila perbaiki ralat dalam borang sebelum menyimpan.', 'TIDAK boleh disimpan');
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

  // Handle modal show event to focus on first input
  $(document).on('shown.bs.modal shown.bs.offcanvas', '#knowledgeBaseModal, #offcanvasAddKnowledgeBase', function () {
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
