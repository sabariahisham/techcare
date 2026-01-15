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
  'resources/assets/js/report/app-aduan-tahunan.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="card-title mb-0">Laporan Aduan Tahunan</h5>
          <small class="text-muted">Statistik aduan berdasarkan kategori dan bulan</small>
        </div>
        <a href="{{ route('report-index') }}" class="btn btn-sm btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Kembali
        </a>
      </div>
      <hr class="my-0">
      <div class="card-body">
        <!-- Filter Section -->
        <div class="row align-items-end filter-row">
          <div class="col-md-3">
            <label for="filter-year" class="form-label fw-semibold">TAHUN <span class="text-danger">*</span></label>
            <select id="filter-year" class="form-select select2" required>
              @foreach($years as $year)
                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
              @endforeach
            </select>
            
          </div>
          
          <!-- FILTER BULAN - MULTI SELECT -->
          <div class="col-md-4">
            <label for="filter-month" class="form-label fw-semibold">BULAN <span class="text-muted">(Pilih satu atau lebih - kosongkan untuk semua)</span></label>
            <select id="filter-month" class="form-select select2" multiple="multiple">
              <option value="1">Jan</option>
              <option value="2">Feb</option>
              <option value="3">Mac</option>
              <option value="4">Apr</option>
              <option value="5">Mei</option>
              <option value="6">Jun</option>
              <option value="7">Jul</option>
              <option value="8">Ogos</option>
              <option value="9">Sep</option>
              <option value="10">Okt</option>
              <option value="11">Nov</option>
              <option value="12">Dis</option>
            </select>
          
          </div>
          
          <div class="col-md-3">
            <label for="filter-category" class="form-label fw-semibold">KATEGORI <span class="text-muted">(Kosongkan untuk semua kategori)</span></label>
            <select id="filter-category" class="form-select select2" multiple="multiple">
              <option value="">Semua Kategori</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
              @endforeach
            </select>
            
          </div>
          
          <div class="col-md-2 d-grid">
            <button type="button" id="btn-papar" class="btn btn-success w-100">
              <i class="bx bx-stats me-1"></i>Papar Statistik
            </button>
          </div>
        </div>
        <hr>

        <!-- DataTable - IMPORTANT: Struktur header mesti tepat -->
        <div class="table-responsive">
          <table id="table-aduan-tahunan" class="table table-bordered table-aduan-tahunan w-100">
            <thead>
              {{-- Row 1: Bulan headers dengan colspan=3 --}}
              <tr>
                <th rowspan="2">Kategori Aduan</th>
                <th colspan="3" class="month-header">Jan</th>
                <th colspan="3" class="month-header">Feb</th>
                <th colspan="3" class="month-header">Mac</th>
                <th colspan="3" class="month-header">Apr</th>
                <th colspan="3" class="month-header">Mei</th>
                <th colspan="3" class="month-header">Jun</th>
                <th colspan="3" class="month-header">Jul</th>
                <th colspan="3" class="month-header">Ogos</th>
                <th colspan="3" class="month-header">Sep</th>
                <th colspan="3" class="month-header">Okt</th>
                <th colspan="3" class="month-header">Nov</th>
                <th colspan="3" class="month-header">Dis</th>
                <th colspan="3" class="total-header">Jumlah</th>
              </tr>
              {{-- Row 2: Sub-headers (39 columns, sebab column 0 sudah rowspan=2) --}}
              <tr>
                {{-- Jan --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Feb --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Mac --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Apr --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Mei --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Jun --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Jul --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Ogos --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Sep --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Okt --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Nov --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Dis --}}<th>Aduan</th><th>Selesai</th><th>%</th>
                {{-- Jumlah --}}<th>Aduan</th><th>Selesai</th><th>%</th>
              </tr>
            </thead>
            {{-- <tfoot>
              <tr>
                <td><strong>Jumlah Keseluruhan</strong></td>
                @for($i = 0; $i < 13; $i++)
                  <td class="total-aduan">0</td>
                  <td class="total-selesai">0</td>
                  <td class="total-persen">100%</td>
                @endfor
              </tr>
            </tfoot> --}}
            <tfoot>
            <tr class="bg-success text-white fw-bold text-center">
            <th>JUMLAH KESELURUHAN</th>

            <!-- Jan -->
            <th></th><th></th><th></th>
            <!-- Feb -->
            <th></th><th></th><th></th>
            <!-- Mac -->
            <th></th><th></th><th></th>
            <!-- Apr -->
            <th></th><th></th><th></th>
            <!-- Mei -->
            <th></th><th></th><th></th>
            <!-- Jun -->
            <th></th><th></th><th></th>
            <!-- Jul -->
            <th></th><th></th><th></th>
            <!-- Ogos -->
            <th></th><th></th><th></th>
            <!-- Sep -->
            <th></th><th></th><th></th>
            <!-- Okt -->
            <th></th><th></th><th></th>
            <!-- Nov -->
            <th></th><th></th><th></th>
            <!-- Dis -->
            <th></th><th></th><th></th>

            <!-- Jumlah Akhir -->
            <th></th><th></th><th></th>
            </tr>
            </tfoot>

          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection