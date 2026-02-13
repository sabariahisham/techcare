'use strict';

$(function () {
  const editCategoryForm = document.getElementById('editCategoryForm');

  // Auto-uppercase name input
  const nameInput = editCategoryForm.querySelector('[name="name"]');
  if (nameInput) {
    nameInput.addEventListener('input', function (e) {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase();
      this.setSelectionRange(start, end);
    });
  }

  // Auto-uppercase description input
  const descriptionInput = editCategoryForm.querySelector('[name="description"]');
  if (descriptionInput) {
    descriptionInput.addEventListener('input', function (e) {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase();
      this.setSelectionRange(start, end);
    });
  }

  // Form Validation
  const fv = FormValidation.formValidation(editCategoryForm, {
    fields: {
      name: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan nama kategori'
          },
          remote: {
            message: 'Nama kategori telah wujud. Sila pilih nama yang lain.',
            method: 'POST',
            url: '/ref-category-management/check-duplicate',
            data: function () {
              return {
                name: editCategoryForm.querySelector('[name="name"]').value,
                id: $('#edit-category-id').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
              };
            }
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
  }).on('core.form.valid', function () {
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
      success: function (response) {
        if (response.success) {
          $('#offcanvasEditCategory').offcanvas('hide');
          editCategoryForm.reset();
          fv.resetForm();

          toastr.success('Kategori berjaya dikemaskini', 'Berjaya!', {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false
          });

          $('.datatables-refcategory').DataTable().ajax.reload();
        } else {
          toastr.error(response.message || 'Ralat berlaku semasa mengemaskini kategori', 'Ralat!', {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false
          });
        }
      },
      error: function (xhr, status, error) {
        let errorMessage = 'Ralat berlaku semasa mengemaskini kategori';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          // Handle validation errors
          const errors = xhr.responseJSON.errors;
          const firstError = Object.values(errors)[0];
          if (firstError && firstError.length > 0) {
            errorMessage = firstError[0];
          }
        }

        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
        console.error(error);
      }
    });
  });

  // Load category data when edit button is clicked
  $(document).on('click', '.edit-record', function () {
    const categoryId = $(this).data('id');

    $.ajax({
      url: `/ref-category-management/${categoryId}/edit`,
      type: 'GET',
      success: function (response) {
        $('#edit-category-id').val(response.id);
        $('#edit-category-name').val(response.cat_name);
        $('#edit-category-description').val(response.cat_desc);

        $('#offcanvasEditCategory').offcanvas('show');
      },
      error: function (xhr) {
        toastr.error('Ralat memuat data kategori', 'Ralat!', {
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
