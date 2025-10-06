@extends('layouts/layoutMaster')

@section('title', 'Senarai Permission')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  ])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-permission-list.js',
  'resources/assets/js/modal-add-permission.js',
  'resources/assets/js/modal-edit-permission.js',
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
              <h4 class="mb-0 me-2">{{ $stats['total_modules'] ?? 0 }}</h4>
              <p class="text-success mb-0">(100%)</p>
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
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Permission</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $stats['total_permissions'] ?? 0 }}</h4>
              <p class="text-success mb-0">({{ $stats['active_permissions'] ?? 0 }} aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
               <i class="bx bx-list-ul bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Permission Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI PERMISSION</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-permissions table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Permission Name</th>
          <th>Module Name</th>
          <th>Assigned To</th>
          {{-- <th>Created Date</th> --}}
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Permission Table -->

<!-- Modal -->
@include('_partials/_modals/modal-add-permission')
@include('_partials/_modals/modal-edit-permission')
<!-- /Modal -->
@endsection
