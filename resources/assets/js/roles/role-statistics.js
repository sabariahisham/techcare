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
        if (response.success && response.statistics) {
          // Update total roles count
          if (response.statistics.total_roles !== undefined) {
            $('#total-roles').text(response.statistics.total_roles);
          }

          // Update active roles count
          if (response.statistics.active_roles !== undefined) {
            $('#active-roles').text(`(${response.statistics.active_roles} aktif)`);
          }

          // Update total users with roles count
          if (response.statistics.total_users_with_roles !== undefined) {
            $('#total-users-with-roles').text(response.statistics.total_users_with_roles);
          }

          // Update active users with roles count
          if (response.statistics.active_users_with_roles !== undefined) {
            $('#active-users-with-roles').text(`(${response.statistics.active_users_with_roles} aktif)`);
          }

          // Update total permissions count
          if (response.statistics.total_permissions !== undefined) {
            $('#total-permissions').text(response.statistics.total_permissions);
          }

          // Update active permissions count
          if (response.statistics.active_permissions !== undefined) {
            $('#active-permissions').text(`(${response.statistics.active_permissions} aktif)`);
          }

          // Update total modules count
          if (response.statistics.total_modules !== undefined) {
            $('#total-modules').text(response.statistics.total_modules);
          }

          // Update active modules count
          if (response.statistics.active_modules !== undefined) {
            $('#active-modules').text(`(${response.statistics.active_modules} aktif)`);
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
