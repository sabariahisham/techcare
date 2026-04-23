'use strict';

import logger from '../logger.js';

// Initialize delete functionality when document is ready
$(document).ready(function () {
  logger.log('Permission delete script loaded');
  logger.log('SweetAlert2 available:', typeof Swal !== 'undefined');
  logger.log('jQuery available:', typeof $ !== 'undefined');
  logger.log('DataTables available:', typeof $.fn.DataTable !== 'undefined');

  // Test if delete buttons exist
  setTimeout(function () {
    const deleteButtons = $('.delete-permission');
    logger.log('Delete buttons found:', deleteButtons.length);
    if (deleteButtons.length > 0) {
      logger.log('First delete button:', deleteButtons.first());
      logger.log('First button data-id:', deleteButtons.first().data('id'));
    }
  }, 2000);
  // Add click handler to delete buttons
  $(document).on('click', '.delete-permission', function (e) {
    e.preventDefault();

    const permissionId = $(this).data('id');
    logger.log('Delete button clicked!');
    logger.log('Permission ID:', permissionId);
    logger.log('Button element:', this);

    if (!permissionId) {
      console.error('Permission ID not found');
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Permission ID not found. Please refresh the page.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      } else {
        alert('Permission ID not found. Please refresh the page.');
      }
      return;
    }

    // Get permission name from DataTable row data
    let permissionName = 'capaian ini'; // fallback in Malay

    try {
      // Find the DataTable row and get the permission name
      if ($.fn.DataTable.isDataTable('.datatables-permissions')) {
        const table = $('.datatables-permissions').DataTable();
        const row = $(this).closest('tr');

        if (row.length) {
          const rowData = table.row(row).data();
          logger.log('Row data:', rowData);
          if (rowData && rowData.name) {
            permissionName = rowData.name;
          }
        }
      }

      // Fallback: try to get from data attributes if DataTable method fails
      if (permissionName === 'capaian ini') {
        permissionName = $(this).data('name') || $(this).data('permission-name') || $(this).attr('data-name') || $(this).attr('title') || 'capaian ini';
      }
    } catch (error) {
      logger.log('Error getting permission name:', error);
      permissionName = 'capaian ini';
    }

    logger.log('Proceeding with delete for permission ID:', permissionId);
    logger.log('Permission name:', permissionName);

    // Check if SweetAlert2 is available
    if (typeof Swal === 'undefined') {
      const confirmDelete = confirm(`Adakah anda pasti ingin memadam ${permissionName}?`);
      if (confirmDelete) {
        executeDelete(permissionId);
      }
      return;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda pasti untuk memadam <strong>${permissionName}</strong>.<br><br>Tindakan ini tidak boleh dibatalkan!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Tidak, batal!',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        executeDelete(permissionId);
      }
    });
  });

  // Function to execute the delete operation
  function executeDelete(permissionId) {
    // Check if CSRF token exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    logger.log('CSRF Token:', csrfToken);
    logger.log('DELETE URL:', `/admin/permissions/${permissionId}`);
    logger.log('About to make fetch request...');

    if (!csrfToken) {
      const errorMsg = 'CSRF token not found. Please refresh the page.';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMsg,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      } else {
        alert(errorMsg);
      }
      return;
    }

    fetch(`/admin/permissions/${permissionId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(async response => {
        logger.log('Response status:', response.status);
        logger.log('Response headers:', response.headers);
        logger.log('Response URL:', response.url);

        if (!response.ok) {
          // Try to get error details from response
          let errorData = null;
          try {
            const errorResponse = await response.text();
            logger.log('Error response body:', errorResponse);
            errorData = JSON.parse(errorResponse);
          } catch (e) {
            logger.log('Could not parse error response');
          }

          // Handle 403 Forbidden specifically (permission validation errors)
          if (response.status === 403 && errorData && errorData.errors) {
            const errorMessage = errorData.message + ':\n\n' + errorData.errors.join('\n');

            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: 'Tidak Dapat Dipadam',
                html: errorMessage.replace(/\n/g, '<br>'),
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
            } else {
              alert(errorMessage);
            }
            return null; // Return null to indicate handled error
          }

          throw new Error(`HTTP error! status: ${response.status} - ${errorData ? JSON.stringify(errorData) : 'Unknown error'}`);
        }

        return response.json();
      })
      .then(data => {
        // Check if data is null (handled error case)
        if (data === null) {
          return; // Exit early, error was already handled
        }

        logger.log('Response data:', data);

        if (data && data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Dipadam!',
              text: data.message,
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              refreshDataTable();
            });
          } else {
            alert(data.message || 'Permission successfully deleted!');
            refreshDataTable();
          }
        } else if (data) {
          console.error('Delete failed:', data);
          const errorMsg = data.message || 'Ralat semasa memadam permission';
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: errorMsg,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          } else {
            alert(errorMsg);
          }
        }
      })
      .catch(error => {
        console.error('Delete error:', error);
        let errorMessage = 'Ralat semasa memadam permission. Sila cuba lagi.';

        if (error.message) {
          errorMessage = `Network error: ${error.message}`;
        }

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        } else {
          alert(errorMessage);
        }
      });
  }

  // Function to refresh DataTable
  function refreshDataTable() {
    logger.log('Reloading DataTable...');
    if ($.fn.DataTable.isDataTable('.datatables-permissions')) {
      $('.datatables-permissions').DataTable().ajax.reload();
    } else {
      logger.log('DataTable not initialized, reloading page');
      window.location.reload();
    }
  }

  // Global test function for debugging (can be called from browser console)
  window.testDeleteButton = function (permissionId) {
    if (permissionId) {
      logger.log('Testing delete for permission ID:', permissionId);
      executeDelete(permissionId);
    } else {
      logger.log('Available delete buttons:');
      $('.delete-permission').each(function (index) {
        logger.log(`Button ${index + 1}: data-id=${$(this).data('id')}`);
      });
    }
  };

  // Global test function for checking delete button event handlers
  window.testDeleteButtonEvents = function () {
    const deleteButtons = $('.delete-permission');
    logger.log('Total delete buttons:', deleteButtons.length);

    deleteButtons.each(function (index) {
      const button = $(this);
      const events = $._data(this, 'events') || {};
      logger.log(`Button ${index + 1}:`, {
        'data-id': button.data('id'),
        'class': button.attr('class'),
        'events': Object.keys(events)
      });
    });
  };

  // Global test function for testing permission deletion endpoint
  window.testPermissionRoute = function (permissionId) {
    if (!permissionId) {
      logger.log('Usage: testPermissionRoute(permissionId)');
      logger.log('Available delete buttons:');
      $('.delete-permission').each(function (index) {
        logger.log(`Button ${index + 1}: data-id=${$(this).data('id')}`);
      });
      return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    logger.log('Testing DELETE /admin/permissions/' + permissionId);
    logger.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');

    fetch(`/admin/permissions/${permissionId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(async response => {
        logger.log('Test response status:', response.status);
        const text = await response.text();
        logger.log('Test response body:', text);

        if (response.ok) {
          try {
            const data = JSON.parse(text);
            logger.log('Test parsed response:', data);
          } catch (e) {
            logger.log('Response is not JSON');
          }
        }

        return response;
      })
      .catch(error => {
        console.error('Test request error:', error);
      });
  };
});
