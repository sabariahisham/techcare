/**
 * Shared Validation Utility for Roles
 */

'use strict';

window.RoleValidation = (function() {

  /**
   * Clear validation errors from a form
   * @param {string} formSelector - jQuery selector for the form
   */
  function clearValidationErrors(formSelector) {
    $(formSelector + ' .is-invalid').removeClass('is-invalid');
    $(formSelector + ' .invalid-feedback').remove();
  }

  /**
   * Show validation error for a specific field
   * @param {string} fieldSelector - jQuery selector for the field
   * @param {string} message - Error message to display
   */
  function showFieldError(fieldSelector, message) {
    $(fieldSelector).addClass('is-invalid');
    // Remove existing error message if any
    $(fieldSelector).siblings('.invalid-feedback').remove();
    // Add new error message
    $(fieldSelector).after(`<div class="invalid-feedback">${message}</div>`);
  }

  /**
   * Validate role name field
   * @param {string} fieldSelector - jQuery selector for the name field
   * @return {boolean} - Returns true if valid, false if invalid
   */
  function validateRoleName(fieldSelector) {
    const nameValue = $(fieldSelector).val();
    let isValid = true;

    // Clear previous error
    $(fieldSelector).removeClass('is-invalid');
    $(fieldSelector).siblings('.invalid-feedback').remove();

    if (!nameValue || nameValue.trim() === '') {
      showFieldError(fieldSelector, 'Nama peranan diperlukan');
      isValid = false;
    } else if (nameValue.trim().length < 2) {
      showFieldError(fieldSelector, 'Nama peranan mesti sekurang-kurangnya 2 aksara');
      isValid = false;
    } else if (nameValue.trim().length > 255) {
      showFieldError(fieldSelector, 'Nama peranan tidak boleh melebihi 255 aksara');
      isValid = false;
    }

    return isValid;
  }

  /**
   * Validate permissions selection
   * @param {string} permissionSelector - jQuery selector for permission checkboxes
   * @param {string} containerSelector - jQuery selector for the permissions container
   * @return {boolean} - Returns true if valid, false if invalid
   */
  function validatePermissions(permissionSelector, containerSelector) {
    const checkedPermissions = $(permissionSelector + ':checked').length;
    let isValid = true;

    // Clear previous error
    $(containerSelector).find('.invalid-feedback').remove();

    if (checkedPermissions === 0) {
      // Add error message at the top of permissions container
      $(containerSelector).prepend('<div class="invalid-feedback d-block mb-3">Sila pilih sekurang-kurangnya satu kebenaran</div>');
      isValid = false;
    }

    return isValid;
  }

  /**
   * Validate entire role form
   * @param {string} formSelector - jQuery selector for the form
   * @param {string} nameFieldSelector - jQuery selector for the name field
   * @param {string} permissionSelector - jQuery selector for permission checkboxes
   * @param {string} permissionContainerSelector - jQuery selector for permissions container
   * @return {boolean} - Returns true if valid, false if invalid
   */
  function validateRoleForm(formSelector, nameFieldSelector, permissionSelector, permissionContainerSelector) {
    // Clear all previous errors
    clearValidationErrors(formSelector);

    let isValid = true;

    // Validate role name
    if (!validateRoleName(nameFieldSelector)) {
      isValid = false;
    }

    // Validate permissions
    if (!validatePermissions(permissionSelector, permissionContainerSelector)) {
      isValid = false;
    }

    return isValid;
  }

  /**
   * Handle server validation errors
   * @param {Object} errors - Validation errors object from server
   * @param {string} formSelector - jQuery selector for the form
   */
  function handleServerErrors(errors, formSelector) {
    clearValidationErrors(formSelector);

    $.each(errors, function(field, messages) {
      let fieldSelector;

      // Map field names to selectors
      switch(field) {
        case 'name':
          fieldSelector = formSelector + ' input[name="name"]';
          break;
        case 'description':
          fieldSelector = formSelector + ' textarea[name="description"]';
          break;
        case 'permissions':
          // For permissions, show error in the permissions container
          const permissionsContainer = formSelector + ' .permissions-container';
          $(permissionsContainer).prepend(`<div class="invalid-feedback d-block mb-3">${messages[0]}</div>`);
          return; // Return instead of continue
        default:
          return; // Return instead of continue
      }

      if (fieldSelector && $(fieldSelector).length) {
        showFieldError(fieldSelector, messages[0]);
      }
    });
  }

  // Public API
  return {
    clearValidationErrors: clearValidationErrors,
    showFieldError: showFieldError,
    validateRoleName: validateRoleName,
    validatePermissions: validatePermissions,
    validateRoleForm: validateRoleForm,
    handleServerErrors: handleServerErrors
  };

})();
