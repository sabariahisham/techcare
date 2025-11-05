'use strict';

$(function () {
  const editUserForm = document.getElementById('editUserForm');

  // Initialize Select2 for edit form
  $('#edit-user-role').select2({
    dropdownParent: $('#offcanvasEditUser')
  });

  // Form Validation
  const fv = FormValidation.formValidation(editUserForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Please enter fullname'
          }
        }
      },
      email: {
        validators: {
          notEmpty: {
            message: 'Please enter your email'
          },
          emailAddress: {
            message: 'The value is not a valid email address'
          }
        }
      },
      role: {
        validators: {
          notEmpty: {
            message: 'Please select a role'
          }
        }
      },
      agency: {
        validators: {
          notEmpty: {
            message: 'Please enter agency name'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.mb-6'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    const formData = new FormData(editUserForm);
    // formData.append('_method', 'PUT');  // Add this line
    const userId = $('#edit-user-id').val();

    $.ajax({
      url: `/user-management/update/${userId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          $('#offcanvasEditUser').offcanvas('hide');
          editUserForm.reset();
          $('#edit-user-role').val('').trigger('change');

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

          $('.datatables-users').DataTable().ajax.reload();
        }
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.message || 'Error updating user';
        toastr.error(errorMessage, 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Load user data when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const userId = $(this).data('id');

    $.ajax({
      url: `/user-management/${userId}/edit`,
      type: 'GET',
      success: function(response) {
        $('#edit-user-id').val(response.id);
        $('#edit-user-fullname').val(response.name);
        $('#edit-user-email').val(response.email);
        $('#edit-user-role').val(response.roles[0].name).trigger('change');
        $('#edit-user-agency').val(response.agency);

        $('#offcanvasEditUser').offcanvas('show');
      },
      error: function(xhr) {
        toastr.error('Error loading user data', 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasEditUser').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    editUserForm.reset();
    $('#edit-user-role').val('').trigger('change');
  });
});
