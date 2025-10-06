@extends('layouts/layoutMaster')

@section('title', 'Senarai Module ')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
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
  'resources/assets/vendor/libs/toastr/toastr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',

  ])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-modules-list.js',
  'resources/assets/js/modal-add-module.js',
  'resources/assets/js/modal-edit-module.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Modul</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalModules }}</h4>
              {{-- <p class="text-success mb-0">(100%)</p> --}}
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-list-ul bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pengguna Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2"></h4>
              <p class="text-success mb-0"></p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-user-check bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
  {{-- <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pengguna Tidak Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2"></h4>
              <p class="text-danger mb-0">()</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-user-x bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
</div>


{{-- <p class="mb-6">Lihat, buat, edit atau padam modul.</p> --}}
<!-- Permission Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI MODUL</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-modules table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Module Name</th>
          <th>Updated Date</th>
          <th>Created Date</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>

  </div>
</div>
<!--/ Permission Table -->

<!-- Modal -->
@include('_partials/_modals/modal-add-module')
@include('_partials/_modals/modal-edit-module')
<!-- /Modal -->
@endsection
