'use strict';

// Initialize Role delete functionality when document is ready
$(function () {
  console.log('Role delete script loaded');

  // Add click handler to Role delete buttons
  $(document).on('click', '.delete-role, .delete-record', function(e) {
    e.preventDefault();
    console.log('Delete button clicked');
    const roleId = $(this).data('id');
    const roleName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get role name from name column

    // Get the DataTable instance and row data to access user information
    let dt_role_instance = $('.datatables-roles').DataTable();
    let rowData = dt_role_instance.row($(this).closest('tr')).data();

    // Extract user names
    let userNames = [];
    if (rowData && rowData.user_names && Array.isArray(rowData.user_names)) {
      userNames = rowData.user_names;
    }

    // Debug logging
    console.log('Delete clicked - Role ID:', roleId, 'Role Name:', roleName, 'Users:', userNames);

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

    // Prepare confirmation message with user details
    let confirmMessage = `Anda ingin memadam peranan <b>${roleName}</b>?`;

    if (userNames.length > 0) {
      const userList = userNames.map(name => `<span class="badge bg-label-warning me-1">${name}</span>`).join('');
      confirmMessage += `<br><br><strong>Pengguna yang akan terjejas:</strong><br><div class="mt-2">${userList}</div>`;
      confirmMessage += `<br><small class="text-muted">Semua ${userNames.length} pengguna akan kehilangan peranan ini dan data berkaitan akan turut dipadam.</small>`;
    } else {
      confirmMessage += `<br><small class="text-muted">Tiada pengguna diberikan peranan ini. Semua data berkaitan akan turut dipadam.</small>`;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: confirmMessage,
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
            let successMessage = data.message || 'Peranan dan data berkaitan berjaya dipadam';
            if (userNames.length > 0) {
              successMessage += `. ${userNames.length} pengguna telah kehilangan peranan ini.`;
            }

            Swal.fire({
              icon: 'success',
              title: 'Dipadam!',
              text: successMessage,
              timer: 3000,
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

    // Get DataTable instance for accessing row data
    let dt_role_instance = $('.datatables-roles').DataTable();
    let totalAffectedUsers = 0;
    let allAffectedUsers = [];

    selectedCheckboxes.each(function() {
      if ($(this).val() !== 'on') { // Skip "select all" checkbox
        const roleId = $(this).val();
        const roleName = $(this).closest('tr').find('td:nth-child(3)').text().trim();

        // Get row data to access user information
        let rowData = dt_role_instance.row($(this).closest('tr')).data();
        let userNames = [];
        if (rowData && rowData.user_names && Array.isArray(rowData.user_names)) {
          userNames = rowData.user_names;
          totalAffectedUsers += userNames.length;
          allAffectedUsers = [...allAffectedUsers, ...userNames];
        }

        selectedRoles.push({
          id: roleId,
          name: roleName,
          users: userNames
        });
      }
    });

    if (selectedRoles.length === 0) return;

    const roleNames = selectedRoles.map(role => role.name).join(', ');

    // Remove duplicates from affected users list
    const uniqueAffectedUsers = [...new Set(allAffectedUsers)];

    // Prepare confirmation message
    let confirmMessage = `Anda ingin memadam ${selectedRoles.length} peranan yang dipilih?<br><b>${roleNames}</b>`;

    if (uniqueAffectedUsers.length > 0) {
      const userList = uniqueAffectedUsers.slice(0, 10).map(name => `<span class="badge bg-label-warning me-1 mb-1">${name}</span>`).join('');
      const remainingCount = uniqueAffectedUsers.length - 10;

      confirmMessage += `<br><br><strong>Pengguna yang akan terjejas (${uniqueAffectedUsers.length} pengguna):</strong><br><div class="mt-2">${userList}`;
      if (remainingCount > 0) {
        confirmMessage += `<br><small class="text-muted">...dan ${remainingCount} pengguna lagi</small>`;
      }
      confirmMessage += `</div><br><small class="text-muted">Semua pengguna ini akan kehilangan peranan mereka dan data berkaitan akan turut dipadam.</small>`;
    } else {
      confirmMessage += `<br><small class="text-muted">Tiada pengguna diberikan peranan-peranan ini. Semua data berkaitan akan turut dipadam.</small>`;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: confirmMessage,
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
            let successMessage = data.message || `${selectedRoles.length} peranan dan data berkaitan berjaya dipadam`;
            if (uniqueAffectedUsers.length > 0) {
              successMessage += `. ${uniqueAffectedUsers.length} pengguna telah kehilangan peranan mereka.`;
            }

            Swal.fire({
              icon: 'success',
              title: 'Dipadam!',
              text: successMessage,
              timer: 3000,
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
