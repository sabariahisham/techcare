'use strict';

$(function () {
  const editOfficerForm = document.getElementById('editOfficerForm');

  // Form Validation
  const fv = FormValidation.formValidation(editOfficerForm, {
    fields: {
      cat_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih kategori'
          }
        }
      },
      subcat_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih sub kategori'
          }
        }
      },
      user_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih pegawai'
          }
        }
      },
      level_tugasan: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan level tugasan'
          },
          between: {
            min: 1,
            max: 10,
            message: 'Level tugasan mesti antara 1 hingga 10'
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
    const formData = new FormData(editOfficerForm);
    const officerId = $('#edit-officer-id').val();

    $.ajax({
      url: `/officer/${officerId}`,
      type: 'PUT',
      data: {
        cat_id: formData.get('cat_id'),
        subcat_id: formData.get('subcat_id'),
        user_id: formData.get('user_id'),
        level_tugasan: formData.get('level_tugasan'),
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          $('#offcanvasEditOfficer').offcanvas('hide');
          editOfficerForm.reset();

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
        const errorMessage = xhr.responseJSON?.message || 'Ralat mengemas kini pegawai tugasan';
        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Load officer data when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const officerId = $(this).data('id');

    $.ajax({
      url: `/officer/${officerId}/edit`,
      type: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;

          $('#edit-officer-id').val(data.id);
          $('#edit-officer-category').val(data.cat_id).trigger('change');
          $('#edit-officer-user').val(data.user_id).trigger('change');
          $('#edit-officer-level').val(data.level_tugasan);

          // Load subcategories for the selected category
          loadSubcategories(data.cat_id, data.subcat_id, '#edit-officer-subcategory');

          $('#offcanvasEditOfficer').offcanvas('show');
        }
      },
      error: function(xhr) {
        toastr.error('Ralat memuatkan data pegawai', 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Handle category change to load subcategories
  $('#edit-officer-category').on('change', function() {
    const categoryId = $(this).val();
    loadSubcategories(categoryId, null, '#edit-officer-subcategory');
  });

  // Function to load subcategories
  function loadSubcategories(categoryId, selectedSubcatId = null, targetSelector) {
    if (!categoryId) {
      $(targetSelector).empty().append('<option value="">Pilih Sub Kategori</option>');
      return;
    }

    $.ajax({
      url: `/ref-subcategory-management/by-category/${categoryId}`,
      type: 'GET',
      success: function(response) {
        let options = '<option value="">Pilih Sub Kategori</option>';
        if (response.success && response.data) {
          response.data.forEach(function(subcategory) {
            const selected = selectedSubcatId && selectedSubcatId == subcategory.id ? 'selected' : '';
            options += `<option value="${subcategory.id}" ${selected}>${subcategory.subcat_name}</option>`;
          });
        }
        $(targetSelector).html(options);
      },
      error: function() {
        toastr.error('Ralat memuatkan sub kategori', 'Ralat!');
        $(targetSelector).empty().append('<option value="">Pilih Sub Kategori</option>');
      }
    });
  }

  // Reset form when offcanvas is hidden
  $('#offcanvasEditOfficer').on('hidden.bs.offcanvas', function () {
    fv.resetForm();
    editOfficerForm.reset();
    $('#edit-officer-subcategory').empty().append('<option value="">Pilih Sub Kategori</option>');
  });
});
