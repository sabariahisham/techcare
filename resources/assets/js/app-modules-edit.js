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
          },
          regexp: {
            regexp: /^[a-zA-Z0-9\s\-\_]+$/,
            message: 'Nama modul hanya boleh mengandungi huruf(a-z), nombor(0-9), ruang kosong( ), tanda sempang(-) dan garis bawah(_)'
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
          if (xhr.responseJSON.errors) {
            // Handle validation errors with user-friendly messages
            const errors = xhr.responseJSON.errors;
            const errorMessages = [];

            Object.keys(errors).forEach(function(key) {
              if (key === 'editModuleName') {
                const fieldErrors = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                fieldErrors.forEach(function(error) {
                  if (error.includes('required')) {
                    errorMessages.push('Sila masukkan nama modul');
                  } else if (error.includes('unique')) {
                    errorMessages.push('Nama modul telah wujud. Sila pilih nama yang lain.');
                  } else if (error.includes('regex')) {
                    errorMessages.push('Nama modul hanya boleh mengandungi huruf(a-z), nombor(0-9), ruang kosong( ), tanda sempang(-) dan garis bawah(_)');
                  } else if (error.includes('min')) {
                    errorMessages.push('Nama modul mestilah sekurang-kurangnya 3 aksara');
                  } else if (error.includes('max')) {
                    errorMessages.push('Nama modul tidak boleh melebihi 100 aksara');
                  } else {
                    errorMessages.push(error);
                  }
                });

                // Highlight the field with error
                $('#edit-module-name').addClass('is-invalid');
                $('#edit-module-name').on('input', function() {
                  $(this).removeClass('is-invalid');
                });
              }
            });

            if (errorMessages.length > 0) {
              errorMessage = errorMessages.join('<br>');
            }
          } else if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
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
