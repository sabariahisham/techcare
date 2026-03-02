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

<style>
  .avatar-initial { cursor: default !important; }
</style>

<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Statistik</h5>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Pegawai</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalOfficers ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-primary">
                   <i class="bx bx-user bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Tiket Diterima</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalTickets ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-news bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Tiket Diselesaikan</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalTicketSelesai ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="bx bx-news bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Tiket Dalam Tindakan</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalTicketDalamTindakan ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="bx bx-news bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Tiket Lewat SLA</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalTicketLewatSla ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="bx bx-news bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">Jumlah Tiket Selesai SLA</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalTicketSelesaiDalamSla ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="bx bx-news bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">JUMLAH HIGH</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalHigh ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="bx bx-error bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">JUMLAH MEDIUM</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalMedium ?? 0) }}</h3>
                </div>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="bx bx-info-circle bx-lg"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="d-flex align-items-start justify-content-between border rounded p-3">
              <div class="content-left">
                <span class="text-heading fw-medium">JUMLAH LOW</span>
                <div class="d-flex align-items-center my-2">
                  <h3 class="mb-0 me-2">{{ number_format($totalLow ?? 0) }}</h3>
                </div>
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
    </div>
  </div>
</div>

<div class="row g-6">
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
          <div class="col-md-3">
            <label for="filter-officer" class="form-label">PEGAWAI TUGASAN</label>
            <select id="filter-officer" class="form-select select2">
              <option value="">Semua</option>
              @foreach($officers as $userId => $userName)
                <option value="{{ $userId }}">{{ $userName }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter-bulan" class="form-label">BULAN</label>
            <select id="filter-bulan" class="form-select select2">
              <option value="">Semua</option>
              <option value="1" {{ ($filterBulan ?? '') == '1' ? 'selected' : '' }}>Januari</option>
              <option value="2" {{ ($filterBulan ?? '') == '2' ? 'selected' : '' }}>Februari</option>
              <option value="3" {{ ($filterBulan ?? '') == '3' ? 'selected' : '' }}>Mac</option>
              <option value="4" {{ ($filterBulan ?? '') == '4' ? 'selected' : '' }}>April</option>
              <option value="5" {{ ($filterBulan ?? '') == '5' ? 'selected' : '' }}>Mei</option>
              <option value="6" {{ ($filterBulan ?? '') == '6' ? 'selected' : '' }}>Jun</option>
              <option value="7" {{ ($filterBulan ?? '') == '7' ? 'selected' : '' }}>Julai</option>
              <option value="8" {{ ($filterBulan ?? '') == '8' ? 'selected' : '' }}>Ogos</option>
              <option value="9" {{ ($filterBulan ?? '') == '9' ? 'selected' : '' }}>September</option>
              <option value="10" {{ ($filterBulan ?? '') == '10' ? 'selected' : '' }}>Oktober</option>
              <option value="11" {{ ($filterBulan ?? '') == '11' ? 'selected' : '' }}>November</option>
              <option value="12" {{ ($filterBulan ?? '') == '12' ? 'selected' : '' }}>Disember</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter-tahun" class="form-label">TAHUN</label>
            <select id="filter-tahun" class="form-select select2">
              <option value="">Semua</option>
              @for($year = date('Y'); $year >= 2020; $year--)
                <option value="{{ $year }}" {{ ($filterTahun ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
              @endfor
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" id="apply-filter" class="btn btn-primary">
              <i class="bx bx-filter me-1"></i>Tapis
            </button>
            <button type="button" id="reset-filter" class="btn btn-secondary ms-2">
              <i class="bx bx-reset me-1"></i>Set Semula
            </button>
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
                <th>BULAN</th>
                <th>TAHUN</th>
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
