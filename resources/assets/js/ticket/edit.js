/**
 * Ticket Edit/Update Functionality
 * Handles ticket editing form, validation, and submission
 */

import logger from '../logger.js';

'use strict';

$(function () {
  logger.log('Ticket edit functionality loaded');

  // Validation helper functions
  function clearValidationErrors() {
    $('#editTicketForm .is-invalid').removeClass('is-invalid');
    $('#editTicketForm .invalid-feedback').removeClass('d-block').text('').hide();
    $('#editTicketForm .was-validated').removeClass('was-validated');
    // Clear Select2 validation styling
    $('#editTicketForm .select2-container').removeClass('is-invalid');
  }

  function displayValidationErrors(errors) {
    clearValidationErrors();

    Object.keys(errors).forEach(function (fieldName) {
      var fieldElement = $('#editTicketForm [name="' + fieldName + '"]');
      var errorMessages = errors[fieldName];
      var errorMessage = errorMessages[0];

      if (fieldElement.length > 0) {
        // Skip validation display for readonly fields
        if (fieldElement.prop('readonly')) {
          return;
        }

        fieldElement.addClass('is-invalid');

        // Handle Select2 fields specially
        if (fieldElement.hasClass('select2-hidden-accessible')) {
          fieldElement.siblings('.select2-container').addClass('is-invalid');
          var feedbackElement = fieldElement.siblings('.invalid-feedback');
          if (feedbackElement.length > 0) {
            feedbackElement.addClass('d-block').text(errorMessage).show();
          } else {
            fieldElement.siblings('.select2-container').after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
          }
        } else {
          // Handle regular fields
          var feedbackElement = fieldElement.siblings('.invalid-feedback');
          if (feedbackElement.length > 0) {
            feedbackElement.addClass('d-block').text(errorMessage).show();
          } else {
            fieldElement.after('<div class="invalid-feedback d-block">' + errorMessage + '</div>');
          }
        }
      }
    });

    // Scroll to and focus on the first invalid field
    setTimeout(function () {
      var firstInvalidField = $('#editTicketForm .is-invalid').first();
      if (firstInvalidField.length > 0) {
        var scrollTarget = firstInvalidField;

        // If it's a Select2 field, scroll to the container instead
        if (firstInvalidField.hasClass('select2-hidden-accessible')) {
          scrollTarget = firstInvalidField.siblings('.select2-container').first();
        }

        // Scroll to the field
        var offcanvasBody = $('#offcanvasEditTicket .offcanvas-body');
        if (offcanvasBody.length > 0 && scrollTarget.length > 0) {
          var fieldTop = scrollTarget.offset().top;
          var offcanvasTop = offcanvasBody.offset().top;
          var scrollPosition = offcanvasBody.scrollTop() + (fieldTop - offcanvasTop) - 100;

          offcanvasBody.animate({
            scrollTop: scrollPosition
          }, 500);
        }

        // Focus on the field if it's not Select2
        if (!firstInvalidField.hasClass('select2-hidden-accessible')) {
          firstInvalidField.focus();
        } else {
          // Open Select2 dropdown to draw attention
          firstInvalidField.select2('open');
          setTimeout(function () {
            firstInvalidField.select2('close');
          }, 100);
        }
      }
    }, 150);
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
      { name: 'subcat_id', label: 'Sub kategori' },
      { name: 'building_loc', label: 'Lokasi bangunan' },
      { name: 'block_loc', label: 'Blok' },
      { name: 'level_loc', label: 'Aras' }
    ];

    // Add conditional required fields based on ticket type
    var ticketType = $('#editTicketType').val();
    if (ticketType == '2') {
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
    var emailField = ticketType == '2' ? 'other_email' : 'email';
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

    $('#edit_building_loc').select2({
      placeholder: 'Pilih Bangunan',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });

    $('#edit_block_loc').select2({
      placeholder: 'Pilih Blok',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#offcanvasEditTicket'),
      theme: 'bootstrap-5'
    });

    $('#edit_level_loc').select2({
      placeholder: 'Pilih Aras',
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
      logger.log('No existing Select2 to destroy');
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
      logger.log('Select2 cleanup error:', e);
    }

    // Hide Serial Number field when form is closed
    hideEditSerialNumberField();

    // Clear form
    resetEditForm();
  });

  // Handle edit button click
  $(document).on('click', '.edit-record', function () {
    var ticketUuid = $(this).data('uuid');
    logger.log('Edit ticket UUID:', ticketUuid);

    // Show loading state
    showEditTicketLoading();

    // Fetch ticket data using UUID
    $.ajax({
      url: '/tickets/' + ticketUuid + '/edit',
      type: 'GET',
      success: function (response) {
        logger.log('Ticket data loaded:', response);

        if (response.success) {
          populateEditForm(response.ticket, response.location, response.categories, response.subCategories);
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
    logger.log('Loading ticket data...');
  }

  // Hide loading state
  function hideEditTicketLoading() {
    logger.log('Loading complete');
  }

  // Populate edit form with ticket data
  function populateEditForm(ticket, location, categories, subCategories) {
    // Reset attachments before populating form
    $('#editTicketAttachments').val(''); // Clear file input
    window.editSelectedAttachments = []; // Reset global array
    $('#editAttachmentsPreview').empty(); // Clear preview

    // Set ticket UUID instead of ID
    $('#editTicketId').val(ticket.uuid);

    // Set basic ticket data
    $('#editTicketTitle').val(ticket.ticket_title);
    $('#editTicketDesc').val(ticket.ticket_desc);
    $('#editTicketType').val(ticket.ticket_type);
    $('#editTicketTypeHidden').val(ticket.ticket_type); // Set hidden input for form submission
    $('#editNoSiri').val(ticket.serial_number);

    // Populate location fields
    if (location) {
      $('#edit_building_loc').val(location.building_loc || '').trigger('change');
      $('#edit_block_loc').val(location.block_loc || '').trigger('change');
      $('#edit_level_loc').val(location.level_loc || '').trigger('change');
    } else {
      $('#edit_building_loc').val('').trigger('change');
      $('#edit_block_loc').val('').trigger('change');
      $('#edit_level_loc').val('').trigger('change');
    }

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
    if (ticket.ticket_type == 2 && ticket.user) {
      // Bagi Pihak - populate other user data
      populateEditOtherUserData(ticket.user);
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

  // Populate other user data for "Bagi Pihak" tickets
  function populateEditOtherUserData(user) {
    $('#editOtherName').val(user.nama || '');
    $('#editOtherJawatan').val(user.jawatan_name || '-');

    // Handle skim display
    var skimDisplay = '-';
    if (user.nama_skim && user.kod_skim) {
      skimDisplay = user.nama_skim + ' (' + user.kod_skim + ')';
    } else if (user.kod_skim) {
      skimDisplay = user.kod_skim;
    }
    $('#editOtherSkim').val(skimDisplay);

    $('#editOtherGred').val(user.nama_gred || '-');
    $('#editOtherEmail').val(user.email || '');
  }

  // Handle ticket type change for edit form
  $('#editTicketType').on('change', function () {
    handleEditTicketTypeChange();
  });

  function handleEditTicketTypeChange() {
    var ticketType = $('#editTicketType').val();

    if (ticketType == '2') {
      $('#editSelfInfo').addClass('d-none');
      $('#editOtherInfo').removeClass('d-none');
      $('#editTicketFormFields').removeClass('d-none');
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
    logger.log('Edit category changed:', catID, catText);

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
          logger.log('Edit subcategories loaded:', response);

          $('#editSubcatId').empty();
          $('#editSubcatId').append('<option value="">Pilih Sub Kategori</option>');

          if (response.success && response.data && response.data.length > 0) {
            $.each(response.data, function (index, subCategory) {
              $('#editSubcatId').append('<option value="' + subCategory.id + '">' + subCategory.subcat_name + '</option>');
            });
          } else {
            $('#editSubcatId').append('<option value="">Tiada sub kategori tersedia</option>');
          }
        },
        error: function (xhr) {
          console.error('Error loading subcategories:', xhr);
          if (typeof toastr !== 'undefined') {
            toastr.error('Ralat memuat sub kategori.');
          }
          $('#editSubcatId').empty();
          $('#editSubcatId').append('<option value="">Gagal memuatkan sub kategori</option>');
        }
      });
    } else {
      $('#editSubcatId').empty();
      $('#editSubcatId').append('<option value="">Pilih Sub Kategori</option>');
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

    // Clear attachment files and reset global array
    $('#editTicketAttachments').val(''); // Clear file input
    window.editSelectedAttachments = []; // Reset global array

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

    var ticketUuid = $('#editTicketId').val();
    if (!ticketUuid) {
      toastr.error('UUID tiket tidak dijumpai. Sila cuba lagi.');
      return;
    }

    // Get selected attachments from global variable
    var selectedAttachments = window.editSelectedAttachments || [];

    var formData = new FormData(this);

    // Always remove existing attachment entries from FormData to avoid conflicts
    // between file input and managed attachments array
    formData.delete('attachments[]');

    // Add selected attachments to form data (only from our managed array)
    if (selectedAttachments.length > 0) {
      selectedAttachments.forEach(function (file, index) {
        formData.append('attachments[]', file);
      });
    }

    var submitBtn = $(this).find('button[type=\"submit\"]');

    // Disable submit button and add visual feedback
    submitBtn.prop('disabled', true).text('Mengemaskini...');

    $.ajax({
      url: '/tickets/' + ticketUuid,
      type: 'POST', // Laravel will handle method spoofing with _method field
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        logger.log('Ticket updated successfully:', response);

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
          logger.log('Server validation errors for edit form:', response.errors);
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

  logger.log('Ticket edit functionality initialized');
});
