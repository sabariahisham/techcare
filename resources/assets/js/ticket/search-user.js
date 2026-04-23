/**
 * Ticket User Search Functionality
 * Handles user search, autocomplete, and user field population for ticket creation
 */

import logger from '../logger.js';

'use strict';

$(function () {
  logger.log('User search functionality loaded');

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

  // Function to search user by name (ADD FORM only - edit functionality removed)
  function searchUserByName(nameOrEmail, isEdit = false, fromAutocomplete = false) {
    // Edit functionality disabled - only support add form
    if (isEdit) {
      logger.log('Edit functionality disabled for complainants');
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
    var searchUrl = fromAutocomplete ? '/ticket-search/user-by-email' : '/ticket-search/user-by-name';
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
      url: '/ticket-search/user-autocomplete',
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

  // Make functions available globally for other components
  window.ticketUserSearch = {
    resetUserSearch: resetUserSearch,
    clearUserFields: clearUserFields,
    populateUserFields: populateUserFields,
    searchUserByName: searchUserByName
  };
});
