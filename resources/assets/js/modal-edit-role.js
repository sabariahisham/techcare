/**
 * Add new role Modal JS
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  // add role form validation
  FormValidation.formValidation(document.getElementById('editRoleForm'), {
    // Add to existing form validation
    fields: {
      modalRoleName: {
        validators: {
          notEmpty: {
            message: 'Please enter role name'
          }
        }
      },
      'permissions[]': {
        validators: {
          notEmpty: {
            message: 'Please select at least one permission'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        // Use this for enabling/changing valid/invalid class
        // eleInvalidClass: '',
        eleValidClass: '',
        rowSelector: '.col-12'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      // Submit the form when all fields are valid
      // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    // Get the form data
    const form = document.getElementById('editRoleForm');
    const formData = new FormData(form);
    const roleId = document.getElementById('roleId').value;

    // Create URL with the role ID
    const url = `/acl/roles/${roleId}`;

    // Send AJAX request
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-HTTP-Method-Override': 'PUT'
        },
        success: function(response) {
            toastr.success(response.message, '', {
              closeButton: true,
              timeOut: 3000,
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

            // Close modal and reload page
            $('#editRoleModal').modal('hide');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr) {
            // Show error toast
            const error = xhr.responseJSON;
            // toastr.error(error.message || 'Something went wrong!', 'Error!', {
            //     closeButton: true,
            //     tapToDismiss: false,
            //     progressBar: true
            // });

            toastr.error(error.message, 'Error', {
              closeButton: true,
              timeOut: 3000,
              progressBar: true,
              positionClass: 'toast-top-right',
              showClass: "animate__animated animate__bounceInRight",
              hideClass: "animate__animated animate__fadeOut",
              "toastClass": "toast ngx-toastr bg-danger text-white"
            });
        }
    });
  });

  // Select All checkbox click
  const selectAll = document.querySelector('#editSelectAll');
  selectAll.addEventListener('change', t => {
    const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    permissionCheckboxes.forEach(checkbox => {
      checkbox.checked = t.target.checked;
    });
  });

  // Load permissions assigned to the role
  $('#editRoleModal').on('show.bs.modal', function (event) {
      const button = $(event.relatedTarget);
      const roleId = button.data('role-id');
      const modal = $(this); // Add this line

      $.ajax({
          url: `/acl/roles/${roleId}/permissions`, // Ensure this matches your route
          method: 'GET',
          success: function (data) {

            // Set role name and ID
            modal.find('#roleId').val(roleId);
            modal.find('#modalRoleName').val(data.role.name);

            // Check the permissions
            data.permissions.forEach(permission => {
                modal.find(`input[value="${permission}"]`).prop('checked', true);
            });

          }
      });
  });

  // when modal is closed, cleared all fields and uncheck all checkboxes
  $('#editRoleModal').on('hidden.bs.modal', function () {

      // Clear form fields
      $('#editRoleForm').trigger('reset');

      // Uncheck all checkboxes
      $('input[type="checkbox"]').prop('checked', false);

      /// Clear validation states by reinitializing form validation
      FormValidation.formValidation(document.getElementById('addRoleForm'), {
          fields: {
              modalRoleName: {
                  validators: {
                      notEmpty: {
                          message: 'Please enter role name'
                      }
                  }
              },
              'permissions[]': {
                  validators: {
                      notEmpty: {
                          message: 'Please select at least one permission'
                      }
                  }
              }
          }
      });
  });

});
