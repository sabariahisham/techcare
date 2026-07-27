/**
 * Public Holiday - Edit
 */

'use strict';

import logger from '../logger.js';

// Valid Malaysian states list for validation
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
 * Sanitize string input
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
 * Validate holiday name (max 255 characters)
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

/**
 * Load holiday data into edit form
 * @param {number} holidayId - Holiday ID to load
 */
async function editPublicHoliday(holidayId) {
  if (!holidayId || isNaN(holidayId)) {
    toastr.error('ID cuti umum tidak sah', 'Ralat!');
    return;
  }

  toastr.info('Sedang memuatkan data...');

  try {
    // Fetch holiday data via AJAX
    const response = await fetch(`/public-holidays/${holidayId}/edit`, {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error('Gagal memuatkan data cuti umum');
    }

    const data = await response.json();

    if (!data.success || !data.holiday) {
      throw new Error(data.message || 'Data tidak ditemui');
    }

    const holiday = data.holiday;

    // Populate form fields
    document.getElementById('edit-holiday-id').value = sanitizeInput(holiday.id.toString());
    // Extract YYYY-MM-DD from datetime string (e.g. "2026-02-15T00:00:00.000000Z")
    const dateOnly = holiday.holiday_date ? holiday.holiday_date.substring(0, 10) : '';
    document.getElementById('edit-holiday-date').value = sanitizeInput(dateOnly);
    document.getElementById('edit-holiday-name').value = sanitizeInput(holiday.holiday_name);

    // Set states using Select2 API (native option.selected does not update Select2 UI)
    // National is stored as ["Nasional"] (or null for legacy rows); the form option
    // value is 'national', so map it back before selecting.
    if (holiday.states && Array.isArray(holiday.states) && !holiday.states.includes('Nasional')) {
      $('#edit-holiday-states').val(holiday.states).trigger('change');
    } else {
      $('#edit-holiday-states').val(['national']).trigger('change');
    }

    // Set except states using Select2 API
    if (holiday.states_except && Array.isArray(holiday.states_except) && holiday.states_except.length > 0) {
      $('#edit-holiday-states-except').val(holiday.states_except).trigger('change');
    } else {
      $('#edit-holiday-states-except').val(null).trigger('change');
    }

    // Revalidate is handled automatically via Select2 change event listeners
    logger.log('Holiday data loaded:', holiday);

  } catch (error) {
    logger.error('Error loading holiday:', error);
    toastr.error(error.message || 'Ralat semasa memuatkan data', 'Ralat!');
  }
}

// Form Validation
(function () {
  const editPublicHolidayForm = document.getElementById('editPublicHolidayForm');

  if (!editPublicHolidayForm) {
    return;
  }

  const fv = FormValidation.formValidation(editPublicHolidayForm, {
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
    const submitBtn = editPublicHolidayForm.querySelector('button[type="submit"]');

    if (submitBtn.disabled) {
      return false;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mengemaskini...';

    // Get form values
    const holidayId = document.getElementById('edit-holiday-id').value;
    const holidayDate = document.getElementById('edit-holiday-date').value;
    const holidayName = document.getElementById('edit-holiday-name').value;

    // Use Select2 API to correctly read selected values
    const selectedStates = $('#edit-holiday-states').val() || [];
    const selectedStatesExcept = $('#edit-holiday-states-except').val() || [];

    // Client-side validation
    try {
      if (!holidayId || isNaN(holidayId)) {
        throw new Error('ID cuti umum tidak sah');
      }

      if (!isValidDate(holidayDate)) {
        throw new Error('Format tarikh tidak sah');
      }

      if (!isValidHolidayName(holidayName)) {
        throw new Error('Nama cuti umum tidak sah');
      }

      if (selectedStates.length === 0) {
        throw new Error('Sila pilih sekurang-kurangnya satu negeri');
      }

      if (!selectedStates.includes('national') && !isValidStates(selectedStates)) {
        throw new Error('Pilihan negeri tidak sah');
      }

      if (!isValidStatesExcept(selectedStatesExcept)) {
        throw new Error('Pilihan negeri berkecuali tidak sah');
      }

    } catch (error) {
      logger.error('Validation Error:', error);
      toastr.error(error.message || 'Ralat validasi', 'Ralat!');
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Kemaskini';
      return false;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('_method', 'PUT');
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
      url: `/public-holidays/${sanitizeInput(holidayId)}/update`,
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
          toastr.success(response.message || 'Cuti umum berhasil dikemaskini', 'Berjaya!');

          // Reset FormValidation state AND form fields (prevents spurious validation errors on reset)
          fv.resetForm(true);

          // Reset Select2 fields
          $('#edit-holiday-states').val(null).trigger('change');
          $('#edit-holiday-states-except').val(null).trigger('change');

          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditPublicHoliday'));
          if (offcanvas) {
            offcanvas.hide();
          }

          // Keep button disabled until page reloads
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuatkan...';

          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          toastr.error(response.message || 'Gagal mengemaskini cuti umum', 'Ralat!');
          logger.error('Update failed:', response);

          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Kemaskini';
        }
      },
      error: function (xhr, status, error) {
        logger.error('AJAX Error:', {
          status,
          error,
          response: xhr.responseJSON
        });

        let errorMessage = 'Ralat semasa mengemaskini cuti umum';
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
        } else if (xhr.status === 404) {
          errorMessage = 'Cuti umum tidak ditemui';
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
        submitBtn.innerHTML = 'Kemaskini';
      }
    });
  });

  // Add Select2 change event listener untuk trigger FormValidation revalidation
  $(document).on('change', '#edit-holiday-states', function () {
    fv.revalidateField('states[]');
  });

  $(document).on('change', '#edit-holiday-states-except', function () {
    fv.revalidateField('states_except');
  });

  // Initialize Select2 for edit form
  function initializeEditFormSelect2() {
    logger.log('Initializing Select2 for edit form...');

    // Destroy existing Select2 instances first
    if ($('#edit-holiday-states').hasClass('select2-hidden-accessible')) {
      $('#edit-holiday-states').select2('destroy');
    }
    if ($('#edit-holiday-states-except').hasClass('select2-hidden-accessible')) {
      $('#edit-holiday-states-except').select2('destroy');
    }

    // Initialize Select2 for states (required)
    $('#edit-holiday-states').select2({
      placeholder: 'Pilih Negeri (boleh pilih lebih dari satu)',
      dropdownParent: $('#offcanvasEditPublicHoliday'),
      width: '100%',
      multiple: true,
      allowClear: true
    });

    // Initialize Select2 for states_except (optional)
    $('#edit-holiday-states-except').select2({
      placeholder: 'Pilih Negeri Berkecuali (opsional)',
      dropdownParent: $('#offcanvasEditPublicHoliday'),
      width: '100%',
      multiple: true,
      allowClear: true
    });

    logger.log('Select2 initialization completed for edit form');
  }

  // Reset form when offcanvas is opened
  $(document).on('show.bs.offcanvas', '#offcanvasEditPublicHoliday', function () {
    logger.log('Offcanvas opening - initializing Select2...');

    if (editPublicHolidayForm) {
      initializeEditFormSelect2();
    }
  });

  // Reset form when offcanvas is closed
  const offcanvasElement = document.getElementById('offcanvasEditPublicHoliday');
  if (offcanvasElement) {
    offcanvasElement.addEventListener('hide.bs.offcanvas', function () {
      editPublicHolidayForm.reset();

      // Reset Select2
      $('#edit-holiday-states').val('').trigger('change');
      $('#edit-holiday-states-except').val('').trigger('change');

      const submitBtn = editPublicHolidayForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Kemaskini';
      }
    });
  }
})();

// Export for use in blade template
window.editPublicHoliday = editPublicHoliday;
