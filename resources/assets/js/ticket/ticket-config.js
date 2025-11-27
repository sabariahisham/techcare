/**
 * Ticket Configuration and Utilities
 * Global configurations, helper functions, and shared utilities for ticket management
 */

'use strict';

// Check if jQuery is available
if (typeof $ === 'undefined') {
  console.error('jQuery is not loaded!');
} else {
  console.log('jQuery is loaded successfully');
}

// Function to reload all DataTables - defined globally so both files can use it
window.reloadAllTables = function () {
  // Reload My Tickets DataTable
  if ($.fn.DataTable.isDataTable('.datatables-my-tickets')) {
    $('.datatables-my-tickets').DataTable().ajax.reload();
  }

  // Reload Manage Tickets DataTable
  if ($.fn.DataTable.isDataTable('.datatables-manage-tickets')) {
    $('.datatables-manage-tickets').DataTable().ajax.reload();
  }

  // Keep backward compatibility for old single table
  if ($.fn.DataTable.isDataTable('.datatables-tickets')) {
    $('.datatables-tickets').DataTable().ajax.reload();
  }
};

// Helper function to check if value is empty/null/undefined and return fallback
window.getValueOrFallback = function (value, fallback = 'Tiada data') {
  if (value === null || value === undefined || value === '' || value === 0) {
    return fallback;
  }
  return value;
};

// Function to validate email format
window.isValidEmail = function (email) {
  var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

// Function to clear all validation errors
window.clearValidationErrors = function () {
  $('.is-invalid').removeClass('is-invalid');
  $('.invalid-feedback').remove();

  // Also clear Select2 validation styling
  $('.select2-container').removeClass('is-invalid');
  // Remove any error messages after Select2 containers
  $('.select2-container').siblings('.invalid-feedback').remove();

  // Clear error-text containers
  $('.error-text').text('').hide();
};

// Function to display validation errors
window.displayValidationErrors = function (errors) {
  console.log('displayValidationErrors called with:', errors);
  clearValidationErrors();

  Object.keys(errors).forEach(function (fieldName) {
    var field;
    var errorMessage = errors[fieldName][0];

    console.log('Processing field:', fieldName, 'Error:', errorMessage);

    // Handle array-based field names like attachments.0, attachments.1, etc.
    if (fieldName.includes('.')) {
      var baseFieldName = fieldName.split('.')[0];
      console.log('Array field detected. Base name:', baseFieldName);
      field = $('[name="' + baseFieldName + '[]"]');
      // If not found, try without array notation
      if (field.length === 0) {
        field = $('[name="' + baseFieldName + '"]');
      }
    } else {
      field = $('[name="' + fieldName + '"]');
    }

    console.log('Field found:', field.length > 0, 'Field type:', field.attr('type'));

    // Skip validation display for readonly fields
    if (field.prop('readonly')) {
      console.log('Skipping readonly field:', fieldName);
      return;
    }

    // Skip if field not found
    if (field.length === 0) {
      console.log('Field not found:', fieldName);
      return;
    }

    // Handle Select2 fields (Kategori and Sub Kategori)
    if (field.hasClass('select2-hidden-accessible')) {
      // Add invalid class to the Select2 container
      field.next('.select2-container').addClass('is-invalid');
      // Add error message after the Select2 container
      field.next('.select2-container').after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
    } else {
      // Handle regular fields and file inputs
      field.addClass('is-invalid');

      // Special handling for file inputs to ensure styling works
      if (field.attr('type') === 'file') {
        field.addClass('is-invalid');
        console.log('Added is-invalid class to file input:', field.attr('name'));
        console.log('File input classes after adding is-invalid:', field.attr('class'));
      }

      // Check if field is in a form-field-wrapper (for file inputs and some other fields)
      var wrapper = field.closest('.form-field-wrapper');
      if (wrapper.length > 0) {
        var errorContainer = wrapper.find('.error-text');
        if (errorContainer.length > 0) {
          console.log('Using error-text container for field:', fieldName);
          errorContainer.text(errorMessage).show();
        } else {
          console.log('No error-text container found, using fallback for field:', fieldName);
          // Fallback: add error message after the field
          field.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
        }
      } else {
        console.log('No form-field-wrapper found, adding error after field:', fieldName);
        // Add error message after the field
        field.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
      }

      // Focus on the first invalid field
      if ($('.is-invalid').length === 1) {
        // Scroll to the field and focus
        setTimeout(function () {
          $('html, body').animate({
            scrollTop: field.offset().top - 100
          }, 500);
          field.focus();
        }, 100);
      }
    }
  });
};

// Function to override HTML5 validation messages to Bahasa Melayu
window.setCustomValidationMessages = function () {
  // Define custom messages in Bahasa Melayu
  var customMessages = {
    valueMissing: 'Ruangan ini adalah wajib.',
    typeMismatch: {
      email: 'Sila masukkan alamat emel yang sah.',
      url: 'Sila masukkan URL yang sah.'
    },
    patternMismatch: 'Format yang dimasukkan tidak sah.',
    tooLong: 'Teks yang dimasukkan terlalu panjang.',
    tooShort: 'Teks yang dimasukkan terlalu pendek.',
    rangeOverflow: 'Nilai yang dimasukkan terlalu besar.',
    rangeUnderflow: 'Nilai yang dimasukkan terlalu kecil.',
    stepMismatch: 'Nilai yang dimasukkan tidak sah.'
  };

  // Apply custom messages to all form inputs
  $('input, select, textarea').each(function () {
    var input = this;

    // Override setCustomValidity for this input
    input.addEventListener('invalid', function (e) {
      var validity = this.validity;
      var customMessage = '';

      if (validity.valueMissing) {
        customMessage = customMessages.valueMissing;
      } else if (validity.typeMismatch) {
        if (this.type === 'email') {
          customMessage = customMessages.typeMismatch.email;
        } else if (this.type === 'url') {
          customMessage = customMessages.typeMismatch.url;
        }
      } else if (validity.patternMismatch) {
        customMessage = customMessages.patternMismatch;
      } else if (validity.tooLong) {
        customMessage = customMessages.tooLong;
      } else if (validity.tooShort) {
        customMessage = customMessages.tooShort;
      } else if (validity.rangeOverflow) {
        customMessage = customMessages.rangeOverflow;
      } else if (validity.rangeUnderflow) {
        customMessage = customMessages.rangeUnderflow;
      } else if (validity.stepMismatch) {
        customMessage = customMessages.stepMismatch;
      }

      if (customMessage) {
        this.setCustomValidity(customMessage);
      }
    });

    // Reset custom validity when input changes
    input.addEventListener('input', function () {
      this.setCustomValidity('');
    });
  });
};

// Common validation event handlers
$(document).ready(function () {
  // Clear validation errors when user starts typing in fields or changes Select2
  $(document).on('input change', 'input[name], select[name], textarea[name]', function () {
    var field = $(this);

    // Remove validation error for this specific field
    field.removeClass('is-invalid');
    field.siblings('.invalid-feedback').remove();

    // Handle Select2 error clearing
    if (field.hasClass('select2-hidden-accessible')) {
      field.next('.select2-container').removeClass('is-invalid');
      field.next('.select2-container').siblings('.invalid-feedback').remove();
    }
  });

  // Handle Select2 specific change events
  $(document).on('select2:select select2:unselect', '#cat_id, #subcat_id', function () {
    var field = $(this);
    // Remove validation errors for Select2 fields
    field.removeClass('is-invalid');
    field.next('.select2-container').removeClass('is-invalid');
    field.next('.select2-container').siblings('.invalid-feedback').remove();
  });
});
