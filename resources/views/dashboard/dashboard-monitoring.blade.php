@extends('layouts/layoutMaster')

@section('title', 'Pemantauan Tiket')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/css/dashboard-monitoring-layout.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard/monitoring.js'])
@endsection

@section('content')
    <div class="monitoring-dashboard" id="monitoringDashboard">
        @if ($sections['helpdesk'] ?? false)
            <div class="card text-dark d-flex flex-column"
                style="background-color: transparent; border: none; box-shadow: none;">
                <div class="card-header monitoring-header" style="padding:1rem 0.5rem 0.3rem; flex-shrink:0;">
                    <div class="p-2 rounded shadow-sm d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2 monitoring-header-bar"
                        style="background-color:rgba(255,255,255,0.95); box-shadow:0 2px 8px rgba(30,58,138,0.15);">
                        <div class="d-flex align-items-center monitoring-header-title">
                            <i id="menu-toggle-icon" class='bx bx-menu me-2 md-menu-icon'
                                style='color:#1431eb; cursor:pointer;' data-bs-toggle="tooltip"
                                data-bs-placement="bottom"
                                title="Toggle Sidebar"></i>

                            <h4 class="mb-0 md-page-title" style="color:#1431eb;">
                                PEMANTAUAN TIKET
                            </h4>
                        </div>

                        <div id="currentDateTime" class="md-datetime monitoring-datetime text-center text-sm-end">
                            <i class="bx bx-time-five me-2"></i>
                            Loading...
                        </div>
                    </div>
                </div>

                <div class="card-body flex-grow-1" style="padding:0.3rem; min-height:0;">
                    <div class="dashboard-row-consistent">
                        <div class="cards-status-row">
                            @include('dashboard.partials.monitoring.cards-status')
                        </div>

                        <div class="row gx-3 charts-row">
                            @include('dashboard.partials.monitoring.chart-status')
                            @include('dashboard.partials.monitoring.chart-category')
                            @include('dashboard.partials.monitoring.chart-subcategory')
                        </div>
                    </div>
                </div>

                <div class="card-footer text-center monitoring-footer" style="padding:0.3rem">
                    <div class="text-body">
                        <span class="md-footer-text" style="color: white;">©
                            <script>
                                document.write(new Date().getFullYear())
                            </script> Sistem BTM TechCare <br> Dibangunkan oleh Bahagian Teknologi Maklumat,
                            Kementerian Kewangan Malaysia
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
