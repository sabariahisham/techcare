/**
 * Role Statistics Dashboard
 */

'use strict';

$(document).ready(function() {
    loadRoleStatistics();
});

function loadRoleStatistics() {
    $.ajax({
        type: 'GET',
        url: '/admin/roles/dashboard-stats',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                updateStatisticsCards(response.role_stats, response.general_stats);
            } else {
                console.error('Error loading role statistics:', response.message);
            }
        },
        error: function(xhr) {
            console.error('Error loading role statistics:', xhr);
        }
    });
}

function updateStatisticsCards(roleStats, generalStats) {
    // Update general statistics
    if (generalStats) {
        $('#total-roles').text(generalStats.total_roles || 0);
        $('#active-roles').text('(' + (generalStats.active_roles || 0) + ' aktif)');
    }

    // Update specific role statistics
    if (roleStats) {
        // Super Admin
        if (roleStats['Pentadbir Sistem']) {
            $('#super-admin-count').text(roleStats['Super Admin'].user_count || 0);
            $('#super-admin-active').text('(' + (roleStats['Super Admin'].active_users || 0) + ' aktif)');
        }

        // Administrator
        if (roleStats['Administrator']) {
            $('#administrator-count').text(roleStats['Administrator'].user_count || 0);
            $('#administrator-active').text('(' + (roleStats['Administrator'].active_users || 0) + ' aktif)');
        }

        // Penyelia Helpdesk
        if (roleStats['Penyelia Helpdesk']) {
            $('#penyelia-helpdesk-count').text(roleStats['Penyelia Helpdesk'].user_count || 0);
            $('#penyelia-helpdesk-active').text('(' + (roleStats['Penyelia Helpdesk'].active_users || 0) + ' aktif)');
        }

        // Helpdesk On Duty
        if (roleStats['Helpdesk On Duty']) {
            $('#helpdesk-on-duty-count').text(roleStats['Helpdesk On Duty'].user_count || 0);
            $('#helpdesk-on-duty-active').text('(' + (roleStats['Helpdesk On Duty'].active_users || 0) + ' aktif)');
        }

        // Pegawai Tugasan
        if (roleStats['Pegawai Tugasan']) {
            $('#pegawai-tugasan-count').text(roleStats['Pegawai Tugasan'].user_count || 0);
            $('#pegawai-tugasan-active').text('(' + (roleStats['Pegawai Tugasan'].active_users || 0) + ' aktif)');
        }

        // Pengurusan
        if (roleStats['Pengurusan']) {
            $('#pengurusan-count').text(roleStats['Pengurusan'].user_count || 0);
            $('#pengurusan-active').text('(' + (roleStats['Pengurusan'].active_users || 0) + ' aktif)');
        }
    }
}

// Refresh statistics when data changes
$(document).on('logsPageLoaded', function() {
    loadRoleStatistics();
});

// Refresh statistics after role operations
$(document).on('roleUpdated roleCreated roleDeleted', function() {
    setTimeout(loadRoleStatistics, 500); // Small delay to allow for database updates
});
