@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Senarai Log Tiket')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/report/app-log-ticket.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">

  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-medium">Jumlah Log Tiket</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalTickets) }}</h3>
            </div>
            <small class="text-muted">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-news bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-medium">Aduan Selesai</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($resolvedTickets ?? 0) }}</h3>
            </div>
            <small class="text-muted">Dari jumlah keseluruhan</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-check-circle bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-medium">Peratusan Selesai</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ $resolvedPercentage ?? 0 }}%</h3>
            </div>
            <small class="text-{{ ($resolvedPercentage ?? 0) >= 70 ? 'success' : 'warning' }} fw-medium">
              {{ ($resolvedPercentage ?? 0) >= 70 ? 'Prestasi Baik' : 'Perlu Penambahbaikan' }}
            </small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-{{ ($resolvedPercentage ?? 0) >= 70 ? 'success' : 'warning' }}">
              <i class="bx bx-stats bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-medium">Aduan Belum Selesai</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format(($totalTickets ?? 0) - ($resolvedTickets ?? 0)) }}</h3>
            </div>
            <small class="text-muted">Masih dalam proses</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="bx bx-time-five bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Senarai Log Tiket</h5>
        <a href="{{ route('report-index') }}" class="btn btn-sm btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Kembali ke Senarai Laporan
        </a>
      </div>
      <hr>
      <div class="card-body">
        <!-- Filter Section -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label for="filter-status" class="form-label">STATUS ADUAN</label>
            <select id="filter-status" class="form-select select2">
              <option value="">Semua</option>
              @foreach($statuses as $status)
                <option value="{{ $status->id }}">{{ $status->st_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="filter-category" class="form-label">KATEGORI</label>
            <select id="filter-category" class="form-select select2">
              <option value="">Semua</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="filter-subcategory" class="form-label">SUB KATEGORI</label>
            <select id="filter-subcategory" class="form-select select2">
              <option value="">Semua</option>
              @foreach($subCategories as $subCategory)
                <option value="{{ $subCategory->id }}" data-category="{{ $subCategory->cat_id }}">{{ $subCategory->subcat_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="filter-officer" class="form-label">PEGAWAI TUGASAN</label>
            <select id="filter-officer" class="form-select select2">
              <option value="">Semua</option>
              @foreach($officers as $userId => $userName)
                <option value="{{ $userId }}">{{ $userName }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="filter-department" class="form-label">BAHAGIAN</label>
            <select id="filter-department" class="form-select select2">
              <option value="">Semua</option>
              @foreach($bahagians as $bahagian)
                <option value="{{ $bahagian->kod_bah }}">{{ $bahagian->nama_bah }}</option>
              @endforeach
            </select>
          </div>
         <div class="col-md-4">
            <label for="filter-month" class="form-label">BULAN</label>
            <select id="filter-month" class="form-select select2">
              <option value="">Semua</option>
              <option value="1">Januari</option>
              <option value="2">Februari</option>
              <option value="3">Mac</option>
              <option value="4">April</option>
              <option value="5">Mei</option>
              <option value="6">Jun</option>
              <option value="7">Julai</option>
              <option value="8">Ogos</option>
              <option value="9">September</option>
              <option value="10">Oktober</option>
              <option value="11">November</option>
              <option value="12">Disember</option>
            </select>
          </div>
            <div class="col-md-4">
            <label for="filter-year" class="form-label">TAHUN</label>
            <select id="filter-year" class="form-select select2">
              <option value="2025">2025</option>
              <option value="2024">2024</option>
              <option value="2023">2023</option>
              <option value="2022">2022</option>
              <option value="2021">2021</option>
              <option value="2020">2020</option>
            </select>
          </div>
          </div>
        <hr>

        <!-- DataTable -->
        <div class="card-datatable table-responsive">
          <table class="datatables-log-ticket table border-top">
            <thead>
              <tr>
                <th>BIL</th>
                <th>NO LOG TIKET</th>
                <th>PENGADU</th>
                <th>BAHAGIAN</th>
                <th>STATUS TIKET</th>
                <th>KATEGORI</th>
                <th>SUB KATEGORI</th>
                <th>PEGAWAI TUGASAN</th>
                <th>SEVERITY</th>
                <th>JUMLAH HARI SLA</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
