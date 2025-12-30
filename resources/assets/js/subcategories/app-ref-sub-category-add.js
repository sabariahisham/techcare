'use strict';

// All messages centralized here
const messages = {
  // Validation messages
  categoryRequired: 'Sila pilih kategori',
  nameRequired: 'Sila masukkan nama sub-kategori',
  descRequired: 'Sila masukkan deskripsi sub-kategori',
  duplicateError: 'Nama sub-kategori sudah wujud dalam kategori ini',

  // Success messages
  addSuccess: 'Sub-kategori berjaya ditambah',
  updateSuccess: 'Sub-kategori berjaya dikemaskini',
  deleteSuccess: 'Sub-kategori berjaya dipadam',

  // Error messages
  addError: 'Ralat semasa menambah sub-kategori',
  updateError: 'Ralat semasa mengemaskini sub-kategori',
  deleteError: 'Ralat semasa memadam sub-kategori',
  loadError: 'Ralat semasa memuat data sub-kategori',

  // Confirmation messages
  deleteTitle: 'Adakah anda pasti?',
  deleteText: 'Anda ingin memadam sub-kategori <b>{subcategoryName}</b>?',
  deleteConfirm: 'Ya, padam!',
  deleteCancel: 'Tidak, batal!',
  deletedTitle: 'Dipadam!',

  // General
  successTitle: 'Berjaya!',
  errorTitle: 'Ralat!'
};

// Form Validation
(function () {
  // Add New Sub Category form
  const addNewSubCategoryForm = document.getElementById('addNewSubCategoryForm');

  if (addNewSubCategoryForm) {
    const fv = FormValidation.formValidation(addNewSubCategoryForm, {
      fields: {
        cat_id: {
          validators: {
            notEmpty: {
              message: messages.categoryRequired
            }
          }
        },
        subcat_name: {
          validators: {
            notEmpty: {
              message: messages.nameRequired
            },
            callback: {
              message: messages.duplicateError,
              callback: function(value, validator, $field) {
                const catId = document.querySelector('[name="cat_id"]').value;

                // Skip validation if no value, no category, or value is too short
                if (!value || !catId || value.trim().length < 2) {
                  return true;
                }

                // Use promise with debouncing
                return new Promise(function(resolve) {
                  clearTimeout(window.duplicateCheckTimeout);
                  window.duplicateCheckTimeout = setTimeout(function() {
                    // Show loading indicator
                    $field.siblings('.fv-plugins-message-container').find('.fv-help-block').html('<i class="bx bx-loader bx-spin"></i> Memeriksa...');

                    $.ajax({
                      url: '/ref-sub-category-management/check-duplicate',
                      type: 'POST',
                      data: {
                        cat_id: catId,
                        subcat_name: value.trim(),
                        _token: document.querySelector('meta[name="csrf-token"]').content
                      }
                    }).done(function(response) {
                      resolve(!response.exists);
                    }).fail(function() {
                      resolve(true); // On error, assume valid to not block form
                    });
                  }, 500); // Increased debounce to 500ms for better performance
                });
              }
            }
          }
        },
        subcat_desc: {
          validators: {
            notEmpty: {
              message: messages.descRequired
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger({
          event: {
            subcat_name: 'blur' // Only validate on blur for name field to reduce lag
          }
        }),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: function (field, ele) {
            return '.mb-6';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    }).on('core.form.valid', function() {
      // Submit form data
      const formData = new FormData(addNewSubCategoryForm);

      $.ajax({
        url: '/ref-sub-category-management',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            // Close offcanvas
            var addOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddSubCategory'));
            addOffcanvas.hide();

            // Clear form
            addNewSubCategoryForm.reset();
            fv.resetForm();

            // Show success message
            toastr.success(messages.addSuccess, messages.successTitle, {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });

            // Refresh the datatable
            if (typeof window.dt_subcategory !== 'undefined') {
              window.dt_subcategory.ajax.reload();
            } else {
              $('.datatables-refcategory').DataTable().ajax.reload();
            }
          } else if (response.duplicate) {
            toastr.error(messages.duplicateError, messages.errorTitle);
          } else {
            toastr.error(messages.addError, messages.errorTitle);
          }
        },
        error: function(xhr) {
          if (xhr.status === 422) {
            if (xhr.responseJSON.duplicate) {
              toastr.error(messages.duplicateError, messages.errorTitle);
            } else {
              const errors = xhr.responseJSON.errors;
              let errorMessage = '';
              for (const field in errors) {
                errorMessage += errors[field][0] + '\n';
              }
              toastr.error(errorMessage, messages.errorTitle);
            }
          } else {
            toastr.error(messages.addError, messages.errorTitle);
          }
          console.error(xhr);
        }
      });
    });

    // Revalidate sub-category name when category changes
    document.querySelector('[name="cat_id"]').addEventListener('change', function() {
      if (fv) {
        fv.revalidateField('subcat_name');
      }
    });

    // Cleanup when offcanvas is hidden
    $('#offcanvasAddSubCategory').on('hidden.bs.offcanvas', function () {
      // Cancel pending duplicate check requests
      if (window.duplicateCheckTimeout) {
        clearTimeout(window.duplicateCheckTimeout);
      }
    });
  }
})();
