'use strict';

// Export the function to global scope
window.deleteRole = function(roleId, roleName) {
  Swal.fire({
    title: 'Are you sure?',
    text: `You want to delete role "${roleName}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    customClass: {
      confirmButton: 'btn btn-danger me-3',
      cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      fetch(`/acl/roles/${roleId}`, {
        method: 'DELETE',
        contentType: 'application/json',
        accept: 'application/json',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          // Swal.fire({
          //   icon: 'success',
          //   title: 'Deleted!',
          //   text: 'Role has been deleted.',
          //   customClass: {
          //     confirmButton: 'btn btn-success'
          //   }
          // }).then(() => {
          //   window.location.reload();
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

          window.location.reload();
        }
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          // text: 'Something went wrong!',
          text: error.message,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      });
    }
  });
};

// function deleteRole(roleId, roleName) {
//   Swal.fire({
//     title: 'Are you sure?',
//     text: `You want to delete role "${roleName}"?`,
//     icon: 'warning',
//     showCancelButton: true,
//     confirmButtonText: 'Yes, delete it!',
//     customClass: {
//       confirmButton: 'btn btn-danger me-3',
//       cancelButton: 'btn btn-secondary'
//     },
//     buttonsStyling: false
//   }).then(function (result) {
//     if (result.value) {
//       fetch(`/roles/${roleId}`, {
//         method: 'DELETE',
//         headers: {
//           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//         }
//       })
//       .then(response => response.json())
//       .then(data => {
//         if(data.success) {
//           Swal.fire({
//             icon: 'success',
//             title: 'Deleted!',
//             text: 'Role has been deleted.',
//             customClass: {
//               confirmButton: 'btn btn-success'
//             }
//           }).then(() => {
//             window.location.reload();
//           });
//         }
//       })
//       .catch(error => {
//         Swal.fire({
//           icon: 'error',
//           title: 'Error',
//           text: 'Something went wrong!',
//           customClass: {
//             confirmButton: 'btn btn-primary'
//           }
//         });
//       });
//     }
//   });
// }
