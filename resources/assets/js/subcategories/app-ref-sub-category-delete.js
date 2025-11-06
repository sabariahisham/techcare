'use strict';

// All messages centralized here
const messages = {
  // Confirmation messages
  deleteTitle: 'Adakah anda pasti?',
  deleteText: 'Anda ingin memadam sub-kategori <b>{subcategoryName}</b>?',
  deleteConfirm: 'Ya, padam!',
  deleteCancel: 'Tidak, batal!',

  // Success messages
  deletedTitle: 'Dipadam!',
  deleteSuccess: 'Sub-kategori berjaya dipadam',

  // Error messages
  errorTitle: 'Ralat',
  deleteError: 'Ralat semasa memadam sub-kategori',

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
    const subCategoryId = $(this).data('id');
    const subCategoryName = $(this).closest('tr').find('td:nth-child(4)').text().trim(); // Get sub-category name from subcat_name column (responsive, index, cat_name[hidden], subcat_name)
    const deleteText = messages.deleteText.replace('{subcategoryName}', subCategoryName);

    Swal.fire({
      title: messages.deleteTitle,
      html: deleteText,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: messages.deleteCancel,
      confirmButtonText: messages.deleteConfirm,
      customClass: {
        confirmButton: messages.btnDanger,
        cancelButton: messages.btnSecondary
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        fetch(`/ref-sub-category-management/${subCategoryId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        })
        .then(response => response.json())
        .then(data => {
          if(data.success) {
            Swal.fire({
              icon: 'success',
              title: messages.deletedTitle,
              text: messages.deleteSuccess,
              timer: 2000,
              showConfirmButton: false,
              customClass: {
                confirmButton: messages.btnSuccess
              },
              buttonsStyling: false
            }).then(() => {
              // Refresh the DataTable after the timer
              if (typeof window.dt_subcategory !== 'undefined') {
                window.dt_subcategory.ajax.reload();
              } else {
                $('.datatables-refcategory').DataTable().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: messages.errorTitle,
              text: messages.deleteError,
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
            text: messages.deleteError,
            customClass: {
              confirmButton: messages.btnPrimary
            },
            buttonsStyling: false
          });
        });
      }
    });
  });
});
