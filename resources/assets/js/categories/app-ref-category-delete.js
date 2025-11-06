'use strict';

// All messages centralized here
const messages = {
  // Initial delete confirmation
  confirmTitle: 'Adakah anda pasti?',
  confirmText: 'Anda ingin memadam kategori <b>{categoryName}</b>?',
  confirmButton: 'Ya, padam!',
  cancelButton: 'Tidak, batal!',

  // Sub-categories warning
  subcategoriesTitle: 'Kategori mempunyai Sub-Kategori',
  subcategoriesText: 'Kategori <b>{categoryName}</b> mempunyai sub-kategori berikut:<br><br>' +
                     '<div class="text-start mt-2 mb-3" style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;">' +
                     '{subcategoriesList}' +
                     '</div>' +
                     '<strong>Adakah anda ingin memadam kategori ini berserta semua sub-kategorinya?</strong>',
  forceDeleteButton: 'Ya, padam semua!',

  // Success messages
  successTitle: 'Dipadam!',
  successText: 'Kategori berjaya dipadam',

  // Error messages
  errorTitle: 'Ralat',
  errorText: 'Ralat berlaku semasa memadam kategori',

  // Button classes
  btnDanger: 'btn btn-danger me-3',
  btnSecondary: 'btn btn-secondary',
  btnSuccess: 'btn btn-success',
  btnPrimary: 'btn btn-primary'
};

// Initialize delete functionality when document is ready
$(function () {
  // Add click handler to delete buttons
  $(document).on('click', '.delete-record', function() {
    const categoryId = $(this).data('id');
    const categoryName = $(this).closest('tr').find('td:nth-child(3)').text().trim(); // Get name from cat_name column (0-indexed: responsive, index, cat_name)

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
      if(data.success) {
        Swal.fire({
          icon: 'success',
          title: messages.successTitle,
          text: messages.successText,
          timer: 2000,
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
        const subcategoriesList = (data.subcategories && data.subcategories.length > 0)
          ? data.subcategories.map(name => `• ${name}`).join('<br>')
          : '• Tiada nama sub-kategori';
        const subcategoriesText = messages.subcategoriesText
          .replace('{categoryName}', categoryName)
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
            cancelButton: messages.btnSecondary
          },
          buttonsStyling: false,
          width: '500px'
        }).then(function (result) {
          if (result.value) {
            // User confirmed to delete category with all sub-categories
            performDelete(categoryId, categoryName, true);
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: messages.errorTitle,
          text: messages.errorText,
          customClass: {
            confirmButton: messages.btnPrimary
          },
          buttonsStyling: false
        });
      }
    })
    .catch(error => {
      Swal.fire({
        icon: 'error',
        title: messages.errorTitle,
        text: messages.errorText,
        customClass: {
          confirmButton: messages.btnPrimary
        },
        buttonsStyling: false
      });
      console.error(error);
    });
  }

  // Initial confirmation for regular delete
  function showInitialDeleteConfirmation(categoryId, categoryName) {
    const confirmText = messages.confirmText.replace('{categoryName}', categoryName);

    Swal.fire({
      title: messages.confirmTitle,
      html: confirmText,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: messages.cancelButton,
      confirmButtonText: messages.confirmButton,
      customClass: {
        confirmButton: messages.btnDanger,
        cancelButton: messages.btnSecondary
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        performDelete(categoryId, categoryName, false);
      }
    });
  }
});
