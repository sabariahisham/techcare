/**
 * Add new role Modal JS
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  // add role form validation
  FormValidation.formValidation(document.getElementById('addRoleForm'), {
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

    // Submit form data via Ajax
    // fetch('/acl/roles/store', {
    //   method: 'POST',
    //   headers: {
    //     'Content-Type': 'application/json',
    //     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    //   },
    //   body: JSON.stringify({
    //     modalRoleName: document.getElementById('modalRoleName').value,
    //     permissions: Array.from(document.querySelectorAll('[name="permissions[]"]'))
    //       .filter(e => e.checked)
    //       .map(e => e.value)
    //   })
    // })
    fetch('/acl/roles/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        // body: JSON.stringify({
        //   modalRoleName: document.getElementById('modalRoleName').value,
        //   permissions: Array.from(document.querySelectorAll('[name="permissions[]"]'))
        //     .filter(e => e.checked)
        //     .map(e => e.value)
        // })
        body: JSON.stringify({
            modalRoleName: document.getElementById('modalRoleName').value,
            permissions: Array.from(document.querySelectorAll('[name="permissions[]"]'))
                .filter(e => e.checked && e.value) // Add value check to filter out empty values
                .map(e => {
                  return e.value;
                })
        })
    })
    .then(response => response.text())
    .then(text => {
      return JSON.parse(text);
    })
    .then(data => {
      if (data.status === 'success') {

        window.location.reload();

        // Close modal and reset form
        $('#addRoleModal').modal('hide');
        document.getElementById('addRoleForm').reset();

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
            backgroundColor: '#dc3545',
            "toastClass": "toast ngx-toastr",
            "closeClass": "toast-close-button",
        });
      }
    }
    )
    .catch(error => {
      toastr.error('An error occurred while processing your request', 'Error', {
          closeButton: true,
          timeOut: 5000,
          progressBar: true,
          positionClass: 'toast-top-right',
          backgroundColor: '#dc3545',
          "toastClass": "toast ngx-toastr",
          "closeClass": "toast-close-button",
      });
    });
  });

  // Select All checkbox click
  const selectAll = document.querySelector('#selectAll'),
    checkboxList = document.querySelectorAll('[type="checkbox"]');
  selectAll.addEventListener('change', t => {
    checkboxList.forEach(e => {
      e.checked = t.target.checked;
    });
  });
  // clear all checkboxes when modal is not dismissed by cancel button
  const addRoleModal = document.getElementById('addRoleModal');
  addRoleModal.addEventListener('hidden.bs.modal', function () {
      // Clear all checkboxes
      const checkboxList = document.querySelectorAll('[type="checkbox"]');
      checkboxList.forEach(checkbox => {
          checkbox.checked = false;
      });
  });

  window.checkModules = function(hasModules) {

    // Convert to boolean
    // hasModules = Boolean(hasModules);

    if (!hasModules) {
      Swal.fire({
        title: 'No Modules Found',
        text: 'Please add modules first before creating roles',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Add Modules',
        cancelButtonText: 'Close',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          window.open('/acl/module-list/index', '_blank');
        }
      });
    }
    else
    {
      $('#addRoleModal').modal('show');
    }
  };

  const button = document.querySelector('.add-new-role');
  const onclickValue = button.getAttribute('onclick');
  const hasModules = onclickValue.includes('(1)'); // Will be true when PHP passes true/1

  button.addEventListener('click', function() {
    checkModules(hasModules);
  });
});
