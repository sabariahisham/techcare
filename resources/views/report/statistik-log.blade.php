@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Statistik dan Log Aduan ICT')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('page-script')
@vite([
  'resources/assets/js/report/app-statistik-log.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <!-- Page Header -->
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-1">
              Statistik dan Log Aduan 
            </h4>
            <p class="text-muted mb-0">Paparan Statistik Log Aduan</p>
          </div>
         
          <div class="d-flex flex-column align-items-end gap-2">
             <a href="{{ route('report-index') }}" class="btn btn-sm btn-secondary">
               <i class="bx bx-arrow-back me-1"></i>Kembali
            </a>
            <select id="filter-year" class="form-select" style="width: 150px;">
              @foreach($years as $year)
                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart Section -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="bx bx-bar-chart me-2"></i>Statistik Log Aduan Mengikut Bulan
        </h5>
      </div>
      <div class="card-body">
        <div style="position: relative; height: 500px;">
          <canvas id="chartStatistikLog"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- DataTable Section -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="bx bx-table me-2"></i>Jadual Statistik Log Aduan
        </h5>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-success btn-sm" id="btn-export-excel">
            
            <i class="bx bx-download me-1"></i>EXCEL
          </button>
          <button type="button" class="btn btn-primary btn-sm" id="btn-export-pdf">
            <i class="bx bx-printer me-1"></i>PDF / PRINT
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="table-statistik-log" class="table table-bordered table-hover">
            <thead>
              <tr class="table-primary">
                <th rowspan="2" class="align-middle">Bulan</th>
                <th colspan="999" class="text-center" id="kategori-colspan">KATEGORI</th>
                <th rowspan="2" class="align-middle text-center">Jumlah</th>
              </tr>
              <tr class="table-primary" id="category-headers">
                <!-- Dynamic category names will be inserted here -->
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <tr class="table-secondary fw-bold">
                <td>JUMLAH</td>
                <!-- Totals will be added dynamically -->
                <td class="text-center" id="total-jumlah">0</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-style')
<style>
  /* Chart Container */
  #chartStatistikLog {
    max-height: 500px;
  }

  /* Table Styling */
  #table-statistik-log {
    font-size: 0.875rem;
  }

  #table-statistik-log thead th {
    background-color: #0d6efd;
    color: white;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
  }

  #table-statistik-log tbody td {
    vertical-align: middle;
    padding: 0.625rem 0.5rem;
  }

  #table-statistik-log tbody td:first-child {
    font-weight: 500;
  }

  #table-statistik-log tbody td:not(:first-child) {
    text-align: center;
  }

  #table-statistik-log tfoot td {
    background-color: #f8f9fa;
    font-weight: 700;
    font-size: 0.9rem;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    #chartStatistikLog {
      height: 400px !important;
    }
  }
</style>
@endsection