'use strict';

// Initialize SLA delete functionality when document is ready
$(function () {
  // Add click handler to SLA delete buttons
  $(document).on('click', '.delete-record', function() {
    const slaId = $(this).data('id');
    const categoryName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get category name from cat_name column
    const subCategoryName = $(this).closest('tr').find('td:nth-child(4)').text().trim(); // Get sub-category name from subcat_name column

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam SLA untuk kategori <b>${categoryName}</b> - <b>${subCategoryName}</b>?`,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: 'Tidak, batal!',
      confirmButtonText: 'Ya, padam!',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        fetch(`/sla-severity/${slaId}`, {
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
              title: 'Dipadam!',
              text: 'SLA berjaya dipadam',
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable after the timer
              if (typeof window.dt_sla !== 'undefined') {
                window.dt_sla.ajax.reload();
              } else {
                $('.datatables-sla').DataTable().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Ralat semasa memadam SLA',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ralat semasa memadam SLA',
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
