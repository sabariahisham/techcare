'use strict';

$(function () {
  const editSubCategoryForm = document.getElementById('editSubCategoryForm');

  // Form Validation for Edit Sub Category
  const fv = FormValidation.formValidation(editSubCategoryForm, {
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
        rowSelector: '.mb-6'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function() {
    const formData = new FormData(editSubCategoryForm);
    const subcategoryId = $('#edit_subcategory_id').val();

    $.ajax({
      url: `/ref-sub-category-management/${subcategoryId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      success: function(response) {
        if (response.success) {
          // Close offcanvas
          var editOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditSubCategory'));
          editOffcanvas.hide();

          // Show success message
          toastr.success('Sub-kategori berjaya dikemaskini', 'Berjaya!', {
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
          toastr.error('Ralat semasa mengemaskini sub-kategori');
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
          toastr.error('Ralat semasa mengemaskini sub-kategori');
        }
        console.error(xhr);
      }
    });
  });

  // Load subcategory data when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const subcategoryId = $(this).data('id');

    $.ajax({
      url: `/ref-sub-category-management/${subcategoryId}/edit`,
      type: 'GET',
      success: function(response) {
        $('#edit_subcategory_id').val(response.id);
        $('#edit_cat_id').val(response.cat_id);
        $('#edit_subcat_name').val(response.subcat_name);
        $('#edit_subcat_desc').val(response.subcat_desc);

        $('#offcanvasEditSubCategory').offcanvas('show');
      },
      error: function(xhr) {
        toastr.error('Ralat semasa memuat data sub-kategori', 'Error!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  $('#offcanvasEditSubCategory').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    editSubCategoryForm.reset();
  });
});
