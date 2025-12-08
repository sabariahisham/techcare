'use strict';

$(document).ready(function() {
    // Debug: Check if elements exist
    console.log('DateFrom element:', $('#dateFrom').length);
    console.log('DateTo element:', $('#dateTo').length);
    console.log('ModelFilter element:', $('#modelFilter').length);
    console.log('UserFilter element:', $('#userFilter').length);
    
    // Initialize DataTable
    var table = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.auditRoutes.data,
            data: function(d) {
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
                d.model = $('#modelFilter').val();
                d.user_id = $('#userFilter').val();
                
                // Debug: Log filter data
                console.log('DataTable filters being sent:', d);
            },
            error: function(xhr, error, code) {
                console.error('AJAX Error:', xhr.responseText);
                console.error('Status:', xhr.status);
                console.error('Error code:', code);
                alert('Error loading data: ' + xhr.statusText);
            }
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            {
                data: 'user_name',
                name: 'user.nama',
                defaultContent: '-'
            },
            {
                data: 'event',
                name: 'event'
            },
            {
                data: 'model_display',
                name: 'auditable_type'
            },
            {
                data: 'url',
                name: 'url'
            },
            {
                data: 'old_values_preview',
                name: 'old_values',
                orderable: false,
                searchable: false
            },
            {
                data: 'new_values_preview',
                name: 'new_values',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            search: "",
            searchPlaceholder: "Carian",
            lengthMenu: "Show _MENU_ entries",
            zeroRecords: "No matching records found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No records available",
            infoFiltered: "(filtered from _MAX_ total records)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            processing: "Processing..."
        }
    });

    // Filter functionality
    $('#filterBtn').on('click', function() {
        console.log('Filter button clicked');
        console.log('Date From:', $('#dateFrom').val());
        console.log('Date To:', $('#dateTo').val()); 
        console.log('Model:', $('#modelFilter').val());
        console.log('User:', $('#userFilter').val());
        table.ajax.reload();
    });

    $('#resetBtn').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    // Auto filter on enter
    $('#dateFrom, #dateTo').on('change', function() {
        table.ajax.reload();
    });

    $('#modelFilter, #userFilter').on('change', function() {
        table.ajax.reload();
    });

    // Show audit detail in modal - moved inside document ready
    window.showAuditDetail = function(auditId) {
        $.ajax({
            url: window.auditRoutes.detail + '/' + auditId,
            method: 'GET',
            beforeSend: function() {
                // Show loading state
                $('#basicInfo').html('<tr><td colspan="2"><div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Memuat data...</div></td></tr>');
                $('#technicalInfo').html('<tr><td colspan="2"><div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Memuat data...</div></td></tr>');
                $('#oldValuesContent').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Memuat data...</div>');
                $('#newValuesContent').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Memuat data...</div>');
                $('#auditDetailModal').modal('show');
            },
            success: function(response) {
                // Populate basic info
                var basicInfo = '';
                basicInfo += '<tr><td width="40%" class="fw-semibold">ID Audit:</td><td>' + response.id + '</td></tr>';
                basicInfo += '<tr><td class="fw-semibold">Aktiviti:</td><td><span class="badge activity-badge activity-' + response.event + '">' + response.event.charAt(0).toUpperCase() + response.event.slice(1) + '</span></td></tr>';
                basicInfo += '<tr><td class="fw-semibold">Pengguna:</td><td>' + (response.user ? response.user.name : '<span class="text-muted">System</span>') + '</td></tr>';
                basicInfo += '<tr><td class="fw-semibold">Model:</td><td><code>' + response.auditable_type.split('\\').pop() + '</code></td></tr>';
                basicInfo += '<tr><td class="fw-semibold">Model ID:</td><td><span class="badge bg-secondary">' + response.auditable_id + '</span></td></tr>';
                basicInfo += '<tr><td class="fw-semibold">Tarikh:</td><td>' + moment(response.created_at).format('DD/MM/YYYY HH:mm:ss') + '</td></tr>';
                $('#basicInfo').html(basicInfo);
                
                // Populate technical info
                var technicalInfo = '';
                technicalInfo += '<tr><td width="40%" class="fw-semibold">URL:</td><td>' + (response.url ? '<small class="text-muted">' + response.url + '</small>' : '<span class="text-muted">-</span>') + '</td></tr>';
                technicalInfo += '<tr><td class="fw-semibold">IP Address:</td><td>' + (response.ip_address ? '<code>' + response.ip_address + '</code>' : '<span class="text-muted">-</span>') + '</td></tr>';
                technicalInfo += '<tr><td class="fw-semibold">User Agent:</td><td>' + (response.user_agent ? '<small class="text-muted" style="word-break: break-all;">' + response.user_agent.substring(0, 100) + (response.user_agent.length > 100 ? '...' : '') + '</small>' : '<span class="text-muted">-</span>') + '</td></tr>';
                $('#technicalInfo').html(technicalInfo);
                
                // Handle old values
                if (response.old_values && Object.keys(response.old_values).length > 0) {
                    $('#oldValuesSection').show();
                    var oldValuesHtml = '<table class="table table-sm table-bordered mb-0">';
                    oldValuesHtml += '<thead class="table-light"><tr><th width="30%">Field</th><th>Value</th></tr></thead><tbody>';
                    
                    Object.keys(response.old_values).forEach(function(key) {
                        var value = response.old_values[key];
                        var displayValue = formatValue(value);
                        oldValuesHtml += '<tr><td class="fw-semibold">' + formatFieldName(key) + '</td><td>' + displayValue + '</td></tr>';
                    });
                    
                    oldValuesHtml += '</tbody></table>';
                    $('#oldValuesContent').html(oldValuesHtml);
                } else {
                    $('#oldValuesSection').hide();
                }
                
                // Handle new values
                if (response.new_values && Object.keys(response.new_values).length > 0) {
                    $('#newValuesSection').show();
                    var newValuesHtml = '<table class="table table-sm table-bordered mb-0">';
                    newValuesHtml += '<thead class="table-light"><tr><th width="30%">Field</th><th>Value</th></tr></thead><tbody>';
                    
                    Object.keys(response.new_values).forEach(function(key) {
                        var value = response.new_values[key];
                        var displayValue = formatValue(value);
                        newValuesHtml += '<tr><td class="fw-semibold">' + formatFieldName(key) + '</td><td>' + displayValue + '</td></tr>';
                    });
                    
                    newValuesHtml += '</tbody></table>';
                    $('#newValuesContent').html(newValuesHtml);
                } else {
                    $('#newValuesSection').hide();
                }
                
                // Adjust layout if only one section has data
                if (response.old_values && Object.keys(response.old_values).length > 0 && (!response.new_values || Object.keys(response.new_values).length === 0)) {
                    $('#oldValuesSection').removeClass('col-md-6').addClass('col-md-12');
                } else if ((!response.old_values || Object.keys(response.old_values).length === 0) && response.new_values && Object.keys(response.new_values).length > 0) {
                    $('#newValuesSection').removeClass('col-md-6').addClass('col-md-12');
                } else {
                    $('#oldValuesSection').removeClass('col-md-12').addClass('col-md-6');
                    $('#newValuesSection').removeClass('col-md-12').addClass('col-md-6');
                }
            },
            error: function(xhr, status, error) {
                $('#basicInfo').html('<tr><td colspan="2"><div class="alert alert-danger">Ralat memuat data audit</div></td></tr>');
                $('#technicalInfo').html('<tr><td colspan="2"><div class="alert alert-danger">Ralat memuat data audit</div></td></tr>');
                $('#oldValuesContent').html('<div class="alert alert-danger">Ralat memuat data audit</div>');
                $('#newValuesContent').html('<div class="alert alert-danger">Ralat memuat data audit</div>');
                console.error('Error loading audit details:', error);
            }
        });
    };
});

// Helper function to format field names
function formatFieldName(fieldName) {
    // Convert snake_case to Title Case
    return fieldName.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Helper function to format values
function formatValue(value) {
    if (value === null || value === undefined) {
        return '<span class="text-muted">null</span>';
    }
    
    if (typeof value === 'boolean') {
        return value ? '<span class="badge bg-success">True</span>' : '<span class="badge bg-danger">False</span>';
    }
    
    if (typeof value === 'string') {
        // Check if it's a date
        if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
            return '<span class="text-info">' + moment(value).format('DD/MM/YYYY HH:mm:ss') + '</span>';
        }
        
        // Check if it's a long text
        if (value.length > 100) {
            return '<div class="text-truncate" style="max-width: 300px;" title="' + value + '">' + value + '</div>';
        }
        
        return value;
    }
    
    if (typeof value === 'number') {
        return '<span class="text-primary">' + value + '</span>';
    }
    
    if (typeof value === 'object') {
        return '<pre class="text-muted" style="font-size: 0.8em; max-height: 100px; overflow-y: auto;">' + JSON.stringify(value, null, 2) + '</pre>';
    }
    
    return value;
}