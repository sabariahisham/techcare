'use strict';

// Initialize SLA delete functionality when document is ready
$(function () {
  // Add click handler to SLA delete buttons
  $(document).on('click', '.delete-record', function() {
    const slaId = $(this).data('id');
    console.log('Delete button clicked for SLA ID:', slaId);

    if (!slaId) {
      console.error('No SLA ID found on delete button');
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'ID SLA tidak dijumpai',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    const row = $(this).closest('tr');

    // Get category from group header (previous group row)
    const categoryName = row.prevAll('tr.dtrg-group').first().find('td').text().replace('Kategori: ', '').split(' (')[0].trim();

    // Get data from table columns (adjusting for correct column positions)
    const subCategoryName = row.find('td:nth-child(3)').text().trim(); // Sub Kategori column (subcat_name)
    const tahapPegawai = row.find('td:nth-child(4)').text().trim(); // Tahap Pegawai Tugasan column (sla_level)
    const tahapKeterukan = row.find('td:nth-child(5)').text().trim(); // Tahap Keterukan column (sla_severity)
    const hari = row.find('td:nth-child(6)').text().trim(); // Hari column (sla_days)

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam SLA untuk:<br/>
             <b>Sub Kategori:</b> ${subCategoryName}<br/>
             <b>Tahap Pegawai Tugasan:</b> ${tahapPegawai}<br/>
             <b>Tahap Keterukan:</b> ${tahapKeterukan}<br/>
             <b>Hari:</b> ${hari}`,
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
        console.log('User confirmed deletion for SLA ID:', slaId);
        fetch(`/sla-severity/${slaId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
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
          console.error('Delete error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Ralat semasa memadam SLA: ${error.message}`,
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
