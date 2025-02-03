@extends('layouts/layoutMaster')

@section('title', 'Permission - Apps')

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
  'resources/assets/js/app-access-module.js',
  'resources/assets/js/modal-add-module.js',
  'resources/assets/js/modal-edit-module.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])
@endsection

@section('content')
<h4 class="mb-1">Module List</h4>

<p class="mb-6">View, create, edit or delete modules.</p>
<!-- Permission Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-modules table">
      <thead class="border-top">
        <tr>
          <th>Name</th>
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
