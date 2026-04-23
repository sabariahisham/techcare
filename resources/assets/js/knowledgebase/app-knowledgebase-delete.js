import logger from '../logger.js';

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
   * Fetch article details including title and documents
   * @param {number} articleId - The ID of the article
   * @returns {Promise<Object>} - Returns article details
   */
  async function fetchArticleDetails(articleId) {
    try {
      const response = await $.ajax({
        type: 'GET',
        url: `/knowledgebase/${articleId}/details`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      return {
        title: response.kb_title || 'Unknown Article',
        documents: response.documents || [],
        hasDocuments: response.documents && response.documents.length > 0
      };
    } catch (error) {
      console.warn('Could not fetch article details:', error);
      // Return basic info if we can't fetch details
      return {
        title: 'this article',
        documents: [],
        hasDocuments: false
      };
    }
  }

  /**
   * Show confirmation dialog for article deletion
   * @param {number} articleId - The ID of the article to delete
   * @param {Object} articleDetails - Article details including title and documents
   * @param {boolean} allowFileOptions - Whether to show file handling options (default: true)
   * @returns {Promise<Object>} - Returns {confirmed: boolean, unlinkFiles: boolean}
   */
  function showDeleteConfirmation(articleId, articleDetails, allowFileOptions = true) {
    return new Promise((resolve) => {
      let confirmationMessage = `Anda akan memadam artikel <strong>"${articleDetails.title}"</strong>.`;

      if (articleDetails.hasDocuments) {
        const docCount = articleDetails.documents.length;
        const docText = docCount === 1 ? 'dokumen' : 'dokumen';

        // Show document names
        let documentList = '<ul class="text-start mt-2 mb-0">';
        articleDetails.documents.forEach(doc => {
          documentList += `<li><i class="bx bx-file me-1"></i>${doc.doc_name || doc.name || 'Unnamed Document'}</li>`;
        });
        documentList += '</ul>';

        confirmationMessage += `<br><br><span class="text-warning"><i class="bx bx-files me-1"></i>Artikel ini mempunyai <strong>${docCount} ${docText}</strong> yang akan turut dipadam (soft delete):</span>${documentList}`;
        confirmationMessage += `<div class="alert alert-warning mt-3">
          <i class="bx bx-exclamation-triangle me-2"></i>Artikel dan dokumen akan dipadam (soft delete) dari database. Fail dokumen akan dibuang <strong>kekal dari storage</strong>.
        </div>`;
      } else {
        // No documents case - show soft delete info for article only
        confirmationMessage += `<br><br><div class="alert alert-info mt-3">
          <i class="bx bx-info-circle me-2"></i>Artikel ini akan dipadam (soft delete) dari database.
        </div>`;
      }

      Swal.fire({
        title: 'Adakah anda pasti?',
        html: confirmationMessage,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, padamkan!',
        cancelButtonText: 'Batal',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true,
        width: '600px',
        preConfirm: () => {
          // For soft delete, soft delete records and permanently delete files from storage
          return {
            confirmed: true
          };
        }
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          resolve(result.value);
        } else {
          resolve({
            confirmed: false,
            unlinkFiles: false
          });
        }
      });
    });
  }
  /**
   * Perform the actual article soft deletion
   * @param {number} articleId - The ID of the article to delete
   * @returns {Promise<Object>} - Returns the response data
   */
  async function performArticleDeletion(articleId) {
    try {
      const response = await $.ajax({
        type: 'POST', // Using POST for soft delete
        url: `/knowledgebase/${articleId}/soft-delete`,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
          'soft_delete': true,
          'include_documents': true, // Include documents in soft delete
          'unlink_files': true, // Always remove files from storage during soft delete
          'preserve_files': false // Do not preserve physical files when soft deleting
        }
      });

      return {
        success: true,
        message: 'Artikel Knowledge Base telah berjaya dipadam (soft delete). Fail dokumen telah dibuang kekal dari storage.',
        deletedDocuments: response.deleted_documents || 0,
        unlinkedFiles: response.unlinked_files || 0
      };
    } catch (error) {
      console.error('Article deletion error:', error);

      let errorMessage = 'Ralat berlaku semasa memadam artikel knowledge base.';
      if (error.responseJSON && error.responseJSON.message) {
        errorMessage = error.responseJSON.message;
      } else if (error.status === 404) {
        errorMessage = 'Artikel tidak dijumpai atau telah dipadam.';
      } else if (error.status === 403) {
        errorMessage = 'Anda tidak mempunyai kebenaran untuk memadam artikel ini.';
      } else if (error.statusText) {
        errorMessage = `Ralat: ${error.statusText}`;
      }

      throw new Error(errorMessage);
    }
  }



  /**
   * Show success notification
   * @param {string} message - Success message to display
   * @param {number} deletedDocuments - Number of documents deleted
   * @param {number} unlinkedFiles - Number of files removed from storage
   */
  function showSuccessNotification(message, deletedDocuments = 0, unlinkedFiles = 0) {
    let successMessage = message || 'Artikel Knowledge Base telah berjaya dipadam.';

    toastr.success(successMessage);

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
    toastr.error(message || 'Ralat berlaku semasa memadam artikel knowledge base.');
  }

  /**
   * Main delete article function
   * @param {number} articleId - The ID of the article to delete
   * @param {string} articleTitle - The title of the article (optional, for display purposes)
   */
  async function deleteArticle(articleId, articleTitle = null) {
    try {
      // Step 1: Validate the deletion
      await validateArticleDeletion(articleId);

      // Step 2: Fetch article details including title and documents
      let articleDetails;
      if (articleTitle) {
        // If title is provided from button data, use it
        articleDetails = {
          title: articleTitle,
          documents: [],
          hasDocuments: false
        };

        // Still try to fetch documents info
        try {
          const fetchedDetails = await fetchArticleDetails(articleId);
          articleDetails.documents = fetchedDetails.documents;
          articleDetails.hasDocuments = fetchedDetails.hasDocuments;
        } catch (error) {
          console.warn('Could not fetch documents info:', error);
        }
      } else {
        // Fetch full article details
        articleDetails = await fetchArticleDetails(articleId);
      }

      // Step 3: Show confirmation dialog
      const confirmResult = await showDeleteConfirmation(articleId, articleDetails);

      if (!confirmResult.confirmed) {
        return; // User cancelled
      }

      // Step 4: Show loading state
      Swal.fire({
        title: 'Memadam artikel...',
        text: 'Sila tunggu semasa kami memadam artikel dan dokumen berkaitan.',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Step 5: Perform the deletion
      const result = await performArticleDeletion(articleId);

      // Step 6: Close loading dialog and show success notification
      Swal.close();
      showSuccessNotification(result.message, result.deletedDocuments, result.unlinkedFiles);

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
  $(document).on('click', '.delete-record, .delete-article', function (e) {
    e.preventDefault();

    // Check if user has a role
    if (typeof window.userHasRole !== 'undefined' && !window.userHasRole) {
      Swal.fire({
        title: 'Akses Ditolak',
        html: 'Anda tidak mempunyai kebenaran untuk menghapus artikel.<br><small class="text-muted">Hanya pengguna dengan peranan yang ditetapkan boleh menghapus artikel.</small>',
        icon: 'error',
        confirmButtonColor: '#696cff',
        confirmButtonText: 'Faham'
      });
      return;
    }

    const $button = $(this);
    const articleId = $button.data('id');
    let articleTitle = $button.data('title') || $button.data('name');

    // Try to get title from the same row in DataTable
    if (!articleTitle && window.dt_knowledgebase) {
      try {
        const $row = $button.closest('tr');
        const rowData = window.dt_knowledgebase.row($row).data();
        if (rowData && rowData.kb_title) {
          articleTitle = rowData.kb_title;
        }
      } catch (error) {
        console.warn('Could not get title from DataTable row:', error);
      }
    }

    // Validate that we have the required data
    if (!articleId) {
      showErrorNotification('ID artikel diperlukan untuk pemadaman.');
      return;
    }

    // Call the delete function
    deleteArticle(articleId, articleTitle);
  });





  // Expose functions globally if needed for external access
  window.KnowledgeBaseDelete = {
    deleteArticle: deleteArticle,
    validateArticleDeletion: validateArticleDeletion,
    fetchArticleDetails: fetchArticleDetails,
    performArticleDeletion: performArticleDeletion
  };

  logger.log('Knowledge Base article delete functionality initialized with soft delete support and permanent file deletion');
});
