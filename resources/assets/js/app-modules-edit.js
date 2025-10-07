'use strict';

$(function () {
  const editModuleForm = document.getElementById('editModuleForm');

  // Form Validation for Edit Module
  const fv = FormValidation.formValidation(editModuleForm, {
    fields: {
      editModuleName: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan nama modul'
          },
          stringLength: {
            min: 3,
            max: 100,
            message: 'Nama modul mestilah antara 3 hingga 100 aksara'
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
    // Get form data
    const formData = new FormData(editModuleForm);
    const moduleId = $('#edit-module-id').val();

    // Prepare data for AJAX request
    const requestData = {
      editModuleName: formData.get('editModuleName'),
      _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Submit form via AJAX
    $.ajax({
      url: `/modules/update/${moduleId}`,
      type: 'POST',
      data: requestData,
      success: function(response) {
        if (response.status === 'success') {
          // Hide the offcanvas
          $('#offcanvasEditModule').offcanvas('hide');

          // Reset the form
          editModuleForm.reset();

          // Show success message
          toastr.success(response.message, 'Berjaya!', {
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

          // Reload the DataTable
          $('.datatables-modules').DataTable().ajax.reload();
        }
      },
      error: function(xhr) {
        let errorMessage = 'Ralat mengemas kini modul';

        if (xhr.responseJSON) {
          if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            // Handle validation errors
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('<br>');

            // Highlight the specific field with error if it's a validation error
            if (errors.editModuleName) {
              $('#edit-module-name').addClass('is-invalid');
              // Remove invalid class after user starts typing
              $('#edit-module-name').on('input', function() {
                $(this).removeClass('is-invalid');
              });
            }
          }
        }

        // Show specific error message for duplicate module name
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.editModuleName) {
          errorMessage = xhr.responseJSON.errors.editModuleName[0];
        }

        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false,
          timeOut: 5000,
          escapeHtml: false
        });
      }
    });
  });

  // Handle edit button click (this will be called from the main modules list)
  $(document).on('click', '.edit-record', function() {
    const moduleId = $(this).data('id');

    // Load module data
    $.ajax({
      url: `/modules/${moduleId}/edit`,
      type: 'GET',
      success: function(response) {
        if (response.status === 'success') {
          const moduleData = response.data;

          // Populate form fields
          $('#edit-module-id').val(moduleData.id);
          $('#edit-module-name').val(moduleData.module_name);

          // Show the offcanvas
          $('#offcanvasEditModule').offcanvas('show');
        }
      },
      error: function(xhr) {
        let errorMessage = 'Ralat memuatkan data modul';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasEditModule').on('hidden.bs.offcanvas', function () {
    if (fv) {
      fv.resetForm();
    }
    editModuleForm.reset();
  });

  // Handle form reset button
  $('#offcanvasEditModule').on('click', 'button[type="reset"]', function() {
    if (fv) {
      fv.resetForm();
    }
    editModuleForm.reset();
  });
});
