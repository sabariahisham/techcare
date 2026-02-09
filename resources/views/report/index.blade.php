@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Senarai Laporan')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
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
  'resources/assets/vendor/libs/toastr/toastr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',

  ])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  'resources/assets/js/report/app-report-list.js',
  ])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Laporan</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">6 laporan</h4>
              {{-- <p class="text-success mb-0">(100%)</p> --}}
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
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

  <div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI LAPORAN</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-refcategory table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Laporan</th>
          <th>Deskripsi Laporan</th>
          <th>Tarikh kemaskini</th>
          <th>Lihat Laporan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@endsection
