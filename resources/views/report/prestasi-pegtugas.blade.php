@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Prestasi Pegawai Tugasan')

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
  'resources/assets/js/report/app-prestasi-pegtugas.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-medium">Jumlah Pegawai</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalOfficers ?? 0) }}</h3>
            </div>
            <small class="text-muted">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-user bx-lg"></i>
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
            <span class="text-heading fw-medium">Jumlah Tiket</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalTickets ?? 0) }}</h3>
            </div>
            <small class="text-muted">Tiket yang ditugaskan</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
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
            <span class="text-heading fw-medium">JUMLAH HIGH</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalHigh ?? 0) }}</h3>
            </div>
            <small class="text-muted">Tiket keutamaan tinggi</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-error bx-lg"></i>
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
            <span class="text-heading fw-medium">JUMLAH MEDIUM</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalMedium ?? 0) }}</h3>
            </div>
            <small class="text-muted">Tiket keutamaan sederhana</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-info-circle bx-lg"></i>
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
            <span class="text-heading fw-medium">JUMLAH LOW</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2">{{ number_format($totalLow ?? 0) }}</h3>
            </div>
            <small class="text-muted">Tiket keutamaan rendah</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="bx bx-check-circle bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Prestasi Pegawai Tugasan</h5>
        <a href="{{ route('report-index') }}" class="btn btn-sm btn-secondary">
          <i class="bx bx-arrow-back me-1"></i>Kembali ke Senarai Laporan
        </a>
      </div>
      <hr>
      <div class="card-body">
        <!-- Filter Section -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label for="filter-officer" class="form-label">PEGAWAI TUGASAN</label>
            <select id="filter-officer" class="form-select select2">
              <option value="">Semua</option>
              @foreach($officers as $userId => $userName)
                <option value="{{ $userId }}">{{ $userName }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <hr>

        <!-- DataTable -->
        <div class="card-datatable table-responsive">
          <table class="datatables-prestasi-pegtugas table border-top">
            <thead>
              <tr>
                <th>BIL</th>
                <th>PEGAWAI TUGASAN</th>
                <th>TIKET DITERIMA</th>
                <th>TIKET DISELESAI</th>
                <th>TIKET DALAM TINDAKAN</th>
                <th>TIKET LEWAT SLA</th>
                <th>TIKET SELESAI DALAM TEMPOH SLA</th>
                <th>HIGH</th>
                <th>MEDIUM</th>
                <th>LOW</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
