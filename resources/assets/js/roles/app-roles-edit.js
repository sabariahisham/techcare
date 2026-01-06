/**
 * Role Edit functionality
 */

'use strict';

(function () {
  // Utility function to reload DataTable
  function reloadRoleDataTable() {
    console.log('Attempting to reload role DataTable...');

    // Try multiple approaches in sequence
    let reloadAttempted = false;

    try {
      // Method 1: Try global dt_role first
      if (typeof window.dt_role !== 'undefined' && window.dt_role && typeof window.dt_role.ajax !== 'undefined') {
        console.log('Reloading via window.dt_role');
        window.dt_role.ajax.reload(null, false);
        reloadAttempted = true;
      }

      // Method 2: Try to find DataTable instance by class
      if (!reloadAttempted) {
        const $table = $('.datatables-roles');
        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table[0])) {
          console.log('Reloading via .datatables-roles selector');
          $table.DataTable().ajax.reload(null, false);
          reloadAttempted = true;
        }
      }

      // Method 3: Try alternative selectors
      if (!reloadAttempted) {
        const selectors = ['#roleTable', '.roles-datatable', '.dt-role', 'table[id*="role"]', 'table[class*="role"]'];
        for (const selector of selectors) {
          const $roleTable = $(selector);
          if ($roleTable.length && $.fn.DataTable && $.fn.DataTable.isDataTable($roleTable[0])) {
            console.log(`Reloading via ${selector} selector`);
            $roleTable.DataTable().ajax.reload(null, false);
            reloadAttempted = true;
            break;
          }
        }
      }

      // Method 4: Find any DataTable with role-related data
      if (!reloadAttempted) {
        $('table').each(function() {
          if ($.fn.DataTable.isDataTable(this)) {
            const table = $(this).DataTable();
            if (table.ajax && table.ajax.url && table.ajax.url().includes('role')) {
              console.log('Reloading via role-related DataTable detection');
              table.ajax.reload(null, false);
              reloadAttempted = true;
              return false; // Break the loop
            }
          }
        });
      }

    } catch (error) {
      console.error('Error in DataTable reload attempt:', error);
    }

    // Final fallback with multiple timing attempts
    if (!reloadAttempted) {
      console.warn('DataTable instance not found - using fallback methods');

      // Try delayed reload with different intervals
      const delays = [200, 500, 1000];
      delays.forEach(delay => {
        setTimeout(function() {
          try {
            if (typeof window.dt_role !== 'undefined' && window.dt_role && window.dt_role.ajax) {
              window.dt_role.ajax.reload(null, false);
              console.log(`Delayed reload successful after ${delay}ms`);
            } else {
              // Try to find and reload any roles DataTable
              const $rolesTable = $('.datatables-roles, #roleTable, .roles-datatable');
              if ($rolesTable.length && $.fn.DataTable.isDataTable($rolesTable[0])) {
                $rolesTable.DataTable().ajax.reload(null, false);
                console.log(`Delayed selector reload successful after ${delay}ms`);
              }
            }
          } catch (delayedError) {
            console.warn(`Delayed reload failed after ${delay}ms:`, delayedError);
          }
        }, delay);
      });
    }

    return reloadAttempted;
  }

  // Initialize select all permissions functionality
  initializeSelectAllPermissions();

  // Initialize user management
  initializeUserManagement();

  const editRoleForm = document.getElementById('editRoleForm');

  // Add click handler for edit button in DataTable
  $(document).on('click', '.edit-role', function() {
    const roleId = $(this).data('id');
    loadRoleData(roleId);
  });

  // Form submit handler
  $('#editRoleForm').on('submit', function(e) {
    e.preventDefault();

    // Validate the form using the shared validation utility
    const isValid = RoleValidation.validateRoleForm(
      '#editRoleForm',
      '#edit-role-name',
      '.edit-permission',
      '#editRoleForm .permissions-container'
    );

    if (!isValid) {
      return false;
    }

    submitEditRoleForm();
    return false;
  });

  // Also handle the submit button click directly
  $('button[form="editRoleForm"]').on('click', function(e) {
    e.preventDefault();
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
          toastr.error(response.message || 'Ralat memuatkan data peranan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Ralat memuatkan data peranan';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      }
    });
  }

  // Populate edit form with role data
  function populateEditForm(role, permissions) {
    // Set role ID
    editRoleForm.setAttribute('data-role-id', role.id);

    // Clear previous validation errors
    RoleValidation.clearValidationErrors('#editRoleForm');

    // Populate basic fields
    const nameField = document.getElementById('edit-role-name');
    const descField = document.getElementById('edit-role-description');

    if (nameField) {
      nameField.value = role.name || '';
    }
    if (descField) {
      descField.value = role.description || '';
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
        }
      });

      // Update select all checkbox state
      updateSelectAllState();
    }

    // Load users assigned to this role
    let users = role.users || [];
    populateUsersSection(users);
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

  // Populate users section
  function populateUsersSection(users) {
    const usersContainer = $('#editRoleUsers');

    if (!users || users.length === 0) {
      usersContainer.html('<p class="text-muted">No users assigned to this role</p>');
      return;
    }

    let usersList = '<div class="list-group list-group-flush">';
    users.forEach(function(user) {
      // Handle both field names for backward compatibility
      const userName = user.nama || user.name || 'Unknown User';
      const userEmail = user.email || '';
      const userId = user.id;

      if (!userId) {
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
      title: 'Keluarkan Pengguna?',
      text: "Adakah anda pasti mahu mengeluarkan pengguna ini dari peranan?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, keluarkan',
      cancelButtonText: 'Batal',
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

          toastr.success(response.message || 'Pengguna berjaya dikeluarkan dari peranan', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable if it exists
          reloadRoleDataTable();

          // Trigger statistics refresh
          $(document).trigger('roleUpdated');
        } else {
          toastr.error(response.message || 'Ralat mengeluarkan pengguna dari peranan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Ralat mengeluarkan pengguna dari peranan';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
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
      RoleValidation.showFieldError('#edit-role-name', 'Role ID not found');
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

    $.ajax({
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
            // Show success message first (before hiding modal)
            const successMessage = selectedUsers && selectedUsers.length > 0 ?
              `Peranan berjaya dikemaskini dan ${selectedUsers.length} pengguna ditambah` :
              'Peranan berjaya dikemaskini';

            // Show success toastr immediately
            toastr.success(successMessage, '', {
              timeOut: 4000,
              closeButton: true,
              progressBar: true,
              positionClass: 'toast-top-right'
            });

            // Small delay to ensure toastr is displayed before modal manipulation
            setTimeout(function() {
              // Hide modal
              var editModal = bootstrap.Modal.getInstance(document.getElementById('modalEditRole'));
              if (editModal) {
                editModal.hide();
              } else {
                $('#modalEditRole').modal('hide');
              }

              // Reset form
              editRoleForm.reset();

              // Reset FormValidation
              if (typeof fvEdit !== 'undefined' && fvEdit) {
                fvEdit.resetForm(true);
              }

              // Clear the select2 selection
              $('#edit-role-users').val(null).trigger('change');

              // Reload DataTable with enhanced method
              reloadRoleDataTable();

              // Trigger custom event to refresh statistics
              $(document).trigger('roleUpdated');

              // Reset submission flag
              isEditSubmitting = false;
            }, 100);
          }
        } else {
          // Show error message
          toastr.error(response.message || 'Ralat mengemaskini peranan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable even on error to ensure consistency
          reloadRoleDataTable();
        }

        // Reset submission flag
        isEditSubmitting = false;
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          // Validation errors - use the shared validation handler
          const errors = xhr.responseJSON.errors;
          RoleValidation.handleServerErrors(errors, '#editRoleForm');
        } else {
          let errorMessage = 'Ralat mengemaskini peranan';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }

          toastr.error(errorMessage, '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable even on error to ensure consistency
          reloadRoleDataTable();
        }

        isEditSubmitting = false;
      }
    });
  }



  // Reset edit form when modal is hidden
  $('#modalEditRole').on('hidden.bs.modal', function () {
    if (editRoleForm) {
      // Reset the form completely
      editRoleForm.reset();
      editRoleForm.removeAttribute('data-role-id');

      // Clear validation errors using shared utility
      RoleValidation.clearValidationErrors('#editRoleForm');

      // Uncheck all permission checkboxes
      $('.edit-permission').prop('checked', false);
      $('#editSelectAllPermissions').prop('checked', false).prop('indeterminate', false);

      // Clear users sections
      $('#editRoleUsers').html('<p class="text-muted">No users assigned to this role</p>');
      $('#edit-role-users').val(null).trigger('change');

      // Reset form fields explicitly
      $('#edit-role-name').val('');
      $('#edit-role-description').val('');

      // Reset submission flag
      isEditSubmitting = false;

      // Ensure DataTable is reloaded when modal closes
      // This provides an additional safety net
      setTimeout(function() {
        reloadRoleDataTable();
      }, 200);
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
          toastr.success(response.message || 'Pengguna berjaya ditambah ke peranan', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable if it exists
          reloadRoleDataTable();

          // Trigger statistics refresh
          $(document).trigger('roleUpdated');
        } else {
          toastr.error(response.message || 'Ralat menambah pengguna ke peranan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function(xhr) {
        let errorMessage = 'Ralat menambah pengguna ke peranan';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
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
