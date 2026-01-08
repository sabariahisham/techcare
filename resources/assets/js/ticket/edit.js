/**
 * Ticket Edit/Update Functionality
 * Handles ticket editing form, validation, and submission
 */

'use strict';

$(function () {
  console.log('Ticket edit functionality loaded');

  // Validation helper functions
  function clearValidationErrors() {
    $('#editTicketForm .is-invalid').removeClass('is-invalid');
    $('#editTicketForm .invalid-feedback').text('').hide();
    $('#editTicketForm .was-validated').removeClass('was-validated');
  }

  function displayValidationErrors(errors) {
    clearValidationErrors();

    // Clear Select2 custom validation classes
    $('#editCatId, #editSubcatId').removeClass('is-invalid');

    Object.keys(errors).forEach(function (fieldName) {
      var fieldElement = $('#editTicketForm [name="' + fieldName + '"]');
      var errorMessages = errors[fieldName];

      if (fieldElement.length > 0) {
        fieldElement.addClass('is-invalid');
        var feedbackElement = fieldElement.siblings('.invalid-feedback');

        if (feedbackElement.length > 0) {
          feedbackElement.text(errorMessages[0]).show();
        }

        // Handle Select2 fields specially
        if (fieldElement.hasClass('select2-hidden-accessible')) {
          fieldElement.siblings('.select2-container').addClass('is-invalid');
        }
      }
    });
  }

  function validateEditForm() {
    var isValid = true;
    var errors = {};

    // Clear previous validation
    clearValidationErrors();

    // Validate required fields
    var requiredFields = [
      { name: 'tick_title', label: 'Tajuk aduan' },
      { name: 'tick_desc', label: 'Keterangan aduan' },
      { name: 'cat_id', label: 'Kategori' },
      { name: 'subcat_id', label: 'Sub kategori' }
    ];

    // Add conditional required fields based on ticket type
    var ticketType = $('#editTicketType').val();
    if (ticketType == '0') {
      // Bagi Pihak - require other_email
      requiredFields.push({ name: 'other_email', label: 'Emel pengadu' });
    }

    requiredFields.forEach(function (field) {
      var value = $('#editTicketForm [name="' + field.name + '"]').val();
      if (!value || value.trim() === '') {
        errors[field.name] = [field.label + ' diperlukan.'];
        isValid = false;
      }
    });

    // Validate email format if provided
    var emailField = ticketType == '0' ? 'other_email' : 'email';
    var emailValue = $('#editTicketForm [name="' + emailField + '"]').val();
    if (emailValue && !isValidEmail(emailValue)) {
      errors[emailField] = ['Format emel tidak sah.'];
      isValid = false;
    }

    if (!isValid) {
      displayValidationErrors(errors);
    }

    return isValid;
  }

  function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Initialize Select2 for Edit Ticket form
  function initializeEditTicketSelect2() {
    if (typeof $.fn.select2 === 'undefined') {
      console.error('Select2 is not loaded!');
      return;
    }

    $('#editCatId').select2({
      placeholder: 'Pilih Kategori',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });

    $('#editSubcatId').select2({
      placeholder: 'Pilih Sub Kategori',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });
  }

  // Initialize when Edit Ticket offcanvas is shown
  $('#offcanvasEditTicket').on('shown.bs.offcanvas', function () {
    // Auto-scroll to top of the offcanvas content
    setTimeout(function () {
      var offcanvasBody = $('#offcanvasEditTicket .offcanvas-body');
      if (offcanvasBody.length > 0) {
        offcanvasBody.scrollTop(0);
      }
    }, 100);

    // Destroy existing select2 instances safely
    try {
      if ($('#editCatId').hasClass('select2-hidden-accessible')) {
        $('#editCatId').select2('destroy');
      }
      if ($('#editSubcatId').hasClass('select2-hidden-accessible')) {
        $('#editSubcatId').select2('destroy');
      }
    } catch (e) {
      console.log('No existing Select2 to destroy');
    }

    // Disable HTML5 validation for custom handling
    $('#editTicketForm').attr('novalidate', 'novalidate');

    // Remove required attributes from fields that use custom validation
    $('#editCatId, #editSubcatId').removeAttr('required');

    // Reinitialize with proper configuration
    setTimeout(function () {
      initializeEditTicketSelect2();
    }, 150);
  });

  // Clean up when offcanvas is hidden
  $('#offcanvasEditTicket').on('hidden.bs.offcanvas', function () {
    try {
      if ($('#editCatId').hasClass('select2-hidden-accessible')) {
        $('#editCatId').select2('destroy');
      }
      if ($('#editSubcatId').hasClass('select2-hidden-accessible')) {
        $('#editSubcatId').select2('destroy');
      }
    } catch (e) {
      console.log('Select2 cleanup error:', e);
    }

    // Hide Serial Number field when form is closed
    hideEditSerialNumberField();

    // Clear form
    resetEditForm();
  });

  // Handle edit button click
  $(document).on('click', '.edit-record', function () {
    var ticketId = $(this).data('id');
    console.log('Edit ticket:', ticketId);

    // Show loading state
    showEditTicketLoading();

    // Fetch ticket data
    $.ajax({
      url: '/tickets/' + ticketId + '/edit',
      type: 'GET',
      success: function (response) {
        console.log('Ticket data loaded:', response);

        if (response.success) {
          populateEditForm(response.ticket, response.categories, response.subCategories);
          hideEditTicketLoading();
          $('#offcanvasEditTicket').offcanvas('show');
        } else {
          hideEditTicketLoading();
          toastr.error(response.message || 'Ralat memuat data tiket.');
        }
      },
      error: function (xhr) {
        hideEditTicketLoading();
        var response = xhr.responseJSON;
        var errorMessage = response && response.message
          ? response.message
          : 'Ralat memuat data tiket. Sila cuba lagi.';

        toastr.error(errorMessage);
        console.error('Error loading ticket data:', xhr);
      }
    });
  });

  // Show loading state
  function showEditTicketLoading() {
    // You can implement a loading overlay here
    console.log('Loading ticket data...');
  }

  // Hide loading state
  function hideEditTicketLoading() {
    console.log('Loading complete');
  }

  // Populate edit form with ticket data
  function populateEditForm(ticket, categories, subCategories) {
    // Set ticket ID
    $('#editTicketId').val(ticket.id);

    // Set basic ticket data
    $('#editTicketTitle').val(ticket.ticket_title);
    $('#editTicketDesc').val(ticket.ticket_desc);
    $('#editTicketType').val(ticket.ticket_type);
    $('#editTicketTypeHidden').val(ticket.ticket_type); // Set hidden input for form submission
    $('#editNoSiri').val(ticket.serial_number);

    // Populate categories dropdown
    var categoriesOptions = '<option value=\"\">Pilih Kategori</option>';
    categories.forEach(function (category) {
      var selected = category.id == ticket.cat_id ? 'selected' : '';
      categoriesOptions += '<option value=\"' + category.id + '\" ' + selected + '>' + category.cat_name + '</option>';
    });
    $('#editCatId').html(categoriesOptions);

    // Populate subcategories dropdown
    var subCategoriesOptions = '<option value=\"\">Pilih Sub Kategori</option>';
    subCategories.forEach(function (subCategory) {
      var selected = subCategory.id == ticket.subcat_id ? 'selected' : '';
      subCategoriesOptions += '<option value=\"' + subCategory.id + '\" ' + selected + '>' + subCategory.subcat_name + '</option>';
    });
    $('#editSubcatId').html(subCategoriesOptions);

    // Handle ticket type display
    handleEditTicketTypeChange();

    // Show/hide serial number field based on category
    if (ticket.category && ticket.category.cat_name === 'Sewaan') {
      showEditSerialNumberField();
    } else {
      hideEditSerialNumberField();
    }

    // Populate user data based on ticket type
    if (ticket.ticket_type == 0 && ticket.user) {
      // Bagi Pihak - populate other user data
      populateEditOtherUserData(ticket.user);
      $('#editFoundUserInfo').show();
      $('#editTicketFormFields').show();
    } else if (ticket.ticket_type == 1 && ticket.user) {
      // Diri Sendiri - populate authenticated user data in self info section
      populateEditSelfUserData(ticket.user);
    }

    // Display existing attachments if any
    displayExistingAttachments(ticket.attachments || []);
  }

  // Populate self user data for "Diri Sendiri" tickets
  function populateEditSelfUserData(user) {
    // Update the self info fields with actual ticket owner data
    $('#editSelfInfo input[name="name"]').val(user.nama || '');
    $('#editSelfInfo input[name="jawatan"]').val(user.jawatan_name || '-');

    // Handle skim display
    var skimDisplay = '-';
    if (user.nama_skim && user.kod_skim) {
      skimDisplay = user.nama_skim + ' (' + user.kod_skim + ')';
    } else if (user.kod_skim) {
      skimDisplay = user.kod_skim;
    }
    $('#editSelfInfo input[name="skim"]').val(skimDisplay);

    $('#editSelfInfo input[name="gred"]').val(user.nama_gred || '-');
    $('#editSelfInfo input[name="email"]').val(user.email || '');
  }

  // Populate other user data for \"Bagi Pihak\" tickets
  function populateEditOtherUserData(user) {
    $('#editOtherName').val(user.nama);
    $('#editOtherEmail').val(user.email);
  }

  // Handle ticket type change for edit form
  $('#editTicketType').on('change', function () {
    handleEditTicketTypeChange();
  });

  function handleEditTicketTypeChange() {
    var ticketType = $('#editTicketType').val();

    if (ticketType == '0') {
      $('#editSelfInfo').addClass('d-none');
      $('#editOtherInfo').removeClass('d-none');

      // Check if user is already selected
      var otherEmail = $('#editOtherEmail').val();
      if (otherEmail) {
        $('#editTicketFormFields').removeClass('d-none');
      } else {
        $('#editTicketFormFields').addClass('d-none');
      }
    } else {
      $('#editSelfInfo').removeClass('d-none');
      $('#editOtherInfo').addClass('d-none');
      $('#editTicketFormFields').removeClass('d-none');

      // Clear other user fields
      $('#editFoundUserInfo').hide();
      $('#editSearchName').val('');
      $('#editOtherName, #editOtherEmail').val('');
    }

    // Clear validation errors when switching types
    clearValidationErrors();
  }

  // Handle category change for edit form
  $('#editCatId').change(function () {
    var catID = $(this).val();
    var catText = $('#editCatId option:selected').text();
    console.log('Edit category changed:', catID, catText);

    // Clear subcategory
    $('#editSubcatId').html('<option value=\"\">Pilih Sub Kategori</option>');

    // Show/hide serial number field based on category selection
    if (catText === 'Sewaan') {
      showEditSerialNumberField();
    } else {
      hideEditSerialNumberField();
    }

    // Clear validation errors
    clearValidationErrors();

    if (catID) {
      $.ajax({
        url: '/get-subcategory/' + catID,
        type: 'GET',
        success: function (response) {
          $('#editSubcatId').empty();
          $('#editSubcatId').append('<option value=\"\">Pilih Sub Kategori</option>');
          $.each(response, function (index, subCategory) {
            $('#editSubcatId').append('<option value=\"' + subCategory.id + '\">' + subCategory.subcat_name + '</option>');
          });
        },
        error: function () {
          toastr.error('Ralat memuat sub kategori.');
        }
      });
    } else {
      $('#editSubcatId').empty();
      $('#editSubcatId').append('<option value=\"\">Pilih Sub Kategori</option>');
    }
  });

  // Handle subcategory change for edit form
  $('#editSubcatId').change(function () {
    // Clear validation errors when subcategory changes
    clearValidationErrors();
  });

  // Real-time validation for form fields
  $(document).on('input blur', '#editTicketForm input[required], #editTicketForm textarea[required]', function () {
    var field = $(this);
    var fieldName = field.attr('name');
    var value = field.val().trim();

    // Remove existing validation state
    field.removeClass('is-invalid is-valid');
    field.siblings('.invalid-feedback').hide();

    if (value === '') {
      field.addClass('is-invalid');
      field.siblings('.invalid-feedback').text('Medan ini diperlukan.').show();
    } else {
      field.addClass('is-valid');
    }
  });

  // Email validation for edit form
  $(document).on('blur', '#editTicketForm input[type=\"email\"]', function () {
    var field = $(this);
    var value = field.val().trim();

    if (value !== '' && !isValidEmail(value)) {
      field.removeClass('is-valid').addClass('is-invalid');
      field.siblings('.invalid-feedback').text('Format emel tidak sah.').show();
    }
  });

  // Real-time validation for select fields
  $(document).on('change', '#editCatId, #editSubcatId', function () {
    var field = $(this);
    var value = field.val();

    // Remove existing validation state
    field.removeClass('is-invalid is-valid');
    if (field.hasClass('select2-hidden-accessible')) {
      field.siblings('.select2-container').removeClass('is-invalid');
    }
    field.siblings('.invalid-feedback').hide();

    if (!value || value === '') {
      field.addClass('is-invalid');
      if (field.hasClass('select2-hidden-accessible')) {
        field.siblings('.select2-container').addClass('is-invalid');
      }
      field.siblings('.invalid-feedback').text('Sila pilih ' + field.siblings('label').text().toLowerCase() + '.').show();
    } else {
      field.addClass('is-valid');
    }
  });

  // Show/hide serial number field functions for edit
  function showEditSerialNumberField() {
    $('#editSerialNumberField').show();
  }

  function hideEditSerialNumberField() {
    $('#editSerialNumberField').hide();
    $('#editNoSiri').val('');
  }

  // Reset edit form
  function resetEditForm() {
    $('#editTicketForm')[0].reset();
    $('#editTicketId').val('');
    $('#editSubcatId').empty().append('<option value=\"\">Pilih Sub Kategori</option>');
    $('#editFoundUserInfo').hide();
    $('#editExistingAttachments').hide();
    hideEditSerialNumberField();

    // Reset ticket type display
    $('#editSelfInfo').removeClass('d-none');
    $('#editOtherInfo').addClass('d-none');
    $('#editTicketType').val('1');
    $('#editTicketFormFields').removeClass('d-none');

    // Clear validation errors
    clearValidationErrors();

    // Clear attachment files
    if (window.removeAllEditAttachments) {
      window.removeAllEditAttachments();
    }
  }

  // Handle form submission for edit
  $('#editTicketForm').on('submit', function (e) {
    e.preventDefault();

    // Perform client-side validation
    if (!validateEditForm()) {
      toastr.error('Sila betulkan ralat dalam borang sebelum menghantar.');
      return;
    }

    var ticketId = $('#editTicketId').val();
    if (!ticketId) {
      toastr.error('ID tiket tidak dijumpai. Sila cuba lagi.');
      return;
    }

    // Get selected attachments from global variable
    var selectedAttachments = window.editSelectedAttachments || [];

    var formData = new FormData(this);

    // Add selected attachments to form data
    if (selectedAttachments.length > 0) {
      // Remove existing attachment entries to avoid duplication
      formData.delete('attachments[]');

      selectedAttachments.forEach(function (file, index) {
        formData.append('attachments[]', file);
      });
    }

    var submitBtn = $(this).find('button[type=\"submit\"]');

    // Disable submit button and add visual feedback
    submitBtn.prop('disabled', true).text('Mengemaskini...');

    $.ajax({
      url: '/tickets/' + ticketId,
      type: 'POST', // Laravel will handle method spoofing with _method field
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log('Ticket updated successfully:', response);

        // Clear any validation errors
        clearValidationErrors();

        // Show success notification with ticket number
        var ticketNumber = response.ticket && response.ticket.ticket_nolog ? response.ticket.ticket_nolog : 'N/A';
        var successMessage = `Tiket ${ticketNumber} berjaya dikemaskini!`;
        toastr.success(successMessage);

        // Close offcanvas
        $('#offcanvasEditTicket').offcanvas('hide');

        // Reload DataTables
        setTimeout(function () {
          if (typeof window.reloadAllTables === 'function') {
            window.reloadAllTables();
          }
        }, 500);
      },
      error: function (xhr) {
        console.error('Ticket update failed:', xhr);
        var response = xhr.responseJSON;

        if (response && response.errors) {
          console.log('Server validation errors for edit form:', response.errors);
          // Display validation errors from server
          displayValidationErrors(response.errors);
          toastr.error('Sila betulkan ralat dalam borang sebelum menghantar semula.');
        } else {
          var errorMessage = response && response.message
            ? response.message
            : 'Ralat tidak dijangka berlaku. Sila cuba lagi atau hubungi pentadbir sistem.';

          toastr.error(errorMessage);
        }
      },
      complete: function () {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Kemaskini');
      }
    });
  });

  console.log('Ticket edit functionality initialized');
});
