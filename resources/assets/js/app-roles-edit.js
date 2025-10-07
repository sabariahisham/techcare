/**
 * Role Edit functionality
 */

'use strict';

(function () {
  // Initialize select all permissions functionality
  initializeSelectAllPermissions();

  // Initialize user management
  initializeUserManagement();

  // Form validation setup - DISABLED FOR DEBUGGING
  const editRoleForm = document.getElementById('editRoleForm');
  let fvEdit = null; // Disable FormValidation temporarily

  // Simple form validation function
  function validateEditForm() {
    const roleName = $('#edit-role-name').val();
    const errors = [];

    // Clear previous validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    if (!roleName || roleName.trim() === '') {
      $('#edit-role-name').addClass('is-invalid');
      $('#edit-role-name').after('<div class="invalid-feedback">Role name is required</div>');
      errors.push('Role name is required');
    } else if (roleName.trim().length < 2) {
      $('#edit-role-name').addClass('is-invalid');
      $('#edit-role-name').after('<div class="invalid-feedback">Role name must be at least 2 characters</div>');
      errors.push('Role name must be at least 2 characters');
    }

    return errors.length === 0;
  }

  // Add click handler for edit button in DataTable
  $(document).on('click', '.edit-role', function() {
    const roleId = $(this).data('id');
    loadRoleData(roleId);
  });

  // Form submit handler
  $('#editRoleForm').on('submit', function(e) {
    e.preventDefault();
    console.log('Form submit triggered');

    // Debug form field values
    const roleName = $('#edit-role-name').val();
    const description = $('#edit-role-description').val();
    console.log('Form values - Name:', roleName, 'Description:', description);
    console.log('Form element found:', $('#edit-role-name').length > 0);

    // Validate the form
    if (!validateEditForm()) {
      console.log('Form validation failed');
      return false;
    }

    console.log('Form validation passed, submitting...');
    submitEditRoleForm();
    return false;
  });

  // Also handle the submit button click directly
  $('button[form="editRoleForm"]').on('click', function(e) {
    e.preventDefault();
    console.log('Submit button clicked');
    $('#editRoleForm').trigger('submit');
  });  // Load role data for editing
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
    console.log('Populating edit form with role data:', role);

    // Set role ID
    editRoleForm.setAttribute('data-role-id', role.id);

    // Populate basic fields - ensure elements exist before setting values
    const nameField = document.getElementById('edit-role-name');
    const descField = document.getElementById('edit-role-description');

    console.log('Name field found:', nameField !== null);
    console.log('Description field found:', descField !== null);
    console.log('Role name to set:', role.name);
    console.log('Role description to set:', role.description);

    if (nameField) {
      nameField.value = role.name || '';
      console.log('Name field value set to:', nameField.value);
    }
    if (descField) {
      descField.value = role.description || '';
      console.log('Description field value set to:', descField.value);
    }

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

    // Collect form data manually
    const roleName = $('#edit-role-name').val();
    const roleDescription = $('#edit-role-description').val();

    const formData = {
      name: roleName,
      description: roleDescription,
      permissions: [],
      _method: 'PUT'
    };

    // Collect selected permissions
    $('.edit-permission:checked').each(function() {
      formData.permissions.push($(this).val());
    });

    // Debug logging
    console.log('Form field values:');
    console.log('- Name field value:', roleName);
    console.log('- Name field length:', roleName ? roleName.length : 'null/undefined');
    console.log('- Description field value:', roleDescription);
    console.log('- Permissions count:', formData.permissions.length);
    console.log('Complete formData object:', formData);
    console.log('Role ID:', roleId);

    // Check if name is empty or just whitespace
    if (!roleName || roleName.trim() === '') {
      toastr.error('Role name cannot be empty. Please enter a role name.', '', {
        timeOut: 5000,
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        "toastClass": "toast ngx-toastr bg-danger text-white"
      });
      isEditSubmitting = false;
      return;
    }    $.ajax({
      type: 'POST', // Change to POST with _method field for Laravel
      url: `/admin/roles/${roleId}`,
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Check if there are users selected to add to the role
          const selectedUsers = $('#edit-role-users').select2('data');

          if (selectedUsers && selectedUsers.length > 0) {
            console.log('Adding selected users to role:', selectedUsers);
            // Add each selected user to the role
            let usersProcessed = 0;
            const totalUsers = selectedUsers.length;

            selectedUsers.forEach(function(userData) {
              // Extract nama from the text format "nama (email)"
              const userName = userData.text.split(' (')[0];
              const userEmail = userData.email || userData.text.split(' (')[1]?.replace(')', '') || '';

              // Add user to role via AJAX
              $.ajax({
                type: 'POST',
                url: `/admin/roles/${roleId}/users`,
                data: { user_id: userData.id },
                headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(userResponse) {
                  usersProcessed++;
                  if (userResponse.success) {
                    console.log(`User ${userName} added to role successfully`);
                  } else {
                    console.warn(`Failed to add user ${userName}:`, userResponse.message);
                  }

                  // When all users are processed, complete the role update
                  if (usersProcessed === totalUsers) {
                    completeRoleUpdate();
                  }
                },
                error: function(xhr) {
                  usersProcessed++;
                  console.error(`Error adding user ${userName}:`, xhr);

                  // Continue even if some users fail
                  if (usersProcessed === totalUsers) {
                    completeRoleUpdate();
                  }
                }
              });
            });
          } else {
            // No users to add, complete the update immediately
            completeRoleUpdate();
          }

          function completeRoleUpdate() {
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
            const successMessage = selectedUsers && selectedUsers.length > 0 ?
              `Role updated successfully and ${selectedUsers.length} user(s) added` :
              'Role updated successfully';

            toastr.success(successMessage, '', {
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
          }
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
        console.log('Error response:', xhr);

        if (xhr.status === 422) {
          // Validation errors - show specific field errors
          console.log('Validation errors:', xhr.responseJSON);
          const errors = xhr.responseJSON.errors || {};
          let errorMessage = 'Validation failed:\n';

          // Clear previous validation classes
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();

          for (const field in errors) {
            if (errors.hasOwnProperty(field)) {
              errorMessage += '• ' + errors[field][0] + '\n';

              // Add validation styling to specific fields
              if (field === 'name') {
                $('#edit-role-name').addClass('is-invalid');
                $('#edit-role-name').after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
              } else if (field === 'description') {
                $('#edit-role-description').addClass('is-invalid');
                $('#edit-role-description').after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
              }
            }
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

  // Reset form when modal is hidden
  $('#modalEditRole').on('hidden.bs.modal', function () {
    console.log('Modal hidden, resetting form');
    if (editRoleForm) {
      editRoleForm.reset();
      editRoleForm.removeAttribute('data-role-id');

      // Reset FormValidation only if it exists
      if (fvEdit && typeof fvEdit.resetForm === 'function') {
        fvEdit.resetForm(true);
      }

      // Uncheck all permission checkboxes
      $('.edit-permission').prop('checked', false);
      $('#editSelectAllPermissions').prop('checked', false).prop('indeterminate', false);

      // Clear users sections
      $('#editRoleUsers').html('');

      // Clear Select2 dropdown properly
      if ($('#edit-role-users').length && $('#edit-role-users').hasClass('select2-hidden-accessible')) {
        $('#edit-role-users').val(null).trigger('change');
      }

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();

      // Reset submission flag
      isEditSubmitting = false;
    }
  });

  // Select All Permissions functionality
  function initializeSelectAllPermissions() {
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

  // User Management functionality for edit form
  function initializeUserManagement() {
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
            exclude_role: roleId
          };
        },
        processResults: function (data) {
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


  }



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

  // Debug function to test form functionality
  window.debugRoleEditForm = function() {
    console.log('=== DEBUG ROLE EDIT FORM ===');
    console.log('Form element:', editRoleForm);
    console.log('Name field value:', $('#edit-role-name').val());
    console.log('Description field value:', $('#edit-role-description').val());
    console.log('Role ID:', editRoleForm ? editRoleForm.getAttribute('data-role-id') : 'No form');
    console.log('Permissions selected:', $('.edit-permission:checked').length);
    console.log('Modal visible:', $('#modalEditRole').hasClass('show'));

    // Test form submission
    if (editRoleForm) {
      console.log('Testing form validation...');
      const isValid = validateEditForm();
      console.log('Form validation result:', isValid);
    }
  };

})();
