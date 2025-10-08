'use strict';

// Initialize Role delete functionality when document is ready
$(function () {
  console.log('Role delete script loaded');

  // Add click handler to Role delete buttons
  $(document).on('click', '.delete-record', function(e) {
    e.preventDefault();
    console.log('Delete button clicked');
    const roleId = $(this).data('id');
    const roleName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get role name from name column

    // Debug logging
    console.log('Delete clicked - Role ID:', roleId, 'Role Name:', roleName);

    if (!roleId) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Role ID tidak dijumpai. Sila refresh halaman dan cuba lagi.',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam peranan <b>${roleName}</b>?<br><small class="text-muted">Semua data berkaitan akan turut dipadam.</small>`,
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
        // Show loading state
        Swal.fire({
          title: 'Memproses...',
          text: 'Sedang memadam peranan dan data berkaitan',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        fetch(`/admin/roles/${roleId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
          }
        })
        .then(response => {
          console.log('Delete response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Delete response data:', data);
          if(data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Dipadam!',
              text: data.message || 'Peranan dan data berkaitan berjaya dipadam',
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable after the timer
              if (typeof window.dt_role !== 'undefined') {
                window.dt_role.ajax.reload();
              } else {
                $('.datatables-roles').DataTable().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Ralat semasa memadam peranan',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        })
        .catch(error => {
          console.error('Delete role error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ralat semasa memadam peranan. Sila cuba lagi.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        });
      }
    });
  });

  // Add click handler for bulk delete (if needed)
  $(document).on('click', '.delete-selected', function() {
    const selectedRoles = [];
    const selectedCheckboxes = $('.datatables-roles input[type="checkbox"]:checked, .datatables-role input[type="checkbox"]:checked');

    if (selectedCheckboxes.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Tiada Pilihan',
        text: 'Sila pilih sekurang-kurangnya satu peranan untuk dipadam',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    selectedCheckboxes.each(function() {
      if ($(this).val() !== 'on') { // Skip "select all" checkbox
        const roleId = $(this).val();
        const roleName = $(this).closest('tr').find('td:nth-child(3)').text().trim();
        selectedRoles.push({ id: roleId, name: roleName });
      }
    });

    if (selectedRoles.length === 0) return;

    const roleNames = selectedRoles.map(role => role.name).join(', ');

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam ${selectedRoles.length} peranan yang dipilih?<br><b>${roleNames}</b><br><small class="text-muted">Semua data berkaitan akan turut dipadam.</small>`,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: 'Tidak, batal!',
      confirmButtonText: 'Ya, padam semua!',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Show loading state
        Swal.fire({
          title: 'Memproses...',
          text: 'Sedang memadam peranan dan data berkaitan',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const roleIds = selectedRoles.map(role => role.id);

        fetch('/admin/roles/bulk-delete', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ role_ids: roleIds })
        })
        .then(response => response.json())
        .then(data => {
          if(data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Dipadam!',
              text: data.message || `${selectedRoles.length} peranan dan data berkaitan berjaya dipadam`,
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable after the timer
              if (typeof window.dt_role !== 'undefined') {
                window.dt_role.ajax.reload();
              } else {
                $('.datatables-roles').DataTable().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Ralat semasa memadam peranan',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        })
        .catch(error => {
          console.error('Bulk delete roles error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ralat semasa memadam peranan. Sila cuba lagi.',
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
