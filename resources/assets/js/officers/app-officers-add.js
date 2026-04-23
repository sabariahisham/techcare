import logger from '../logger.js';

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
        user_id: {
          validators: {
            notEmpty: {
              message: 'Sila pilih sekurang-kurangnya seorang pegawai'
            }
          }
        },
        level_tugasan: {
          validators: {
            notEmpty: {
              message: 'Sila pilih level tugasan'
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
      // Prevent multiple submissions
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      if (submitBtn.prop('disabled')) {
        return false;
      }

      // Disable submit button and show loading
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...');

      // Prepare form data with multiple user IDs
      var formData = new FormData();
      var selectedUserIds = $('#add-officer-user').val();

      // Add form fields manually
      formData.append('_token', $('input[name="_token"]').val());
      formData.append('cat_id', $('#add-officer-category').val());
      formData.append('subcat_id', $('#add-officer-subcategory').val());
      formData.append('level_tugasan', $('#add-officer-level').val());

      // Add multiple user IDs
      if (selectedUserIds && selectedUserIds.length > 0) {
        selectedUserIds.forEach(function (userId) {
          if (userId && userId.trim() !== '') {
            formData.append('user_id[]', userId);
          }
        });
      } else {
        toastr.error('Sila pilih sekurang-kurangnya seorang pegawai', 'Ralat!');
        submitBtn.prop('disabled', false).html('Simpan');
        return false;
      }

      // Add validation flag to check for duplicates
      formData.append('check_duplicates', 'true');      // Submit form data
      $.ajax({
        url: '/officer/store',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          logger.log('AJAX Success Response:', response);

          if (response.success) {
            // Show success message for multiple officers
            var successMessage = response.message || 'Pegawai tugasan berjaya ditambah';

            if (response.data && response.data.duplicate) {
              toastr.warning('Pegawai tugasan sudah wujud untuk kategori, sub kategori dan level yang sama', 'Amaran!', {
                closeButton: true,
                tapToDismiss: false,
                progressBar: true,
                rtl: false,
                timeOut: 5000
              });
            } else {
              toastr.success(successMessage, 'Berjaya!', {
                closeButton: true,
                tapToDismiss: false,
                progressBar: true,
                rtl: false
              });
            }

            // Close offcanvas first
            $('#offcanvasAddOfficer').offcanvas('hide');

            // Refresh the datatable with proper checks - do this before form reset
            setTimeout(function () {
              try {
                var table = $('.datatables-refcategory');
                if (table.length && $.fn.DataTable.isDataTable(table)) {
                  logger.log('Reloading DataTable after officer creation...');
                  table.DataTable().ajax.reload(function () {
                    logger.log('DataTable reload completed');

                    // Reset form after table reload is complete
                    setTimeout(function () {
                      logger.log('Resetting form...');
                      fv.resetForm(true);
                      addNewOfficerForm.reset();
                      $('#add-officer-category').val('').trigger('change');
                      $('#add-officer-subcategory').val('').trigger('change');
                      $('#add-officer-level').val('').trigger('change');
                      $('#add-officer-user').val('').trigger('change');
                      logger.log('Form reset completed');
                    }, 100);
                  }, false); // false = don't reset pagination
                } else {
                  logger.log('DataTable not found or not initialized');
                  // Reset form immediately if no DataTable
                  fv.resetForm(true);
                  addNewOfficerForm.reset();
                  $('#add-officer-category').val('').trigger('change');
                  $('#add-officer-subcategory').val('').trigger('change');
                  $('#add-officer-level').val('').trigger('change');
                  $('#add-officer-user').val('').trigger('change');
                }
              } catch (error) {
                console.error('Error reloading DataTable:', error);
                // Reset form on error
                fv.resetForm(true);
                addNewOfficerForm.reset();
                $('#add-officer-category').val('').trigger('change');
                $('#add-officer-subcategory').val('').trigger('change');
                $('#add-officer-level').val('').trigger('change');
                $('#add-officer-user').val('').trigger('change');
              }
            }, 100);
          }

          // Re-enable submit button
          setTimeout(function () {
            submitBtn.prop('disabled', false).html('Simpan');
          }, 200);
        },
        error: function (xhr) {
          logger.log('AJAX Error:', xhr);

          // Re-enable submit button
          submitBtn.prop('disabled', false).html('Simpan');

          var errorMessage = 'Ralat berlaku semasa menambah pegawai tugasan';
          var errorTitle = 'Ralat!';

          if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            // Handle specific duplicate entry errors
            if (xhr.responseJSON.errors) {
              if (xhr.responseJSON.errors.duplicate) {
                errorMessage = 'Terdapat pendaftaran berulang: ' + xhr.responseJSON.errors.duplicate.join(', ');
                errorTitle = 'Pendaftaran Berulang';
              } else if (xhr.responseJSON.errors.validation) {
                errorMessage = 'Ralat pengesahan: ' + Object.values(xhr.responseJSON.errors.validation).flat().join(', ');
              }
            }
          }

          toastr.error(errorMessage, errorTitle, {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false,
            timeOut: 5000
          });

          console.error('Full error details:', xhr);
        }
      });
    });
  }

  // Category change handler - load subcategories (using document delegation)
  $(document).on('change', '#add-officer-category', function () {
    const categoryId = $(this).val();
    const subcategorySelect = $('#add-officer-subcategory');

    logger.log('Category changed to:', categoryId); // Debug log

    // Clear and reset subcategory dropdown
    subcategorySelect.html('<option value="">Pilih Sub Kategori</option>');

    if (categoryId) {
      logger.log('Loading subcategories for category:', categoryId); // Debug log

      // Load subcategories based on selected category
      $.ajax({
        url: '/ref-subcategory-management/by-category/' + categoryId,
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          logger.log('Subcategories response:', response); // Debug log

          if (response.success && response.data) {
            if (response.data.length === 0) {
              subcategorySelect.append('<option value="">Tiada Sub Kategori</option>');
              logger.log('No subcategories found for category:', categoryId);
            } else {
              response.data.forEach(function (subcategory) {
                subcategorySelect.append(
                  '<option value="' + subcategory.id + '">' + subcategory.subcat_name + '</option>'
                );
              });
              logger.log('Added', response.data.length, 'subcategories');
            }
          } else {
            console.error('Invalid response format:', response);
            toastr.error('Format respons tidak sah', 'Ralat!');
          }
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          console.error('Status:', xhr.status, 'Response:', xhr.responseText);

          let errorMsg = 'Ralat semasa memuatkan sub kategori';
          if (xhr.status === 404) {
            errorMsg = 'Endpoint sub kategori tidak dijumpai';
          } else if (xhr.status === 500) {
            errorMsg = 'Ralat pelayan semasa memuatkan sub kategori';
          }

          toastr.error(errorMsg, 'Ralat!');
        }
      });
    }
  });

  // Subcategory change handler - clear user selection if needed
  $(document).on('change', '#add-officer-subcategory', function () {
    logger.log('Subcategory changed to:', $(this).val());
    // You can add logic here if users need to be filtered by subcategory
  });

  // User selection change handler - for multiple selection
  $(document).on('change', '#add-officer-user', function () {
    var selectedValues = $(this).val();
    logger.log('Selected users:', selectedValues);
  });

  // Initialize select2 for add form
  function initializeAddFormSelect2() {
    logger.log('Initializing select2 for add form...');

    // Destroy existing select2 instances first
    if ($('#add-officer-category').hasClass('select2-hidden-accessible')) {
      $('#add-officer-category').select2('destroy');
    }
    if ($('#add-officer-subcategory').hasClass('select2-hidden-accessible')) {
      $('#add-officer-subcategory').select2('destroy');
    }
    if ($('#add-officer-level').hasClass('select2-hidden-accessible')) {
      $('#add-officer-level').select2('destroy');
    }
    if ($('#add-officer-user').hasClass('select2-hidden-accessible')) {
      $('#add-officer-user').select2('destroy');
    }

    // Initialize select2 for add form dropdowns
    $('#add-officer-category').select2({
      placeholder: 'Pilih Kategori',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%',
      allowClear: true
    });

    $('#add-officer-subcategory').select2({
      placeholder: 'Pilih Sub Kategori',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%',
      allowClear: true
    });

    $('#add-officer-level').select2({
      placeholder: 'Pilih Level Tugasan',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%',
      allowClear: true,
      minimumResultsForSearch: Infinity // Disable search for small dropdown
    });

    $('#add-officer-user').select2({
      placeholder: 'Pilih Pegawai (boleh pilih lebih dari satu)',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%',
      multiple: true
    });

    logger.log('Select2 initialization completed for add form');
  }

  // Reset form when offcanvas is opened (using document delegation)
  $(document).on('show.bs.offcanvas', '#offcanvasAddOfficer', function () {
    logger.log('Offcanvas opening - initializing form...');

    if (addNewOfficerForm) {
      // Always initialize select2 to ensure it's working
      initializeAddFormSelect2();

      // Reset form validation state
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Clear form
      addNewOfficerForm.reset();

      // Reset select2 elements after a small delay to ensure they're initialized
      setTimeout(function () {
        $('#add-officer-category').val(null).trigger('change');
        $('#add-officer-subcategory').html('<option value="">Pilih Sub Kategori</option>').val(null).trigger('change');
        $('#add-officer-level').val(null).trigger('change');
        $('#add-officer-user').val([]).trigger('change'); // Empty array for multiple select

        logger.log('Form and select2 elements reset');
      }, 100);      // Reset submit button
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });

  // Reset form when offcanvas is closed (using document delegation)
  $(document).on('hidden.bs.offcanvas', '#offcanvasAddOfficer', function () {
    logger.log('Offcanvas closing - cleaning up...');

    if (addNewOfficerForm) {
      // Reset form validation state
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Clear form completely
      addNewOfficerForm.reset();

      // Reset select2 elements with proper cleanup
      setTimeout(function () {
        $('#add-officer-category').val(null).trigger('change');
        $('#add-officer-subcategory').html('<option value="">Pilih Sub Kategori</option>').val(null).trigger('change');
        $('#add-officer-level').val(null).trigger('change');
        $('#add-officer-user').val([]).trigger('change'); // Empty array for multiple select

        // Clear any validation errors
        $('#offcanvasAddOfficer .is-invalid').removeClass('is-invalid');
        $('#offcanvasAddOfficer .invalid-feedback').remove();

        logger.log('Complete form reset on offcanvas close');
      }, 100);      // Reset submit button
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });

  // Handle close button click specifically
  $(document).on('click', '#offcanvasAddOfficer .btn-close, #offcanvasAddOfficer .btn-label-danger', function () {
    logger.log('Close button clicked - refreshing form...');

    if (addNewOfficerForm) {
      // Force form reset
      setTimeout(function () {
        if (typeof fv !== 'undefined') {
          fv.resetForm(true);
        }
        addNewOfficerForm.reset();

        // Refresh all dropdowns
        $('#add-officer-category').val('').trigger('change');
        $('#add-officer-subcategory').html('<option value="">Pilih Sub Kategori</option>').val('').trigger('change');
        $('#add-officer-level').val('').trigger('change');
        $('#add-officer-user').val([]).trigger('change'); // Empty array for multiple select

        logger.log('Manual form refresh completed');
      }, 50);
    }
  });

  // Initialize select2 on document ready
  $(document).ready(function () {
    logger.log('Document ready - checking form elements...');

    // Initialize select2 for add form on page load if elements exist
    if ($('#add-officer-category').length) {
      logger.log('Form elements found, initializing select2...');
      initializeAddFormSelect2();
    } else {
      logger.log('Form elements not found on page load');
    }
  });

})();
