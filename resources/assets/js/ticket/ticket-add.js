/**
 * Ticket Add/Log Functionality
 * Handles ticket creation form, validation, and submission
 */

'use strict';

$(function () {
  console.log('Ticket add functionality loaded');

  // Initialize Select2 for Add Ticket form
  function initializeAddTicketSelect2() {
    // Check if Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
      console.error('Select2 is not loaded!');
      return;
    }

    $('#cat_id').select2({
      placeholder: '-- Pilih Kategori --',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasAddTicket'),
      theme: 'bootstrap-5'
    });

    $('#subcat_id').select2({
      placeholder: '-- Pilih Sub Kategori --',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasAddTicket'),
      theme: 'bootstrap-5'
    });
  }

  // Initialize when Add Ticket offcanvas is shown
  $('#offcanvasAddTicket').on('shown.bs.offcanvas', function () {
    // Auto-scroll to top of the offcanvas content
    setTimeout(function () {
      var offcanvasBody = $('#offcanvasAddTicket .offcanvas-body');
      if (offcanvasBody.length > 0) {
        offcanvasBody.scrollTop(0);
      }
    }, 100);

    // Destroy existing select2 instances safely
    try {
      if ($('#cat_id').hasClass('select2-hidden-accessible')) {
        $('#cat_id').select2('destroy');
      }
      if ($('#subcat_id').hasClass('select2-hidden-accessible')) {
        $('#subcat_id').select2('destroy');
      }
    } catch (e) {
      console.log('No existing Select2 to destroy');
    }

    // Disable HTML5 validation for custom handling
    $('#addTicketForm').attr('novalidate', 'novalidate');

    // Remove required attributes from fields that use custom validation
    $('#cat_id, #subcat_id').removeAttr('required');

    // Reinitialize with proper configuration
    setTimeout(function () {
      initializeAddTicketSelect2();
    }, 150);
  });

  // Clean up when offcanvas is hidden
  $('#offcanvasAddTicket').on('hidden.bs.offcanvas', function () {
    try {
      if ($('#cat_id').hasClass('select2-hidden-accessible')) {
        $('#cat_id').select2('destroy');
      }
      if ($('#subcat_id').hasClass('select2-hidden-accessible')) {
        $('#subcat_id').select2('destroy');
      }
    } catch (e) {
      console.log('Select2 cleanup error:', e);
    }

    // Hide Serial Number field when form is closed
    hideSerialNumberField();
  });

  // Handle select2 selection events
  $(document).on('select2:select', '#cat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
    // Trigger change for subcategory loading
    $(this).trigger('change');
  });

  // Handle Select2 selection for subcategory dropdown
  $(document).on('select2:select', '#subcat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
    // Trigger change for Serial Number field visibility
    $(this).trigger('change');
  });

  // Handle ticket type change (ADD FORM)
  $('#ticketType').on('change', function () {
    if ($(this).val() == '0') {
      $('#selfInfo').addClass('d-none');
      $('#otherInfo').removeClass('d-none');
      // Hide form fields until user is found
      $('#ticketFormFields').addClass('d-none');
      // Reset search when switching to "Bagi Pihak"
      if (window.ticketUserSearch) {
        window.ticketUserSearch.resetUserSearch();
      }
    } else {
      $('#selfInfo').removeClass('d-none');
      $('#otherInfo').addClass('d-none');
      // Show form fields for "Diri Sendiri"
      $('#ticketFormFields').removeClass('d-none');
    }
  });

  // Handle category change for subcategory (ADD FORM)
  $('#cat_id').change(function () {
    var catID = $(this).val();
    console.log('Add category changed:', catID);

    // Hide serial number field when category changes
    hideSerialNumberField();

    if (catID) {
      $.ajax({
        url: '/get-subcategory/' + catID,
        type: 'GET',
        success: function (response) {
          console.log('Add subcategories loaded:', response);

          $('#subcat_id').empty();
          $('#subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');

          if (response.success && response.data && response.data.length > 0) {
            $.each(response.data, function (key, subcategory) {
              $('#subcat_id').append('<option value="' + subcategory.id + '">' + subcategory.subcat_name + '</option>');
            });
          } else {
            $('#subcat_id').append('<option value="">Tiada sub kategori tersedia</option>');
          }
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          if (typeof toastr !== 'undefined') {
            toastr.error('Ralat memuat subkategori');
          }
          $('#subcat_id').empty();
          $('#subcat_id').append('<option value="">Gagal memuatkan sub kategori</option>');
        }
      });
    } else {
      $('#subcat_id').empty();
      $('#subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');
    }
  });

  // Handle subcategory change to show/hide Serial Number field (ADD FORM)
  $('#subcat_id').change(function () {
    var selectedText = $('#subcat_id option:selected').text();
    console.log('Subcategory changed:', selectedText);

    // Check if serial number should be shown
    if (shouldShowSerialNumber(selectedText)) {
      showSerialNumberField();
    } else {
      hideSerialNumberField();
    }
  });

  // Function to check if Serial Number field should be shown
  function shouldShowSerialNumber(subcategoryName) {
    const ictHardwareSubcategories = [
      'Komputer Desktop/Laptop',
      'Peralatan Komputer (Monitor/Mouse/Keyboard/etc)',
      'Pencetak'
    ];

    return ictHardwareSubcategories.includes(subcategoryName);
  }

  // Function to show Serial Number field
  function showSerialNumberField() {
    $('#serialNumberField').show();
    console.log('Serial number field shown');
  }

  // Function to hide Serial Number field and clear its value
  function hideSerialNumberField() {
    $('#serialNumberField').hide();
    $('[name="no_siri"]').val(''); // Clear the field value
    console.log('Serial number field hidden and cleared');
  }

  // Function to validate form based on ticket type (ADD FORM)
  function validateAddForm() {
    clearValidationErrors();
    var isValid = true;
    var errors = {};

    var ticketType = $('#ticketType').val();

    // Validate based on ticket type
    if (ticketType == '1') {
      // Diri Sendiri - validate authenticated user fields (should be pre-filled and readonly)
      var nameField = $('[name="name"]');
      var emailField = $('[name="email"]');

      // Only validate if field is not readonly and is visible
      if (!nameField.prop('readonly') && nameField.is(':visible')) {
        var name = nameField.val();
        if (!name || name.trim() === '') {
          errors['name'] = ['Nama pengadu diperlukan untuk aduan diri sendiri.'];
          isValid = false;
        }
      }

      if (!emailField.prop('readonly') && emailField.is(':visible')) {
        var email = emailField.val();
        if (!email || email.trim() === '') {
          errors['email'] = ['Emel diperlukan untuk aduan diri sendiri.'];
          isValid = false;
        }
      }
    } else if (ticketType == '0') {
      // Bagi Pihak - validate other person fields (new field names)
      var otherNameField = $('#other_name');
      var otherEmailField = $('#other_email');

      // Check if user info is shown (meaning search was successful)
      if ($('#foundUserInfo').is(':visible')) {
        // If user info is shown, validate the readonly fields have values
        var otherName = otherNameField.val();
        var otherEmail = otherEmailField.val();

        if (!otherName || otherName.trim() === '') {
          errors['other_name'] = ['Sila cari dan pilih pengguna untuk aduan bagi pihak.'];
          isValid = false;
        }

        if (!otherEmail || otherEmail.trim() === '') {
          errors['other_email'] = ['Sila cari dan pilih pengguna untuk aduan bagi pihak.'];
          isValid = false;
        }
      } else {
        // If user info is not shown, user hasn't searched or found a user
        errors['other_email'] = ['Sila cari pengguna menggunakan nama untuk aduan bagi pihak.'];
        isValid = false;
      }
    }

    // Common validations
    var title = $('[name="tick_title"]').val();
    var category = $('[name="cat_id"]').val();
    var subcategory = $('[name="subcat_id"]').val();
    var serialNumber = $('[name="no_siri"]').val();
    var description = $('[name="tick_desc"]').val();

    if (!title || title.trim() === '') {
      errors['tick_title'] = ['Tajuk aduan diperlukan.'];
      isValid = false;
    }

    if (!category || category === '') {
      errors['cat_id'] = ['Sila pilih kategori.'];
      isValid = false;
    }

    if (!subcategory || subcategory === '') {
      errors['subcat_id'] = ['Sila pilih sub kategori.'];
      isValid = false;
    }

    // Serial No. field is optional for all categories - no validation required

    if (!description || description.trim() === '') {
      errors['tick_desc'] = ['Keterangan aduan diperlukan.'];
      isValid = false;
    }

    // Validate file attachments from selectedAttachments array
    if (window.selectedAttachments && window.selectedAttachments.length > 0) {
      var allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
      var maxFileSize = 2 * 1024 * 1024; // 2MB in bytes

      for (var i = 0; i < window.selectedAttachments.length; i++) {
        var file = window.selectedAttachments[i];
        var fileName = file.name.toLowerCase();
        var fileExtension = fileName.split('.').pop();

        // Check file extension
        if (!allowedExtensions.includes(fileExtension)) {
          errors['attachments'] = ['Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.'];
          isValid = false;
          break;
        }

        // Check file size
        if (file.size > maxFileSize) {
          errors['attachments'] = ['Saiz fail lampiran tidak boleh melebihi 2MB.'];
          isValid = false;
          break;
        }
      }
    }

    if (!isValid) {
      displayValidationErrors(errors);
    }

    return isValid;
  }

  // Function to validate files immediately when selected
  function validateFileInput(fileInput) {
    var allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    var maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
    var wrapper = $(fileInput).closest('.form-field-wrapper');
    var errorContainer = wrapper.find('.error-text');

    // Clear previous errors
    errorContainer.text('').hide();
    $(fileInput).removeClass('is-invalid');
    wrapper.find('.invalid-feedback').remove(); // Remove any old Bootstrap feedback

    if (fileInput.files && fileInput.files.length > 0) {
      for (var i = 0; i < fileInput.files.length; i++) {
        var file = fileInput.files[i];
        var fileName = file.name.toLowerCase();
        var fileExtension = fileName.split('.').pop();

        // Check file extension
        if (!allowedExtensions.includes(fileExtension)) {
          errorContainer.text('Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.').show();
          $(fileInput).addClass('is-invalid');
          console.log('File validation failed - invalid extension. Added is-invalid class.');
          return false;
        }

        // Check file size
        if (file.size > maxFileSize) {
          errorContainer.text('Saiz fail lampiran tidak boleh melebihi 2MB.').show();
          $(fileInput).addClass('is-invalid');
          console.log('File validation failed - file too large. Added is-invalid class.');
          return false;
        }
      }
    }

    console.log('File validation passed.');
    return true;
  }

  // Add file validation listeners
  $(document).on('change', '[name="attachments[]"]', function () {
    validateFileInput(this);
  });

  // Test function to manually trigger file validation error for testing
  window.testFileValidation = function () {
    var fileInput = $('[name="attachments[]"]').first();
    fileInput.addClass('is-invalid');
    var wrapper = fileInput.closest('.form-field-wrapper');
    var errorContainer = wrapper.find('.error-text');
    errorContainer.text('Test error message - file validation styling test').show();
    console.log('Test file validation styling applied');
  };

  // Test function to clear file validation for testing
  window.clearFileValidation = function () {
    var fileInput = $('[name="attachments[]"]').first();
    fileInput.removeClass('is-invalid');
    var wrapper = fileInput.closest('.form-field-wrapper');
    var errorContainer = wrapper.find('.error-text');
    errorContainer.text('').hide();
    console.log('Test file validation styling cleared');
  };

  // Test function to simulate server-side attachment validation error
  window.testServerFileError = function () {
    var serverErrors = {
      "attachments.0": ["Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG."]
    };
    console.log('Simulating server-side file validation error...');
    displayValidationErrors(serverErrors);
    toastr.error('Sila betulkan ralat dalam borang.');
  };

  // Handle form submission (ADD FORM)
  $('#addTicketForm').on('submit', function (e) {
    e.preventDefault();

    // Prevent default HTML5 validation and use custom validation
    if (this.checkValidity) {
      this.checkValidity = function () { return true; };
    }

    // Client-side validation
    if (!validateAddForm()) {
      toastr.error('Sila betulkan ralat dalam borang sebelum menghantar.');
      return;
    }

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');

    // Handle attachments from the global selectedAttachments array
    // Remove any existing attachment files from FormData first
    formData.delete('attachments[]');

    // Add files from selectedAttachments if available
    if (window.selectedAttachments && window.selectedAttachments.length > 0) {
      console.log('Adding', window.selectedAttachments.length, 'attachments to form data');
      window.selectedAttachments.forEach(function (file, index) {
        formData.append('attachments[]', file);
        console.log('Added attachment:', file.name);
      });
    } else {
      console.log('No attachments to add');
    }

    // Disable submit button and add visual feedback
    submitBtn.prop('disabled', true).text('Menghantar...');
    submitBtn.addClass('submitted');

    // Add visual indicator that tables will be refreshed
    $('.datatables-my-tickets, .datatables-manage-tickets').closest('.card-datatable')
      .addClass('datatable-refreshing');

    // Remove the submitted class after animation
    setTimeout(function () {
      submitBtn.removeClass('submitted');
    }, 600);

    $.ajax({
      url: '/tickets',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log('Ticket submitted successfully:', response);

        // Clear any validation errors
        clearValidationErrors();

        // Show enhanced success notification with ticket details
        showTicketSuccessAlert(
          response.message || 'Tiket berjaya dicipta!',
          response.ticket
        );

        // Close offcanvas with fade effect
        $('#offcanvasAddTicket').offcanvas('hide');

        // Reset form
        $('#addTicketForm')[0].reset();
        $('#subcat_id').empty().append('<option value="">-- Pilih Sub Kategori --</option>');

        // Hide Serial Number field on reset
        hideSerialNumberField();

        // Reset ticket type display
        $('#selfInfo').removeClass('d-none');
        $('#otherInfo').addClass('d-none');
        $('#ticketType').val('1');
        // Show form fields since we're back to "Diri Sendiri"
        $('#ticketFormFields').removeClass('d-none');
        // Restore normal Select2 behavior
        $('#cat_id, #subcat_id').next('.select2-container').removeClass('select2-normal-state');

        // Clear attachments
        if (window.removeAllAttachments) {
          window.removeAllAttachments();
        }

        // Trigger custom event for table reload
        $(document).trigger('ticketSubmitted', [response.ticket]);

        // Reload tables with enhanced feedback after ensuring form is closed and DOM is updated
        setTimeout(function () {
          console.log('Initiating table reload after successful ticket submission...');
          reloadDataTablesAfterSubmission();
        }, 500);

        // Log success for debugging
        if (response.ticket) {
          console.log('New ticket created with ID:', response.ticket.id, 'and number:', response.ticket.ticket_nolog);
        }
      },
      error: function (xhr) {
        console.error('Ticket submission failed:', xhr);
        var response = xhr.responseJSON;

        if (response && response.errors) {
          console.log('Server validation errors for add form:', response.errors);
          // Display validation errors from server
          displayValidationErrors(response.errors);

          // Enhanced error notification
          if (typeof toastr !== 'undefined') {
            toastr.error('Sila betulkan ralat dalam borang sebelum menghantar semula.', 'Ralat Pengesahan', {
              timeOut: 6000,
              extendedTimeOut: 3000,
              closeButton: true,
              progressBar: true
            });
          }
        } else {
          // Generic error handling
          var errorMessage = response && response.message
            ? response.message
            : 'Ralat tidak dijangka berlaku. Sila cuba lagi atau hubungi pentadbir sistem.';

          if (typeof toastr !== 'undefined') {
            toastr.error(errorMessage, 'Ralat Sistem', {
              timeOut: 8000,
              extendedTimeOut: 4000,
              closeButton: true,
              progressBar: true
            });
          }
        }

        // Scroll to first error if validation errors exist
        setTimeout(function () {
          var firstError = $('.is-invalid').first();
          if (firstError.length > 0) {
            $('html, body').animate({
              scrollTop: firstError.offset().top - 150
            }, 500);
            firstError.focus();
          }
        }, 100);
      },
      complete: function () {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Simpan & Hantar');

        // Remove loading indicator from tables
        $('.datatables-my-tickets, .datatables-manage-tickets').closest('.card-datatable')
          .removeClass('datatable-refreshing');
      }
    });
  });

  // Clear validation errors when ticket type changes (add form only)
  $(document).on('change', '#ticketType', function () {
    clearValidationErrors();
  });

  // Initialize custom validation messages on document ready
  setCustomValidationMessages();

  // Re-initialize when add ticket offcanvas is shown
  $('#offcanvasAddTicket').on('shown.bs.offcanvas', function () {
    setCustomValidationMessages();
  });

  // Handle delete ticket
  $(document).on('click', '.delete-record', function () {
    var ticketId = $(this).data('id');

    Swal.fire({
      title: 'Adakah anda pasti?',
      text: "Tiket ini akan dipadam secara kekal!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/tickets/' + ticketId,
          type: 'DELETE',
          data: {
            "_token": $('meta[name="csrf-token"]').attr('content'),
          },
          success: function (response) {
            reloadAllTables();
            Swal.fire('Dipadam!', 'Tiket telah dipadam.', 'success');
          },
          error: function () {
            Swal.fire('Ralat!', 'Ralat berlaku semasa memadam tiket.', 'error');
          }
        });
      }
    });
  });

  // Handle view button click
  $(document).on('click', '.view-record', function () {
    var ticketId = $(this).data('id');

    // Navigate to the detailed view page
    window.location.href = '/tickets/' + ticketId;
  });
});

/**
 * Enhanced DataTable reload function specifically for after ticket submission
 * This function ensures reliable table refresh with multiple fallback mechanisms
 */
function reloadDataTablesAfterSubmission() {
  console.log('Starting enhanced DataTable reload after ticket submission...');

  var reloadPromises = [];
  var reloadedTables = [];

  // Method 1: Try global DataTable instances first
  if (window.dt_my_tickets && typeof window.dt_my_tickets.ajax === 'object') {
    console.log('Attempting to reload My Tickets table via global instance...');
    reloadPromises.push(
      new Promise(function (resolve) {
        try {
          window.dt_my_tickets.ajax.reload(function (json) {
            console.log('My Tickets table reloaded successfully via global instance');
            reloadedTables.push('My Tickets');
            resolve(true);
          }, false);
        } catch (e) {
          console.error('Error reloading My Tickets via global instance:', e);
          resolve(false);
        }
      })
    );
  }

  if (window.dt_manage_tickets && typeof window.dt_manage_tickets.ajax === 'object') {
    console.log('Attempting to reload Manage Tickets table via global instance...');
    reloadPromises.push(
      new Promise(function (resolve) {
        try {
          window.dt_manage_tickets.ajax.reload(function (json) {
            console.log('Manage Tickets table reloaded successfully via global instance');
            reloadedTables.push('Manage Tickets');
            resolve(true);
          }, false);
        } catch (e) {
          console.error('Error reloading Manage Tickets via global instance:', e);
          resolve(false);
        }
      })
    );
  }

  // Method 2: Fallback - Direct jQuery DataTable API
  if (reloadPromises.length === 0) {
    console.log('No global instances found, trying jQuery DataTable API...');

    $('.datatables-my-tickets').each(function () {
      if ($.fn.DataTable.isDataTable(this)) {
        reloadPromises.push(
          new Promise(function (resolve) {
            try {
              $(this).DataTable().ajax.reload(function () {
                console.log('My Tickets table reloaded via jQuery API');
                reloadedTables.push('My Tickets (jQuery)');
                resolve(true);
              }, false);
            } catch (e) {
              console.error('Error reloading My Tickets via jQuery API:', e);
              resolve(false);
            }
          }.bind(this))
        );
      }
    });

    $('.datatables-manage-tickets').each(function () {
      if ($.fn.DataTable.isDataTable(this)) {
        reloadPromises.push(
          new Promise(function (resolve) {
            try {
              $(this).DataTable().ajax.reload(function () {
                console.log('Manage Tickets table reloaded via jQuery API');
                reloadedTables.push('Manage Tickets (jQuery)');
                resolve(true);
              }, false);
            } catch (e) {
              console.error('Error reloading Manage Tickets via jQuery API:', e);
              resolve(false);
            }
          }.bind(this))
        );
      }
    });
  }

  // Method 3: If still no promises, try the global reload function
  if (reloadPromises.length === 0 && typeof window.reloadAllTables === 'function') {
    console.log('Using global reloadAllTables function as fallback...');
    reloadPromises.push(
      new Promise(function (resolve) {
        try {
          window.reloadAllTables();
          reloadedTables.push('All Tables (Global)');
          resolve(true);
        } catch (e) {
          console.error('Error with global reloadAllTables:', e);
          resolve(false);
        }
      })
    );
  }

  // Execute reload promises and provide feedback
  if (reloadPromises.length > 0) {
    Promise.all(reloadPromises).then(function (results) {
      var successCount = results.filter(Boolean).length;
      console.log(`DataTable reload completed. ${successCount}/${results.length} tables reloaded successfully.`);
      console.log('Reloaded tables:', reloadedTables);

      // Show success notification
      if (typeof toastr !== 'undefined') {
        toastr.success(
          'Tiket berjaya dihantar! Senarai tiket telah dikemaskini.',
          'Berjaya',
          {
            timeOut: 4000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-bottom-right',
            preventDuplicates: true
          }
        );
      }

      // Add visual feedback to refreshed tables
      setTimeout(function () {
        $('.datatables-my-tickets, .datatables-manage-tickets').closest('.card-datatable')
          .addClass('table-refresh-success');

        setTimeout(function () {
          $('.table-refresh-success').removeClass('table-refresh-success');
        }, 2000);
      }, 300);
    });
  } else {
    console.warn('No DataTable instances found for reload. Tables may not be initialized yet.');

    // Show fallback notification
    if (typeof toastr !== 'undefined') {
      toastr.info(
        'Tiket berjaya dihantar! Sila refresh halaman untuk melihat tiket terbaru.',
        'Berjaya',
        {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-bottom-right'
        }
      );
    }
  }
}
