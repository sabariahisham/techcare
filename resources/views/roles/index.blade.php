@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Senarai Roles ')

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
  'resources/assets/js/app-roles-list.js',
  'resources/assets/js/app-roles-add.js',
  'resources/assets/js/modal-edit-role.js',
  'resources/assets/js/modal-delete-role.js',
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
            <span class="text-heading">Jumlah Roles</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $stats['total_roles'] ?? 0 }}</h4>
              <p class="text-success mb-0">({{ $stats['active_roles'] ?? 0 }} aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-user-check bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


{{-- <p class="mb-6">Lihat, buat, edit atau padam roles.</p> --}}
<!-- Roles Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI ROLES</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-roles table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Role Name</th>
          <th>Users</th>
          <th>Total Users</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>

  </div>
</div>
<!--/ Roles Table -->

<!-- Offcanvas to add new Role -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddRole" aria-labelledby="offcanvasAddRoleLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddRoleLabel" class="offcanvas-title">Add New Role</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="add-new-role pt-0" id="addNewRoleForm" onsubmit="return false">
      @csrf
      <div class="mb-6">
        <label class="form-label" for="add-role-name">Role Name</label>
        <input type="text" class="form-control" id="add-role-name" placeholder="Enter role name" name="modalRoleName" required />
      </div>

      <div class="mb-6">
        <h6>Role Permissions</h6>
        <div class="permissions-container" style="max-height: 400px; overflow-y: auto;">
          @if(isset($permissions) && count($permissions) > 0)
            @foreach($permissions as $module => $modulePermissions)
            <div class="mb-4">
              <h6 class="text-primary mb-2">{{ $module }}</h6>
              @foreach($modulePermissions as $permission)
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="permission_{{ $permission->name }}" name="permissions[]" value="{{ $permission->name }}">
                <label class="form-check-label" for="permission_{{ $permission->name }}">
                  {{ $permission->name }}
                </label>
              </div>
              @endforeach
            </div>
            @endforeach
          @else
            <p class="text-muted">No permissions available</p>
          @endif
        </div>
      </div>

      <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
    </form>
  </div>
</div>

<!-- Edit Role Modal -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditRole" aria-labelledby="offcanvasEditRoleLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditRoleLabel" class="offcanvas-title">Edit Role</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-role-form pt-0" id="editRoleForm" onsubmit="return false">
      @csrf
      @method('PUT')
      <div class="mb-6">
        <label class="form-label" for="edit-role-name">Role Name</label>
        <input type="text" class="form-control" id="edit-role-name" placeholder="Enter role name" name="modalRoleName" required />
      </div>

      <div class="mb-6">
        <h6>Role Permissions</h6>
        <div class="permissions-container" style="max-height: 400px; overflow-y: auto;">
          @if(isset($permissions) && count($permissions) > 0)
            @foreach($permissions as $module => $modulePermissions)
            <div class="mb-4">
              <h6 class="text-primary mb-2">{{ $module }}</h6>
              @foreach($modulePermissions as $permission)
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="edit_permission_{{ $permission->name }}" name="permissions[]" value="{{ $permission->name }}">
                <label class="form-check-label" for="edit_permission_{{ $permission->name }}">
                  {{ $permission->name }}
                </label>
              </div>
              @endforeach
            </div>
            @endforeach
          @else
            <p class="text-muted">No permissions available</p>
          @endif
        </div>
      </div>

      <button type="submit" class="btn btn-primary me-3 data-submit">Update</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
    </form>
  </div>
</div>
@endsection
