/**
 * Page SLA and Severity Add/Create
 */

import logger from '../logger.js';

'use strict';

// Initialize form elements and components
$(function () {
  // Select2 initialization for dropdowns
  var select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Pilih Kategori',
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
        'sla_days_high': {
          validators: {
            notEmpty: {
              message: 'Sila masukkan bilangan hari untuk HIGH'
            },
            integer: {
              message: 'Bilangan hari mesti dalam bentuk nombor'
            },
            greaterThan: {
              message: 'Bilangan hari mesti lebih daripada 0',
              min: 0
            }
          }
        },
        'sla_days_medium': {
          validators: {
            notEmpty: {
              message: 'Sila masukkan bilangan hari untuk MEDIUM'
            },
            integer: {
              message: 'Bilangan hari mesti dalam bentuk nombor'
            },
            greaterThan: {
              message: 'Bilangan hari mesti lebih daripada 0',
              min: 0
            }
          }
        },
        'sla_days_low': {
          validators: {
            notEmpty: {
              message: 'Sila masukkan bilangan hari untuk LOW'
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
            setTimeout(function () {
              window.location.href = '/sla-severity';
            }, 1500);
          }
        } else {
          // Show error message (defined in JS, not from controller)
          toastr.error('Ralat mencipta SLA', '', {
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
          let errorMessage = 'Pengesahan gagal:\n';
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
          toastr.error('Ralat pelayan berlaku semasa mencipta SLA. Sila cuba lagi.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 403) {
          // Forbidden
          toastr.error('Anda tidak mempunyai kebenaran untuk mencipta rekod SLA.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 401) {
          // Unauthorized
          toastr.error('Sesi anda telah tamat tempoh. Sila log masuk semula.', '', {
            timeOut: 6000,
            closeButton: true,
            progressBar: true
          });
        } else {
          // General error
          toastr.error('Ralat tidak dijangka berlaku semasa mencipta SLA. Sila cuba lagi.', '', {
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

  // Function to check for duplicate SLA (cat_id, subcat_id, sla_level)
  function checkDuplicateSla(catId, subcatId, slaLevel, callback) {
    // Validate that all fields are present and valid
    if (!catId || !subcatId || !slaLevel) {
      callback(false);
      return;
    }

    $.ajax({
      url: '/sla-severity/check-duplicate',
      type: 'POST',
      data: {
        cat_id: catId,
        subcat_id: subcatId,
        sla_level: slaLevel
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        logger.log('Duplicate check response:', response);
        callback(response.exists === true);
      },
      error: function (xhr) {
        console.error('Duplicate check error:', xhr);
        if (xhr.status === 422) {
          console.error('Validation errors:', xhr.responseJSON);
        }
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
    duplicateValidationTimer = setTimeout(function () {
      const catId = $('#add-cat-id').val();
      const subcatId = $('#add-subcat-id').val();
      const slaLevel = $('#add-sla-level').val();

      // Get error container or create it
      let errorDiv = document.getElementById('duplicate-error-container');
      if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'duplicate-error-container';
        errorDiv.className = 'mt-3';
        // Insert after the form or at the end of the offcanvas body
        const formContainer = document.getElementById('addNewSlaForm').parentNode;
        formContainer.appendChild(errorDiv);
      }

      // Only check for duplicates if ALL 3 fields have values
      if (catId && subcatId && slaLevel) {
        // All fields are valid, check for duplicates
        checkDuplicateSla(catId, subcatId, slaLevel, function (isDuplicate) {
          if (isDuplicate) {
            errorDiv.innerHTML = '<div class="alert alert-warning"><i class="bx bx-info-circle me-2"></i>SLA dengan kombinasi Kategori, Sub Kategori dan Tahap Pegawai Tugasan ini sudah wujud.</div>';
            errorDiv.style.display = 'block';
          } else {
            errorDiv.style.display = 'none';
            errorDiv.innerHTML = '';
          }
        });
      } else {
        // Clear any existing duplicate errors when not all 3 fields are filled
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '';
      }
    }, 500); // 500ms delay
  }

  // Add event listeners for duplicate checking (cat_id, subcat_id, sla_level only)
  $('#add-cat-id, #add-subcat-id, #add-sla-level').on('change', validateDuplicateOnChange);

  // Category change handler to populate subcategories
  $('#add-cat-id').on('change', function () {
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
        success: function (response) {
          if (response.success && response.data && Array.isArray(response.data)) {
            $.each(response.data, function (index, subcategory) {
              subcategorySelect.append(`<option value="${subcategory.id}">${subcategory.subcat_name}</option>`);
            });
          } else {
            toastr.warning('Tiada sub kategori dijumpai untuk kategori yang dipilih.', '', {
              timeOut: 4000,
              closeButton: true,
              progressBar: true
            });
          }
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          if (xhr.status === 404) {
            toastr.error('Kategori tidak dijumpai. Sila muat semula halaman dan cuba lagi.', '', {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            });
          } else if (xhr.status === 500) {
            toastr.error('Ralat pelayan berlaku semasa memuatkan sub kategori. Sila cuba lagi.', '', {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            });
          } else {
            toastr.error('Tidak dapat memuatkan sub kategori. Sila muat semula halaman dan cuba lagi.', '', {
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
