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
          $('#edit_sla_priority').val(data.sla_priority);
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
        success: function(data) {
          if (data && Array.isArray(data)) {
            $.each(data, function(index, subcategory) {
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

  // Category change handler for edit form to populate subcategories
  $('#edit_cat_id').on('change', function() {
    const categoryId = $(this).val();
    loadSubcategoriesForEdit(categoryId);
  });
});
