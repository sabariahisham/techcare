'use strict';

$(function () {
  const addNewUserForm = document.getElementById('addNewUserForm');

  // Initialize Select2
  $('.select2').each(function() {
    var $this = $(this);
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Role',
      dropdownParent: $this.parent()
    });
  });

  // Form Validation
  const fv = FormValidation.formValidation(addNewUserForm, {
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
    // Submit form when validation succeeds
    const formData = new FormData(addNewUserForm);

    $.ajax({
      url: '/user-management/store',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          // Close offcanvas
          $('#offcanvasAddUser').offcanvas('hide');

          // Reset form
          addNewUserForm.reset();

          // Reset Select2
          $('.select2').val('').trigger('change');

          // Show success message
          toastr.success('User created successfully', 'Success!', {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false
          });

          // Reload DataTable
          $('.datatables-users').DataTable().ajax.reload();
        }
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.message || 'Error creating user';
        toastr.error(errorMessage, 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasAddUser').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    addNewUserForm.reset();
    $('.select2').val('').trigger('change');
  });
});
