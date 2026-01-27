{{-- filepath: c:\laragon\www\ehelp\resources\views\report\aduan-tahunan.blade.php --}}
@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Laporan Aduan Tahunan')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('page-style')
<style>
  /* ========================================
     ENTERPRISE/CORPORATE DASHBOARD STYLING
     ======================================== */
  
  /* Table styling */
  .dataTables_scrollFootInner table {
    width: 100% !important;
  }
  .dataTables_scrollFoot table {
    width: 100% !important;
    table-layout: fixed;
  }
  .dataTables_scrollHeadInner table {
    width: 100% !important;
    table-layout: fixed;
  }
  
  .table-aduan-tahunan {
    font-size: 0.75rem;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
  }
  
  .table-aduan-tahunan th,
  .table-aduan-tahunan td {
    text-align: center;
    vertical-align: middle;
    padding: 0.5rem 0.6rem;
    border: 1px solid #E5E7EB !important;
  }
  
  /* First column sticky */
  .table-aduan-tahunan th:first-child,
  .table-aduan-tahunan td:first-child {
    text-align: left;
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: #fff;
    min-width: 180px;
    max-width: 200px;
    box-shadow: 2px 0 4px rgba(0,0,0,0.05);
  }
  
  .dark-style .table-aduan-tahunan th:first-child,
  .dark-style .table-aduan-tahunan td:first-child {
    background-color: #2b2c40;
  }
  
  /* ========================================
     HEADER UTAMA - Navy Blue Enterprise
     ======================================== */
  .table-aduan-tahunan thead tr:first-child th {
    background-color: #1E3A8A !important;
    color: #F8FAFC !important;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #1E40AF !important;
    text-transform: uppercase;
  }
  
  .table-aduan-tahunan thead tr:first-child th:first-child {
    background-color: #1E3A8A !important;
    z-index: 3;
  }
  
  /* ========================================
     SUB-HEADER - Biru Sederhana
     ======================================== */
  .table-aduan-tahunan thead tr:last-child th {
    background-color: #3B82F6 !important;
    color: #F8FAFC !important;
    font-weight: 500;
    font-size: 0.7rem;
    border-top: none !important;
  }
  
  /* Month header - Biru sederhana */
  .month-header {
    background-color: #3B82F6 !important;
  }
  
  /* Total header - Biru lebih gelap untuk pembezaan */
  .total-header {
    background-color: #2563EB !important;
    font-weight: 700 !important;
  }
  
  /* ========================================
     PEMISAH VISUAL ANTARA BULAN
     ======================================== */
  /* Border kanan pada column % setiap bulan (col 3, 6, 9, dst) */
  .table-aduan-tahunan thead th:nth-child(4),
  .table-aduan-tahunan thead th:nth-child(7),
  .table-aduan-tahunan thead th:nth-child(10),
  .table-aduan-tahunan thead th:nth-child(13),
  .table-aduan-tahunan thead th:nth-child(16),
  .table-aduan-tahunan thead th:nth-child(19),
  .table-aduan-tahunan thead th:nth-child(22),
  .table-aduan-tahunan thead th:nth-child(25),
  .table-aduan-tahunan thead th:nth-child(28),
  .table-aduan-tahunan thead th:nth-child(31),
  .table-aduan-tahunan thead th:nth-child(34),
  .table-aduan-tahunan thead th:nth-child(37) {
    border-right: 2px solid #CBD5E1 !important;
  }
  
  .table-aduan-tahunan tbody td:nth-child(4),
  .table-aduan-tahunan tbody td:nth-child(7),
  .table-aduan-tahunan tbody td:nth-child(10),
  .table-aduan-tahunan tbody td:nth-child(13),
  .table-aduan-tahunan tbody td:nth-child(16),
  .table-aduan-tahunan tbody td:nth-child(19),
  .table-aduan-tahunan tbody td:nth-child(22),
  .table-aduan-tahunan tbody td:nth-child(25),
  .table-aduan-tahunan tbody td:nth-child(28),
  .table-aduan-tahunan tbody td:nth-child(31),
  .table-aduan-tahunan tbody td:nth-child(34),
  .table-aduan-tahunan tbody td:nth-child(37) {
    border-right: 2px solid #CBD5E1 !important;
  }
  
  .table-aduan-tahunan tfoot th:nth-child(4),
  .table-aduan-tahunan tfoot th:nth-child(7),
  .table-aduan-tahunan tfoot th:nth-child(10),
  .table-aduan-tahunan tfoot th:nth-child(13),
  .table-aduan-tahunan tfoot th:nth-child(16),
  .table-aduan-tahunan tfoot th:nth-child(19),
  .table-aduan-tahunan tfoot th:nth-child(22),
  .table-aduan-tahunan tfoot th:nth-child(25),
  .table-aduan-tahunan tfoot th:nth-child(28),
  .table-aduan-tahunan tfoot th:nth-child(31),
  .table-aduan-tahunan tfoot th:nth-child(34),
  .table-aduan-tahunan tfoot th:nth-child(37) {
    border-right: 2px solid #CBD5E1 !important;
  }
  
  /* ========================================
     BODY ROWS - Zebra & Hover Professional
     ======================================== */
  .table-aduan-tahunan tbody tr {
    transition: background-color 0.2s ease;
  }
  
  /* Odd rows - Light gray */
  .table-aduan-tahunan tbody tr:nth-child(odd) td {
    background-color: #F9FAFB;
  }
  
  .table-aduan-tahunan tbody tr:nth-child(odd) td:first-child {
    background-color: #F9FAFB;
  }
  
  /* Even rows - White */
  .table-aduan-tahunan tbody tr:nth-child(even) td {
    background-color: #FFFFFF;
  }
  
  .table-aduan-tahunan tbody tr:nth-child(even) td:first-child {
    background-color: #FFFFFF;
  }
  
  /* Hover effect - Biru lembut */
  .table-aduan-tahunan tbody tr:hover td {
    background-color: #EFF6FF !important;
  }
  
  .table-aduan-tahunan tbody tr:hover td:first-child {
    background-color: #EFF6FF !important;
  }
  
  /* ========================================
     FOOTER - Dark Green Professional (UPDATED)
     ======================================== */
  .table-aduan-tahunan tfoot th {
    background-color: #166534 !important;
    color: #ECFDF5 !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    border-top: 3px solid #15803D !important;
    padding: 0.7rem 0.6rem;
  }
  
  .table-aduan-tahunan tfoot th:first-child {
    background-color: #166534 !important;
    z-index: 3;
    text-transform: uppercase;
  }
  
  /* ========================================
     BADGE PERATUS - Enterprise Color Scheme
     ======================================== */
  
  /* 100% - Hijau profesional */
  .badge.badge-success-enterprise {
    background-color: #16A34A !important;
    color: #FFFFFF !important;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
    font-size: 0.7rem;
    border-radius: 6px;
  }
  
  /* 75-99% - Biru lembut */
  .badge.badge-info-enterprise {
    background-color: #0EA5E9 !important;
    color: #FFFFFF !important;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
    font-size: 0.7rem;
    border-radius: 6px;
  }
  
  /* 50-74% - Amber */
  .badge.badge-warning-enterprise {
    background-color: #F59E0B !important;
    color: #FFFFFF !important;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
    font-size: 0.7rem;
    border-radius: 6px;
  }
  
  /* <50% - Merah muted (UPDATED) */
  .badge.badge-danger-enterprise {
    background-color: #B91C1C !important;
    color: #FFFFFF !important;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
    font-size: 0.7rem;
    border-radius: 6px;
  }
  
  /* ========================================
     SCROLLING & RESPONSIVENESS
     ======================================== */
  .dataTables_scrollHead,
  .dataTables_scrollBody {
    overflow: visible !important;
  }
  
  .dataTables_scroll {
    overflow-x: auto;
  }
  
  /* Enhanced scrollbar untuk professional look */
  .table-responsive::-webkit-scrollbar {
    height: 10px;
  }
  
  .table-responsive::-webkit-scrollbar-track {
    background: #F3F4F6;
    border-radius: 5px;
  }
  
  .table-responsive::-webkit-scrollbar-thumb {
    background: #9CA3AF;
    border-radius: 5px;
  }
  
  .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #6B7280;
  }
  
  /* Dark mode adjustments */
  .dark-style .table-aduan-tahunan tbody tr:nth-child(odd) td {
    background-color: #2b2c40;
  }
  
  .dark-style .table-aduan-tahunan tbody tr:nth-child(even) td {
    background-color: #232333;
  }
  
  .dark-style .table-aduan-tahunan tbody tr:hover td {
    background-color: #3b3c55 !important;
  }

  /* ========================================
     BUTTON STYLING - Enterprise Theme
     ======================================== */
  
  /* Butang "Papar Statistik" - Primary Navy Blue */
  #btn-papar {
    background-color: #1E3A8A !important;
    color: #F8FAFC !important;
    border-color: #1E3A8A !important;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
  }
  
  #btn-papar:hover,
  #btn-papar:focus {
    background-color: #1E40AF !important;
    border-color: #1E40AF !important;
    color: #F8FAFC !important;
    box-shadow: 0 4px 8px rgba(30, 64, 175, 0.3);
    transform: translateY(-1px);
  }
  
  #btn-papar:active {
    background-color: #1E3A8A !important;
    transform: translateY(0);
  }
  
  /* Butang "EXCEL" - Secondary Success Green */
  .dt-button.btn-success {
    background-color: #166534 !important;
    color: #ECFDF5 !important;
    border-color: #166534 !important;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(22, 101, 52, 0.2);
  }
  
  .dt-button.btn-success:hover,
  .dt-button.btn-success:focus {
    background-color: #14532D !important;
    border-color: #14532D !important;
    color: #ECFDF5 !important;
    box-shadow: 0 4px 8px rgba(20, 83, 45, 0.3);
    transform: translateY(-1px);
  }
  
  .dt-button.btn-success:active {
    background-color: #166534 !important;
    transform: translateY(0);
  }
  
  /* Butang "PDF / PRINT" - Kekalkan primary blue DataTables */
  .dt-button.btn-primary {
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
  }
  
  .dt-button.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
  }
  
  .dt-button.btn-primary:active {
    transform: translateY(0);
  }

  /* ========================================
     MONTH FILTER - Dynamic Column Visibility
     ======================================== */

  /* Ensure proper header visibility handling */
  .table-aduan-tahunan thead th {
    display: table-cell;
  }

  .table-aduan-tahunan thead th[style*="display: none"] {
    display: none !important;
  }

  /* Smooth transition for column show/hide */
  .table-aduan-tahunan tbody td,
  .table-aduan-tahunan tfoot th {
    transition: all 0.2s ease;
  }

  .filter-row .form-select,
  .filter-row .select2-container {
    min-height: 38px;
  }

    .filter-row .select2-container--default .select2-selection--single,
    .filter-row .select2-container--default .select2-selection--multiple {
    min-height: 38px;
    display: flex;
    align-items: center;
  }

    .filter-row small {
    display: none;
    font-size: 12px;
    margin-top: 4px;
    color: #6B7280;
    line-height: 1.2;
    }

    .filter-row .col-md-2 {
    display: flex;
    align-items: flex-end;
    }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/report/app-laporan-status-aduan.js',
])
@endsection

@section('content')
<div class="card shadow-sm">

    {{-- HEADER LAPORAN --}}
    <div class="card-header bg-white">
        <h5 class="mb-1 fw-bold">LAPORAN ADUAN MENGIKUT STATUS</h5>
        <small class="text-muted">
            Tahun: <span id="label-year"></span> |
            Bulan: <span id="label-month"></span>
        </small>
    </div>

    <div class="card-body">

        {{-- FILTER --}}
        <div class="row align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label fw-semibold">TAHUN</label>
                <input type="number" id="filter-year" class="form-control"
                       value="{{ now()->year }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    BULAN
                    <small class="text-muted">(Pilih satu atau lebih - kosongkan untuk semua)</small>
                </label>
                <select id="filter-month" class="form-control" multiple>
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}">
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table id="laporan-table" class="table table-bordered table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th class="bg-light">KATEGORI</th>
                        <th class="bg-info-subtle text-center">ADUAN BARU</th>
                        <th class="bg-info-subtle text-center">ADUAN DIPROSES</th>
                        <th class="bg-info-subtle text-center">SERAHAN KEPADA</th>
                        <th class="bg-info-subtle text-center">ADUAN DIRUJUK</th>
                        <th class="bg-info-subtle text-center">ADUAN DIBATAL</th>
                        <th class="bg-info-subtle text-center">ADUAN SELESAI</th>
                        <th class="bg-success-subtle text-center">JUMLAH</th>
                    </tr>
                </thead>

                <tfoot>
                    <tr class="table-success fw-bold">
                        <th>Σ JUMLAH KESELURUHAN</th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                        <th class="text-center"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>
@endsection