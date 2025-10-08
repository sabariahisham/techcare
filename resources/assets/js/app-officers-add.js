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
              message: 'Sila pilih seorang pegawai'
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

      // Prepare form data with multiple user IDs
      var formData = new FormData(addNewOfficerForm);
      var selectedUserIds = $('#add-officer-user').val();

      // Remove existing user_id entries and add the selected array
      formData.delete('user_id');
      if (selectedUserIds && selectedUserIds.length > 0) {
        selectedUserIds.forEach(function(userId) {
          formData.append('user_id[]', userId);
        });
      }

      // Submit form data
      $.ajax({
        url: '/officer/store',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          console.log('AJAX Success Response:', response);

          if (response.success) {
            // Show success message for single officer
            toastr.success('Pegawai tugasan berjaya ditambah', 'Berjaya!', {
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
                      $('#add-officer-user').val('').trigger('change');
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
                  $('#add-officer-user').val('').trigger('change');
                }
              } catch (error) {
                console.error('Error reloading DataTable:', error);
                // Reset form on error
                fv.resetForm(true);
                addNewOfficerForm.reset();
                $('#add-officer-category').val('').trigger('change');
                $('#add-officer-subcategory').val('').trigger('change');
                $('#add-officer-user').val('').trigger('change');
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

  // Category change handler - load subcategories (using document delegation)
  $(document).on('change', '#add-officer-category', function() {
    const categoryId = $(this).val();
    const subcategorySelect = $('#add-officer-subcategory');

    console.log('Category changed to:', categoryId); // Debug log

    // Clear and reset subcategory dropdown
    subcategorySelect.html('<option value="">Pilih Sub Kategori</option>');

    if (categoryId) {
      console.log('Loading subcategories for category:', categoryId); // Debug log

      // Load subcategories based on selected category
      $.ajax({
        url: '/ref-subcategory-management/by-category/' + categoryId,
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          console.log('Subcategories response:', response); // Debug log

          if (response.success && response.data) {
            if (response.data.length === 0) {
              subcategorySelect.append('<option value="">Tiada Sub Kategori</option>');
              console.log('No subcategories found for category:', categoryId);
            } else {
              response.data.forEach(function(subcategory) {
                subcategorySelect.append(
                  '<option value="' + subcategory.id + '">' + subcategory.subcat_name + '</option>'
                );
              });
              console.log('Added', response.data.length, 'subcategories');
            }
          } else {
            console.error('Invalid response format:', response);
            toastr.error('Format respons tidak sah', 'Ralat!');
          }
        },
        error: function(xhr) {
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
  $(document).on('change', '#add-officer-subcategory', function() {
    console.log('Subcategory changed to:', $(this).val());
    // You can add logic here if users need to be filtered by subcategory
  });

  // Initialize select2 for add form
  function initializeAddFormSelect2() {
    console.log('Initializing select2 for add form...');

    // Initialize select2 for add form dropdowns
    $('#add-officer-category').select2({
      placeholder: 'Pilih Kategori',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%'
    });

    $('#add-officer-subcategory').select2({
      placeholder: 'Pilih Sub Kategori',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%'
    });

    $('#add-officer-user').select2({
      placeholder: 'Pilih Pegawai',
      dropdownParent: $('#offcanvasAddOfficer'),
      width: '100%',
      multiple: true
    });

    console.log('Select2 initialization completed for add form');
  }

  // Reset form when offcanvas is opened (using document delegation)
  $(document).on('show.bs.offcanvas', '#offcanvasAddOfficer', function() {
    if (addNewOfficerForm) {
      // Initialize select2 if not already initialized
      if (!$('#add-officer-category').hasClass('select2-hidden-accessible')) {
        initializeAddFormSelect2();
      }

      // Reset form validation state
      if (typeof fv !== 'undefined') {
        fv.resetForm(true);
      }

      // Clear form
      addNewOfficerForm.reset();

      // Reset select2 elements
      $('#add-officer-category').val('').trigger('change');
      $('#add-officer-subcategory').val('').trigger('change');
      $('#add-officer-user').val('').trigger('change');

      // Reset submit button
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });

  // Reset form when offcanvas is closed (using document delegation)
  $(document).on('hidden.bs.offcanvas', '#offcanvasAddOfficer', function() {
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
      $('#add-officer-user').val('').trigger('change');

      // Reset submit button
      var submitBtn = $(addNewOfficerForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');

      console.log('Form reset on offcanvas close');
    }
  });

  // Initialize select2 on document ready
  $(document).ready(function() {
    // Initialize select2 for add form on page load if elements exist
    if ($('#add-officer-category').length && !$('#add-officer-category').hasClass('select2-hidden-accessible')) {
      initializeAddFormSelect2();
    }
  });

})();
