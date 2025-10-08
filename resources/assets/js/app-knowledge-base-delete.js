'use strict';

/**
 * Knowledge Base Article Delete Functionality
 * Handles knowledge base article deletion with comprehensive validation and error handling
 */

$(function () {

  /**
   * Validate knowledge base article deletion prerequisites
   * @param {number} articleId - The ID of the article to validate
   * @returns {Promise<boolean>} - Returns true if validation passes
   */
  async function validateArticleDeletion(articleId) {
    try {
      // Basic validation
      if (!articleId || isNaN(articleId) || articleId <= 0) {
        throw new Error('Invalid article ID provided');
      }

      // The controller will handle checking if the article exists
      return true;
    } catch (error) {
      console.error('Article validation error:', error);
      throw error;
    }
  }

  /**
   * Show confirmation dialog for article deletion
   * @param {number} articleId - The ID of the article to delete
   * @param {string} articleTitle - The title of the article (optional)
   * @returns {Promise<boolean>} - Returns true if user confirms deletion
   */
  function showDeleteConfirmation(articleId, articleTitle = 'this article') {
    return new Promise((resolve) => {
      Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete <strong>${articleTitle}</strong>.<br><br>You won't be able to revert this!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true
      }).then((result) => {
        resolve(result.isConfirmed);
      });
    });
  }

  /**
   * Perform the actual article deletion
   * @param {number} articleId - The ID of the article to delete
   * @returns {Promise<Object>} - Returns the response data
   */
  async function performArticleDeletion(articleId) {
    try {
      const response = await $.ajax({
        type: 'DELETE',
        url: `/knowledgebase/${articleId}`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      return {
        success: true,
        message: 'Knowledge Base article has been deleted successfully.'
      };
    } catch (error) {
      console.error('Article deletion error:', error);
      
      let errorMessage = 'An error occurred while deleting the knowledge base article.';
      if (error.responseJSON && error.responseJSON.message) {
        errorMessage = error.responseJSON.message;
      } else if (error.statusText) {
        errorMessage = `Error: ${error.statusText}`;
      }
      
      throw new Error(errorMessage);
    }
  }

  /**
   * Show success notification
   * @param {string} message - Success message to display
   */
  function showSuccessNotification(message) {
    toastr.success(message || 'Knowledge Base article has been deleted successfully.');
    
    // Refresh the DataTable after successful deletion
    if (window.dt_knowledgebase && typeof window.dt_knowledgebase.ajax === 'object') {
      window.dt_knowledgebase.ajax.reload();
    }
  }

  /**
   * Show error notification
   * @param {string} message - Error message to display
   */
  function showErrorNotification(message) {
    toastr.error(message || 'An error occurred while deleting the knowledge base article.');
  }

  /**
   * Main delete article function
   * @param {number} articleId - The ID of the article to delete
   * @param {string} articleTitle - The title of the article (optional, for display purposes)
   */
  async function deleteArticle(articleId, articleTitle = 'Unknown Article') {
    try {
      // Step 1: Validate the deletion
      await validateArticleDeletion(articleId);

      // Step 2: Show confirmation dialog
      const confirmed = await showDeleteConfirmation(articleId, articleTitle);

      if (!confirmed) {
        return; // User cancelled
      }

      // Step 3: Show loading state
      Swal.fire({
        title: 'Deleting...',
        text: 'Please wait while we delete the article.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Step 4: Perform the deletion
      const result = await performArticleDeletion(articleId);

      // Step 5: Close loading dialog and show success notification
      Swal.close();
      showSuccessNotification(result.message);

    } catch (error) {
      // Close any open Swal dialogs and show error notification
      Swal.close();
      showErrorNotification(error.message);
    }
  }

  /**
   * Event handler for delete buttons
   * Handles clicks on elements with class 'delete-record' or 'delete-article'
   */
  $(document).on('click', '.delete-record, .delete-article', function(e) {
    e.preventDefault();

    const articleId = $(this).data('id');
    const articleTitle = $(this).data('title') || $(this).data('name') || 'this article';

    // Validate that we have the required data
    if (!articleId) {
      showErrorNotification('Article ID is required for deletion.');
      return;
    }

    // Call the delete function
    deleteArticle(articleId, articleTitle);
  });

  /**
   * Bulk delete functionality
   * Handles deletion of multiple articles at once
   */
  function bulkDeleteArticles(articleIds) {
    if (!Array.isArray(articleIds) || articleIds.length === 0) {
      showErrorNotification('No articles selected for deletion.');
      return;
    }

    Swal.fire({
      title: 'Bulk Delete Confirmation',
      html: `You are about to delete <strong>${articleIds.length}</strong> articles.<br><br>This action cannot be undone!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: `Yes, delete ${articleIds.length} articles!`,
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
            title: 'Deleting articles...',
            text: 'Please wait while we delete the selected articles.',
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
          const errors = [];

          for (const articleId of articleIds) {
            try {
              await validateArticleDeletion(articleId);
              await performArticleDeletion(articleId);
              successCount++;
            } catch (error) {
              console.error(`Failed to delete article ${articleId}:`, error);
              errorCount++;
              errors.push(`Article ${articleId}: ${error.message}`);
            }
          }

          // Show final result
          Swal.close();
          
          if (errorCount === 0) {
            showSuccessNotification(`Successfully deleted ${successCount} articles.`);
          } else if (successCount === 0) {
            showErrorNotification(`Failed to delete all ${errorCount} articles.`);
            console.error('Bulk delete errors:', errors);
          } else {
            toastr.warning(`Deleted ${successCount} articles successfully. Failed to delete ${errorCount} articles.`);
            console.warn('Partial bulk delete errors:', errors);
            
            // Refresh DataTable on partial success
            if (window.dt_knowledgebase && typeof window.dt_knowledgebase.ajax === 'object') {
              window.dt_knowledgebase.ajax.reload();
            }
          }

        } catch (error) {
          Swal.close();
          showErrorNotification('An error occurred during bulk deletion.');
        }
      }
    });
  }

  /**
   * Event handler for bulk delete button
   */
  $(document).on('click', '.bulk-delete-articles', function(e) {
    e.preventDefault();

    const selectedIds = [];
    $('.article-checkbox:checked').each(function() {
      const articleId = parseInt($(this).val());
      if (articleId) {
        selectedIds.push(articleId);
      }
    });

    bulkDeleteArticles(selectedIds);
  });

  // Expose functions globally if needed for external access
  window.KnowledgeBaseDelete = {
    deleteArticle: deleteArticle,
    bulkDeleteArticles: bulkDeleteArticles,
    validateArticleDeletion: validateArticleDeletion
  };

  console.log('Knowledge Base article delete functionality initialized');
});