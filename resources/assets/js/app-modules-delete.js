'use strict';

/**
 * Module Delete Functionality
 * Handles module deletion with comprehensive validation and error handling
 */

$(function () {

  /**
   * Validate module deletion prerequisites
   * @param {number} moduleId - The ID of the module to validate
   * @returns {Promise<boolean>} - Returns true if validation passes
   */
  async function validateModuleDeletion(moduleId) {
    try {
      // Basic validation
      if (!moduleId || isNaN(moduleId) || moduleId <= 0) {
        throw new Error('Invalid module ID provided');
      }

      // Since we don't have a GET endpoint for individual modules,
      // we'll rely on the server-side validation in the destroy method
      // The controller will handle checking if the module exists and
      // will also delete associated permissions automatically

      return true;
    } catch (error) {
      console.error('Module validation error:', error);
      throw error;
    }
  }

  /**
   * Show confirmation dialog for module deletion
   * @param {number} moduleId - The ID of the module to delete
   * @param {string} moduleName - The name of the module
   * @returns {Promise<boolean>} - Returns true if user confirms deletion
   */
  function showDeleteConfirmation(moduleId, moduleName) {
    return new Promise((resolve) => {
      Swal.fire({
        title: 'Are you sure?',
        html: `Anda pasti untuk memadam <strong>${moduleName}</strong>.<br><br>Tindakan ini tidak boleh dibatalkan!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, padam!',
        cancelButtonText: 'Tidak, batalkan!',
        customClass: {
          confirmButton: 'btn btn-danger me-3',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true
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
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('Module deletion error:', error);
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
      title: 'Successfully Deleted!',
      text: message || 'Module has been deleted successfully.',
      timer: 2000,
      showConfirmButton: false,
      customClass: {
        confirmButton: 'btn btn-success'
      },
      buttonsStyling: false
    }).then(() => {
      // Refresh the DataTable after successful deletion
      if ($.fn.DataTable && $('.datatables-modules').length) {
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
      title: 'Deletion Failed',
      text: message || 'An error occurred while deleting the module.',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  /**
   * Main delete module function
   * @param {number} moduleId - The ID of the module to delete
   * @param {string} moduleName - The name of the module (optional, for display purposes)
   */
  async function deleteModule(moduleId, moduleName = 'Unknown Module') {
    try {
      // Step 1: Validate the deletion
      await validateModuleDeletion(moduleId);

      // Step 2: Show confirmation dialog
      const confirmed = await showDeleteConfirmation(moduleId, moduleName);

      if (!confirmed) {
        return; // User cancelled
      }

      // Step 3: Show loading state
      Swal.fire({
        title: 'Deleting...',
        text: 'Please wait while we delete the module.',
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
        showErrorNotification(result.message || 'Unknown error occurred');
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
    const moduleName = $(this).data('name') || 'Unknown Module';

    // Validate that we have the required data
    if (!moduleId) {
      showErrorNotification('Module ID is required for deletion.');
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
      showErrorNotification('No modules selected for deletion.');
      return;
    }

    Swal.fire({
      title: 'Bulk Delete Confirmation',
      html: `You are about to delete <strong>${moduleIds.length}</strong> modules.<br><br>This action cannot be undone!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: `Yes, delete ${moduleIds.length} modules!`,
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          Swal.fire({
            title: 'Deleting modules...',
            text: 'Please wait while we delete the selected modules.',
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
              await validateModuleDeletion(moduleId);
              await performModuleDeletion(moduleId);
              successCount++;
            } catch (error) {
              console.error(`Failed to delete module ${moduleId}:`, error);
              errorCount++;
            }
          }

          // Show final result
          if (errorCount === 0) {
            showSuccessNotification(`Successfully deleted ${successCount} modules.`);
          } else if (successCount === 0) {
            showErrorNotification(`Failed to delete all ${errorCount} modules.`);
          } else {
            Swal.fire({
              icon: 'warning',
              title: 'Partial Success',
              text: `Deleted ${successCount} modules successfully. Failed to delete ${errorCount} modules.`,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            }).then(() => {
              if ($.fn.DataTable && $('.datatables-modules').length) {
                $('.datatables-modules').DataTable().ajax.reload();
              }
            });
          }

        } catch (error) {
          showErrorNotification('An error occurred during bulk deletion.');
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

  console.log('Module delete functionality initialized');
});
