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
              message: 'Sila pilih level petugas'
            },
            choice: {
              min: 1,
              max: 1,
              message: 'Sila pilih level petugas'
            }
          }
        },
        'sla_severity': {
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
      $('.duplicate-error').remove();
      $('.days-validation-error').remove();
      
      // Clear duplicate error container
      const errorDiv = document.getElementById('duplicate-error-container');
      if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '';
      }

      // Reset submission flag
      isSubmitting = false;
    }
  });

  // Function to check for duplicate SLA (all 5 fields: cat_id, subcat_id, sla_level, sla_severity, sla_days)
  function checkDuplicateSla(catId, subcatId, slaLevel, slaSeverity, slaDays, callback) {
    if (!catId || !subcatId || !slaLevel || !slaSeverity || !slaDays || parseInt(slaDays) <= 0) {
      callback(false);
      return;
    }

    $.ajax({
      url: '/sla-severity/check-duplicate',
      type: 'POST',
      data: {
        cat_id: catId,
        subcat_id: subcatId,
        sla_level: slaLevel,
        sla_severity: slaSeverity,
        sla_days: slaDays
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        callback(response.exists || false);
      },
      error: function() {
        callback(false);
      }
    });
  }

  // Debounce timer for duplicate validation
  let duplicateValidationTimer;

  // Check for duplicates when key fields change (all 5 fields: cat_id, subcat_id, sla_level, sla_severity, sla_days)
  function validateDuplicateOnChange() {
    // Clear previous timer
    if (duplicateValidationTimer) {
      clearTimeout(duplicateValidationTimer);
    }

    // Set a small delay to avoid checking on every keystroke
    duplicateValidationTimer = setTimeout(function() {
      const catId = $('#add-cat-id').val();
      const subcatId = $('#add-subcat-id').val();
      const slaLevel = $('#add-sla-level').val();
      const slaSeverity = $('#add-sla-severity').val();
      const slaDays = $('#add-sla-days').val();

      // Only check for duplicates if ALL 5 fields have values and sla_days is a valid positive number
      if (catId && subcatId && slaLevel && slaSeverity && slaDays && parseInt(slaDays) > 0) {
        checkDuplicateSla(catId, subcatId, slaLevel, slaSeverity, slaDays, function(isDuplicate) {
          // Show error in a separate div (create if doesn't exist)
          let errorDiv = document.getElementById('duplicate-error-container');
          if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'duplicate-error-container';
            errorDiv.className = 'mt-3';
            // Insert after the form or at the end of the offcanvas body
            const formContainer = document.getElementById('addNewSlaForm').parentNode;
            formContainer.appendChild(errorDiv);
          }

          if (isDuplicate) {
            errorDiv.innerHTML = '<div class="alert alert-warning"><i class="bx bx-info-circle me-2"></i>SLA dengan kombinasi semua data ini sudah wujud.</div>';
            errorDiv.style.display = 'block';
          } else {
            errorDiv.style.display = 'none';
            errorDiv.innerHTML = '';
          }
        });
      } else {
        // Clear any existing duplicate errors when not all 5 fields are filled
        const errorDiv = document.getElementById('duplicate-error-container');
        if (errorDiv) {
          errorDiv.style.display = 'none';
          errorDiv.innerHTML = '';
        }
      }
    }, 500); // 500ms delay
  }

  // Add event listeners for duplicate checking (all 5 fields)
  $('#add-cat-id, #add-subcat-id, #add-sla-level, #add-sla-severity, #add-sla-days').on('change input', validateDuplicateOnChange);

  // Additional validation specifically for sla_days field
  $('#add-sla-days').on('input blur', function() {
    const slaDays = $(this).val();
    const daysField = this;
    const existingDaysError = daysField.parentNode.querySelector('.days-validation-error');

    // Remove existing validation error
    if (existingDaysError) {
      existingDaysError.remove();
    }

    // Validate that days is a positive number for duplicate checking
    if (slaDays && (isNaN(slaDays) || parseInt(slaDays) <= 0)) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'text-warning mt-1 days-validation-error';
      errorDiv.textContent = 'Bilangan hari mesti lebih daripada 0 untuk pemeriksaan pendua.';
      daysField.parentNode.appendChild(errorDiv);
    }

    // Note: Duplicate validation will be triggered automatically by the main event listener
    // No need to manually trigger it here to avoid false positives
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
        success: function(response) {
          if (response.success && response.data && Array.isArray(response.data)) {
            $.each(response.data, function(index, subcategory) {
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
