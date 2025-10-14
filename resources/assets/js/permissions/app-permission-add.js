

'use strict';

// Form Validation for Add Permission (Offcanvas style)
$(document).ready(function () {
  console.log('Document ready, FormValidation available:', typeof FormValidation !== 'undefined');

  // Add New Permission form
  const addNewPermissionForm = document.getElementById('addNewPermissionForm');
  console.log('Looking for form:', addNewPermissionForm);

  if (addNewPermissionForm && typeof FormValidation !== 'undefined') {
    console.log('Form found, initializing validation');
    const fv = FormValidation.formValidation(addNewPermissionForm, {
      fields: {
        name: {
          validators: {
            notEmpty: {
              message: 'Sila masukkan nama capaian'
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
        description: {
          validators: {
            notEmpty: {
              message: 'Sila masukkan deskripsi capaian`'
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
      // Submit form data
      $.ajax({
        url: '/admin/permissions',
        type: 'POST',
        data: $(addNewPermissionForm).serialize(),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          console.log('Success response:', response);
          if (response.success) {
            // Close offcanvas
            $('#offcanvasAddPermission').offcanvas('hide');
            // Clear form
            addNewPermissionForm.reset();
            // Reset form validation
            fv.resetForm();
            // Show success message
            if (typeof toastr !== 'undefined') {
              toastr.success('Capaian berjaya ditambah', 'Berjaya!', {
                closeButton: true,
                tapToDismiss: false,
                progressBar: true,
                rtl: false
              });
            } else {
              alert('Capaian berjaya ditambah!');
            }
            // Refresh the datatable
            if ($('.datatables-permissions').length && $('.datatables-permissions').DataTable()) {
              $('.datatables-permissions').DataTable().ajax.reload();
            }
          } else {
            // Handle server-side errors
            if (typeof toastr !== 'undefined') {
              toastr.error(response.message || 'Ralat berlaku semasa menambah capaian');
            } else {
              alert('Error: ' + (response.message || 'Ralat berlaku semasa menambah capaian'));
            }
          }
        },
        error: function(xhr, status, error) {
          console.log('Error response:', xhr.responseJSON);

          // Handle validation errors (422 status)
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            console.log('Validation errors:', errors);

            // Check specifically for name already exists error
            if (errors.name && errors.name.length > 0) {
              const nameError = errors.name[0];
              console.log('Name validation error:', nameError);

              // Check if it's a uniqueness error (both English and Malay)
              if (nameError.includes('already') || nameError.includes('unique') || nameError.includes('exists') ||
                  nameError.includes('sudah wujud') || nameError.includes('sudah ada'))  {
                // Show specific toastr message for duplicate name
                if (typeof toastr !== 'undefined') {
                  toastr.error('Nama capaian ini sudah wujud. Sila gunakan nama lain.', 'Nama Sudah Wujud!', {
                    closeButton: true,
                    tapToDismiss: false,
                    progressBar: true,
                    rtl: false,
                    timeOut: 5000
                  });
                } else {
                  alert('Nama capaian ini sudah wujud. Sila gunakan nama lain.');
                }
              } else {
                // Show generic name error
                if (typeof toastr !== 'undefined') {
                  toastr.error(nameError, 'Ralat Nama Capaian');
                }
              }

              // Update form validation for name field
              const nameField = document.querySelector('[name="name"]');
              if (nameField) {
                fv.updateFieldStatus('name', 'Invalid', 'notEmpty');
                fv.updateMessage('name', 'notEmpty', nameError);
              }
            }

            // Display validation errors on other form fields
            for (const field in errors) {
              if (field !== 'name') { // Skip name as we handled it above
                const fieldElement = document.querySelector(`[name="${field}"]`);
                if (fieldElement) {
                  console.log(`Setting error for field: ${field}`, errors[field][0]);

                  // Use FormValidation to update field status
                  fv.updateFieldStatus(field, 'Invalid', 'notEmpty');

                  // Update the validation message for this specific field
                  fv.updateMessage(field, 'notEmpty', errors[field][0]);
                }
              }
            }
          } else {
            // Handle other errors
            const errorMessage = xhr.responseJSON?.message || 'Ralat berlaku semasa menambah capaian';
            if (typeof toastr !== 'undefined') {
              toastr.error(errorMessage, 'Ralat!');
            } else {
              alert('Error: ' + errorMessage);
            }
          }
          console.error('AJAX Error:', error);
        }
      });
    });

    // Reset form when offcanvas is closed
    $('#offcanvasAddPermission').on('hidden.bs.offcanvas', function () {
      console.log('Offcanvas closed, resetting form');
      // Clear form
      addNewPermissionForm.reset();
      // Reset form validation
      fv.resetForm();
      // Clear any validation messages and classes
      $(addNewPermissionForm).find('.is-invalid').removeClass('is-invalid');
      $(addNewPermissionForm).find('.invalid-feedback').remove();
      $(addNewPermissionForm).find('.fv-plugins-message-container').empty();
    });

  } else {
    console.log('Form not found or FormValidation not available');
    console.log('Form element:', addNewPermissionForm);
    console.log('FormValidation available:', typeof FormValidation !== 'undefined');
  }
});
