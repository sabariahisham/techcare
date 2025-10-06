/**
 * Page Role Add/Create
 */

'use strict';

// Initialize form elements and components
$(function () {
  // Any initialization code for role form can go here
});

// Form submission handlers
(function () {
  // Form validation setup
  const addNewRoleForm = document.getElementById('addNewRoleForm');
  let fv;

  if (addNewRoleForm) {
    fv = FormValidation.formValidation(addNewRoleForm, {
      fields: {
        'modalRoleName': {
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
      submitRoleForm();
    });
  }

  // Add Role form submission function
  let isSubmitting = false;

  function submitRoleForm() {
    // Prevent multiple submissions
    if (isSubmitting) {
      return;
    }

    isSubmitting = true;
    const formData = new FormData(addNewRoleForm);

    $.ajax({
      type: 'POST',
      url: '/acl/roles/store',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 'success') {
          // Hide offcanvas
          var addOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddRole'));
          addOffcanvas.hide();

          // Reset form
          addNewRoleForm.reset();

          // Reset FormValidation
          if (fv) {
            fv.resetForm(true);
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
        } else {
          // Show error message
          toastr.error(response.message || 'Error creating role', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-danger text-white",
            "closeClass": "toast-close-button",
            "titleClass": "toast-title",
            "messageClass": "toast-message",
            "progressClass": "toast-progress"
          });
        }

        // Reset submission flag
        isSubmitting = false;
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
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
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
        isSubmitting = false;
      }
    });
  }

  // Reset form when offcanvas is hidden
  $('#offcanvasAddRole').on('hidden.bs.offcanvas', function () {
    if (addNewRoleForm) {
      addNewRoleForm.reset();

      // Reset FormValidation
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Uncheck all permission checkboxes
      $('input[name="permissions[]"]').prop('checked', false);

      // Clear any validation errors
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();

      // Reset submission flag
      isSubmitting = false;
    }
  });
})();
