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
      placeholder: 'Pilih Kategori',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasAddTicket'),
      theme: 'bootstrap-5'
    });

    $('#subcat_id').select2({
      placeholder: 'Pilih Sub Kategori',
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
    if ($(this).val() == '2') {
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
    var catText = $('#cat_id option:selected').text();
    console.log('Add category changed:', catID, catText);

    // Show/hide serial number field based on category selection
    if (catText === 'Sewaan') {
      showSerialNumberField();
    } else {
      hideSerialNumberField();
    }

    if (catID) {
      $.ajax({
        url: '/get-subcategory/' + catID,
        type: 'GET',
        success: function (response) {
          console.log('Add subcategories loaded:', response);

          $('#subcat_id').empty();
          $('#subcat_id').append('<option value="">Pilih Sub Kategori</option>');

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
      $('#subcat_id').append('<option value="">Pilih Sub Kategori</option>');
    }
  });

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

    // Validate file attachments using attachment.js function
    var attachmentValidation = validateAttachments();
    if (!attachmentValidation.isValid) {
      Object.assign(errors, attachmentValidation.errors);
      isValid = false;
    }

    if (!isValid) {
      displayValidationErrors(errors);
    }

    return isValid;
  }

  // Add file validation listeners (using attachment.js function)
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

        // Reset form
        $('#addTicketForm')[0].reset();
        $('#subcat_id').empty().append('<option value="">Pilih Sub Kategori</option>');

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

        // Close offcanvas and handle table reload on complete close
        var $offcanvas = $('#offcanvasAddTicket');

        // Set up one-time event listener for when offcanvas is fully closed
        $offcanvas.one('hidden.bs.offcanvas', function () {
          console.log('Offcanvas fully closed, reloading DataTables...');

          // Call the global reload function which has comprehensive table detection
          if (typeof window.reloadAllTables === 'function') {
            window.reloadAllTables();
          } else {
            // Fallback: reload tables directly if global function not available
            setTimeout(function () {
              if (window.dt_my_tickets && window.dt_my_tickets.ajax) {
                window.dt_my_tickets.ajax.reload(null, false);
                console.log('Reloaded dt_my_tickets');
              }
              if (window.dt_manage_tickets && window.dt_manage_tickets.ajax) {
                window.dt_manage_tickets.ajax.reload(null, false);
                console.log('Reloaded dt_manage_tickets');
              }

              // Show brief success notification about table update
              if (typeof toastr !== 'undefined') {
                toastr.success('Senarai tiket dikemaskini dengan tiket baharu', '', {
                  timeOut: 3000,
                  positionClass: 'toast-bottom-right',
                  closeButton: false,
                  progressBar: true,
                  preventDuplicates: true
                });
              }
            }, 100);
          }
        });

        // Now close the offcanvas
        $offcanvas.offcanvas('hide');

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
        // Re-enable submit button and remove loading spinner
        submitBtn.prop('disabled', false).text('Simpan & Hantar');
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
      showDenyButton: false,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Tidak'
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

// End of file
