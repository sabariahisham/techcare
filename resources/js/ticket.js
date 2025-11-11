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

  // Handle ticket type change (EDIT FORM) - TAMBAH INI
  $('#edit_ticketType').on('change', function () {
    if ($(this).val() == '0') {
      $('#edit_selfInfo').addClass('d-none');
      $('#edit_otherInfo').removeClass('d-none');
      // Hide form fields until user is found
      $('#editTicketFormFields').addClass('d-none');
      // Reset search when switching to "Bagi Pihak"
      resetEditUserSearch();
    } else {
      $('#edit_selfInfo').removeClass('d-none');
      $('#edit_otherInfo').addClass('d-none');
      // Show form fields for "Diri Sendiri"
      $('#editTicketFormFields').removeClass('d-none');
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
    $('#search_email').val('');
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

  // Function to reset user search (EDIT FORM)
  function resetEditUserSearch() {
    $('#edit_search_email').val('');
    $('#editFoundUserInfo').hide();
    $('#editSearchLoading').hide();
    // Hide alert notifications
    $('#editSearchAlert').hide();
    // Hide form fields when clearing search
    $('#editTicketFormFields').addClass('d-none');
    // Restore normal Select2 behavior
    $('#edit_cat_id, #edit_subcat_id').next('.select2-container').removeClass('select2-normal-state');
    clearEditUserFields();
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

  // Function to clear user fields (EDIT FORM)
  function clearEditUserFields() {
    $('#edit_other_name').val('');
    $('#edit_other_jawatan').val('');
    $('#edit_other_skim').val('');
    $('#edit_other_gred').val('');
    $('#edit_other_email').val('');
    $('#edit_other_sektor').val('');
    $('#edit_other_agency').val('');
    $('#edit_other_seksyen').val('');
    $('#edit_other_unit').val('');
    $('#edit_other_p').val('');
    $('#edit_other_hp').val('');
  }

  // Handle search user button click (ADD FORM)
  $('#searchUserBtn').on('click', function () {
    var email = $('#search_email').val().trim();

    if (!email) {
      toastr.error('Sila masukkan alamat emel untuk mencari.');
      return;
    }

    // Auto-append @treasury.gov.my if not present
    if (!email.includes('@')) {
      email = email + '@treasury.gov.my';
      $('#search_email').val(email); // Update the input field to show full email
    }

    // Comment out these lines if you want to use full email address validation
    // if (!isValidEmail(email)) {
    //   toastr.error('Format alamat emel tidak sah.');
    //   return;
    // }

    searchUserByEmail(email, false);
  });

  // Handle search user button click (EDIT FORM)
  $('#editSearchUserBtn').on('click', function () {
    var email = $('#edit_search_email').val().trim();

    if (!email) {
      toastr.error('Sila masukkan alamat emel untuk mencari.');
      return;
    }

    // Auto-append @treasury.gov.my if not present
    if (!email.includes('@')) {
      email = email + '@treasury.gov.my';
      $('#edit_search_email').val(email); // Update the input field to show full email
    }

    // Comment out these lines if you want to use full email address validation
    // if (!isValidEmail(email)) {
    //   toastr.error('Format alamat emel tidak sah.');
    //   return;
    // }

    searchUserByEmail(email, true);
  });

  // Handle clear search button (ADD FORM)
  $('#clearSearchBtn').on('click', function () {
    resetUserSearch();
  });

  // Handle clear search button (EDIT FORM)
  $('#editClearSearchBtn').on('click', function () {
    resetEditUserSearch();
  });

  // Handle Enter key in search email fields
  $('#search_email').on('keypress', function (e) {
    if (e.which === 13) { // Enter key
      e.preventDefault();
      $('#searchUserBtn').click();
    }
  });

  $('#edit_search_email').on('keypress', function (e) {
    if (e.which === 13) { // Enter key
      e.preventDefault();
      $('#editSearchUserBtn').click();
    }
  });

  // Function to search user by email
  function searchUserByEmail(email, isEdit) {
    var loadingElement = isEdit ? $('#editSearchLoading') : $('#searchLoading');
    var userInfoElement = isEdit ? $('#editFoundUserInfo') : $('#foundUserInfo');
    var searchBtn = isEdit ? $('#editSearchUserBtn') : $('#searchUserBtn');

    // Show loading, hide user info
    loadingElement.show();
    userInfoElement.hide();
    searchBtn.prop('disabled', true);

    $.ajax({
      url: '/search-user-by-email',
      type: 'POST',
      data: {
        email: email,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success && response.user) {
          // Populate user fields
          if (isEdit) {
            populateEditUserFields(response.user);
          } else {
            populateUserFields(response.user);
          }

          // Show user info section
          userInfoElement.show();

          // Hide any existing alerts
          var alertContainer = isEdit ? $('#editSearchAlert') : $('#searchAlert');
          alertContainer.hide();

          // Show form fields after user is found
          if (isEdit) {
            $('#editTicketFormFields').removeClass('d-none');
            // Remove blue border from Select2 elements in edit form
            $('#edit_cat_id, #edit_subcat_id').next('.select2-container').addClass('select2-normal-state');
          } else {
            $('#ticketFormFields').removeClass('d-none');
            // Remove blue border from Select2 elements in add form
            $('#cat_id, #subcat_id').next('.select2-container').addClass('select2-normal-state');
          }

          // Show success message
          toastr.success('Maklumat pengguna berjaya ditemui!');
        } else {
          // Hide user info and show error message
          userInfoElement.hide();

          // Show inline alert notification in the form
          var alertContainer = isEdit ? $('#editSearchAlert') : $('#searchAlert');
          var alertMessage = isEdit ? $('#editSearchAlertMessage') : $('#searchAlertMessage');

          alertMessage.text(response.message || 'Pengguna tidak ditemui dengan alamat emel tersebut. Sila periksa alamat emel dan cuba lagi.');
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
        var alertContainer = isEdit ? $('#editSearchAlert') : $('#searchAlert');
        var alertMessage = isEdit ? $('#editSearchAlertMessage') : $('#searchAlertMessage');

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

  // Function to populate user fields (VALUE-EDIT FORM BAGI PIHAK)
  function populateEditUserFields(user) {
    $('#edit_other_name').val(getValueOrFallback(user.nama));
    $('#edit_other_jawatan').val(getValueOrFallback(user.nama_jawatan) || getValueOrFallback(user.kod_jawatan));

    // Handle Skim Perkhidmatan with complex formatting
    var skimValue = 'Tiada data';
    if (getValueOrFallback(user.nama_skim, null) && getValueOrFallback(user.kod_skim, null)) {
      skimValue = user.nama_skim + ' (' + user.kod_skim + ')';
    } else if (getValueOrFallback(user.kod_skim, null)) {
      skimValue = user.kod_skim;
    }
    $('#edit_other_skim').val(skimValue);

    $('#edit_other_gred').val(getValueOrFallback(user.nama_gred) || getValueOrFallback(user.kod_gred));
    $('#edit_other_email').val(getValueOrFallback(user.email));
    $('#edit_other_sektor').val(getValueOrFallback(user.nama_sektor) || getValueOrFallback(user.kod_sektor));
    $('#edit_other_agency').val(getValueOrFallback(user.nama_bah) || getValueOrFallback(user.kod_bah));
    $('#edit_other_seksyen').val(getValueOrFallback(user.nama_sec) || getValueOrFallback(user.kod_sec));
    $('#edit_other_unit').val(getValueOrFallback(user.nama_unit) || getValueOrFallback(user.kod_unit));
    $('#edit_other_p').val(getValueOrFallback(user.tel_no_p));
    $('#edit_other_hp').val(getValueOrFallback(user.tel_no_hp));
  }

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
        errors['other_email'] = ['Sila cari pengguna menggunakan alamat emel untuk aduan bagi pihak.'];
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

  // Function to validate form based on ticket type (EDIT FORM)
  function validateEditForm() {
    clearValidationErrors();
    var isValid = true;
    var errors = {};

    var ticketType = $('#edit_ticketType').val();

    // Validate based on ticket type
    if (ticketType == '0') {
      // Bagi Pihak - check if user search was completed
      if (!$('#editFoundUserInfo').is(':visible')) {
        errors['other_email'] = ['Sila cari pengguna menggunakan alamat emel untuk aduan bagi pihak.'];
        isValid = false;
      } else {
        // If user info is shown, validate the readonly fields have values
        var otherEmail = $('#edit_other_email').val();
        if (!otherEmail || otherEmail.trim() === '') {
          errors['other_email'] = ['Sila cari dan pilih pengguna untuk aduan bagi pihak.'];
          isValid = false;
        }
      }
    }

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

    // Validate file attachments
    var attachmentField = $('#editTicketForm [name="attachments[]"]');
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

        // Reload both DataTables
        reloadAllTables();

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success(response.message || 'Tiket berjaya dikemas kini!');
        }
      },
      error: function (xhr) {
        console.log('Edit form submission error:', xhr.responseJSON);
        var response = xhr.responseJSON;
        if (response && response.errors) {
          console.log('Server validation errors for edit form:', response.errors);
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

    // Handle "Bagi Pihak" (ticket_type == 0) by loading edit user data
    if (ticket.ticket_type == 0 && ticket.user) {
      // Populate search email field with the user's email
      $('#edit_search_email').val(ticket.user.email);

      // Populate user display fields with the associated user's data
      $('#edit_other_name').val(getValueOrFallback(ticket.user.nama));
      $('#edit_other_jawatan').val(
        getValueOrFallback(ticket.user.jawatan?.nama_jawatan) ||
        getValueOrFallback(ticket.user.kod_jawatan)
      );

      // Handle Skim Perkhidmatan with complex formatting for loadTicketData
      var skimValue = 'Tiada data';
      if (getValueOrFallback(ticket.user.skim?.nama_skim, null) && getValueOrFallback(ticket.user.kod_skim, null)) {
        skimValue = ticket.user.skim.nama_skim + ' (' + ticket.user.kod_skim + ')';
      } else if (getValueOrFallback(ticket.user.kod_skim, null)) {
        skimValue = ticket.user.kod_skim;
      }
      $('#edit_other_skim').val(skimValue);

      $('#edit_other_gred').val(getValueOrFallback(ticket.user.nama_gred));
      $('#edit_other_email').val(getValueOrFallback(ticket.user.email));
      $('#edit_other_sektor').val(
        getValueOrFallback(ticket.user.sektor?.nama_sektor) ||
        getValueOrFallback(ticket.user.kod_sektor)
      );
      $('#edit_other_agency').val(
        getValueOrFallback(ticket.user.bahagian?.nama_bah) ||
        getValueOrFallback(ticket.user.kod_bah)
      );
      $('#edit_other_seksyen').val(
        getValueOrFallback(ticket.user.seksyen?.nama_sec) ||
        getValueOrFallback(ticket.user.kod_sec)
      );
      $('#edit_other_unit').val(
        getValueOrFallback(ticket.user.unit?.nama_unit) ||
        getValueOrFallback(ticket.user.kod_unit)
      );
      $('#edit_other_p').val(getValueOrFallback(ticket.user.tel_no_p));
      $('#edit_other_hp').val(getValueOrFallback(ticket.user.tel_no_hp));

      // Show the user info section
      $('#editFoundUserInfo').show();
      // Show form fields since user is already loaded
      $('#editTicketFormFields').removeClass('d-none');
      // Remove blue border from Select2 elements since user is found
      setTimeout(function () {
        $('#edit_cat_id, #edit_subcat_id').next('.select2-container').addClass('select2-normal-state');
      }, 1000); // Wait for Select2 to initialize
    } else {
      // Reset edit form if switching back to "Diri Sendiri"
      resetEditUserSearch();
      // Ensure form fields are shown for "Diri Sendiri" tickets
      $('#editTicketFormFields').removeClass('d-none');
    }

    console.log('Ticket data loaded successfully');
  };

  // Handle view button click
  $(document).on('click', '.view-record', function () {
    var ticketId = $(this).data('id');

    // Navigate to the detailed view page
    window.location.href = '/tickets/' + ticketId;
  });

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
    // Remove validation errors for Select2 fields
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
