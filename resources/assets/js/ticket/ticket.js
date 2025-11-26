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

  // Edit functionality removed - complainants can only log tickets, not edit them

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
  });

  // Handle select2 selection events
  $(document).on('select2:select', '#cat_id', function (e) {
    // Close the dropdown
    $(this).select2('close');
    // Trigger change for subcategory loading
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
      resetUserSearch();
    } else {
      $('#selfInfo').removeClass('d-none');
      $('#otherInfo').addClass('d-none');
      // Show form fields for "Diri Sendiri"
      $('#ticketFormFields').removeClass('d-none');
    }
  });

  // Helper function to check if value is empty/null/undefined and return fallback
  function getValueOrFallback(value, fallback = 'Tiada data') {
    if (value === null || value === undefined || value === '' || value === 0) {
      return fallback;
    }
    return value;
  }

  // Function to reset user search (ADD FORM)
  function resetUserSearch() {
    $('#search_name').val('');
    $('#foundUserInfo').hide();
    $('#searchLoading').hide();
    // Hide alert notifications
    $('#searchAlert').hide();
    // Hide form fields when clearing search
    $('#ticketFormFields').addClass('d-none');
    // Restore normal Select2 behavior
    $('#cat_id, #subcat_id').next('.select2-container').removeClass('select2-normal-state');
    clearUserFields();
  }

  // Function to clear user fields (ADD FORM)
  function clearUserFields() {
    $('#other_name').val('');
    $('#other_jawatan').val('');
    $('#other_skim').val('');
    $('#other_gred').val('');
    $('#other_email').val('');
    $('#other_sektor').val('');
    $('#other_agency').val('');
    $('#other_seksyen').val('');
    $('#other_unit').val('');
    $('#other_p').val('');
    $('#other_hp').val('');
  }

  // Handle search user button click (ADD FORM)
  $('#searchUserBtn').on('click', function () {
    var username = $('#search_name').val().trim();

    if (!username) {
      toastr.error('Sila masukkan nama pengguna untuk mencari.');
      return;
    }

    if (username.length < 2) {
      toastr.error('Sila masukkan sekurang-kurangnya 2 aksara untuk mencari.');
      return;
    }

    searchUserByName(username, false);
  });

  // Handle clear search button (ADD FORM)
  $('#clearSearchBtn').on('click', function () {
    resetUserSearch();
  });

  // Autocomplete functionality variables
  let autocompleteTimeout = null;
  let currentAutocompleteIndex = -1;
  let autocompleteResults = [];

  // Handle input for autocomplete search (ADD FORM)
  $('#search_name').on('input', function () {
    const query = $(this).val().trim();

    // Clear existing timeout
    if (autocompleteTimeout) {
      clearTimeout(autocompleteTimeout);
    }

    // Hide dropdown if query is empty
    if (query === '') {
      hideAutocompleteDropdown();
      return;
    }

    // Only trigger search if query is at least 2 characters
    if (query.length < 2) {
      hideAutocompleteDropdown();
      return;
    }

    // Debounce the search
    autocompleteTimeout = setTimeout(function () {
      performAutocompleteSearch(query);
    }, 300); // 300ms delay
  });

  // Handle keyboard navigation in autocomplete
  $('#search_name').on('keydown', function (e) {
    const dropdown = $('#autocompleteDropdown');

    if (!dropdown.is(':visible')) {
      return;
    }

    const items = $('.autocomplete-item');

    switch (e.which) {
      case 40: // Arrow Down
        e.preventDefault();
        currentAutocompleteIndex = Math.min(currentAutocompleteIndex + 1, items.length - 1);
        updateAutocompleteHighlight();
        break;
      case 38: // Arrow Up
        e.preventDefault();
        currentAutocompleteIndex = Math.max(currentAutocompleteIndex - 1, -1);
        updateAutocompleteHighlight();
        break;
      case 13: // Enter
        if (currentAutocompleteIndex >= 0 && items[currentAutocompleteIndex]) {
          e.preventDefault();
          $(items[currentAutocompleteIndex]).click();
        }
        break;
      case 27: // Escape
        e.preventDefault();
        hideAutocompleteDropdown();
        break;
    }
  });

  // Handle autocomplete item selection
  $(document).on('click', '.autocomplete-item', function () {
    const email = $(this).data('email');
    const userData = $(this).data('user');

    // Format the name to title case
    const formattedName = userData.nama.toLowerCase()
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');

    // Keep the user's formatted name in the input field
    $('#search_name').val(formattedName);
    hideAutocompleteDropdown();

    // Automatically search using the email (backend will still use email to find exact match)
    setTimeout(function () {
      searchUserByName(email, false, true); // Pass true to indicate this is from autocomplete
    }, 150);
  });

  // Hide dropdown when clicking outside
  $(document).on('click', function (e) {
    if (!$(e.target).closest('#search_name, #autocompleteDropdown').length) {
      hideAutocompleteDropdown();
    }
  });

  // Function to perform autocomplete search
  function performAutocompleteSearch(query) {
    if (query.length < 2) {
      hideAutocompleteDropdown();
      return;
    }

    // Show loading state
    showAutocompleteLoading();

    $.ajax({
      url: '/search-user-autocomplete',
      type: 'POST',
      data: {
        query: query,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success && response.users.length > 0) {
          showAutocompleteResults(response.users);
        } else {
          showNoResults();
        }
      },
      error: function () {
        hideAutocompleteDropdown();
      }
    });
  }

  // Function to show autocomplete results
  function showAutocompleteResults(users) {
    autocompleteResults = users;
    currentAutocompleteIndex = -1;

    const resultsHtml = users.map(function (user, index) {
      // Format the name to title case for better display
      const formattedName = user.nama.toLowerCase()
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

      return `
        <div class="list-group-item autocomplete-item" data-email="${user.email}" data-user='${JSON.stringify(user)}' data-index="${index}">
          <div class="user-info">
            <div class="user-details">
              <div class="user-name">${formattedName}</div>
              <div class="user-email">${user.email}</div>
            </div>
          </div>
        </div>
      `;
    }).join('');

    $('#autocompleteResults').html(resultsHtml);
    $('#autocompleteDropdown').show();
  }

  // Function to show no results
  function showNoResults() {
    $('#autocompleteResults').html(`
      <div class="list-group-item no-results">
        <i class="bx bx-search"></i>
        Tiada pengguna ditemui
      </div>
    `);
    $('#autocompleteDropdown').show();
  }

  // Function to show loading state
  function showAutocompleteLoading() {
    $('#autocompleteResults').html(`
      <div class="list-group-item loading">
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        Mencari pengguna...
      </div>
    `);
    $('#autocompleteDropdown').show();
  }

  // Function to hide autocomplete dropdown
  function hideAutocompleteDropdown() {
    $('#autocompleteDropdown').hide();
    currentAutocompleteIndex = -1;
    autocompleteResults = [];
  }

  // Function to update autocomplete highlight
  function updateAutocompleteHighlight() {
    $('.autocomplete-item').removeClass('highlighted');

    if (currentAutocompleteIndex >= 0) {
      $('.autocomplete-item').eq(currentAutocompleteIndex).addClass('highlighted');
    }
  }

  // Handle Enter key in search name fields
  $('#search_name').on('keypress', function (e) {
    if (e.which === 13) { // Enter key
      // Check if autocomplete is visible
      if ($('#autocompleteDropdown').is(':visible') && currentAutocompleteIndex >= 0) {
        // Let the keydown handler manage this
        return;
      }

      e.preventDefault();
      hideAutocompleteDropdown();
      $('#searchUserBtn').click();
    }
  });

  // Function to search user by name (ADD FORM only - edit functionality removed)
  function searchUserByName(nameOrEmail, isEdit = false, fromAutocomplete = false) {
    // Edit functionality disabled - only support add form
    if (isEdit) {
      console.warn('Edit functionality disabled for complainants');
      return;
    }

    var loadingElement = $('#searchLoading');
    var userInfoElement = $('#foundUserInfo');
    var searchBtn = $('#searchUserBtn');

    // Show loading, hide user info
    loadingElement.show();
    userInfoElement.hide();
    searchBtn.prop('disabled', true);

    // Use name-based search endpoint
    var searchUrl = fromAutocomplete ? '/search-user-by-email' : '/search-user-by-name';
    var searchField = fromAutocomplete ? 'email' : 'name';
    var searchData = {
      _token: $('meta[name="csrf-token"]').attr('content')
    };
    searchData[searchField] = nameOrEmail;

    $.ajax({
      url: searchUrl,
      type: 'POST',
      data: searchData,
      success: function (response) {
        if (response.success && response.user) {
          // Populate user fields (add form only)
          populateUserFields(response.user);

          // Show user info section
          userInfoElement.show();

          // Hide any existing alerts
          var alertContainer = $('#searchAlert');
          alertContainer.hide();

          // Show form fields after user is found
          $('#ticketFormFields').removeClass('d-none');
          // Remove blue border from Select2 elements in add form
          $('#cat_id, #subcat_id').next('.select2-container').addClass('select2-normal-state');

          // Show success message
          toastr.success('Maklumat pengguna berjaya ditemui!');
        } else {
          // Hide user info and show error message
          userInfoElement.hide();

          // Show inline alert notification in the form
          var alertContainer = $('#searchAlert');
          var alertMessage = $('#searchAlertMessage');

          alertMessage.text(response.message || 'Pengguna tidak ditemui dengan nama tersebut. Sila periksa nama dan cuba lagi.');
          alertContainer.show();

          // Auto-hide alert after 5 seconds
          setTimeout(function () {
            alertContainer.fadeOut();
          }, 5000);
        }
      },
      error: function (xhr) {
        var response = xhr.responseJSON;
        var message = 'Ralat berlaku semasa mencari pengguna.';

        if (response && response.message) {
          message = response.message;
        }

        // Hide user info and show error
        userInfoElement.hide();

        // Show inline alert notification in the form
        var alertContainer = $('#searchAlert');
        var alertMessage = $('#searchAlertMessage');

        alertMessage.text(message);
        alertContainer.show();

        // Auto-hide alert after 5 seconds
        setTimeout(function () {
          alertContainer.fadeOut();
        }, 5000);
      },
      complete: function () {
        loadingElement.hide();
        searchBtn.prop('disabled', false);
      }
    });
  }

  // Function to populate user fields (VALUE-ADD FORM BAGI PIHAK)
  function populateUserFields(user) {
    $('#other_name').val(getValueOrFallback(user.nama));
    $('#other_jawatan').val(getValueOrFallback(user.nama_jawatan) || getValueOrFallback(user.kod_jawatan));

    // Handle Skim Perkhidmatan with complex formatting
    var skimValue = 'Tiada data';
    if (getValueOrFallback(user.nama_skim, null) && getValueOrFallback(user.kod_skim, null)) {
      skimValue = user.nama_skim + ' (' + user.kod_skim + ')';
    } else if (getValueOrFallback(user.kod_skim, null)) {
      skimValue = user.kod_skim;
    }
    $('#other_skim').val(skimValue);

    $('#other_gred').val(getValueOrFallback(user.nama_gred) || getValueOrFallback(user.kod_gred));
    $('#other_email').val(getValueOrFallback(user.email));
    $('#other_sektor').val(getValueOrFallback(user.nama_sektor) || getValueOrFallback(user.kod_sektor));
    $('#other_agency').val(getValueOrFallback(user.nama_bah) || getValueOrFallback(user.kod_bah));
    $('#other_seksyen').val(getValueOrFallback(user.nama_sec) || getValueOrFallback(user.kod_sec));
    $('#other_unit').val(getValueOrFallback(user.nama_unit) || getValueOrFallback(user.kod_unit));
    $('#other_p').val(getValueOrFallback(user.tel_no_p));
    $('#other_hp').val(getValueOrFallback(user.tel_no_hp));
  }

  // Edit form user population function removed - edit functionality disabled for complainants

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

  // Edit category change handler removed - edit functionality disabled for complainants

  // Function to clear all validation errors
  function clearValidationErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Also clear Select2 validation styling
    $('.select2-container').removeClass('is-invalid');
    // Remove any error messages after Select2 containers
    $('.select2-container').siblings('.invalid-feedback').remove();

    // Clear error-text containers
    $('.error-text').text('').hide();
  }

  // Function to display validation errors
  function displayValidationErrors(errors) {
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

    // Validate file attachments
    var attachmentField = $('[name="attachments[]"]');
    if (attachmentField.length > 0 && attachmentField[0].files && attachmentField[0].files.length > 0) {
      var allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
      var maxFileSize = 2 * 1024 * 1024; // 2MB in bytes

      for (var i = 0; i < attachmentField[0].files.length; i++) {
        var file = attachmentField[0].files[i];
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

  // Function to validate email format
  function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
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
        // Show form fields since we're back to "Diri Sendiri"
        $('#ticketFormFields').removeClass('d-none');
        // Restore normal Select2 behavior
        $('#cat_id, #subcat_id').next('.select2-container').removeClass('select2-normal-state');

        // Reload both DataTables
        reloadAllTables();

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success(response.message || 'Tiket berjaya dicipta!');
        }
      },
      error: function (xhr) {
        console.log('Add form submission error:', xhr.responseJSON);
        var response = xhr.responseJSON;
        if (response && response.errors) {
          console.log('Server validation errors for add form:', response.errors);
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

  // Edit form validation function removed - edit functionality disabled for complainants

  // Edit form submission handler removed - edit functionality disabled for complainants

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

  // loadTicketData function removed - edit functionality disabled for complainants

  // Handle view button click
  $(document).on('click', '.view-record', function () {
    var ticketId = $(this).data('id');

    // Navigate to the detailed view page
    window.location.href = '/tickets/' + ticketId;
  });

  // Edit button click handler removed - edit functionality disabled for complainants

  // Clear validation errors when ticket type changes (add form only)
  $(document).on('change', '#ticketType', function () {
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

  // Handle Select2 specific change events (add form only - edit functionality removed)
  $(document).on('select2:select select2:unselect', '#cat_id, #subcat_id', function () {
    var field = $(this);
    // Remove validation errors for Select2 fields
    field.removeClass('is-invalid');
    field.next('.select2-container').removeClass('is-invalid');
    field.next('.select2-container').siblings('.invalid-feedback').remove();
  });

  // Initialize custom validation messages on document ready
  setCustomValidationMessages();

  // Re-initialize when add ticket offcanvas is shown (edit functionality removed)
  $('#offcanvasAddTicket').on('shown.bs.offcanvas', function () {
    setCustomValidationMessages();
  });
});
