@extends('layouts.app')

@section('content')<div class="col-lg-12 col-md-12 mt-6 d-flex align-items-stretch">
    <div class="card flex-fill">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="m-0 me-2">
                    <i class="ti ti-clock-exclamation me-2 text-warning"></i>
                    SENARAI TIKET PERLU TINDAKAN
                </h5>
            </div>
            <small class="text-muted">Tiket ini memerlukan tindakan segera</small>
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
                            <th>TARIKH LOG</th>
                            <th>TARIKH DUE</th> 
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

@endsection