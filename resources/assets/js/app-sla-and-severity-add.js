/**
 * Page SLA and Severity Add/Create
 */

'use strict';

// Initialize form elements and components
$(function () {
  // Select2 initialization for dropdowns
  var select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Category',
      dropdownParent: $this.parent()
    });
  }
});

// Form submission handlers
(function () {
  // Form validation setup
  const addNewSlaForm = document.getElementById('addNewSlaForm');
  let fv;

  if (addNewSlaForm) {
    fv = FormValidation.formValidation(addNewSlaForm, {
      fields: {
        'cat_id': {
          validators: {
            notEmpty: {
              message: 'Sila pilih kategori'
            }
          }
        },
        'subcat_id': {
          validators: {
            notEmpty: {
              message: 'Sila pilih sub-kategori'
            }
          }
        },
        'sla_level': {
          validators: {
            notEmpty: {
              message: 'Sila masukkan tahap SLA'
            }
          }
        },
        'sla_priority': {
          validators: {
            notEmpty: {
              message: 'Sila pilih keutamaan SLA'
            }
          }
        },
        'sla_days': {
          validators: {
            notEmpty: {
              message: 'Sila masukkan bilangan hari SLA'
            },
            integer: {
              message: 'Bilangan hari mesti dalam bentuk nombor'
            },
            greaterThan: {
              message: 'Bilangan hari mesti lebih daripada 0',
              min: 0
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: function (field, ele) {
            return '.mb-3, .mb-4, .mb-5, .mb-6';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    }).on('core.form.valid', function () {
      // Form is valid, proceed with AJAX submission
      submitSlaForm();
    });
  }

  // Add SLA form submission function
  let isSubmitting = false;

  function submitSlaForm() {
    // Prevent multiple submissions
    if (isSubmitting) {
      return;
    }

    isSubmitting = true;
    const formData = new FormData(addNewSlaForm);

    $.ajax({
      type: 'POST',
      url: '/sla-severity',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Hide offcanvas
          var addOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddSla'));
          addOffcanvas.hide();

          // Reset form
          addNewSlaForm.reset();

          // Reset FormValidation
          if (fv) {
            fv.resetForm(true);
          }

          // Reload DataTable if it exists on the page
          if (typeof window.dt_sla !== 'undefined' && window.dt_sla) {
            window.dt_sla.ajax.reload();
          }

          // Show success message (defined in JS, not from controller)
          toastr.success('SLA berjaya ditambah', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true
          });

          // Redirect to list page if we're on a dedicated add page
          if (window.location.pathname.includes('/add') || window.location.pathname.includes('/create')) {
            setTimeout(function() {
              window.location.href = '/sla-severity';
            }, 1500);
          }
        } else {
          // Show error message (defined in JS, not from controller)
          toastr.error('Error creating SLA', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        }

        // Reset submission flag
        isSubmitting = false;
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          // Validation errors - show specific field errors
          const errors = xhr.responseJSON.errors;
          let errorMessage = 'Validation failed:\n';
          for (const field in errors) {
            errorMessage += '• ' + errors[field][0] + '\n';
          }
          toastr.error(errorMessage, '', {
            timeOut: 7000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 500) {
          // Server error
          toastr.error('Server error occurred while creating the SLA. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 403) {
          // Forbidden
          toastr.error('You do not have permission to create SLA records.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 401) {
          // Unauthorized
          toastr.error('Your session has expired. Please login again.', '', {
            timeOut: 6000,
            closeButton: true,
            progressBar: true
          });
        } else {
          // General error
          toastr.error('An unexpected error occurred while creating the SLA. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        }

        // Reset submission flag
        isSubmitting = false;
      }
    });
  }

  // Reset form when offcanvas is hidden
  $('#offcanvasAddSla').on('hidden.bs.offcanvas', function () {
    if (addNewSlaForm) {
      addNewSlaForm.reset();

      // Reset FormValidation
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Reset select2 dropdowns
      $('.select2').val(null).trigger('change');

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();

      // Reset submission flag
      isSubmitting = false;
    }
  });

  // Category change handler to populate subcategories
  $('#add-cat-id').on('change', function() {
    const categoryId = $(this).val();
    const subcategorySelect = $('#add-subcat-id');

    // Clear subcategory dropdown completely
    subcategorySelect.empty().append('<option value="">Pilih Sub Kategori</option>');

    if (categoryId) {
      $.ajax({
        url: `/categories/${categoryId}/subcategories`,
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
          if (data && Array.isArray(data)) {
            $.each(data, function(index, subcategory) {
              subcategorySelect.append(`<option value="${subcategory.id}">${subcategory.subcat_name}</option>`);
            });
          } else {
            toastr.warning('No subcategories found for the selected category.', '', {
              timeOut: 4000,
              closeButton: true,
              progressBar: true
            });
          }
        },
        error: function(xhr) {
          console.error('Error loading subcategories:', xhr);
          if (xhr.status === 404) {
            toastr.error('Category not found. Please refresh the page and try again.', '', {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            });
          } else if (xhr.status === 500) {
            toastr.error('Server error occurred while loading subcategories. Please try again.', '', {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            });
          } else {
            toastr.error('Unable to load subcategories. Please refresh the page and try again.', '', {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            });
          }
        }
      });
    }
  });
})();
