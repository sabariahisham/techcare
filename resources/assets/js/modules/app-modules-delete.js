'use strict';

/**
 * Module Delete Functionality
 * Handles module deletion with comprehensive validation, permissions display, and error handling
 * Features:
 * - Fetches and displays module details including associated permissions
 * - Shows confirmation dialog with module name and permissions list
 * - Automatically deletes associated permissions when deleting a module
 * - Supports both single and bulk deletion operations
 */

$(function () {

  /**
   * Validate module deletion prerequisites and fetch module details
   * @param {number} moduleId - The ID of the module to validate
   * @returns {Promise<Object>} - Returns module details if validation passes
   */
  async function validateModuleDeletion(moduleId) {
    try {
      // Basic validation
      if (!moduleId || isNaN(moduleId) || moduleId <= 0) {
        throw new Error('ID modul tidak sah');
      }

      // Fetch module details including permissions
      const response = await fetch(`/modules/${moduleId}/details`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `Ralat HTTP! status: ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message || 'Gagal mendapatkan maklumat modul');
      }

      return result.data;
    } catch (error) {
      console.error('Ralat validasi modul:', error);
      throw error;
    }
  }

  /**
   * Show confirmation dialog for module deletion
   * @param {Object} moduleData - The module data including permissions
   * @returns {Promise<boolean>} - Returns true if user confirms deletion
   */
  function showDeleteConfirmation(moduleData) {
    return new Promise((resolve) => {
      // Build permissions list HTML
      let permissionsHtml = '';
      if (moduleData.permissions && moduleData.permissions.length > 0) {
        permissionsHtml = `
          <div class="mt-3">
            <strong>Capaian yang akan dipadam (${moduleData.permissions_count}):</strong>
            <div class="mt-2" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
              <ul class="mb-0" style="padding-left: 20px;">
                ${moduleData.permissions.map(permission => `
                  <li style="margin-bottom: 5px;">
                    <code>${permission.name}</code>
                    ${permission.guard_name ? ` <small class="text-muted">(${permission.guard_name})</small>` : ''}
                  </li>
                `).join('')}
              </ul>
            </div>
          </div>
        `;
      } else {
        permissionsHtml = `
          <div class="mt-3">
            <span class="text-muted">Tiada capaian yang berkaitan dengan modul ini.</span>
          </div>
        `;
      }

      const html = `
        <div style="text-align: left;">
          <p>Anda pasti untuk memadam modul <strong>${moduleData.module_name}</strong>?</p>
          ${permissionsHtml}
          <div class="mt-3 p-2" style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <small class="text-warning">
              <i class="ti ti-alert-triangle"></i>
              <strong>Amaran:</strong> Tindakan ini tidak boleh dibatalkan!
            </small>
          </div>
        </div>
      `;

      Swal.fire({
        title: 'Adakah anda pasti?',
        html: html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, padam!',
        cancelButtonText: 'Tidak, batalkan!',
        customClass: {
          confirmButton: 'btn btn-danger me-3',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true,
        width: '600px'
      }).then((result) => {
        resolve(result.isConfirmed);
      });
    });
  }

  /**
   * Perform the actual module deletion
   * @param {number} moduleId - The ID of the module to delete
   * @returns {Promise<Object>} - Returns the response data
   */
  async function performModuleDeletion(moduleId) {
    try {
      const response = await fetch(`/modules/${moduleId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `Ralat HTTP! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('Ralat pemadaman modul:', error);
      throw error;
    }
  }

  /**
   * Show success notification
   * @param {string} message - Success message to display
   */
  function showSuccessNotification(message) {
    Swal.fire({
      icon: 'success',
      title: 'Berjaya Dipadam!',
      text: message || 'Modul dan capaian berkaitan telah berjaya dipadam.',
      timer: 3000,
      showConfirmButton: false,
      customClass: {
        confirmButton: 'btn btn-success'
      },
      buttonsStyling: false
    }).then(() => {
      // Refresh the DataTable after successful deletion
      if ($.fn.DataTable && $('.datatables-permissions').length) {
        $('.datatables-permissions').DataTable().ajax.reload();
      } else if ($.fn.DataTable && $('.datatables-modules').length) {
        $('.datatables-modules').DataTable().ajax.reload();
      }
    });
  }

  /**
   * Show error notification
   * @param {string} message - Error message to display
   */
  function showErrorNotification(message) {
    Swal.fire({
      icon: 'error',
      title: 'Pemadaman Gagal',
      text: message || 'Ralat berlaku semasa memadam modul.',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  /**
   * Main delete module function
   * @param {number} moduleId - The ID of the module to delete
   * @param {string} moduleName - The name of the module (optional, for display purposes - will be overridden by fetched data)
   */
  async function deleteModule(moduleId, moduleName = 'Modul Tidak Dikenali') {
    try {
      // Step 1: Validate the deletion and fetch module details
      const moduleData = await validateModuleDeletion(moduleId);

      // Step 2: Show confirmation dialog with module details and permissions
      const confirmed = await showDeleteConfirmation(moduleData);

      if (!confirmed) {
        return; // Pengguna membatalkan
      }

      // Step 3: Show loading state
      Swal.fire({
        title: 'Memadam Modul...',
        html: `Sedang memadam <strong>${moduleData.module_name}</strong> dan ${moduleData.permissions_count} capaian...`,
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Step 4: Perform the deletion
      const result = await performModuleDeletion(moduleId);

      // Step 5: Show success notification
      if (result.success) {
        showSuccessNotification(result.message);
      } else {
        showErrorNotification(result.message || 'Ralat tidak dikenali berlaku');
      }

    } catch (error) {
      // Show error notification
      showErrorNotification(error.message);
    }
  }

  /**
   * Event handler for delete buttons
   */
  $(document).on('click', '.delete-module', function(e) {
    e.preventDefault();

    const moduleId = $(this).data('id');
    const moduleName = $(this).data('name') || 'Modul Tidak Dikenali';

    // Validate that we have the required data
    if (!moduleId) {
      showErrorNotification('ID Modul diperlukan untuk pemadaman.');
      return;
    }

    // Call the delete function
    deleteModule(moduleId, moduleName);
  });

  /**
   * Bulk delete functionality (optional)
   * Handles deletion of multiple modules at once
   */
  function bulkDeleteModules(moduleIds) {
    if (!Array.isArray(moduleIds) || moduleIds.length === 0) {
      showErrorNotification('Tiada modul dipilih untuk pemadaman.');
      return;
    }

    Swal.fire({
      title: 'Pengesahan Pemadaman Beramai-ramai',
      html: `Anda akan memadam <strong>${moduleIds.length}</strong> modul.<br><br>Tindakan ini tidak boleh dibatalkan!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: `Ya, padam ${moduleIds.length} modul!`,
      cancelButtonText: 'Batalkan',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          Swal.fire({
            title: 'Memadam modul...',
            text: 'Sila tunggu semasa kami memadam modul yang dipilih.',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Process deletions sequentially to avoid overwhelming the server
          let successCount = 0;
          let errorCount = 0;

          for (const moduleId of moduleIds) {
            try {
              const moduleData = await validateModuleDeletion(moduleId);
              await performModuleDeletion(moduleId);
              successCount++;
            } catch (error) {
              console.error(`Gagal memadam modul ${moduleId}:`, error);
              errorCount++;
            }
          }

          // Show final result
          if (errorCount === 0) {
            showSuccessNotification(`Berjaya memadam ${successCount} modul.`);
          } else if (successCount === 0) {
            showErrorNotification(`Gagal memadam semua ${errorCount} modul.`);
          } else {
            Swal.fire({
              icon: 'warning',
              title: 'Sebahagian Berjaya',
              text: `Berjaya memadam ${successCount} modul. Gagal memadam ${errorCount} modul.`,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            }).then(() => {
              if ($.fn.DataTable && $('.datatables-permissions').length) {
                $('.datatables-permissions').DataTable().ajax.reload();
              } else if ($.fn.DataTable && $('.datatables-modules').length) {
                $('.datatables-modules').DataTable().ajax.reload();
              }
            });
          }

        } catch (error) {
          showErrorNotification('Ralat berlaku semasa pemadaman beramai-ramai.');
        }
      }
    });
  }

  /**
   * Event handler for bulk delete button (if you have one)
   */
  $(document).on('click', '.bulk-delete-modules', function(e) {
    e.preventDefault();

    const selectedIds = [];
    $('.module-checkbox:checked').each(function() {
      const moduleId = parseInt($(this).val());
      if (moduleId) {
        selectedIds.push(moduleId);
      }
    });

    bulkDeleteModules(selectedIds);
  });

  // Expose functions globally if needed
  window.ModuleDelete = {
    deleteModule: deleteModule,
    bulkDeleteModules: bulkDeleteModules,
    validateModuleDeletion: validateModuleDeletion
  };

  console.log('Fungsi pemadaman modul telah diinisialisasi');
});
