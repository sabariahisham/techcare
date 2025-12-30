'use strict';

// Initialize delete functionality when document is ready
$(function () {
  // Add click handler to delete buttons
  $(document).on('click', '.delete-record', function() {
    const userId = $(this).data('id');
    const userName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get name from the 3rd column

    Swal.fire({
      title: 'Are you sure?',
      text: `You want to delete user "${userName}"?`,
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
        fetch(`/user-management/${userId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        })
        .then(response => response.json())
        .then(data => {
          if(data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: data.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable
              $('.datatables-users').DataTable().ajax.reload();
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        });
      }
    });
  });
});
