

'use strict';

// Form Validation for Edit Permission (Offcanvas style)
$(document).ready(function () {
  console.log('Edit Permission JS loaded');
  console.log('FormValidation available:', typeof FormValidation !== 'undefined');
  console.log('jQuery available:', typeof $ !== 'undefined');
  console.log('Toastr available:', typeof toastr !== 'undefined');

  // Edit Permission form
  const editPermissionForm = document.getElementById('editPermissionForm');
  let editFormValidator = null;
  let currentPermissionId = null;

  // Initialize form validation for edit form
  function initializeEditFormValidation() {
    if (editPermissionForm && typeof FormValidation !== 'undefined') {
      console.log('Initializing edit form validation');

      // Destroy existing validator if any
      if (editFormValidator) {
        editFormValidator.destroy();
      }

      editFormValidator = FormValidation.formValidation(editPermissionForm, {
        fields: {
          name: {
            validators: {
              notEmpty: {
                message: 'Sila masukkan nama permission'
              }
            }
          },
          module: {
            validators: {
              notEmpty: {
                message: 'Sila pilih modul'
              }
            }
          },
          category: {
            validators: {
              // Optional field - no validation needed
            }
          },
          description: {
            validators: {
              notEmpty: {
                message: 'Sila masukkan deskripsi permission'
              }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
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
        // Submit form data for update
        updatePermission();
      });
    }
  }

  // Function to load permission data for editing
  function loadPermissionData(permissionId) {
    console.log('Loading permission data for ID:', permissionId);
    currentPermissionId = permissionId;

    $.ajax({
      url: `/admin/permissions/${permissionId}/edit`,
      type: 'GET',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        console.log('Permission data loaded:', response);
        if (response.success) {
          populateEditForm(response.permission, response.assigned_roles);
        } else {
          showError('Ralat memuat data permission');
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading permission data:', error);
        const errorMessage = xhr.responseJSON?.message || 'Ralat memuat data permission';
        showError(errorMessage);
      }
    });
  }

  // Function to populate the edit form with permission data
  function populateEditForm(permission, assignedRoles) {
    console.log('Populating edit form with data:', permission);

    // Reset form validation first
    if (editFormValidator) {
      editFormValidator.resetForm();
    }

    // Populate basic fields using correct IDs
    $('#edit-permission-name').val(permission.name || '');
    $('#edit-permission-module').val(permission.module || '');
    $('#edit-permission-category').val(permission.category || '');
    $('#edit-permission-description').val(permission.description || '');

    // Handle role assignments if role selection exists
    if (assignedRoles && assignedRoles.length > 0) {
      $('#editPermissionForm [name="roles[]"]').each(function() {
        const roleValue = $(this).val();
        $(this).prop('checked', assignedRoles.includes(roleValue));
      });
    } else {
      // Uncheck all roles if none assigned
      $('#editPermissionForm [name="roles[]"]').prop('checked', false);
    }

    // Refresh any select2 dropdowns if they exist
    $('#editPermissionForm select').trigger('change');
  }

  // Function to update permission
  function updatePermission() {
    if (!currentPermissionId) {
      showError('ID permission tidak dijumpai');
      return;
    }

    console.log('Updating permission ID:', currentPermissionId);

    // Prepare form data with method spoofing
    var formData = $(editPermissionForm).serialize();
    formData += '&_method=PUT';

    $.ajax({
      url: `/admin/permissions/${currentPermissionId}`,
      type: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        console.log('Update success response:', response);
        if (response.success) {
          // Close offcanvas
          $('#offcanvasEditPermission').offcanvas('hide');

          // Clear form
          editPermissionForm.reset();

          // Reset form validation
          if (editFormValidator) {
            editFormValidator.resetForm();
          }

          // Show success message
          showSuccess('Permission berjaya dikemaskini');

          // Refresh the datatable
          refreshDataTable();
        } else {
          showError(response.message || 'Ralat berlaku semasa mengemaskini permission');
        }
      },
      error: function(xhr, status, error) {
        console.log('Update error response:', xhr.responseJSON);
        handleUpdateErrors(xhr);
      }
    });
  }

  // Function to handle update errors
  function handleUpdateErrors(xhr) {
    // Handle validation errors (422 status)
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
      const errors = xhr.responseJSON.errors;
      console.log('Validation errors:', errors);

      // Handle name field specifically for uniqueness errors
      if (errors.name && errors.name.length > 0) {
        const nameError = errors.name[0];
        console.log('Name validation error:', nameError);

        // Check if it's a uniqueness error
        if (nameError.includes('already') || nameError.includes('unique') || nameError.includes('exists') ||
            nameError.includes('sudah wujud') || nameError.includes('sudah ada')) {
          showError('Nama permission ini sudah wujud. Sila gunakan nama lain.', 'Nama Sudah Wujud!');
        } else {
          showError(nameError, 'Ralat Nama Permission');
        }

        // Update form validation for name field
        if (editFormValidator) {
          editFormValidator.updateFieldStatus('name', 'Invalid', 'notEmpty');
          editFormValidator.updateMessage('name', 'notEmpty', nameError);
        }
      }

      // Display validation errors on other form fields
      for (const field in errors) {
        if (field !== 'name' && editFormValidator) {
          const fieldElement = editPermissionForm.querySelector(`[name="${field}"]`);
          if (fieldElement) {
            console.log(`Setting error for field: ${field}`, errors[field][0]);

            editFormValidator.updateFieldStatus(field, 'Invalid', 'notEmpty');
            editFormValidator.updateMessage(field, 'notEmpty', errors[field][0]);
          }
        }
      }
    } else {
      // Handle other errors
      const errorMessage = xhr.responseJSON?.message || 'Ralat berlaku semasa mengemaskini permission';
      showError(errorMessage);
    }
  }

  // Event handlers for edit buttons
  $(document).on('click', '.edit-permission', function(e) {
    e.preventDefault();
    const permissionId = $(this).data('id');
    console.log('Edit button clicked for permission ID:', permissionId);

    if (permissionId) {
      // Initialize form validation if not already done
      if (!editFormValidator) {
        console.log('Initializing form validation...');
        initializeEditFormValidation();
      }

      // Load permission data
      loadPermissionData(permissionId);

      // Show the edit offcanvas
      $('#offcanvasEditPermission').offcanvas('show');
    } else {
      showError('ID permission tidak dijumpai');
    }
  });

  // Form submit handler (fallback for manual form submission)
  $(document).on('submit', '#editPermissionForm', function(e) {
    e.preventDefault();
    console.log('Form submitted manually');

    // Check if we have a current permission ID
    if (currentPermissionId) {
      updatePermission();
    } else {
      showError('ID permission tidak dijumpai');
    }
  });

  // Reset form when offcanvas is closed
  $('#offcanvasEditPermission').on('hidden.bs.offcanvas', function () {
    console.log('Edit form container closed, resetting form');

    // Clear form
    if (editPermissionForm) {
      editPermissionForm.reset();
    }

    // Reset form validation
    if (editFormValidator) {
      editFormValidator.resetForm();
    }

    // Clear any validation messages and classes
    $(editPermissionForm).find('.is-invalid').removeClass('is-invalid');
    $(editPermissionForm).find('.invalid-feedback').remove();
    $(editPermissionForm).find('.fv-plugins-message-container').empty();

    // Reset current permission ID
    currentPermissionId = null;
  });

  // Utility functions
  function showSuccess(message, title = 'Berjaya!') {
    if (typeof toastr !== 'undefined') {
      toastr.success(message, title, {
        closeButton: true,
        tapToDismiss: false,
        progressBar: true,
        rtl: false
      });
    } else {
      alert(message);
    }
  }

  function showError(message, title = 'Ralat!') {
    if (typeof toastr !== 'undefined') {
      toastr.error(message, title, {
        closeButton: true,
        tapToDismiss: false,
        progressBar: true,
        rtl: false,
        timeOut: 5000
      });
    } else {
      alert('Error: ' + message);
    }
  }

  function refreshDataTable() {
    if ($('.datatables-permissions').length && $('.datatables-permissions').DataTable()) {
      $('.datatables-permissions').DataTable().ajax.reload();
    }
  }

  // Initialize form validation on document ready if form exists
  if (editPermissionForm) {
    initializeEditFormValidation();
  }

  // Global test functions for debugging (can be called from browser console)
  window.testEditButton = function(permissionId) {
    if (permissionId) {
      console.log('Testing edit for permission ID:', permissionId);
      loadPermissionData(permissionId);
      $('#offcanvasEditPermission').offcanvas('show');
    } else {
      console.log('Available edit buttons:');
      $('.edit-permission').each(function(index) {
        console.log(`Button ${index + 1}: data-id=${$(this).data('id')}`);
      });
    }
  };

  window.testEditButtonEvents = function() {
    const editButtons = $('.edit-permission');
    console.log('Total edit buttons:', editButtons.length);

    editButtons.each(function(index) {
      const button = $(this);
      const events = $._data(this, 'events') || {};
      console.log(`Button ${index + 1}:`, {
        'data-id': button.data('id'),
        'class': button.attr('class'),
        'events': Object.keys(events)
      });
    });
  };

  console.log('Edit Permission JS initialization completed');
});
