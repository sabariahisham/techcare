import $ from 'jquery';

'use strict';

$(document).ready(function () {
    $('#logsTable').DataTable({
        pageLength: 10,
        ordering: true,
        searching: true,
        lengthChange: true,
        language: {
            lengthMenu: "Show _MENU_ entries",
            zeroRecords: "No matching records found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No records available",
            search: "",
            searchPlaceholder: "Carian",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
});

// Make showLogDetails available globally
window.showLogDetails = function(date, filename) {
    $('#logLoading').show();
    $('#logContent').hide();
    $('#logError').hide();
    $('#logFileName').text(filename);
    $('#downloadLogBtn').attr('href', window.logRoutes.baseUrl + '/download/' + date);
    
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    modal.show();
    
    $.ajax({
        url: window.logRoutes.baseUrl + '/view/' + date,
        method: 'GET',
        success: function(response) {
            $('#logLoading').hide();
            $('#logContent').show();
            
            // Store original and highlighted content
            window.originalLogContent = response;
            window.highlightedLogContent = highlightLog(response);
            
            $('#logText').html(window.highlightedLogContent);
        },
        error: function(xhr) {
            $('#logLoading').hide();
            $('#logError').show();
            let errorMsg = 'Gagal memuat log';
            if (xhr.status === 404) errorMsg = 'Log file tidak dijumpai';
            else if (xhr.status === 403) errorMsg = 'Akses ditolak';
            $('#logErrorMessage').text(errorMsg);
        }
    });
};

function highlightLog(content) {
    if (!content) return '';
    
    content = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    content = content.replace(/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/g, '<span class="text-primary">[$1]</span>');
    content = content.replace(/(local\.ERROR:|\bERROR\b)/g, '<span class="badge bg-danger">ERROR</span>');
    content = content.replace(/(local\.WARNING:|\bWARNING\b)/g, '<span class="badge bg-warning text-dark">WARNING</span>');
    content = content.replace(/(local\.INFO:|\bINFO\b)/g, '<span class="badge bg-info text-dark">INFO</span>');
    content = content.replace(/(local\.DEBUG:|\bDEBUG\b)/g, '<span class="badge bg-secondary">DEBUG</span>');
    content = content.replace(/(#\d+)/g, '<span style="color: #ff8c00;">$1</span>');
    content = content.replace(/([a-zA-Z]:\\[^\s:]+\.php)/g, '<span style="color: #daa520;">$1</span>');
    content = content.replace(/(\/[^\s:]+\.php)/g, '<span style="color: #daa520;">$1</span>');
    
    return content;
}

// Debounce function for search
let searchTimeout;
$('#logSearch').on('keyup', function() {
    const searchTerm = $(this).val();
    
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Debounce search by 300ms
    searchTimeout = setTimeout(function() {
        if (!window.highlightedLogContent) return;
        
        if (!searchTerm) {
            // Reset to cached highlighted version
            $('#logText').html(window.highlightedLogContent);
        } else {
            // Only apply search highlight on cached content
            const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + escapedTerm + ')', 'gi');
            const result = window.highlightedLogContent.replace(regex, '<mark style="background-color: #ffeb3b; color: #000; padding: 2px 4px;">$1</mark>');
            $('#logText').html(result);
        }
    }, 300);
});

$('#logDetailsModal').on('hidden.bs.modal', function () {
    window.originalLogContent = '';
    window.highlightedLogContent = '';
    $('#logSearch').val('');
    clearTimeout(searchTimeout);
});