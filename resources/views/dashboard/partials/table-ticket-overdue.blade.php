<div class="col-lg-12 col-md-12 mt-3 d-flex align-items-stretch">
    <div class="card flex-fill">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="m-0 me-2">
                    <i class="ti ti-clock-exclamation me-2 text-warning"></i>
                    SENARAI TIKET OVERDUE
                </h5>
            </div>
            <small class="text-muted">Senarai tiket yang telah melepasi tarikh due</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="table-overdue">
                    <thead>
                        <tr>
                            <th>BIL</th>
                            <th>NO. LOG TIKET</th>
                            <th>KATEGORI</th>
                            <th>TAJUK</th>
                            <th>STATUS</th>
                            <th>DITUGASKAN</th>
                            <th>TARIKH DUE</th>
                            <th>LEWAT</th>
                            <th>TINDAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be populated via DataTable -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Blinking animation for overdue bell icon */
    @keyframes blink {

        0%,
        50% {
            opacity: 1;
        }

        51%,
        100% {
            opacity: 0.3;
        }
    }

    .blink-icon {
        animation: blink 1.5s ease-in-out infinite;
    }

    /* Enhanced overdue styling */
    .text-danger.fw-bold {
        color: #dc3545 !important;
        font-weight: 700 !important;
    }

    .text-warning.fw-bold {
        color: #fd7e14 !important;
        font-weight: 700 !important;
    }

    /* DataTable responsive improvements */
    #table-overdue thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.875rem;
    }

    #table-overdue tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Badge styling for status */
    .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.5rem;
    }

    .badge-info {
        background-color: #17a2b8;
        color: white;
    }

    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }

    .badge-primary {
        background-color: #007bff;
        color: white;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable for overdue tickets
        if ($.fn.DataTable.isDataTable('#table-overdue')) {
            $('#table-overdue').DataTable().destroy();
        }

        const overdueTable = $('#table-overdue').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('dashboard.list-overdue') }}',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('DataTable error:', error);
                    toastr.error('Gagal memuat data tiket overdue');
                }
            },
            columns: [{
                    data: 'bil',
                    name: 'bil',
                    width: '5%',
                    className: 'text-center'
                },
                {
                    data: 'ticket_nolog',
                    name: 'ticket_nolog',
                    width: '15%'
                },
                {
                    data: 'kategori',
                    name: 'kategori',
                    width: '15%'
                },
                {
                    data: 'tajuk',
                    name: 'tajuk',
                    width: '25%'
                },
                {
                    data: 'status',
                    name: 'status',
                    width: '10%',
                    className: 'text-center'
                },
                {
                    data: 'assigned_to',
                    name: 'assigned_to',
                    width: '10%'
                },
                {
                    data: 'sla_duedate',
                    name: 'sla_duedate',
                    width: '10%',
                    className: 'text-center'
                },
                {
                    data: 'days_overdue',
                    name: 'days_overdue',
                    width: '8%',
                    className: 'text-center'
                },
                {
                    data: 'action',
                    name: 'action',
                    width: '7%',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            pageLength: 10,
            lengthMenu: [
                [5, 10, 25, 50],
                [5, 10, 25, 50]
            ],
            order: [
                [7, 'desc']
            ], // Sort by days_overdue descending
            language: {
                processing: "Memproses...",
                lengthMenu: "Papar _MENU_ rekod",
                zeroRecords: "Tiada tiket overdue dijumpai",
                info: "Memapar _START_ hingga _END_ daripada _TOTAL_ rekod",
                infoEmpty: "Memapar 0 hingga 0 daripada 0 rekod",
                infoFiltered: "(ditapis daripada _MAX_ jumlah rekod)",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Seterusnya",
                    previous: "Sebelumnya"
                }
            },
            responsive: true,
            autoWidth: false,
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
            drawCallback: function(settings) {
                // Re-initialize tooltips after table redraw
                $('[title]').tooltip();

                // Add click handler for view ticket buttons
                $('.view-ticket').off('click').on('click', function() {
                    const ticketNo = $(this).data('id');
                    // You can implement view ticket modal or redirect here
                    toastr.info('Fungsi lihat tiket: ' + ticketNo);
                });
            }
        });

        // Refresh table every 5 minutes for real-time updates
        setInterval(function() {
            if (overdueTable) {
                overdueTable.ajax.reload(null, false);
            }
        }, 300000); // 5 minutes
    });
</script>
