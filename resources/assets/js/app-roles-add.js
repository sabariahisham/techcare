/**
 * Role Add and Edit functionality
 */

'use strict';

(function () {
  // Initialize select all permissions functionality
  initializeSelectAllPermissions();

  // Initialize user management
  initializeUserManagement();

  // Form validation setup for Add Role form
  const addRoleForm = document.getElementById('addNewRoleForm');
  let fvAdd;

  if (addRoleForm) {
    fvAdd = FormValidation.formValidation(addRoleForm, {
      fields: {
        'name': {
          validators: {
            notEmpty: {
              message: 'Please enter role name'
            },
            stringLength: {
              min: 2,
              max: 255,
              message: 'Role name must be between 2 and 255 characters'
            }
          }
        },
        'permissions[]': {
          validators: {
            choice: {
              min: 1,
              message: 'Please select at least one permission'
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
      submitAddRoleForm();
    });
  }

  // Add direct form submit handler as backup to form validation
  $(document).on('click', 'button[form="addNewRoleForm"]', function(e) {
    e.preventDefault();

    // Basic validation - just check if name is filled
    const roleName = $('#add-role-name').val();
    if (!roleName || roleName.trim() === '') {
      toastr.error('Please enter role name', '', {
        timeOut: 3000,
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        "toastClass": "toast ngx-toastr bg-danger text-white"
      });
      return;
    }

    submitAddRoleForm();
  });

  // Form validation setup for Edit Role form
  const editRoleForm = document.getElementById('editRoleForm');
  let fvEdit;

  if (editRoleForm) {
    fvEdit = FormValidation.formValidation(editRoleForm, {
      fields: {
        'name': {
          validators: {
            notEmpty: {
              message: 'Please enter role name'
            },
            stringLength: {
              min: 2,
              max: 255,
              message: 'Role name must be between 2 and 255 characters'
            }
          }
        },
        'permissions[]': {
          validators: {
            choice: {
              min: 1,
              message: 'Please select at least one permission'
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
      submitEditRoleForm();
    });
  }

  // Add direct form submit handler for edit form as backup
  $(document).on('click', 'button[form="editRoleForm"]', function(e) {
    e.preventDefault();

    // Basic validation - just check if name is filled
    const roleName = $('#edit-role-name').val();
    if (!roleName || roleName.trim() === '') {
      toastr.error('Please enter role name', '', {
        timeOut: 3000,
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        "toastClass": "toast ngx-toastr bg-danger text-white"
      });
      return;
    }

    submitEditRoleForm();
  });

  // Add event handler for when modal is about to be shown to reset form
  $('#modalAddRole').on('show.bs.modal', function () {
    // Clear and reset the form completely
    if (addRoleForm) {
      addRoleForm.reset();

      // Reset FormValidation
      if (fvAdd) {
        fvAdd.resetForm(true);
      }

      // Uncheck all permission checkboxes
      $('.add-permission').prop('checked', false);
      $('#selectAllPermissions').prop('checked', false).prop('indeterminate', false);

      // Clear users sections if exists
      const addRoleUsers = $('#addRoleUsers');
      if (addRoleUsers.length) {
        addRoleUsers.html('<p class="text-muted">No users selected for this role</p>');
      }
      $('#add-role-users').val(null).trigger('change');

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();
      $('.fv-plugins-message-container').empty();

      // Reset form fields explicitly
      $('#add-role-name').val('');
      $('#add-role-description').val('');

      // Reset submission flag
      isAddSubmitting = false;
    }
  });

  // Add click handler for edit button in DataTable
  $(document).on('click', '.edit-role', function() {
    const roleId = $(this).data('id');
    loadRoleData(roleId);
  });

  // Load role data for editing
  function loadRoleData(roleId) {
    $.ajax({
      type: 'GET',
      url: `/admin/roles/${roleId}/edit`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          populateEditForm(response.role, response.permissions);
          $('#modalEditRole').modal('show');
        } else {
          toastr.error(response.message || 'Error loading role data', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Error loading role data';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right',
          "toastClass": "toast ngx-toastr bg-danger text-white"
        });
      }
    });
  }

  // Populate edit form with role data
  function populateEditForm(role, permissions) {
    // Set role ID
    editRoleForm.setAttribute('data-role-id', role.id);

    // Populate basic fields - ensure elements exist before setting values
    const nameField = document.getElementById('edit-role-name');
    const descField = document.getElementById('edit-role-description');

    if (nameField) nameField.value = role.name || '';
    if (descField) descField.value = role.description || '';

    // Clear all permission checkboxes first
    $('.edit-permission').prop('checked', false);
    $('#editSelectAllPermissions').prop('checked', false).prop('indeterminate', false);

    // Check permissions assigned to this role
    if (permissions && permissions.length > 0) {
      permissions.forEach(function(permissionName) {
        // Use the same ID format as in the HTML template
        const checkboxId = 'edit_permission_' + permissionName.replace(/[^a-zA-Z0-9]/g, '_');
        const checkbox = $('#' + checkboxId);

        if (checkbox.length > 0) {
          checkbox.prop('checked', true);
        } else {
          console.warn('Permission checkbox not found:', checkboxId, 'for permission:', permissionName);
        }
      });

      // Update select all checkbox state
      updateSelectAllState();
    }

    // Load users assigned to this role
    loadRoleUsers(role.id);
  }

  // Update select all permissions checkbox state
  function updateSelectAllState() {
    const totalPermissions = $('.edit-permission').length;
    const checkedPermissions = $('.edit-permission:checked').length;
    const selectAllCheckbox = $('#editSelectAllPermissions');

    if (checkedPermissions === 0) {
      selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
    } else if (checkedPermissions === totalPermissions) {
      selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
    } else {
      selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
    }
  }

  // Load users assigned to the role
  function loadRoleUsers(roleId) {
    $.ajax({
      type: 'GET',
      url: `/admin/roles/${roleId}`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        console.log('Role users response:', response);
        if (response.success && response.role) {
          // Try to get users from different possible locations in the response
          let users = response.users || response.role.users || [];
          console.log('Users data:', users);
          populateUsersSection(users);
        } else {
          console.error('Invalid response structure:', response);
          $('#editRoleUsers').html('<p class="text-muted">No users found for this role</p>');
        }
      },
      error: function (xhr) {
        console.error('Error loading role users:', xhr);
        let errorMessage = 'Error loading users';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (xhr.statusText) {
          errorMessage = `Error: ${xhr.statusText} (${xhr.status})`;
        }
        $('#editRoleUsers').html(`<p class="text-danger">${errorMessage}</p>`);
      }
    });
  }

  // Populate users section
  function populateUsersSection(users) {
    const usersContainer = $('#editRoleUsers');

    console.log('Populating users section with data:', users);

    if (!users || users.length === 0) {
      usersContainer.html('<p class="text-muted">No users assigned to this role</p>');
      return;
    }

    let usersList = '<div class="list-group list-group-flush">';
    users.forEach(function(user, index) {
      console.log(`Processing user ${index}:`, user);

      // Handle both field names for backward compatibility
      const userName = user.nama || user.name || 'Unknown User';
      const userEmail = user.email || '';
      const userId = user.id;

      if (!userId) {
        console.warn('User missing ID:', user);
        return; // Skip users without IDs
      }

      const initials = userName.substring(0, 2).toUpperCase();
      usersList += `
        <div class="list-group-item d-flex align-items-center px-0">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">${userName}</h6>
            <small class="text-muted">${userEmail}</small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-user-role"
                  data-user-id="${userId}"
                  data-role-id="${editRoleForm.getAttribute('data-role-id')}"
                  title="Remove from role">
            <i class="bx bx-x"></i>
          </button>
        </div>
      `;
    });
    usersList += '</div>';

    usersContainer.html(usersList);
  }

  // Handle remove user from role
  $(document).on('click', '.remove-user-role', function() {
    const userId = $(this).data('user-id');
    const roleId = $(this).data('role-id');
    const button = $(this);

    Swal.fire({
      title: 'Remove User?',
      text: "Are you sure you want to remove this user from the role?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, remove',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        removeUserFromRole(userId, roleId, button);
      }
    });
  });

  // Remove user from role
  function removeUserFromRole(userId, roleId, button) {
    $.ajax({
      type: 'DELETE',
      url: `/admin/roles/${roleId}/users/${userId}`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          button.closest('.list-group-item').fadeOut(300, function() {
            $(this).remove();
            // Check if no users left
            if ($('#editRoleUsers .list-group-item').length === 0) {
              $('#editRoleUsers').html('<p class="text-muted">No users assigned to this role</p>');
            }
          });

          toastr.success(response.message || 'User removed from role successfully', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-success text-white"
          });

          // Reload DataTable if it exists
          if (typeof window.dt_role !== 'undefined' && window.dt_role) {
            window.dt_role.ajax.reload();
          } else if ($('.datatables-roles').length) {
            $('.datatables-roles').DataTable().ajax.reload();
          }
        } else {
          toastr.error(response.message || 'Error removing user from role', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Error removing user from role';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right',
          "toastClass": "toast ngx-toastr bg-danger text-white"
        });
      }
    });
  }

  // Edit Role form submission function
  let isEditSubmitting = false;

  function submitEditRoleForm() {
    // Prevent multiple submissions
    if (isEditSubmitting) {
      return;
    }

    const roleId = editRoleForm.getAttribute('data-role-id');
    if (!roleId) {
      toastr.error('Role ID not found', '', {
        timeOut: 5000,
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        "toastClass": "toast ngx-toastr bg-danger text-white"
      });
      return;
    }

    isEditSubmitting = true;
    const formData = new FormData(editRoleForm);

    $.ajax({
      type: 'PUT',
      url: `/admin/roles/${roleId}`,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Hide modal
          var editModal = bootstrap.Modal.getInstance(document.getElementById('modalEditRole'));
          editModal.hide();

          // Reset form
          editRoleForm.reset();

          // Reset FormValidation
          if (fvEdit) {
            fvEdit.resetForm(true);
          }

          // Reload DataTable if it exists on the page
          if (typeof window.dt_role !== 'undefined' && window.dt_role) {
            window.dt_role.ajax.reload();
          } else if ($('.datatables-roles').length) {
            $('.datatables-roles').DataTable().ajax.reload();
          }

          // Show success message
          toastr.success(response.message || 'Role updated successfully', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-success text-white",
            "closeClass": "toast-close-button",
            "titleClass": "toast-title",
            "messageClass": "toast-message",
            "progressClass": "toast-progress"
          });

          // Trigger custom event to refresh statistics
          $(document).trigger('roleUpdated');
        } else {
          // Show error message
          toastr.error(response.message || 'Error updating role', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }

        // Reset submission flag
        isEditSubmitting = false;
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
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 500) {
          // Server error
          toastr.error('Server error occurred while updating the role. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 403) {
          // Forbidden
          toastr.error('You do not have permission to update roles.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 401) {
          // Unauthorized
          toastr.error('Your session has expired. Please login again.', '', {
            timeOut: 6000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else {
          // General error
          toastr.error('An unexpected error occurred while updating the role. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }

        // Reset submission flag
        isEditSubmitting = false;
      }
    });
  }

  // Add Role form submission function
  let isAddSubmitting = false;

  function submitAddRoleForm() {
    // Prevent multiple submissions
    if (isAddSubmitting) {
      return;
    }

    isAddSubmitting = true;
    const formData = new FormData(addRoleForm);

    $.ajax({
      type: 'POST',
      url: '/admin/roles',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Hide modal
          var addModal = bootstrap.Modal.getInstance(document.getElementById('modalAddRole'));
          addModal.hide();

          // Reset form
          addRoleForm.reset();

          // Reset FormValidation
          if (fvAdd) {
            fvAdd.resetForm(true);
          }

          // Reload DataTable if it exists on the page
          if (typeof window.dt_role !== 'undefined' && window.dt_role) {
            window.dt_role.ajax.reload();
          } else if ($('.datatables-roles').length) {
            $('.datatables-roles').DataTable().ajax.reload();
          }

          // Show success message
          toastr.success(response.message || 'Role created successfully', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-success text-white",
            "closeClass": "toast-close-button",
            "titleClass": "toast-title",
            "messageClass": "toast-message",
            "progressClass": "toast-progress"
          });

          // Trigger custom event to refresh statistics
          $(document).trigger('roleAdded');
        } else {
          // Show error message
          toastr.error(response.message || 'Error creating role', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }

        // Reset submission flag
        isAddSubmitting = false;
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
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 500) {
          // Server error
          toastr.error('Server error occurred while creating the role. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 403) {
          // Forbidden
          toastr.error('You do not have permission to create roles.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else if (xhr.status === 401) {
          // Unauthorized
          toastr.error('Your session has expired. Please login again.', '', {
            timeOut: 6000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        } else {
          // General error
          toastr.error('An unexpected error occurred while creating the role. Please try again.', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }

        // Reset submission flag
        isAddSubmitting = false;
      }
    });
  }

  // Reset add form when modal is hidden
  $('#modalAddRole').on('hidden.bs.modal', function () {
    if (addRoleForm) {
      // Reset the form completely
      addRoleForm.reset();

      // Reset FormValidation
      if (typeof fvAdd !== 'undefined') {
        fvAdd.resetForm(true);
      }

      // Uncheck all permission checkboxes
      $('.add-permission').prop('checked', false);
      $('#selectAllPermissions').prop('checked', false).prop('indeterminate', false);

      // Clear users sections
      $('#addRoleUsers').html('<p class="text-muted">No users selected for this role</p>');
      $('#add-role-users').val(null).trigger('change');

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();
      $('.fv-plugins-message-container').empty();

      // Reset form fields explicitly
      $('#add-role-name').val('');
      $('#add-role-description').val('');

      // Reset submission flag
      isAddSubmitting = false;
    }
  });

  // Reset edit form when modal is hidden
  $('#modalEditRole').on('hidden.bs.modal', function () {
    if (editRoleForm) {
      // Reset the form completely
      editRoleForm.reset();
      editRoleForm.removeAttribute('data-role-id');

      // Reset FormValidation
      if (typeof fvEdit !== 'undefined') {
        fvEdit.resetForm(true);
      }

      // Uncheck all permission checkboxes
      $('.edit-permission').prop('checked', false);
      $('#editSelectAllPermissions').prop('checked', false).prop('indeterminate', false);

      // Clear users sections
      $('#editRoleUsers').html('<p class="text-muted">No users assigned to this role</p>');
      $('#edit-role-users').val(null).trigger('change');

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();
      $('.fv-plugins-message-container').empty();

      // Reset form fields explicitly
      $('#edit-role-name').val('');
      $('#edit-role-description').val('');

      // Reset submission flag
      isEditSubmitting = false;
    }
  });

  // Select All Permissions functionality
  function initializeSelectAllPermissions() {
    // Handle select all permissions for add form
    $(document).on('change', '#selectAllPermissions', function() {
      const isChecked = $(this).is(':checked');
      $('.add-permission').prop('checked', isChecked);
    });

    // Handle individual permission changes for add form
    $(document).on('change', '.add-permission', function() {
      updateSelectAllStateAdd();
    });

    // Handle select all permissions for edit form
    $(document).on('change', '#editSelectAllPermissions', function() {
      const isChecked = $(this).is(':checked');
      $('.edit-permission').prop('checked', isChecked);
    });

    // Handle individual permission changes for edit form
    $(document).on('change', '.edit-permission', function() {
      updateSelectAllState();
    });
  }

  // Update select all permissions checkbox state for add form
  function updateSelectAllStateAdd() {
    const totalPermissions = $('.add-permission').length;
    const checkedPermissions = $('.add-permission:checked').length;
    const selectAllCheckbox = $('#selectAllPermissions');

    if (checkedPermissions === 0) {
      selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
    } else if (checkedPermissions === totalPermissions) {
      selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
    } else {
      selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
    }
  }

  // User Management functionality for add and edit forms
  function initializeUserManagement() {
    // Initialize Select2 for user selection in add form
    $('#add-role-users').select2({
      dropdownParent: $('#modalAddRole'),
      placeholder: 'Select users to add to this role...',
      allowClear: true,
      multiple: true,
      ajax: {
        url: '/admin/users/search',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term,
            format: 'select2',
            exclude_test: true
          };
        },
        processResults: function (data) {
          // Filter out test users on frontend as well
          if (data.results) {
            data.results = data.results.filter(function(user) {
              const userName = user.text.toLowerCase();
              return !userName.includes('test user') && !userName.includes('testuser');
            });
          }
          return data;
        },
        cache: true
      },
      minimumInputLength: 0,
      templateResult: function(user) {
        if (user.loading) {
          return user.text;
        }

        if (!user.id) {
          return user.text;
        }

        // Extract name from text (format: "nama (email)")
        const displayName = user.text.split(' (')[0];
        const initials = displayName.substring(0, 2).toUpperCase();

        var $container = $(
          '<div class="d-flex align-items-center">' +
            '<div class="avatar avatar-sm me-3">' +
              '<span class="avatar-initial rounded-circle bg-label-primary">' + initials + '</span>' +
            '</div>' +
            '<div>' +
              '<div class="fw-medium">' + displayName + '</div>' +
              '<small class="text-muted">' + (user.email || '') + '</small>' +
            '</div>' +
          '</div>'
        );

        return $container;
      },
      templateSelection: function(user) {
        if (user.id) {
          return user.text.split(' (')[0] || user.text;
        }
        return user.text;
      }
    });

    // Initialize Select2 for user selection in edit form
    $('#edit-role-users').select2({
      dropdownParent: $('#modalEditRole'),
      placeholder: 'Select users to add to this role...',
      allowClear: true,
      multiple: true,
      ajax: {
        url: '/admin/users/search',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          const roleId = editRoleForm.getAttribute('data-role-id');
          return {
            q: params.term,
            format: 'select2',
            exclude_role: roleId,
            exclude_test: true
          };
        },
        processResults: function (data) {
          // Filter out test users on frontend as well
          if (data.results) {
            data.results = data.results.filter(function(user) {
              const userName = user.text.toLowerCase();
              return !userName.includes('test user') && !userName.includes('testuser');
            });
          }
          return data;
        },
        cache: false // Don't cache for edit form as it changes based on role
      },
      minimumInputLength: 0,
      templateResult: function(user) {
        if (user.loading) {
          return user.text;
        }

        if (!user.id) {
          return user.text;
        }

        // Extract name from text (format: "nama (email)")
        const displayName = user.text.split(' (')[0];
        const initials = displayName.substring(0, 2).toUpperCase();

        var $container = $(
          '<div class="d-flex align-items-center">' +
            '<div class="avatar avatar-sm me-3">' +
              '<span class="avatar-initial rounded-circle bg-label-primary">' + initials + '</span>' +
            '</div>' +
            '<div>' +
              '<div class="fw-medium">' + displayName + '</div>' +
              '<small class="text-muted">' + (user.email || '') + '</small>' +
            '</div>' +
          '</div>'
        );

        return $container;
      },
      templateSelection: function(user) {
        if (user.id) {
          return user.text.split(' (')[0] || user.text;
        }
        return user.text;
      }
    });

    // Handle Add Selected Users button
    $('#addSelectedUsers').on('click', function() {
      const selectedUsers = $('#edit-role-users').select2('data');
      const roleId = editRoleForm.getAttribute('data-role-id');

      if (selectedUsers.length === 0) {
        toastr.warning('Please select users to add to the role', '', {
          timeOut: 3000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right',
          "toastClass": "toast ngx-toastr bg-warning text-white"
        });
        return;
      }

      // Add each selected user to the role
      selectedUsers.forEach(function(userData) {
        // Extract nama from the text format "nama (email)"
        const userName = userData.text.split(' (')[0];
        const userEmail = userData.email || userData.text.split(' (')[1]?.replace(')', '') || '';
        addUserToRole(userData.id, userName, userEmail, roleId);
      });

      // Clear the selection
      $('#edit-role-users').val(null).trigger('change');
    });

    // Handle Add Selected Users button for add role form
    $('#addSelectedUsersToNew').on('click', function() {
      const selectedUsers = $('#add-role-users').select2('data');

      if (selectedUsers.length === 0) {
        toastr.warning('Please select users to add to the role', '', {
          timeOut: 3000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right',
          "toastClass": "toast ngx-toastr bg-warning text-white"
        });
        return;
      }

      // Add each selected user to temporary list for new role
      selectedUsers.forEach(function(userData) {
        // Extract nama from the text format "nama (email)"
        const userName = userData.text.split(' (')[0];
        const userEmail = userData.email || userData.text.split(' (')[1]?.replace(')', '') || '';
        addUserToNewRoleList(userData.id, userName, userEmail);
      });

      // Clear the selection
      $('#add-role-users').val(null).trigger('change');
    });
  }

  // Add user to temporary list for new role
  function addUserToNewRoleList(userId, userName, userEmail) {
    const initials = userName.substring(0, 2).toUpperCase();
    const userHtml = `
      <div class="list-group-item d-flex align-items-center" data-user-id="${userId}">
        <div class="avatar avatar-sm me-3">
          <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
        </div>
        <div class="flex-grow-1">
          <h6 class="mb-0">${userName}</h6>
          <small class="text-muted">${userEmail}</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-user-new-role"
                data-user-id="${userId}"
                title="Remove from list">
          <i class="bx bx-x"></i>
        </button>
        <input type="hidden" name="users[]" value="${userId}">
      </div>
    `;

    // Check if users container is empty and replace placeholder
    if ($('#addRoleUsers').find('.text-muted').length > 0) {
      $('#addRoleUsers').html('<div class="list-group list-group-flush">' + userHtml + '</div>');
    } else {
      $('#addRoleUsers .list-group').append(userHtml);
    }

    toastr.success(`${userName} added to role`, '', {
      timeOut: 2000,
      closeButton: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      "toastClass": "toast ngx-toastr bg-success text-white"
    });
  }

  // Handle remove user from new role list
  $(document).on('click', '.remove-user-new-role', function() {
    const userId = $(this).data('user-id');
    const listItem = $(this).closest('.list-group-item');
    const userName = listItem.find('h6').text();

    listItem.fadeOut(300, function() {
      $(this).remove();
      // Check if no users left
      if ($('#addRoleUsers .list-group-item').length === 0) {
        $('#addRoleUsers').html('<p class="text-muted">No users selected for this role</p>');
      }
    });

    toastr.info(`${userName} removed from role`, '', {
      timeOut: 2000,
      closeButton: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      "toastClass": "toast ngx-toastr bg-info text-white"
    });
  });

  // Add user directly to role via AJAX
  function addUserToRole(userId, userName, userEmail, roleId) {
    $.ajax({
      type: 'POST',
      url: `/admin/roles/${roleId}/users`,
      data: { user_id: userId },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          // Add to current role users display
          const initials = userName.substring(0, 2).toUpperCase();
          const userHtml = `
            <div class="list-group-item d-flex align-items-center">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">${userName}</h6>
                <small class="text-muted">${userEmail}</small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger remove-user-role"
                      data-user-id="${userId}"
                      data-role-id="${roleId}"
                      title="Remove from role">
                <i class="bx bx-x"></i>
              </button>
            </div>
          `;

          // Check if users container is empty and replace placeholder
          if ($('#editRoleUsers').find('.text-muted').length > 0) {
            $('#editRoleUsers').html('<div class="list-group list-group-flush">' + userHtml + '</div>');
          } else {
            $('#editRoleUsers .list-group').append(userHtml);
          }

          // No need to clear anything since we're using Select2 now

          // Show success message
          toastr.success(response.message || 'User added to role successfully', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-success text-white"
          });

          // Reload DataTable if it exists
          if (typeof window.dt_role !== 'undefined' && window.dt_role) {
            window.dt_role.ajax.reload();
          } else if ($('.datatables-roles').length) {
            $('.datatables-roles').DataTable().ajax.reload();
          }
        } else {
          toastr.error(response.message || 'Error adding user to role', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            "toastClass": "toast ngx-toastr bg-danger text-white"
          });
        }
      },
      error: function(xhr) {
        let errorMessage = 'Error adding user to role';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right',
          "toastClass": "toast ngx-toastr bg-danger text-white"
        });
      }
    });
  }

})();
