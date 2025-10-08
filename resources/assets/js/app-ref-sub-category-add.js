'use strict';

// Form Validation
(function () {
  // Add New Sub Category form
  const addNewSubCategoryForm = document.getElementById('addNewSubCategoryForm');

  if (addNewSubCategoryForm) {
    const fv = FormValidation.formValidation(addNewSubCategoryForm, {
      fields: {
        cat_id: {
          validators: {
            notEmpty: {
              message: 'Sila pilih kategori'
            }
          }
        },
        subcat_name: {
          validators: {
            notEmpty: {
              message: 'Sila masukkan nama sub-kategori'
            }
          }
        },
        subcat_desc: {
          validators: {
            notEmpty: {
              message: 'Sila masukkan deskripsi sub-kategori'
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
      const formData = new FormData(addNewSubCategoryForm);
      
      $.ajax({
        url: '/ref-sub-category-management',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            // Close offcanvas
            var addOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddSubCategory'));
            addOffcanvas.hide();
            
            // Clear form
            addNewSubCategoryForm.reset();
            fv.resetForm();
            
            // Show success message
            toastr.success(response.message || 'Sub-kategori berjaya ditambah', 'Berjaya!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });
            
            // Refresh the datatable
            if (typeof window.dt_subcategory !== 'undefined') {
              window.dt_subcategory.ajax.reload();
            } else {
              $('.datatables-refcategory').DataTable().ajax.reload();
            }
          } else {
            toastr.error(response.message || 'Ralat semasa menambah sub-kategori');
          }
        },
        error: function(xhr) {
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors;
            let errorMessage = '';
            for (const field in errors) {
              errorMessage += errors[field][0] + '\n';
            }
            toastr.error(errorMessage);
          } else {
            toastr.error('Ralat semasa menambah sub-kategori');
          }
          console.error(xhr);
        }
      });
    });
  }
})();
