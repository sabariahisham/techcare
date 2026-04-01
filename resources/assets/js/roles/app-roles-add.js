/**
 * Role Add and Edit functionality
 */

'use strict';

(function () {
  $(function () {
    // Initialize select all permissions functionality
    initializeSelectAllPermissions();

    // Initialize user management
    initializeUserManagement();

    // Form validation setup for Add Role form
    const addRoleForm = document.getElementById('addNewRoleForm');

    // Form submit handler
    $('#addNewRoleForm').on('submit', function (e) {
      e.preventDefault();

      // Validate the form using the shared validation utility
      const isValid = RoleValidation.validateRoleForm(
        '#addNewRoleForm',
        '#add-role-name',
        '.add-permission',
        '#addNewRoleForm .permissions-container'
      );

      if (!isValid) {
        return false;
      }

      submitAddRoleForm();
      return false;
    });

    // Add direct form submit handler for the submit button
    $(document).on('click', 'button[form="addNewRoleForm"]', function (e) {
      e.preventDefault();
      $('#addNewRoleForm').trigger('submit');
    });



    // Add event handler for when modal is about to be shown to reset form
    $('#modalAddRole').on('show.bs.modal', function () {
      // Clear and reset the form completely
      if (addRoleForm) {
        addRoleForm.reset();

        // Clear validation errors using shared utility
        RoleValidation.clearValidationErrors('#addNewRoleForm');

        // Uncheck all permission checkboxes
        $('.add-permission').prop('checked', false);
        $('#selectAllPermissions').prop('checked', false).prop('indeterminate', false);

        // Clear users sections if exists
        const addRoleUsers = $('#addRoleUsers');
        if (addRoleUsers.length) {
          addRoleUsers.html('<p class="text-muted">No users selected for this role</p>');
        }
        $('#add-role-users').val(null).trigger('change');

        // Reset form fields explicitly
        $('#add-role-name').val('');
        $('#add-role-description').val('');

        // Reset submission flag
        isAddSubmitting = false;
      }
    });






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

            // Reload DataTable if it exists on the page
            if (typeof window.dt_role !== 'undefined' && window.dt_role) {
              window.dt_role.ajax.reload(null, false);
            } else if ($('.datatables-roles').length) {
              $('.datatables-roles').DataTable().ajax.reload(null, false);
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
            // Validation errors - use the shared validation handler
            const errors = xhr.responseJSON.errors;
            RoleValidation.handleServerErrors(errors, '#addNewRoleForm');
          } else {
            let errorMessage = 'Error creating role';
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

        // Clear validation errors using shared utility
        RoleValidation.clearValidationErrors('#addNewRoleForm');

        // Uncheck all permission checkboxes
        $('.add-permission').prop('checked', false);
        $('#selectAllPermissions').prop('checked', false).prop('indeterminate', false);

        // Clear users sections
        $('#addRoleUsers').html('<p class="text-muted">No users selected for this role</p>');
        $('#add-role-users').val(null).trigger('change');

        // Reset form fields explicitly
        $('#add-role-name').val('');
        $('#add-role-description').val('');

        // Reset submission flag
        isAddSubmitting = false;
      }
    });



    // Select All Permissions functionality for Add form only
    function initializeSelectAllPermissions() {
      // Handle select all permissions for add form
      $(document).on('change', '#selectAllPermissions', function () {
        const isChecked = $(this).is(':checked');
        $('.add-permission').prop('checked', isChecked);
      });

      // Handle individual permission changes for add form
      $(document).on('change', '.add-permission', function () {
        updateSelectAllStateAdd();
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
              data.results = data.results.filter(function (user) {
                const userName = user.text.toLowerCase();
                return !userName.includes('test user') && !userName.includes('testuser');
              });
            }
            return data;
          },
          cache: true
        },
        minimumInputLength: 0,
        templateResult: function (user) {
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
        templateSelection: function (user) {
          if (user.id) {
            return user.text.split(' (')[0] || user.text;
          }
          return user.text;
        }
      });

      // Handle Add Selected Users button for add role form
      $('#addSelectedUsersToNew').on('click', function () {
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
        selectedUsers.forEach(function (userData) {
          // Extract nama from the text format "nama (email)"
          const userName = userData.text.split(' (')[0];
          const _emailPart = userData.text.split(' (')[1];
          const userEmail = userData.email || (_emailPart ? _emailPart.replace(')', '') : '');
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
    $(document).on('click', '.remove-user-new-role', function () {
      const userId = $(this).data('user-id');
      const listItem = $(this).closest('.list-group-item');
      const userName = listItem.find('h6').text();

      listItem.fadeOut(300, function () {
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
        data: {
          user_id: userId
        },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
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
              window.dt_role.ajax.reload(null, false);
            } else if ($('.datatables-roles').length) {
              $('.datatables-roles').DataTable().ajax.reload(null, false);
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
        error: function (xhr) {
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

  }); // end $(function(){...})
})();
