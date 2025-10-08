'use strict';

$(function () {
  const editCategoryForm = document.getElementById('editCategoryForm');

  // Form Validation
  const fv = FormValidation.formValidation(editCategoryForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan nama kategori'
          }
        }
      },
      description: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan deskripsi kategori'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.mb-6'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    const formData = new FormData(editCategoryForm);
    const categoryId = $('#edit-category-id').val();

    $.ajax({
      url: `/ref-category-management/update/${categoryId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          $('#offcanvasEditCategory').offcanvas('hide');
          editCategoryForm.reset();

          toastr.success(response.message, '', {
            closeButton: true,
            timeOut: 3000,
            progressBar: true,
            positionClass: 'toast-top-right',
            showClass: "animate__animated animate__bounceInRight",
            hideClass: "animate__animated animate__fadeOut",
            "toastClass": "toast ngx-toastr bg-success text-white",
            "closeClass": "toast-close-button",
            "titleClass": "toast-title",
            "messageClass": "toast-message",
            "progressClass": "toast-progress"
        });

          $('.datatables-refcategory').DataTable().ajax.reload();
        }
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.message || 'Error updating category';
        toastr.error(errorMessage, 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Load category data when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const categoryId = $(this).data('id');

    $.ajax({
      url: `/ref-category-management/${categoryId}/edit`,
      type: 'GET',
      success: function(response) {
        $('#edit-category-id').val(response.id);
        $('#edit-category-name').val(response.cat_name);
        $('#edit-category-description').val(response.cat_desc);

        $('#offcanvasEditCategory').offcanvas('show');
      },
      error: function(xhr) {
        toastr.error('Error loading category data', 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasEditCategory').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    editCategoryForm.reset();
  });
});
