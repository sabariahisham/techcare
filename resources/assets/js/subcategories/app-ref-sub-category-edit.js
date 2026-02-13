'use strict';

// All messages centralized here
const messages = {
  // Validation messages
  categoryRequired: 'Sila pilih kategori',
  nameRequired: 'Sila masukkan nama sub-kategori',
  descRequired: 'Sila masukkan deskripsi sub-kategori',
  duplicateError: 'Nama sub-kategori sudah wujud dalam kategori ini',

  // Success messages
  updateSuccess: 'Sub-kategori berjaya dikemaskini',

  // Error messages
  updateError: 'Ralat semasa mengemaskini sub-kategori',
  loadError: 'Ralat semasa memuat data sub-kategori',

  // General
  successTitle: 'Berjaya!',
  errorTitle: 'Ralat!'
};

$(function () {
  const editSubCategoryForm = document.getElementById('editSubCategoryForm');

  // Form Validation for Edit Sub Category
  const fv = FormValidation.formValidation(editSubCategoryForm, {
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
              const catId = document.querySelector('#edit_cat_id').value;
              const subcategoryId = document.querySelector('#edit_subcategory_id').value;

              // Skip validation if no value, no category, or value is too short
              if (!value || !catId || value.trim().length < 2) {
                return true;
              }

              // Use promise with debouncing
              return new Promise(function(resolve) {
                clearTimeout(window.duplicateCheckEditTimeout);
                window.duplicateCheckEditTimeout = setTimeout(function() {
                  // Show loading indicator
                  $field.siblings('.fv-plugins-message-container').find('.fv-help-block').html('<i class="bx bx-loader bx-spin"></i> Memeriksa...');

                  $.ajax({
                    url: '/ref-sub-category-management/check-duplicate',
                    type: 'POST',
                    data: {
                      cat_id: catId,
                      subcat_name: value.trim(),
                      id: subcategoryId,
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
        rowSelector: '.mb-6'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    const formData = new FormData(editSubCategoryForm);
    const subcategoryId = $('#edit_subcategory_id').val();

    $.ajax({
      url: `/ref-sub-category-management/${subcategoryId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      success: function(response) {
        if (response.success) {
          // Close offcanvas
          var editOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditSubCategory'));
          editOffcanvas.hide();

          // Show success message
          toastr.success(messages.updateSuccess, messages.successTitle, {
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
          toastr.error(messages.updateError, messages.errorTitle);
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
          toastr.error(messages.updateError, messages.errorTitle);
        }
        console.error(xhr);
      }
    });
  });

  // Load subcategory data when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const subcategoryId = $(this).data('id');

    $.ajax({
      url: `/ref-sub-category-management/${subcategoryId}/edit`,
      type: 'GET',
      success: function(response) {
        $('#edit_subcategory_id').val(response.id);
        $('#edit_cat_id').val(response.cat_id);
        $('#edit_subcat_name').val(response.subcat_name);
        $('#edit_subcat_desc').val(response.subcat_desc);

        $('#offcanvasEditSubCategory').offcanvas('show');
      },
      error: function(xhr) {
        toastr.error(messages.loadError, messages.errorTitle, {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasEditSubCategory').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    editSubCategoryForm.reset();

    // Cancel pending duplicate check requests
    if (window.duplicateCheckEditTimeout) {
      clearTimeout(window.duplicateCheckEditTimeout);
    }
  });

  // Revalidate sub-category name when category changes
  document.querySelector('#edit_cat_id').addEventListener('change', function() {
    if (fv) {
      fv.revalidateField('subcat_name');
    }
  });

  // Convert description to uppercase as user types
  document.querySelector('#edit_subcat_desc').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });
});
