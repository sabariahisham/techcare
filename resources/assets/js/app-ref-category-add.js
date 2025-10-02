'use strict';

// Form Validation
(function () {
  // Add New Category form
  const addNewCategoryForm = document.getElementById('addNewCategoryForm');

  if (addNewCategoryForm) {
    const fv = FormValidation.formValidation(addNewCategoryForm, {
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
          rowSelector: function (field, ele) {
            return '.mb-6';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    }).on('core.form.valid', function() {
      // Submit form data
      $.ajax({
        url: '/ref-category-management/store',
        type: 'POST',
        data: $(addNewCategoryForm).serialize(),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            // Close offcanvas
            $('#offcanvasAddCategory').offcanvas('hide');
            // Clear form
            addNewCategoryForm.reset();
            // Show success message
            toastr.success('Kategori berjaya ditambah', 'Berjaya!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });
            // Refresh the datatable
            $('.datatables-refcategory').DataTable().ajax.reload();
          }
        },
        error: function(error) {
          toastr.error('An error occurred while creating the category');
          console.error(error);
        }
      });
    });
  }
})();
