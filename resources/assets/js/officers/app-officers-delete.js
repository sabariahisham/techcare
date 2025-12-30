'use strict';

// Initialize delete functionality when document is ready
$(function () {
  // Add click handler to delete buttons
  $(document).on('click', '.delete-record', function() {
    const officerId = $(this).data('id');
    const $row = $(this).closest('tr');
    const categoryName = $row.find('td:nth-child(3)').text().trim(); // Get category name
    const subcategoryName = $row.find('td:nth-child(4)').text().trim(); // Get subcategory name

    // Show loading dialog first
    Swal.fire({
      title: 'Sila tunggu...',
      text: 'Sedang mengambil maklumat pegawai yang akan dipadam',
      icon: 'info',
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Fetch the list of officers that will be deleted
    fetch(`/officer/${officerId}/preview-delete`, {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        // Create the officer list HTML
        let officerListHtml = '';
        if(data.officers && data.officers.length > 0) {
          officerListHtml = '<div class="mt-3"><strong>Senarai pegawai yang akan dipadam:</strong><ul class="text-start mt-2">';
          data.officers.forEach(officer => {
            officerListHtml += `<li><strong>${officer.user_name}</strong> (Level: ${officer.level_tugasan})</li>`;
          });
          officerListHtml += '</ul></div>';
        }

        const totalOfficers = data.officers ? data.officers.length : 1;
        const deleteMessage = totalOfficers > 1
          ? `Anda ingin memadam <strong>${totalOfficers} pegawai tugasan</strong> untuk kategori <strong>${categoryName}</strong> - <strong>${subcategoryName}</strong>`
          : `Anda ingin memadam tugasan pegawai untuk kategori <strong>${categoryName}</strong> - <strong>${subcategoryName}</strong>`;

        Swal.fire({
          title: 'Adakah anda pasti?',
          html: deleteMessage + officerListHtml,
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
      } else {
        // Handle error case for preview fetch
        Swal.fire({
          icon: 'error',
          title: 'Ralat',
          text: data.message || 'Ralat berlaku semasa mengambil maklumat pegawai',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      }
    })
    .catch(error => {
      console.error('Preview delete error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Ralat',
        text: 'Ralat berlaku semasa mengambil maklumat pegawai',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
    });
  });
});
