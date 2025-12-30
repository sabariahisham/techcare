/**
 * Add Permission Modal JS
 */

'use strict';

// Add permission form validation
document.addEventListener('DOMContentLoaded', function (e) {
  const addModuleForm = document.getElementById('addModuleForm');

  const fv = FormValidation.formValidation(addModuleForm, {
    fields: {
      modalModuleName: {
        validators: {
          notEmpty: {
            message: 'Please enter module name'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.col-12'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    // Submit form data via Ajax
    fetch('/modules', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        modalModuleName: document.getElementById('modalModuleName').value
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        // Refresh datatable
        $('.datatables-modules').DataTable().ajax.reload();

        // Close modal and reset form
        $('#addModuleModal').modal('hide');
        addModuleForm.reset();

        // Show success message
        // toastr.success(data.message, '', {
        //     closeButton: true,
        //     timeOut: 5000,
        //     progressBar: true,
        //     positionClass: 'toast-top-right',
        //     backgroundColor: '#28a745',
        //     "toastClass": "toast ngx-toastr",
        //     "closeClass": "toast-close-button",
        //     "titleClass": "toast-title",
        //     "messageClass": "toast-message",
        //     "progressClass": "toast-progress",
        //     toastClass: 'bg-success text-white'
        // });

        toastr.success(data.message, '', {
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
      } else {
        // Animated error toast
        toastr.error(data.message, '', {
            closeButton: true,
            timeOut: 3000,
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
    })
    .catch(error => {
      toastr.error('An error occurred while processing your request', 'Error', {
          closeButton: true,
          timeOut: 3000,
          progressBar: true,
          positionClass: 'toast-top-right',
          showMethod: 'bounceInRight',
          hideMethod: 'fadeOut',
          showDuration: 300,
          hideDuration: 1000,
          toastClass: 'bg-danger text-white animate__animated animate__bounceInRight'
      });
    });
  });
});
