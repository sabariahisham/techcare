/**
 * Edit Permission Modal JS
 */

'use strict';

// Edit permission form validation
document.addEventListener('DOMContentLoaded', function (e) {
  const editModuleForm = document.getElementById('editModuleForm');

  FormValidation.formValidation(editModuleForm, {
    fields: {
      editModuleName: {
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
        rowSelector: '.col-sm-9'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    const moduleId = editModuleForm.dataset.moduleId;

    fetch(`/modules/update/${moduleId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        editModuleName: document.getElementById('editModuleName').value
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        $('.datatables-modules').DataTable().ajax.reload();
        $('#editModuleModal').modal('hide');
        editModuleForm.reset();

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
        showClass: "animate__animated animate__bounceInRight",
        hideClass: "animate__animated animate__fadeOut",
        "toastClass": "toast ngx-toastr bg-danger text-white"
      });
    });
  });
});
