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
  console.log('Reloading all DataTables...');

  var tablesReloaded = 0;
  var totalTables = 0;
  var reloadPromises = [];

  // Function to create a reload promise for a DataTable
  function createReloadPromise(table, tableName) {
    return new Promise(function (resolve) {
      try {
        // Add loading state visual feedback
        var tableWrapper = $(table.table().node()).closest('.card-datatable');
        tableWrapper.addClass('datatable-refreshing');

        table.ajax.reload(function (json) {
          console.log(tableName + ' reloaded successfully', json ? 'with ' + (json.recordsTotal || 0) + ' total records' : '');
          tablesReloaded++;

          // Remove loading state
          setTimeout(function () {
            tableWrapper.removeClass('datatable-refreshing');
          }, 300);

          resolve(true);
        }, false);
      } catch (e) {
        console.error('Error reloading ' + tableName + ':', e);

        // Remove loading state even on error
        var tableWrapper = $(table.table().node()).closest('.card-datatable');
        tableWrapper.removeClass('datatable-refreshing');

        tablesReloaded++;
        resolve(false);
      }
    });
  }

  // Try global variables first (preferred method)
  if (window.dt_my_tickets && typeof window.dt_my_tickets.ajax === 'object' && window.dt_my_tickets.ajax) {
    totalTables++;
    console.log('Found My Tickets DataTable via global variable');
    reloadPromises.push(createReloadPromise(window.dt_my_tickets, 'My Tickets'));
  }

  if (window.dt_manage_tickets && typeof window.dt_manage_tickets.ajax === 'object' && window.dt_manage_tickets.ajax) {
    totalTables++;
    console.log('Found Manage Tickets DataTable via global variable');
    reloadPromises.push(createReloadPromise(window.dt_manage_tickets, 'Manage Tickets'));
  }

  // Fallback: Try to detect other DataTables by their CSS classes
  var fallbackSelectors = ['.datatables-tickets', '.datatables-my-tickets', '.datatables-manage-tickets'];

  fallbackSelectors.forEach(function (selector) {
    // Only check if we haven't already found this table via global variables
    var skipSelector = false;
    if (selector === '.datatables-my-tickets' && window.dt_my_tickets) skipSelector = true;
    if (selector === '.datatables-manage-tickets' && window.dt_manage_tickets) skipSelector = true;

    if (!skipSelector && $.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
      var table = $(selector).DataTable();
      if (table && table.ajax) {
        totalTables++;
        console.log('Found DataTable by selector:', selector);
        reloadPromises.push(createReloadPromise(table, 'DataTable (' + selector + ')'));
      }
    }
  });

  console.log('Total tables found:', totalTables);

  // Wait for all reloads to complete
  if (reloadPromises.length > 0) {
    Promise.all(reloadPromises).then(function () {
      console.log('All DataTables reload completed. Tables reloaded:', tablesReloaded);

      // Table reload completed without visual feedback
      setTimeout(function () {
        // Silent reload - no notifications or visual effects
      }, 200);
    });
  } else {
    console.log('No DataTables found to reload. Checking for table elements...');

    // Check if table elements exist but aren't initialized
    var tableElements = $('.datatables-my-tickets, .datatables-manage-tickets, .datatables-tickets');
    if (tableElements.length > 0) {
      console.log('Found', tableElements.length, 'table elements, but they appear to not be initialized as DataTables yet.');
    } else {
      console.log('No table elements found at all.');
    }

    // Still show a success message to indicate the operation completed
    if (typeof toastr !== 'undefined') {
      toastr.info('Tiket berjaya dicipta! Sila refresh halaman untuk melihat tiket terbaru.', '', {
        timeOut: 5000,
        positionClass: 'toast-bottom-right',
        closeButton: true,
        progressBar: true
      });
    }
  }
};

// Enhanced success notification function for ticket operations
window.showTicketSuccessAlert = function (message, ticketData = null) {
  // Create enhanced success message with ticket number if available
  var successMessage = message || 'Operasi berjaya!';

  if (ticketData && ticketData.ticket_nolog) {
    successMessage = `Tiket berjaya dicipta! <br> No. Log: ${ticketData.ticket_nolog}`;
  }

  // Show main success toast
  if (typeof toastr !== 'undefined') {
    toastr.success(successMessage);
  }

  // Removed visual highlighting effect - no longer needed
};

// Function to check if value is empty/null/undefined and return fallback
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

  // Enhanced DataTable reload mechanism with retry logic
  window.forceReloadDataTables = function (maxRetries = 3, retryDelay = 1000) {
    console.log('Force reloading DataTables with retry mechanism...');

    function attemptReload(attempt) {
      console.log(`Reload attempt ${attempt}/${maxRetries}`);

      // Check if DataTables are initialized
      var myTicketsReady = window.dt_my_tickets && typeof window.dt_my_tickets.ajax === 'object';
      var manageTicketsReady = window.dt_manage_tickets && typeof window.dt_manage_tickets.ajax === 'object';

      if (myTicketsReady || manageTicketsReady) {
        console.log('DataTables found, proceeding with reload...');
        if (typeof window.reloadAllTables === 'function') {
          window.reloadAllTables();
        } else if (typeof reloadDataTablesAfterSubmission === 'function') {
          reloadDataTablesAfterSubmission();
        }
        return true;
      } else if (attempt < maxRetries) {
        console.log(`DataTables not ready yet, retrying in ${retryDelay}ms...`);
        setTimeout(function () {
          attemptReload(attempt + 1);
        }, retryDelay);
        return false;
      } else {
        console.warn('DataTables not available after maximum retries. User may need to refresh page.');
        if (typeof toastr !== 'undefined') {
          toastr.warning(
            'Sila refresh halaman untuk melihat tiket terbaru.',
            'Tiket Berjaya Dihantar',
            {
              timeOut: 5000,
              closeButton: true,
              progressBar: true
            }
          );
        }
        return false;
      }
    }

    return attemptReload(1);
  };

  // Listen for custom events that might trigger table reloads
  $(document).on('ticketSubmitted', function (event, ticketData) {
    console.log('Ticket submitted event received:', ticketData);
    setTimeout(function () {
      window.forceReloadDataTables();
    }, 500);
  });

  // Backup mechanism: Monitor for successful ticket submissions and auto-refresh tables
  var lastTicketCount = {};

  function monitorTicketChanges() {
    if (window.dt_my_tickets && typeof window.dt_my_tickets.ajax === 'object') {
      var currentPage = window.dt_my_tickets.page.info();
      if (currentPage && currentPage.recordsTotal !== undefined) {
        var currentCount = currentPage.recordsTotal;
        if (lastTicketCount.myTickets !== undefined && currentCount > lastTicketCount.myTickets) {
          console.log('New ticket detected, refreshing table...');
          window.dt_my_tickets.ajax.reload(null, false);
        }
        lastTicketCount.myTickets = currentCount;
      }
    }

    if (window.dt_manage_tickets && typeof window.dt_manage_tickets.ajax === 'object') {
      var currentPage = window.dt_manage_tickets.page.info();
      if (currentPage && currentPage.recordsTotal !== undefined) {
        var currentCount = currentPage.recordsTotal;
        if (lastTicketCount.manageTickets !== undefined && currentCount > lastTicketCount.manageTickets) {
          console.log('New ticket detected in manage tickets, refreshing table...');
          window.dt_manage_tickets.ajax.reload(null, false);
        }
        lastTicketCount.manageTickets = currentCount;
      }
    }
  }

  // Set up periodic monitoring (every 10 seconds, but only when page is active)
  setInterval(function () {
    if (!document.hidden && typeof window.dt_my_tickets !== 'undefined' || typeof window.dt_manage_tickets !== 'undefined') {
      monitorTicketChanges();
    }
  }, 10000);
});
