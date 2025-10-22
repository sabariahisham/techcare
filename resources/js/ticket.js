'use strict';

// Check if jQuery is available
if (typeof $ === 'undefined') {
  console.error('jQuery is not loaded!');
} else {
  console.log('jQuery is loaded successfully');
}

// Ticket form functionality
$(function () {
  console.log('Document ready - ticket.js loaded');

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

  // Initialize Select2 for Edit Ticket form
  function initializeEditTicketSelect2() {
    // Check if Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
      console.error('Select2 is not loaded!');
      return;
    }

    $('#edit_cat_id').select2({
      placeholder: '-- Pilih Kategori --',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });

    $('#edit_subcat_id').select2({
      placeholder: '-- Pilih Sub Kategori --',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });
  }

  // Initialize when Add Ticket offcanvas is shown
  $('#offcanvasAddTicket').on('shown.bs.offcanvas', function () {
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

  // Initialize when Edit Ticket offcanvas is shown
  $('#offcanvasEditTicket').on('shown.bs.offcanvas', function () {
    // Destroy existing select2 instances safely
    try {
      if ($('#edit_cat_id').hasClass('select2-hidden-accessible')) {
        $('#edit_cat_id').select2('destroy');
      }
      if ($('#edit_subcat_id').hasClass('select2-hidden-accessible')) {
        $('#edit_subcat_id').select2('destroy');
      }
    } catch (e) {
      console.log('No existing Select2 to destroy');
    }

    // Disable HTML5 validation for custom handling
    $('#editTicketForm').attr('novalidate', 'novalidate');

    // Remove required attributes from fields that use custom validation
    $('#edit_cat_id, #edit_subcat_id').removeAttr('required');

    // Reinitialize with proper configuration
    setTimeout(function () {
      initializeEditTicketSelect2();
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
  });

  $('#offcanvasEditTicket').on('hidden.bs.offcanvas', function () {
    try {
      if ($('#edit_cat_id').hasClass('select2-hidden-accessible')) {
        $('#edit_cat_id').select2('destroy');
      }
      if ($('#edit_subcat_id').hasClass('select2-hidden-accessible')) {
        $('#edit_subcat_id').select2('destroy');
      }
    } catch (e) {
      console.log('Select2 cleanup error:', e);
    }
  });

  // Handle select2 selection events
  $(document).on('select2:select', '#cat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
    // Trigger change for subcategory loading
    $(this).trigger('change');
  });

  $(document).on('select2:select', '#edit_cat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
    // Trigger change for subcategory loading
    $(this).trigger('change');
  });

  $(document).on('select2:select', '#subcat_id, #edit_subcat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
  });
  // Handle ticket type change (ADD FORM)
  $('#ticketType').on('change', function () {
    if ($(this).val() == '0') {
      $('#selfInfo').addClass('d-none');
      $('#otherInfo').removeClass('d-none');
    } else {
      $('#selfInfo').removeClass('d-none');
      $('#otherInfo').addClass('d-none');
    }
  });

  // Handle ticket type change (EDIT FORM) - TAMBAH INI
  $('#edit_ticketType').on('change', function () {
    if ($(this).val() == '0') {
      $('#edit_selfInfo').addClass('d-none');
      $('#edit_otherInfo').removeClass('d-none');
    } else {
      $('#edit_selfInfo').removeClass('d-none');
      $('#edit_otherInfo').addClass('d-none');
    }
  });

  // Handle category change for subcategory (ADD FORM)
  $('#cat_id').change(function () {
    var catID = $(this).val();
    console.log('Add category changed:', catID);

    if (catID) {
      $.ajax({
        url: '/get-subcategory/' + catID,
        type: 'GET',
        success: function (data) {
          console.log('Add subcategories loaded:', data);

          $('#subcat_id').empty();
          $('#subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');

          $.each(data, function (key, value) {
            $('#subcat_id').append('<option value="' + value.id + '">' + value.name + '</option>');
          });
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          if (typeof toastr !== 'undefined') {
            toastr.error('Ralat memuat subkategori');
          }
        }
      });
    } else {
      $('#subcat_id').empty();
      $('#subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');
    }
  });

  // Handle category change for subcategory (EDIT FORM) - TAMBAH INI
  $('#edit_cat_id').change(function () {
    var catID = $(this).val();
    console.log('Edit category changed:', catID);

    if (catID) {
      $.ajax({
        url: '/get-subcategory/' + catID,
        type: 'GET',
        success: function (data) {
          console.log('Edit subcategories loaded:', data);

          // Clear and repopulate subcategory dropdown
          $('#edit_subcat_id').empty();
          $('#edit_subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');

          $.each(data, function (key, value) {
            $('#edit_subcat_id').append('<option value="' + value.id + '">' + value.name + '</option>');
          });

          // Reinitialize Select2 if it was destroyed
          if (!$('#edit_subcat_id').hasClass('select2-hidden-accessible')) {
            setTimeout(function () {
              $('#edit_subcat_id').select2({
                placeholder: '-- Pilih Sub Kategori --',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#offcanvasEditTicket'),
                theme: 'bootstrap-5'
              });
            }, 100);
          }
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          if (typeof toastr !== 'undefined') {
            toastr.error('Ralat memuat subkategori');
          }
        }
      });
    } else {
      $('#edit_subcat_id').empty();
      $('#edit_subcat_id').append('<option value="">-- Pilih Sub Kategori --</option>');
    }
  });

  // Function to clear all validation errors
  function clearValidationErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Also clear Select2 validation styling
    $('.select2-container').removeClass('is-invalid');
  }

  // Function to display validation errors
  function displayValidationErrors(errors) {
    clearValidationErrors();

    Object.keys(errors).forEach(function (fieldName) {
      var field = $('[name="' + fieldName + '"]');
      var errorMessage = errors[fieldName][0];

      // Skip validation display for readonly fields
      if (field.prop('readonly')) {
        return;
      }

      // Add invalid class
      field.addClass('is-invalid');

      // Add error message
      field.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
    });
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
      // Bagi Pihak - validate other person fields
      var otherNameField = $('[name="other_name"]');
      var otherEmailField = $('[name="other_email"]');

      // Only validate if field is not readonly and is visible
      if (!otherNameField.prop('readonly') && otherNameField.is(':visible')) {
        var otherName = otherNameField.val();
        if (!otherName || otherName.trim() === '') {
          errors['other_name'] = ['Nama pengadu diperlukan untuk aduan bagi pihak.'];
          isValid = false;
        }
      }

      if (!otherEmailField.prop('readonly') && otherEmailField.is(':visible')) {
        var otherEmail = otherEmailField.val();
        if (!otherEmail || otherEmail.trim() === '') {
          errors['other_email'] = ['Emel diperlukan untuk aduan bagi pihak.'];
          isValid = false;
        } else if (!isValidEmail(otherEmail)) {
          errors['other_email'] = ['Format emel (bagi pihak) tidak sah.'];
          isValid = false;
        }
      }
    }

    // Common validations
    var title = $('[name="tick_title"]').val();
    var category = $('[name="cat_id"]').val();
    var subcategory = $('[name="subcat_id"]').val();
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

    if (!description || description.trim() === '') {
      errors['tick_desc'] = ['Keterangan aduan diperlukan.'];
      isValid = false;
    }

    if (!isValid) {
      displayValidationErrors(errors);
    }

    return isValid;
  }

  // Function to validate email format
  function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Function to override HTML5 validation messages to Bahasa Melayu
  function setCustomValidationMessages() {
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
  }

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

    // Disable submit button
    submitBtn.prop('disabled', true).text('Menghantar...');

    $.ajax({
      url: '/tickets',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        // Clear any validation errors
        clearValidationErrors();

        // Close offcanvas
        $('#offcanvasAddTicket').offcanvas('hide');

        // Reset form
        $('#addTicketForm')[0].reset();
        $('#subcat_id').empty().append('<option value="">-- Pilih Sub Kategori --</option>');

        // Reset ticket type display
        $('#selfInfo').removeClass('d-none');
        $('#otherInfo').addClass('d-none');
        $('#ticketType').val('1');

        // Reload datatable
        if ($.fn.DataTable.isDataTable('.datatables-tickets')) {
          $('.datatables-tickets').DataTable().ajax.reload();
        }

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success(response.message || 'Tiket berjaya dicipta!');
        }
      },
      error: function (xhr) {
        var response = xhr.responseJSON;
        if (response && response.errors) {
          // Display validation errors from server
          displayValidationErrors(response.errors);
          toastr.error('Sila betulkan ralat dalam borang.');
        } else {
          toastr.error('Ralat berlaku. Sila cuba lagi.');
        }
      },
      complete: function () {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Simpan & Hantar');
      }
    });
  });

  // Function to validate form based on ticket type (EDIT FORM)
  function validateEditForm() {
    clearValidationErrors();
    var isValid = true;
    var errors = {};

    // For edit form, focus on the main ticket fields that are being updated
    // User information fields are typically pre-populated and readonly

    // Common validations
    var title = $('#editTicketForm [name="tick_title"]').val();
    var category = $('#editTicketForm [name="cat_id"]').val();
    var subcategory = $('#editTicketForm [name="subcat_id"]').val();
    var description = $('#editTicketForm [name="tick_desc"]').val();

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

    if (!description || description.trim() === '') {
      errors['tick_desc'] = ['Keterangan aduan diperlukan.'];
      isValid = false;
    }

    if (!isValid) {
      displayValidationErrors(errors);
    }

    return isValid;
  }

  // Handle form submission (EDIT FORM) - TAMBAH INI
  $('#editTicketForm').on('submit', function (e) {
    e.preventDefault();

    // Client-side validation
    if (!validateEditForm()) {
      toastr.error('Sila betulkan ralat dalam borang sebelum menghantar.');
      return;
    }

    var formData = new FormData(this);
    var ticketId = $('#edit_ticket_id').val();
    var submitBtn = $(this).find('button[type="submit"]');

    // Add method spoofing for PUT request
    formData.append('_method', 'PUT');

    // Disable submit button
    submitBtn.prop('disabled', true).text('Mengemas kini...');

    $.ajax({
      url: '/tickets/' + ticketId,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        // Clear any validation errors
        clearValidationErrors();

        // Close offcanvas
        $('#offcanvasEditTicket').offcanvas('hide');

        // Reload datatable
        if ($.fn.DataTable.isDataTable('.datatables-tickets')) {
          $('.datatables-tickets').DataTable().ajax.reload();
        }

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success(response.message || 'Tiket berjaya dikemas kini!');
        }
      },
      error: function (xhr) {
        var response = xhr.responseJSON;
        if (response && response.errors) {
          // Display validation errors from server
          displayValidationErrors(response.errors);
          toastr.error('Sila betulkan ralat dalam borang.');
        } else {
          toastr.error('Ralat berlaku. Sila cuba lagi.');
        }
      },
      complete: function () {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Kemaskini Tiket');
      }
    });
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
            $('.datatables-tickets').DataTable().ajax.reload();
            Swal.fire('Dipadam!', 'Tiket telah dipadam.', 'success');
          },
          error: function () {
            Swal.fire('Ralat!', 'Ralat berlaku semasa memadam tiket.', 'error');
          }
        });
      }
    });
  });

  // Function to load ticket data for editing
  window.loadTicketData = function (ticket) {
    console.log('Loading ticket data:', ticket);

    // Set basic ticket information
    $('#edit_ticket_id').val(ticket.id);
    $('#edit_tick_title').val(ticket.ticket_title);  // Updated field mapping
    $('#edit_tick_desc').val(ticket.ticket_desc);    // Updated field mapping

    // Clear any previous validation errors
    clearValidationErrors();

    // Set ticket type and trigger change first
    $('#edit_ticketType').val(ticket.ticket_type).trigger('change');

    // Set category - make sure to destroy and reinitialize Select2 if needed
    if ($('#edit_cat_id').hasClass('select2-hidden-accessible')) {
      $('#edit_cat_id').select2('destroy');
    }

    $('#edit_cat_id').val(ticket.cat_id);

    // Reinitialize Select2 for category
    initializeEditTicketSelect2();

    // Trigger change to load subcategories
    $('#edit_cat_id').trigger('change');

    // Wait for subcategories to load, then set the value
    setTimeout(function () {
      $('#edit_subcat_id').val(ticket.subcat_id);

      // Reinitialize Select2 for subcategory if needed
      if ($('#edit_subcat_id').hasClass('select2-hidden-accessible')) {
        $('#edit_subcat_id').select2('destroy');
        setTimeout(function () {
          initializeEditTicketSelect2();
        }, 100);
      }
    }, 800);

    // If it's for other person, populate the fields
    if (ticket.ticket_type == 0) {
      $('#edit_other_name').val(ticket.other_name || '');
      $('#edit_other_email').val(ticket.other_email || '');
      $('#edit_other_sektor').val(ticket.other_sektor || '');
      $('#edit_other_agency').val(ticket.other_agency || '');
      $('#edit_other_seksyen').val(ticket.other_seksyen || '');
      $('#edit_other_unit').val(ticket.other_unit || '');
    }

    console.log('Ticket data loaded successfully');
  };

  // Handle edit button click - TAMBAH INI
  $(document).on('click', '.edit-record', function () {
    var ticketId = $(this).data('id');

    // Fetch ticket data
    $.ajax({
      url: '/tickets/' + ticketId + '/edit',
      type: 'GET',
      success: function (response) {
        // Load data into form
        loadTicketData(response.ticket);

        // Show offcanvas
        $('#offcanvasEditTicket').offcanvas('show');

        // Initialize custom validation messages for edit form
        setCustomValidationMessages();

        // Clear any existing validation errors since we're loading valid data
        clearValidationErrors();
      },
      error: function () {
        toastr.error('Ralat memuat data tiket');
      }
    });
  });

  // Clear validation errors when ticket type changes
  $(document).on('change', '#ticketType, #edit_ticket_type', function () {
    clearValidationErrors();
  });

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
  $(document).on('select2:select select2:unselect', '#cat_id, #subcat_id, #edit_cat_id, #edit_subcat_id', function () {
    var field = $(this);
    field.removeClass('is-invalid');
    field.next('.select2-container').removeClass('is-invalid');
    field.next('.select2-container').siblings('.invalid-feedback').remove();
  });

  // Initialize custom validation messages on document ready
  setCustomValidationMessages();

  // Re-initialize when offcanvas is shown
  $('#offcanvasAddTicket, #offcanvasEditTicket').on('shown.bs.offcanvas', function () {
    setCustomValidationMessages();
  });
});
