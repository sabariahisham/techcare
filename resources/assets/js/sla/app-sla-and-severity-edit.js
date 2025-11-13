/**
 * Page SLA and Severity Add/Create/Edit
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
$(document).ready(function() {
  // Function to ensure all edit buttons are properly restored
  function restoreEditButtons() {
    $('.edit-record').each(function() {
      var $btn = $(this);
      $btn.prop('disabled', false);
      // Only restore if the button doesn't have the correct icon
      if (!$btn.find('i.bx-edit').length) {
        $btn.html('<i class="bx bx-edit"></i>');
      }
    });
  }

  // Form validation setup for Edit form
  const editSlaForm = document.getElementById('editSlaForm');
  let editFv;

  if (editSlaForm) {
    editFv = FormValidation.formValidation(editSlaForm, {
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
      // Form is valid, proceed with AJAX submission for edit
      submitEditSlaForm();
    });
  }

  // Reset edit form when offcanvas is hidden
  $('#offcanvasEditSla').on('hidden.bs.offcanvas', function () {
    const editForm = document.getElementById('editSlaForm');
    if (editForm) {
      editForm.reset();

      // Reset FormValidation for edit form
      if (typeof editFv !== 'undefined' && editFv) {
        editFv.resetForm(true);
      }

      // Reset select2 dropdowns in edit form
      $('#edit_cat_id, #edit_subcat_id').val(null).trigger('change');

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();
      $('.duplicate-error').remove();
      $('.days-validation-error').remove();

      // Clear duplicate error container
      const errorDiv = document.getElementById('duplicate-error-container-edit');
      if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '';
      }

      // Restore all edit buttons to proper state
      restoreEditButtons();
    }
  });

  // Edit Record click handler - using document delegation for dynamically loaded content
  $(document).on('click', '.edit-record', function (e) {
    e.preventDefault();
    console.log('Edit button clicked'); // Debug log
    var id = $(this).data('id');
    var $clickedButton = $(this);
    var originalHtml = $clickedButton.html();
    console.log('SLA ID:', id); // Debug log

    if (!id) {
      toastr.error('SLA ID not found. Please try again.');
      return;
    }

    // Show loading state
    $clickedButton.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    // Fetch SLA data and populate form
    $.ajax({
      type: 'GET',
      url: `/sla-severity/${id}/edit`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        if (data && data.id) {
          // Populate the edit form with data
          $('#edit_sla_level').val(data.sla_level);
          $('#edit_sla_severity').val(data.sla_severity);
          $('#edit_sla_days').val(data.sla_days);
          $('#edit_sla_id').val(data.id);

          // Set category first
          $('#edit_cat_id').val(data.cat_id).trigger('change');

          // Load subcategories for the selected category, then set the subcategory
          if (data.cat_id) {
            loadSubcategoriesForEdit(data.cat_id, data.subcat_id);
          }

          // Show the edit offcanvas
          var editOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEditSla'));
          editOffcanvas.show();
        } else {
          toastr.error('Invalid SLA data received.');
        }
      },
      error: function (xhr) {
        console.error('Error fetching SLA data:', xhr);
        if (xhr.status === 404) {
          toastr.error('SLA record not found. It may have been deleted.');
        } else if (xhr.status === 403) {
          toastr.error('You do not have permission to edit this SLA record.');
        } else if (xhr.status === 500) {
          toastr.error('Server error occurred while fetching SLA data. Please try again.');
        } else {
          toastr.error('An error occurred while fetching SLA data. Please try again.');
        }
      },
      complete: function() {
        // Restore button state for the clicked button only
        $clickedButton.prop('disabled', false).html(originalHtml);
      }
    });
  });

  // Edit SLA form submission function
  function submitEditSlaForm() {
    const formData = new FormData(editSlaForm);
    const id = $('#edit_sla_id').val();

    if (!id) {
      toastr.error('SLA ID is missing. Please try again.');
      return;
    }

    // Disable submit button to prevent multiple submissions
    const submitBtn = $(editSlaForm).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).text('Updating...');

    $.ajax({
      type: 'POST',
      url: `/sla-severity/${id}`,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      success: function (response) {
        if (response.success) {
          // Hide offcanvas
          var editOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditSla'));
          if (editOffcanvas) {
            editOffcanvas.hide();
          }

          // Reset FormValidation for edit form
          if (editFv) {
            editFv.resetForm(true);
          }

          // Reload DataTable if it exists on the page
          if (typeof window.dt_sla !== 'undefined' && window.dt_sla) {
            window.dt_sla.ajax.reload(function() {
              // Ensure buttons are restored after reload
              setTimeout(restoreEditButtons, 100);
            }, false); // false = keep current page
          }

          // Show success message
          toastr.success(response.message || 'SLA berjaya dikemaskini', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true
          });
        } else {
          toastr.error(response.message || 'Error updating SLA', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        }
      },
      error: function (xhr) {
        console.error('Error updating SLA:', xhr);
        if (xhr.status === 422) {
          // Validation errors
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
        } else if (xhr.status === 404) {
          toastr.error('SLA record not found. It may have been deleted.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 403) {
          toastr.error('You do not have permission to update this SLA record.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 500) {
          toastr.error('Server error occurred while updating the SLA. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else {
          toastr.error('An error occurred while updating the SLA. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        }
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  }

  // Helper function to load subcategories for edit form
  function loadSubcategoriesForEdit(categoryId, selectedSubcategoryId = null) {
    const subcategorySelect = $('#edit_subcat_id');

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
              const selected = selectedSubcategoryId == subcategory.id ? 'selected' : '';
              subcategorySelect.append(`<option value="${subcategory.id}" ${selected}>${subcategory.subcat_name}</option>`);
            });

            // Trigger change to update select2 if used
            subcategorySelect.trigger('change');
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
  }

  // Function to check for duplicate SLA in edit form (all 5 fields: cat_id, subcat_id, sla_level, sla_severity, sla_days)
  function checkDuplicateSlaForEdit(catId, subcatId, slaLevel, slaSeverity, slaDays, excludeId, callback) {
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
        sla_days: slaDays,
        id: excludeId
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

  // Debounce timer for duplicate validation in edit form
  let duplicateValidationTimerEdit;

  // Check for duplicates when key fields change in edit form (all 5 fields: cat_id, subcat_id, sla_level, sla_severity, sla_days)
  function validateDuplicateOnChangeEdit() {
    // Clear previous timer
    if (duplicateValidationTimerEdit) {
      clearTimeout(duplicateValidationTimerEdit);
    }

    // Set a small delay to avoid checking on every keystroke
    duplicateValidationTimerEdit = setTimeout(function() {
      const catId = $('#edit_cat_id').val();
      const subcatId = $('#edit_subcat_id').val();
      const slaLevel = $('#edit_sla_level').val();
      const slaSeverity = $('#edit_sla_severity').val();
      const slaDays = $('#edit_sla_days').val();
      const excludeId = $('#edit_sla_id').val();

      // Only check for duplicates if ALL 5 fields have values and sla_days is a valid positive number
      if (catId && subcatId && slaLevel && slaSeverity && slaDays && parseInt(slaDays) > 0) {
        checkDuplicateSlaForEdit(catId, subcatId, slaLevel, slaSeverity, slaDays, excludeId, function(isDuplicate) {
          // Show error in a separate div (create if doesn't exist)
          let errorDiv = document.getElementById('duplicate-error-container-edit');
          if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'duplicate-error-container-edit';
            errorDiv.className = 'mt-3';
            // Insert after the form or at the end of the offcanvas body
            const formContainer = document.getElementById('editSlaForm').parentNode;
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
        const errorDiv = document.getElementById('duplicate-error-container-edit');
        if (errorDiv) {
          errorDiv.style.display = 'none';
          errorDiv.innerHTML = '';
        }
      }
    }, 500); // 500ms delay
  }

  // Add event listeners for duplicate checking in edit form (all 5 fields)
  $('#edit_cat_id, #edit_subcat_id, #edit_sla_level, #edit_sla_severity, #edit_sla_days').on('change input', validateDuplicateOnChangeEdit);

  // Additional validation specifically for edit sla_days field
  $('#edit_sla_days').on('input blur', function() {
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

  // Category change handler for edit form to populate subcategories
  $('#edit_cat_id').on('change', function() {
    const categoryId = $(this).val();
    loadSubcategoriesForEdit(categoryId);
  });
});
