/**
 * Config
 * -------------------------------------------------------------------------------------
 * ! IMPORTANT: Make sure you clear the browser local storage In order to see the config changes in the template.
 * ! To clear local storage: (https://www.leadshook.com/help/how-to-clear-local-storage-in-google-chrome-browser/).
 */

'use strict';

// JS global variables
window.config = {
  colors: {
    primary: '#696cff',
    secondary: '#8592a3',
    success: '#71dd37',
    info: '#03c3ec',
    warning: '#ffab00',
    danger: '#ff3e1d',
    dark: '#233446',
    black: '#22303e',
    white: '#fff',
    cardColor: '#fff',
    bodyBg: '#f5f5f9',
    bodyColor: '#646E78',
    headingColor: '#384551',
    textMuted: '#a7acb2',
    borderColor: '#e4e6e8'
  },
  colors_label: {
    primary: '#696cff29',
    secondary: '#8592a329',
    success: '#71dd3729',
    info: '#03c3ec29',
    warning: '#ffab0029',
    danger: '#ff3e1d29',
    dark: '#181c211a'
  },
  colors_dark: {
    cardColor: '#2b2c40',
    bodyBg: '#232333',
    bodyColor: '#b2b2c4',
    headingColor: '#d5d5e2',
    textMuted: '#7e7f96',
    borderColor: '#4e4f6c'
  },
  enableMenuLocalStorage: true // Enable menu state with local storage support
};

window.assetsPath = document.documentElement.getAttribute('data-assets-path');
window.baseUrl = document.documentElement.getAttribute('data-base-url') + '/';

// Global SweetAlert2 configuration to ensure only 2 buttons (Ya/Tidak)
(function () {
  // Wait for Swal to be available
  function configureSweetAlert() {
    if (typeof Swal !== 'undefined') {
      // Store the original fire method
      const originalFire = Swal.fire;

      // Override Swal.fire to enforce our button configuration
      Swal.fire = function () {
        let options = {};

        // Handle different parameter formats
        if (arguments.length === 1 && typeof arguments[0] === 'object') {
          options = arguments[0];
        } else if (arguments.length >= 2) {
          // Handle Swal.fire('title', 'text', 'icon') format
          options = {
            title: arguments[0],
            text: arguments[1],
            icon: arguments[2] || undefined
          };
        } else {
          options = arguments[0] || {};
        }

        // Enforce our button configuration
        if (typeof options === 'object' && options !== null) {
          // Always disable the deny button (third button)
          options.showDenyButton = false;

          // If this is a confirmation dialog (has cancel button)
          if (options.showCancelButton === true) {
            // Set default button texts for Malay language
            if (!options.confirmButtonText) {
              if (options.icon === 'warning' || options.type === 'warning') {
                options.confirmButtonText = 'Ya, padam!';
              } else {
                options.confirmButtonText = 'Ya';
              }
            }

            if (!options.cancelButtonText) {
              options.cancelButtonText = 'Tidak';
            }

            // Ensure proper styling
            if (!options.customClass) {
              options.customClass = {};
            }

            if (!options.customClass.confirmButton) {
              options.customClass.confirmButton = 'btn btn-primary me-2';
            }

            if (!options.customClass.cancelButton) {
              options.customClass.cancelButton = 'btn btn-label-secondary';
            }

            if (!options.hasOwnProperty('buttonsStyling')) {
              options.buttonsStyling = false;
            }
          }
        }

        return originalFire.call(this, options);
      };

      console.log('SweetAlert2 global configuration applied - Only 2 buttons (Ya/Tidak) will be shown');
    } else {
      // Retry after a short delay if Swal is not yet available
      setTimeout(configureSweetAlert, 100);
    }
  }

  // Apply configuration when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configureSweetAlert);
  } else {
    configureSweetAlert();
  }
})();
window.templateName = document.documentElement.getAttribute('data-template');
window.rtlSupport = true; // set true for rtl support (rtl + ltr), false for ltr only.
