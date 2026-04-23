import logger from '../logger.js';

'use strict';

// Add custom styles for better subcategory display
const customStyles = `
<style>
.swal2-large {
  font-size: 0.95rem !important;
}
.swal2-large .swal2-html-container {
  font-size: 0.9rem !important;
  line-height: 1.5 !important;
}
</style>
`;

// Inject styles if not already present
if (!document.querySelector('#category-delete-styles')) {
  const styleElement = document.createElement('div');
  styleElement.id = 'category-delete-styles';
  styleElement.innerHTML = customStyles;
  document.head.appendChild(styleElement);
}

// All messages centralized here
const messages = {
  // Initial delete confirmation
  confirmTitle: 'Adakah anda pasti?',
  confirmText: 'Anda ingin memadam kategori <b>{categoryName}</b>?<br><br>' +
    '<small class="text-muted">Sistem akan memeriksa sama ada kategori ini mempunyai sub-kategori.</small>',
  confirmButton: 'Ya, padam!',
  cancelButton: 'Tidak, batal!',

  // Sub-categories warning
  subcategoriesTitle: 'Kategori mempunyai Sub-Kategori',
  subcategoriesText: 'Kategori <b>{categoryName}</b> mempunyai <b>{subcategoriesCount}</b> sub-kategori berikut:<br><br>' +
    '<div class="text-start mt-2 mb-3" style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; max-height: 200px; overflow-y: auto;">' +
    '{subcategoriesList}' +
    '</div>' +
    '<div class="alert alert-warning mt-3 mb-0">' +
    '<i class="bx bx-warning me-2"></i>' +
    '<strong>Amaran:</strong> Tindakan ini akan memadam kategori ini berserta SEMUA sub-kategorinya dan tidak boleh dibatalkan!' +
    '</div>',
  forceDeleteButton: 'Ya, padam semua!',

  // Success messages
  successTitle: 'Dipadam!',
  successText: 'Kategori berjaya dipadam',
  successForceText: 'Kategori dan semua sub-kategorinya berjaya dipadam',

  // Error messages
  errorTitle: 'Ralat',
  errorText: 'Ralat berlaku semasa memadam kategori',

  // Loading messages
  processingTitle: 'Memproses...',
  processingText: 'Sedang memadam kategori dan sub-kategori',

  // Button classes
  btnDanger: 'btn btn-danger me-3',
  btnSecondary: 'btn btn-secondary',
  btnSuccess: 'btn btn-success',
  btnPrimary: 'btn btn-primary'
};

// Initialize delete functionality when document is ready
$(function () {
  // Add click handler to delete buttons
  $(document).on('click', '.delete-record', function () {
    const categoryId = $(this).data('id');
    const categoryName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get name from cat_name column (0-indexed: responsive, index, cat_name)

    // Validate required data
    if (!categoryId) {
      Swal.fire({
        icon: 'error',
        title: messages.errorTitle,
        text: 'ID kategori tidak dijumpai. Sila muat semula halaman.',
        customClass: {
          confirmButton: messages.btnPrimary
        },
        buttonsStyling: false
      });
      return;
    }

    if (!categoryName) {
      Swal.fire({
        icon: 'error',
        title: messages.errorTitle,
        text: 'Nama kategori tidak dijumpai. Sila muat semula halaman.',
        customClass: {
          confirmButton: messages.btnPrimary
        },
        buttonsStyling: false
      });
      return;
    }

    // Show initial confirmation
    showInitialDeleteConfirmation(categoryId, categoryName);
  });

  // Function to perform delete operation
  function performDelete(categoryId, categoryName, forceDelete = false) {
    const endpoint = forceDelete ?
      `/ref-category-management/${categoryId}/force` :
      `/ref-category-management/${categoryId}`;

    fetch(endpoint, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
      .then(response => {
        // Parse JSON for both success and error responses
        return response.json().then(data => ({
          status: response.status,
          data: data
        }));
      })
      .then(result => {
        const data = result.data;
        logger.log('Delete response received:', data); // Debug log
        if (data.success) {
          const successMessage = forceDelete ? messages.successForceText : messages.successText;
          Swal.fire({
            icon: 'success',
            title: messages.successTitle,
            text: successMessage,
            timer: 2500,
            showConfirmButton: false,
            customClass: {
              confirmButton: messages.btnSuccess
            },
            buttonsStyling: false
          }).then(() => {
            // Refresh the DataTable after the timer
            $('.datatables-refcategory').DataTable().ajax.reload();
          });
        } else if (data.has_subcategories) {
          // Category has sub-categories, show confirmation with sub-category names
          logger.log('Sub-categories data received:', data.subcategories); // Debug log
          let subcategoriesList = '';
          let subcategoriesCount = 0;

          if (data.subcategories && Array.isArray(data.subcategories) && data.subcategories.length > 0) {
            subcategoriesCount = data.subcategories.length;
            subcategoriesList = data.subcategories.map((subcategory, index) => {
              // Handle both string and object formats
              const name = typeof subcategory === 'string' ? subcategory :
                (subcategory.subcat_name || subcategory.name || 'Tiada nama');
              return `<div class="d-flex align-items-center mb-2">
                      <span class="badge bg-secondary me-2">${index + 1}</span>
                      <span class="fw-medium">${name}</span>
                    </div>`;
            }).join('');
          } else {
            subcategoriesList = '<div class="text-muted fst-italic">• Tiada sub-kategori ditemui</div>';
          }

          const subcategoriesText = messages.subcategoriesText
            .replace('{categoryName}', categoryName)
            .replace('{subcategoriesCount}', subcategoriesCount)
            .replace('{subcategoriesList}', subcategoriesList);

          Swal.fire({
            title: messages.subcategoriesTitle,
            html: subcategoriesText,
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: messages.cancelButton,
            confirmButtonText: messages.forceDeleteButton,
            customClass: {
              confirmButton: messages.btnDanger,
              cancelButton: messages.btnSecondary,
              popup: 'swal2-large'
            },
            buttonsStyling: false,
            width: '600px',
            showLoaderOnConfirm: true,
            preConfirm: () => {
              return new Promise((resolve) => {
                // Add a small delay to show the loader
                setTimeout(() => {
                  resolve();
                }, 500);
              });
            }
          }).then(function (result) {
            if (result.value) {
              // User confirmed to delete category with all sub-categories
              performDelete(categoryId, categoryName, true);
            }
          });
        } else {
          logger.log('Delete failed with data:', data); // Debug log
          const errorMessage = data.message || messages.errorText;
          Swal.fire({
            icon: 'error',
            title: messages.errorTitle,
            text: errorMessage,
            customClass: {
              confirmButton: messages.btnPrimary
            },
            buttonsStyling: false
          });
        }
      })
      .catch(error => {
        console.error('Delete request failed:', error); // Debug log

        let errorMessage = messages.errorText;

        // Provide more specific error messages based on error type
        if (error instanceof TypeError && error.message.includes('fetch')) {
          errorMessage = 'Masalah rangkaian. Sila periksa sambungan internet anda.';
        } else if (error.name === 'AbortError') {
          errorMessage = 'Permintaan dibatalkan. Sila cuba lagi.';
        }

        Swal.fire({
          icon: 'error',
          title: messages.errorTitle,
          text: errorMessage,
          footer: '<small class="text-muted">Jika masalah berterusan, sila hubungi pentadbir sistem.</small>',
          customClass: {
            confirmButton: messages.btnPrimary
          },
          buttonsStyling: false
        });
      });
  }

  // Initial confirmation for regular delete
  function showInitialDeleteConfirmation(categoryId, categoryName) {
    const confirmText = messages.confirmText.replace('{categoryName}', categoryName);

    Swal.fire({
      title: messages.confirmTitle,
      html: confirmText,
      icon: 'question',
      showCancelButton: true,
      cancelButtonText: messages.cancelButton,
      confirmButtonText: messages.confirmButton,
      customClass: {
        confirmButton: messages.btnDanger,
        cancelButton: messages.btnSecondary
      },
      buttonsStyling: false,
      showLoaderOnConfirm: true,
      preConfirm: () => {
        return new Promise((resolve) => {
          // Add a small delay to show checking status
          setTimeout(() => {
            resolve();
          }, 300);
        });
      }
    }).then(function (result) {
      if (result.value) {
        performDelete(categoryId, categoryName, false);
      }
    });
  }
});
