/**
 * Public Holiday - Add
 */

'use strict';

import logger from '../logger.js';

// Valid Malaysian states list for server-side validation
const VALID_STATES = [
  'national',
  'Johor',
  'Kedah',
  'Kelantan',
  'Melaka',
  'Negeri Sembilan',
  'Pahang',
  'Pulau Pinang',
  'Perak',
  'Perlis',
  'Selangor',
  'Terengganu',
  'Sabah',
  'Sarawak',
  'Wilayah Persekutuan Kuala Lumpur',
  'Wilayah Persekutuan Labuan',
  'Wilayah Persekutuan Putrajaya'
];

/**
 * Sanitize string input - remove potential XSS vectors
 * @param {string} input - Input string to sanitize
 * @returns {string} - Sanitized string
 */
function sanitizeInput(input) {
  if (typeof input !== 'string') {
    return '';
  }

  const div = document.createElement('div');
  div.textContent = input;
  return div.innerHTML;
}

/**
 * Validate date format (YYYY-MM-DD)
 * @param {string} dateString - Date string to validate
 * @returns {boolean} - True if valid date format
 */
function isValidDate(dateString) {
  if (!dateString || typeof dateString !== 'string') {
    return false;
  }

  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateRegex.test(dateString)) {
    return false;
  }

  const date = new Date(dateString);
  return date instanceof Date && !isNaN(date.getTime());
}

/**
 * Validate holiday name (max 255 characters, no special characters)
 * @param {string} name - Holiday name to validate
 * @returns {boolean} - True if valid
 */
function isValidHolidayName(name) {
  if (!name || typeof name !== 'string') {
    return false;
  }

  const trimmed = name.trim();
  if (trimmed.length === 0 || trimmed.length > 255) {
    return false;
  }

  // Allow only alphanumeric, spaces, and common punctuation
  const nameRegex = /^[a-zA-Z0-9\s\-()\/\.,']*$/i;
  return nameRegex.test(trimmed);
}

/**
 * Validate selected states
 * @param {Array} states - Selected states array
 * @returns {boolean} - True if valid
 */
function isValidStates(states) {
  if (!Array.isArray(states) || states.length === 0) {
    return false;
  }

  // Check if all selected states are valid
  return states.every(state => VALID_STATES.includes(state));
}

/**
 * Validate except states (optional, cannot include 'national')
 * @param {Array} states - Selected except states array
 * @returns {boolean} - True if valid (including empty array)
 */
function isValidStatesExcept(states) {
  if (!Array.isArray(states)) {
    return true; // Optional field
  }

  // Empty array is valid (optional)
  if (states.length === 0) {
    return true;
  }

  // If provided, must not include 'national' and must be valid states
  const validExceptStates = VALID_STATES.filter(state => state !== 'national');
  return states.every(state => validExceptStates.includes(state));
}

// Form Validation
(function () {
  const addNewPublicHolidayForm = document.getElementById('addNewPublicHolidayForm');

  if (!addNewPublicHolidayForm) {
    return;
  }

  const fv = FormValidation.formValidation(addNewPublicHolidayForm, {
    fields: {
      holiday_date: {
        validators: {
          notEmpty: {
            message: 'Sila pilih tarikh'
          },
          date: {
            format: 'YYYY-MM-DD',
            message: 'Format tarikh tidak sah'
          }
        }
      },
      holiday_name: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan nama cuti umum'
          },
          stringLength: {
            min: 3,
            max: 255,
            message: 'Nama cuti umum mesti antara 3 hingga 255 perkataan'
          }
        }
      },
      'states[]': {
        validators: {
          notEmpty: {
            message: 'Sila pilih sekurang-kurangnya satu negeri'
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
    const submitBtn = addNewPublicHolidayForm.querySelector('button[type="submit"]');

    // Prevent multiple submissions
    if (submitBtn.disabled) {
      return false;
    }

    // Disable submit button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...';

    // Get form values
    const holidayDate = document.getElementById('add-holiday-date').value;
    const holidayName = document.getElementById('add-holiday-name').value;

    // Use Select2 API to correctly read selected values
    const selectedStates = $('#add-holiday-states').val() || [];
    const selectedStatesExcept = $('#add-holiday-states-except').val() || [];

    // Client-side validation
    try {
      // Validate date
      if (!isValidDate(holidayDate)) {
        throw new Error('Format tarikh tidak sah');
      }

      // Validate holiday name
      if (!isValidHolidayName(holidayName)) {
        throw new Error('Nama cuti umum tidak sah');
      }

      // Validate states - must have at least one state selected
      if (selectedStates.length === 0) {
        throw new Error('Sila pilih sekurang-kurangnya satu negeri');
      }

      // If NOT national, validate that all states are valid
      if (!selectedStates.includes('national') && !isValidStates(selectedStates)) {
        throw new Error('Pilihan negeri tidak sah');
      }

      // If states_except is provided, validate it (optional field)
      if (!isValidStatesExcept(selectedStatesExcept)) {
        throw new Error('Pilihan negeri berkecuali tidak sah');
      }

    } catch (error) {
      logger.error('Validation Error:', error);
      toastr.error(error.message || 'Ralat validasi', 'Ralat!');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Simpan';
      return false;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('holiday_date', sanitizeInput(holidayDate));
    formData.append('holiday_name', sanitizeInput(holidayName));

    // Always send selected states - backend converts 'national' to null
    selectedStates.forEach(function (state) {
      if (state && state.trim() !== '' && VALID_STATES.includes(state)) {
        formData.append('states[]', sanitizeInput(state));
      }
    });

    // Add except states (must not include 'national')
    selectedStatesExcept.forEach(function (state) {
      if (state && state.trim() !== '' && VALID_STATES.includes(state) && state !== 'national') {
        formData.append('states_except[]', sanitizeInput(state));
      }
    });

    // Submit form data via AJAX
    $.ajax({
      url: '/public-holidays/store',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      success: function (response) {
        logger.log('AJAX Success Response:', response);

        if (response.success) {
          toastr.success(response.message || 'Cuti umum berhasil disimpan', 'Berjaya!');

          // Reset FormValidation state AND form fields (prevents spurious validation errors on reset)
          fv.resetForm(true);

          // Reset Select2 fields
          $('#add-holiday-states').val(null).trigger('change');
          $('#add-holiday-states-except').val(null).trigger('change');

          // Close offcanvas
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddPublicHoliday'));
          if (offcanvas) {
            offcanvas.hide();
          }

          // Keep button disabled until page reloads
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuatkan...';

          // Reload page after short delay to show updated data
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          toastr.error(response.message || 'Gagal menyimpan cuti umum', 'Ralat!');
          logger.error('Save failed:', response);

          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Simpan';
        }
      },
      error: function (xhr, status, error) {
        logger.error('AJAX Error:', {
          status,
          error,
          response: xhr.responseJSON
        });

        let errorMessage = 'Ralat semasa menyimpan cuti umum';
        let errors = [];

        if (xhr.status === 422) {
          // Validation error from server - parse all errors
          if (xhr.responseJSON && xhr.responseJSON.errors) {
            const validationErrors = xhr.responseJSON.errors;
            // Collect all error messages
            Object.keys(validationErrors).forEach(field => {
              const fieldErrors = validationErrors[field];
              if (Array.isArray(fieldErrors)) {
                errors = errors.concat(fieldErrors);
              } else {
                errors.push(fieldErrors);
              }
            });

            if (errors.length > 0) {
              errorMessage = 'Ralat Validasi:\n' + errors.join('\n');
            }
          } else {
            errorMessage = 'Data yang anda masukkan tidak sah';
          }
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (xhr.status === 401) {
          errorMessage = 'Anda tidak sah masuk';
        } else if (xhr.status === 403) {
          errorMessage = 'Anda tidak mempunyai kebenaran untuk melakukan tindakan ini';
        } else if (xhr.status === 500) {
          errorMessage = 'Ralat pelayan dalaman';
        }

        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          timeOut: 0 // Keep error until user closes
        });
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Simpan';
      }
    });
  });

  // Add Select2 change event listener untuk trigger FormValidation revalidation
  $(document).on('change', '#add-holiday-states', function () {
    fv.revalidateField('states[]');
  });

  $(document).on('change', '#add-holiday-states-except', function () {
    fv.revalidateField('states_except');
  });

  // Initialize Select2 for add form
  function initializeAddFormSelect2() {
    logger.log('Initializing Select2 for add form...');

    // Destroy existing Select2 instances first
    if ($('#add-holiday-states').hasClass('select2-hidden-accessible')) {
      $('#add-holiday-states').select2('destroy');
    }
    if ($('#add-holiday-states-except').hasClass('select2-hidden-accessible')) {
      $('#add-holiday-states-except').select2('destroy');
    }

    // Initialize Select2 for states (required)
    $('#add-holiday-states').select2({
      placeholder: 'Pilih Negeri (boleh pilih lebih dari satu)',
      dropdownParent: $('#offcanvasAddPublicHoliday'),
      width: '100%',
      multiple: true,
      allowClear: true
    });

    // Initialize Select2 for states_except (optional)
    $('#add-holiday-states-except').select2({
      placeholder: 'Pilih Negeri Berkecuali (opsional)',
      dropdownParent: $('#offcanvasAddPublicHoliday'),
      width: '100%',
      multiple: true,
      allowClear: true
    });

    logger.log('Select2 initialization completed for add form');
  }

  // Reset form when offcanvas is opened
  $(document).on('show.bs.offcanvas', '#offcanvasAddPublicHoliday', function () {
    logger.log('Offcanvas opening - initializing form...');

    if (addNewPublicHolidayForm) {
      initializeAddFormSelect2();
    }
  });

  // Reset form when offcanvas is closed
  const offcanvasElement = document.getElementById('offcanvasAddPublicHoliday');
  if (offcanvasElement) {
    offcanvasElement.addEventListener('hide.bs.offcanvas', function () {
      addNewPublicHolidayForm.reset();

      // Reset Select2
      $('#add-holiday-states').val('').trigger('change');
      $('#add-holiday-states-except').val('').trigger('change');

      const submitBtn = addNewPublicHolidayForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Simpan';
      }
    });
  }
})();
