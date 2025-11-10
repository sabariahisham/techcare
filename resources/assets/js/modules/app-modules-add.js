'use strict';

// Form Validation for Add Module
$(document).ready(function() {
  console.log('Document ready - initializing module add functionality');
  console.log('jQuery version:', $.fn.jquery);
  console.log('FormValidation available:', typeof FormValidation !== 'undefined');

  // Add New Module form
  const addNewModuleForm = document.getElementById('addNewModuleForm');
  let fv; // Declare fv outside to make it accessible

  if (addNewModuleForm) {
    console.log('Add module form found:', addNewModuleForm);

    // Check if FormValidation is available
    if (typeof FormValidation !== 'undefined') {
      console.log('FormValidation is available, initializing...');

      try {
        // Initialize FormValidation
        fv = FormValidation.formValidation(addNewModuleForm, {
          fields: {
            name: {
              validators: {
                notEmpty: {
                  message: 'Sila masukkan nama modul'
                },
                stringLength: {
                  min: 3,
                  max: 100,
                  message: 'Nama modul mestilah antara 3 hingga 100 aksara'
                },
                regexp: {
                  regexp: /^[a-zA-Z0-9\s\-\_]+$/,
                  message: 'Nama modul hanya boleh mengandungi huruf(a-z), nombor(0-9), ruang kosong( ), tanda sempang(-) dan garis bawah(_)'
                }
              }
            },
            description: {
              validators: {
                notEmpty: {
                  message: 'Sila pilih kategori modul'
                }
              }
            },
            status: {
              validators: {
                notEmpty: {
                  message: 'Sila pilih status modul'
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
        });

        console.log('FormValidation initialized successfully');

        // Handle form submission
        fv.on('core.form.valid', function() {
          console.log('Form validation passed, submitting...');
          submitModuleForm();
        });

        // Handle validation events for debugging
        fv.on('core.element.validated', function(e) {
          console.log('Field validated:', e.field, 'Valid:', e.valid);
        });

        fv.on('core.form.invalid', function() {
          console.log('Form validation failed');
        });
      } catch (error) {
        console.error('Error initializing FormValidation:', error);
      }
    } else {
      console.log('FormValidation not available, using fallback validation');
    }

    // Always add fallback form submission handler
    $(addNewModuleForm).on('submit', function(e) {
      e.preventDefault();
      console.log('Form submit event triggered');

      // Get form values
      var moduleNameInput = document.getElementById('add-module-name');
      var moduleName = moduleNameInput ? moduleNameInput.value.trim() : '';

      console.log('Form values:', {
        name: moduleName
      });

      // Basic validation
      if (!moduleName) {
        toastr.error('Sila masukkan nama modul', 'Ralat!');
        if (moduleNameInput) moduleNameInput.focus();
        return false;
      }

      if (moduleName.length < 3 || moduleName.length > 100) {
        toastr.error('Nama modul mestilah antara 3 hingga 100 aksara', 'Ralat!');
        if (moduleNameInput) moduleNameInput.focus();
        return false;
      }

      // If FormValidation is available and initialized, validate through it
      if (fv && typeof FormValidation !== 'undefined') {
        console.log('Revalidating form through FormValidation');
        fv.validate().then(function(status) {
          console.log('FormValidation status:', status);
          if (status === 'Valid') {
            submitModuleForm();
          } else {
            console.log('FormValidation failed');
          }
        });
      } else {
        // If FormValidation is not available, proceed directly
        console.log('Submitting without FormValidation');
        submitModuleForm();
      }
    });

    // Also add click handler for submit button as fallback
    $(addNewModuleForm).find('button[type="submit"]').on('click', function(e) {
      console.log('Submit button clicked');
      // Let the form submit event handle it
    });
  } else {
    console.error('Add module form not found!');
  }

  // Function to handle module submission
  function submitModuleForm() {
    console.log('submitModuleForm called');
    var submitBtn = $(addNewModuleForm).find('button[type="submit"]');

    // Prevent multiple submissions
    if (submitBtn.prop('disabled')) {
      console.log('Submit button already disabled, preventing double submission');
      return false;
    }

    console.log('Preparing to submit form...');

    // Disable submit button and show loading
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...');

    // Prepare form data
    var formData = new FormData(addNewModuleForm);

    // Debug: Log form data
    console.log('Form data being submitted:');
    for (var pair of formData.entries()) {
      console.log(pair[0] + ': ' + pair[1]);
    }

    // Get CSRF token
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    console.log('CSRF Token:', csrfToken);

    // Submit form data
    $.ajax({
      url: '/modules',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      beforeSend: function() {
        console.log('AJAX request starting...');
      },
      success: function(response) {
        console.log('AJAX Success Response:', response);

        if (response.status === 'success') {
          // Show success message
          toastr.success('Modul berjaya ditambah dan permission telah dijana secara automatik', 'Berjaya!', {
            closeButton: true,
            tapToDismiss: false,
            progressBar: true,
            rtl: false
          });

          // Close offcanvas first
          $('#offcanvasAddModule').offcanvas('hide');

          // Refresh the datatable
          setTimeout(function() {
            try {
              var table = $('.datatables-permissions');
              if (table.length && $.fn.DataTable.isDataTable(table)) {
                console.log('Reloading DataTable after module creation...');
                table.DataTable().ajax.reload(function() {
                  console.log('DataTable reload completed');
                  // Reset form after table reload
                  setTimeout(function() {
                    if (fv) {
                      fv.resetForm(true);
                    }
                    addNewModuleForm.reset();
                  }, 100);
                }, false);
              } else {
                console.log('DataTable not found or not initialized, trying modules table...');
                var modulesTable = $('.datatables-modules');
                if (modulesTable.length && $.fn.DataTable.isDataTable(modulesTable)) {
                  console.log('Reloading modules DataTable...');
                  modulesTable.DataTable().ajax.reload(function() {
                    console.log('Modules DataTable reload completed');
                    setTimeout(function() {
                      if (fv) {
                        fv.resetForm(true);
                      }
                      addNewModuleForm.reset();
                    }, 100);
                  }, false);
                } else {
                  console.log('No DataTable found');
                  if (fv) {
                    fv.resetForm(true);
                  }
                  addNewModuleForm.reset();
                }
              }
            } catch (error) {
              console.error('Error reloading DataTable:', error);
              if (fv) {
                fv.resetForm(true);
              }
              addNewModuleForm.reset();
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

        var errorMessage = 'Ralat berlaku semasa menambah modul';

        if (xhr.responseJSON) {
          if (xhr.responseJSON.errors) {
            // Handle validation errors with user-friendly messages
            var errors = xhr.responseJSON.errors;
            var errorMessages = [];

            Object.keys(errors).forEach(function(key) {
              if (key === 'module_name') {
                var fieldErrors = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                fieldErrors.forEach(function(error) {
                  if (error.includes('required')) {
                    errorMessages.push('Sila masukkan nama modul');
                  } else if (error.includes('unique')) {
                    errorMessages.push('Nama modul telah wujud. Sila pilih nama yang lain.');
                  } else if (error.includes('regex')) {
                    errorMessages.push('Nama modul hanya boleh mengandungi huruf(a-z), nombor(0-9), ruang kosong( ), tanda sempang(-) dan garis bawah(_)');
                  } else if (error.includes('min')) {
                    errorMessages.push('Nama modul mestilah sekurang-kurangnya 3 aksara');
                  } else if (error.includes('max')) {
                    errorMessages.push('Nama modul tidak boleh melebihi 100 aksara');
                  } else {
                    errorMessages.push(error);
                  }
                });

                // Highlight the field with error
                $('#add-module-name').addClass('is-invalid');
                $('#add-module-name').on('input', function() {
                  $(this).removeClass('is-invalid');
                });
              }
            });

            if (errorMessages.length > 0) {
              errorMessage = errorMessages.join('<br>');
            }
          } else if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
        }

        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false,
          escapeHtml: false
        });
      }
    });
  }

  // Reset form when offcanvas is opened
  $(document).on('show.bs.offcanvas', '#offcanvasAddModule', function() {
    console.log('Offcanvas opened, resetting form');
    if (addNewModuleForm) {
      if (fv && typeof fv.resetForm === 'function') {
        fv.resetForm(true);
      }
      addNewModuleForm.reset();
      var submitBtn = $(addNewModuleForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });

  // Reset form when offcanvas is closed
  $(document).on('hidden.bs.offcanvas', '#offcanvasAddModule', function() {
    console.log('Offcanvas closed, resetting form');
    if (addNewModuleForm) {
      if (fv && typeof fv.resetForm === 'function') {
        fv.resetForm(true);
      }
      addNewModuleForm.reset();
      var submitBtn = $(addNewModuleForm).find('button[type="submit"]');
      submitBtn.prop('disabled', false).html('Simpan');
    }
  });
});
