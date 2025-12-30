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
      placeholder: 'Pilih Kategori',
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

      // Reset severity day fields
      $('#edit_sla_days_high, #edit_sla_days_medium, #edit_sla_days_low').val('');

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
      toastr.error('ID SLA tidak dijumpai. Sila cuba lagi.');
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
          $('#edit_sla_id').val(data.id);

          // Populate severity-specific days (expecting data to have days per severity)
          $('#edit_sla_days_high').val(data.sla_days_high || '');
          $('#edit_sla_days_medium').val(data.sla_days_medium || '');
          $('#edit_sla_days_low').val(data.sla_days_low || '');

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
          toastr.error('Data SLA tidak sah diterima.');
        }
      },
      error: function (xhr) {
        console.error('Error fetching SLA data:', xhr);
        if (xhr.status === 404) {
          toastr.error('Rekod SLA tidak dijumpai. Ia mungkin telah dipadam.');
        } else if (xhr.status === 403) {
          toastr.error('Anda tidak mempunyai kebenaran untuk mengedit rekod SLA ini.');
        } else if (xhr.status === 500) {
          toastr.error('Ralat pelayan berlaku semasa mengambil data SLA. Sila cuba lagi.');
        } else {
          toastr.error('Ralat berlaku semasa mengambil data SLA. Sila cuba lagi.');
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
      toastr.error('ID SLA tiada. Sila cuba lagi.');
      return;
    }

    // Disable submit button to prevent multiple submissions
    const submitBtn = $(editSlaForm).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).text('Mengemaskini...');

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

          // Show success message (defined in JS, not from controller)
          toastr.success('SLA berjaya dikemaskini', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true
          });
        } else {
          // Show error message (defined in JS, not from controller)
          toastr.error('Ralat mengemaskini SLA', '', {
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
          let errorMessage = 'Pengesahan gagal:\n';
          for (const field in errors) {
            errorMessage += '• ' + errors[field][0] + '\n';
          }
          toastr.error(errorMessage, '', {
            timeOut: 7000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 404) {
          toastr.error('Rekod SLA tidak dijumpai. Ia mungkin telah dipadam.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 403) {
          toastr.error('Anda tidak mempunyai kebenaran untuk mengemaskini rekod SLA ini.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else if (xhr.status === 500) {
          toastr.error('Ralat pelayan berlaku semasa mengemaskini SLA. Sila cuba lagi.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
          });
        } else {
          toastr.error('Ralat berlaku semasa mengemaskini SLA. Sila cuba lagi.', '', {
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
            toastr.warning('Tiada sub kategori dijumpai untuk kategori yang dipilih.', '', {
              timeOut: 4000,
              closeButton: true,
              progressBar: true
            });
          }
        },
        error: function(xhr) {
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
  }

  // Function to check for duplicate SLA in edit form (cat_id, subcat_id, sla_level)
  function checkDuplicateSlaForEdit(catId, subcatId, slaLevel, excludeId, callback) {
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
        sla_level: slaLevel,
        id: excludeId
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        console.log('Edit duplicate check response:', response);
        callback(response.exists === true);
      },
      error: function(xhr) {
        console.error('Edit duplicate check error:', xhr);
        if (xhr.status === 422) {
          console.error('Validation errors:', xhr.responseJSON);
        }
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
      const excludeId = $('#edit_sla_id').val();

      // Get error container or create it
      let errorDiv = document.getElementById('duplicate-error-container-edit');
      if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'duplicate-error-container-edit';
        errorDiv.className = 'mt-3';
        // Insert after the form or at the end of the offcanvas body
        const formContainer = document.getElementById('editSlaForm').parentNode;
        formContainer.appendChild(errorDiv);
      }

      // Only check for duplicates if ALL 3 fields have values
      if (catId && subcatId && slaLevel) {
        // All fields are valid, check for duplicates
        checkDuplicateSlaForEdit(catId, subcatId, slaLevel, excludeId, function(isDuplicate) {
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

  // Add event listeners for duplicate checking in edit form (cat_id, subcat_id, sla_level only)
  $('#edit_cat_id, #edit_subcat_id, #edit_sla_level').on('change', validateDuplicateOnChangeEdit);

  // Category change handler for edit form to populate subcategories
  $('#edit_cat_id').on('change', function() {
    const categoryId = $(this).val();
    loadSubcategoriesForEdit(categoryId);
  });
});
