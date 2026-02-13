'use strict';

// Form Validation
(function () {
  // Add New Category form
  const addNewCategoryForm = document.getElementById('addNewCategoryForm');

  if (addNewCategoryForm) {
    // Auto-uppercase name input
    const nameInput = addNewCategoryForm.querySelector('[name="name"]');
    if (nameInput) {
      nameInput.addEventListener('input', function (e) {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(start, end);
      });
    }

    // Auto-uppercase description input
    const descriptionInput = addNewCategoryForm.querySelector('[name="description"]');
    if (descriptionInput) {
      descriptionInput.addEventListener('input', function (e) {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(start, end);
      });
    }

    const fv = FormValidation.formValidation(addNewCategoryForm, {
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
                  name: addNewCategoryForm.querySelector('[name="name"]').value,
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
          rowSelector: function (field, ele) {
            return '.mb-6';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    }).on('core.form.valid', function () {
      // Submit form data
      $.ajax({
        url: '/ref-category-management/store',
        type: 'POST',
        data: $(addNewCategoryForm).serialize(),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          if (response.success) {
            // Close offcanvas
            $('#offcanvasAddCategory').offcanvas('hide');
            // Clear form
            addNewCategoryForm.reset();
            // Reset form validation
            fv.resetForm();
            // Show success message
            toastr.success('Kategori berjaya ditambah', 'Berjaya!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });
            // Refresh the datatable
            $('.datatables-refcategory').DataTable().ajax.reload();
          } else {
            toastr.error(response.message || 'Ralat berlaku semasa menambah kategori', 'Ralat!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = 'Ralat berlaku semasa menambah kategori';

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

    // Reset form when offcanvas is hidden
    $('#offcanvasAddCategory').on('hidden.bs.offcanvas', function () {
      fv.resetForm();
      addNewCategoryForm.reset();
    });
  }
})();
