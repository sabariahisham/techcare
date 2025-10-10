/**
 * Role Statistics Management
 */

'use strict';

(function () {
  // Function to refresh role statistics
  function refreshRoleStatistics() {
    $.ajax({
      url: '/admin/roles/statistics',
      type: 'GET',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success && response.stats) {
          // Update total roles count
          if (response.stats.total_roles !== undefined) {
            $('#total-roles').text(response.stats.total_roles);
          }

          // Update active roles count
          if (response.stats.active_roles !== undefined) {
            $('#active-roles').text(`(${response.stats.active_roles} aktif)`);
          }

          console.log('Role statistics refreshed successfully');
        }
      },
      error: function(xhr) {
        console.warn('Failed to refresh role statistics:', xhr.responseText);
      }
    });
  }

  // Listen for role events and refresh statistics
  $(document).on('roleAdded roleUpdated roleDeleted', function(event) {
    console.log('Role event detected:', event.type);

    // Add a small delay to ensure backend processing is complete
    setTimeout(function() {
      refreshRoleStatistics();
    }, 500);
  });

  // Initial load
  $(document).ready(function() {
    console.log('Role statistics script loaded');
  });

  // Export function for manual refresh
  window.refreshRoleStatistics = refreshRoleStatistics;

})();
