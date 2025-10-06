'use strict';

// Form Validation
(function () {
  // Add New Officer form
  const addNewOfficerForm = document.getElementById('addNewOfficerForm');

  if (addNewOfficerForm) {
    const fv = FormValidation.formValidation(addNewOfficerForm, {
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
        'user_id[]': {
          validators: {
            callback: {
              message: 'Sila pilih sekurang-kurangnya seorang pegawai',
              callback: function(value, validator, $field) {
                var selectedValues = $('#add-officer-user').val();
                return selectedValues && selectedValues.length > 0;
              }
            }
          }
        },
        level_tugasan: {
          validators: {
            notEmpty: {
              message: 'Sila masukkan level tugasan'
            },
            integer: {
              message: 'Level tugasan mestilah nombor'
            },
            between: {
              min: 1,
              max: 3,
              message: 'Level tugasan mestilah antara 1 hingga 3'
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
      // Prevent multiple submissions
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      if (submitBtn.prop('disabled')) {
        return false;
      }

      // Disable submit button and show loading
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...');

      // Submit form data
      $.ajax({
        url: '/officer/store',
        type: 'POST',
        data: $(addNewOfficerForm).serialize(),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          console.log('AJAX Success Response:', response);

          if (response.success) {
            // Show success message first
            var message = response.data && response.data.length > 1
              ? response.data.length + ' pegawai tugasan berjaya ditambah'
              : 'Pegawai tugasan berjaya ditambah';
            toastr.success(message, 'Berjaya!', {
              closeButton: true,
              tapToDismiss: false,
              progressBar: true,
              rtl: false
            });

            // Close offcanvas first
            $('#offcanvasAddOfficer').offcanvas('hide');

            // Refresh the datatable with proper checks - do this before form reset
            setTimeout(function() {
              try {
                var table = $('.datatables-refcategory');
                if (table.length && $.fn.DataTable.isDataTable(table)) {
                  console.log('Reloading DataTable after officer creation...');
                  table.DataTable().ajax.reload(function() {
                    console.log('DataTable reload completed');

                    // Reset form after table reload is complete
                    setTimeout(function() {
                      console.log('Resetting form...');
                      fv.resetForm(true);
                      addNewOfficerForm.reset();
                      $('#add-officer-category').val('').trigger('change');
                      $('#add-officer-subcategory').val('').trigger('change');
                      $('#add-officer-user').val(null).trigger('change');
                      console.log('Form reset completed');
                    }, 100);
                  }, false); // false = don't reset pagination
                } else {
                  console.log('DataTable not found or not initialized');
                  // Reset form immediately if no DataTable
                  fv.resetForm(true);
                  addNewOfficerForm.reset();
                  $('#add-officer-category').val('').trigger('change');
                  $('#add-officer-subcategory').val('').trigger('change');
                  $('#add-officer-user').val(null).trigger('change');
                }
              } catch (error) {
                console.error('Error reloading DataTable:', error);
                // Reset form on error
                fv.resetForm(true);
                addNewOfficerForm.reset();
                $('#add-officer-category').val('').trigger('change');
                $('#add-officer-subcategory').val('').trigger('change');
                $('#add-officer-user').val(null).trigger('change');
              }
            }, 100);
          }

          // Re-enable submit button
          setTimeout(function() {
            submitBtn.prop('disabled', false).html('Simpan');
          }, 200);
        },
        error: function(xhr) {
          console.log('AJAX Error:', xhr);

          // Re-enable submit button
          submitBtn.prop('disabled', false).html('Simpan');

          var errorMessage = 'Ralat berlaku semasa menambah pegawai tugasan';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
          toastr.error(errorMessage, 'Ralat!', {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false
          });
          console.error('Full error details:', xhr);
        }
      });
    });
  }

  // Category change handler - load subcategories
  $('#add-officer-category').on('change', function() {
    const categoryId = $(this).val();
    const subcategorySelect = $('#add-officer-subcategory');

    // Clear and reset subcategory dropdown
    subcategorySelect.html('<option value="">Pilih Sub Kategori</option>');

    if (categoryId) {
      // Load subcategories based on selected category
      $.ajax({
        url: '/ref-subcategory-management/by-category/' + categoryId,
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success && response.data) {
            response.data.forEach(function(subcategory) {
              subcategorySelect.append(
                '<option value="' + subcategory.id + '">' + subcategory.subcat_name + '</option>'
              );
            });
          }
        },
        error: function(xhr) {
          console.error('Error loading subcategories:', xhr);
          toastr.error('Ralat semasa memuatkan sub kategori', 'Ralat!');
        }
      });
    }
  });

  // Reset form when offcanvas is opened
  $('#offcanvasAddOfficer').on('show.bs.offcanvas', function() {
    if (addNewOfficerForm) {
      // Reset form validation state
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Clear form
      addNewOfficerForm.reset();

      // Reset select2 elements
      $('#add-officer-category').val('').trigger('change');
      $('#add-officer-subcategory').val('').trigger('change');
      $('#add-officer-user').val(null).trigger('change');

      // Reset submit button
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });

})();
