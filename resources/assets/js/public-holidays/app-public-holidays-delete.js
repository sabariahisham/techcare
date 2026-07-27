/**
 * Public Holiday - Delete
 */

'use strict';

import logger from '../logger.js';

/**
 * Sanitize input to prevent XSS
 */
function sanitizeInput(input) {
  if (typeof input !== 'string') {
    return '';
  }

  const div = document.createElement('div');
  div.textContent = input;
  return div.innerHTML;
}

// Delete public holiday
async function deletePublicHoliday(holidayId) {
  // Validate holiday ID
  if (!holidayId || isNaN(holidayId)) {
    toastr.error('ID cuti umum tidak sah', 'Ralat!');
    return;
  }

  // Show confirmation dialog
  Swal.fire({
    title: 'Pengesahan Pemadaman',
    text: 'Adakah anda pasti ingin memadam cuti umum ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Padam!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      // Disable button and show loading
      const deleteBtn = document.querySelector(`button[data-holiday-id="${holidayId}"][data-action="delete"]`);
      if (deleteBtn) {
        deleteBtn.disabled = true;
      }

      toastr.info('Sedang memadam...');

      // Submit DELETE request via AJAX
      $.ajax({
        url: `/public-holidays/${sanitizeInput(holidayId.toString())}/delete`,
        type: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        },
        success: function (response) {
          logger.log('Delete Success Response:', response);

          if (response.success) {
            toastr.success(response.message || 'Cuti umum berhasil dipadam', 'Berjaya!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              timeOut: 5000
            });

            // Reload page after delay
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            toastr.error(response.message || 'Gagal memadam cuti umum', 'Ralat!');
            if (deleteBtn) {
              deleteBtn.disabled = false;
            }
          }
        },
        error: function (xhr, status, error) {
          logger.error('Delete Error:', {
            status,
            error,
            response: xhr.responseJSON
          });

          let errorMessage = 'Ralat semasa memadam cuti umum';

          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.status === 401) {
            errorMessage = 'Anda tidak sah masuk';
          } else if (xhr.status === 403) {
            errorMessage = 'Anda tidak mempunyai kebenaran untuk melakukan tindakan ini';
          } else if (xhr.status === 404) {
            errorMessage = 'Cuti umum tidak ditemui';
          } else if (xhr.status === 500) {
            errorMessage = 'Ralat pelayan dalaman';
          }

          toastr.error(errorMessage, 'Ralat!');

          if (deleteBtn) {
            deleteBtn.disabled = false;
          }
        }
      });
    }
  });
}

// Export for use in blade template
window.deletePublicHoliday = deletePublicHoliday;
