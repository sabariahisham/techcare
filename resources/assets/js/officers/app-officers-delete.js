'use strict';

// Initialize delete functionality when document is ready
$(function () {
  // Add click handler to delete buttons
  $(document).on('click', '.delete-record', function() {
    const officerId = $(this).data('id');
    const $row = $(this).closest('tr');
    const officerName = $row.find('td:nth-child(6)').text().trim(); // Get name from user_name column
    const categoryName = $row.find('td:nth-child(3)').text().trim(); // Get category name
    const subcategoryName = $row.find('td:nth-child(4)').text().trim(); // Get subcategory name

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam tugasan pegawai <b>${officerName}</b><br>untuk kategori <b>${categoryName}</b> - <b>${subcategoryName}</b>?`,
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
        fetch(`/officer/${officerId}`, {
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
              title: 'Berjaya Dipadam!',
              text: data.message,
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable after the timer
              $('.datatables-refcategory').DataTable().ajax.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Ralat',
              text: data.message || 'Ralat berlaku semasa memadam pegawai tugasan',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        })
        .catch(error => {
          console.error('Delete error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Ralat',
            text: 'Ralat berlaku semasa memadam pegawai tugasan',
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
