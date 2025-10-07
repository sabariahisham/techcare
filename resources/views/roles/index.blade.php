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
  'resources/assets/js/app-roles-list.js',
  'resources/assets/js/app-roles-add.js',
  'resources/assets/js/app-roles-edit.js',
  'resources/assets/js/app-roles-delete.js',
  'resources/assets/js/role-statistics.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])
@endsection

@section('content')

{{-- <div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Roles</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="total-roles">{{ $stats['total_roles'] ?? 0 }}</h4>
              <p class="text-success mb-0" id="active-roles">({{ $stats['active_roles'] ?? 0 }} aktif)</p>
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
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pentadbir Sistem</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="super-admin-count">0</h4>
              <p class="text-success mb-0" id="super-admin-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-crown bx-lg"></i>
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
            <span class="text-heading">Administrator</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="administrator-count">0</h4>
              <p class="text-info mb-0" id="administrator-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="bx bx-user-badge bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div> --}}
{{-- <div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Penyelia Helpdesk</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="penyelia-helpdesk-count">0</h4>
              <p class="text-warning mb-0" id="penyelia-helpdesk-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
               <i class="bx bx-user-voice bx-lg"></i>
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
            <span class="text-heading">Helpdesk On Duty</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="helpdesk-on-duty-count">0</h4>
              <p class="text-success mb-0" id="helpdesk-on-duty-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-support bx-lg"></i>
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
            <span class="text-heading">Pegawai Tugasan</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="pegawai-tugasan-count">0</h4>
              <p class="text-secondary mb-0" id="pegawai-tugasan-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-secondary">
              <i class="bx bx-user-pin bx-lg"></i>
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
            <span class="text-heading">Pengurusan</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="pengurusan-count">0</h4>
              <p class="text-dark mb-0" id="pengurusan-active">(0 aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-dark">
              <i class="bx bx-group bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div> --}}


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

<!-- Modal to add new Role -->
<div class="modal fade" id="modalAddRole" tabindex="-1" aria-labelledby="modalAddRoleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddRoleLabel">Add New Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="add-new-role pt-0" id="addNewRoleForm" onsubmit="return false">
          @csrf
          <div class="mb-4">
            <label class="form-label" for="add-role-name">Role Name</label>
            <input type="text" class="form-control" id="add-role-name" placeholder="Enter role name" name="name" required />
          </div>

          <div class="mb-4">
            <label class="form-label" for="add-role-description">Description</label>
            <textarea class="form-control" id="add-role-description" name="description" rows="3" placeholder="Enter role description"></textarea>
          </div>

          <div class="mb-4">
            <h6>Role Permissions</h6>
            <!-- Select All Permissions -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="selectAllPermissions">
              <label class="form-check-label fw-bold text-primary" for="selectAllPermissions">
                Select All Permissions
              </label>
            </div>
            <hr class="mb-3">

            <div class="permissions-container" style="max-height: 300px; overflow-y: auto;">
              @if(isset($permissions) && count($permissions) > 0)
                @foreach($permissions as $module => $modulePermissions)
                <div class="mb-3">
                  <h6 class="text-primary mb-2">{{ $module }}</h6>
                  @foreach($modulePermissions as $permission)
                  <div class="form-check mb-2">
                    <input class="form-check-input add-permission" type="checkbox" id="permission_{{ str_replace([' ', '.', '-'], '_', $permission->name) }}" name="permissions[]" value="{{ $permission->name }}">
                    <label class="form-check-label" for="permission_{{ str_replace([' ', '.', '-'], '_', $permission->name) }}">
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

          <div class="mb-4">
            <h6>Assign Users to Role</h6>
            <div class="mb-3">
              <label class="form-label" for="add-role-users">Select Users</label>
              <select class="form-select" id="add-role-users" name="users[]" multiple>
              </select>
              <div class="form-text">Search and select multiple users to assign to this role</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="addNewRoleForm">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal to edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-labelledby="modalEditRoleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditRoleLabel">Edit Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editRoleForm" onsubmit="return false">
          @csrf
          @method('PUT')

          <div class="mb-4">
            <label class="form-label" for="edit-role-name">Role Name</label>
            <input type="text" class="form-control" id="edit-role-name" placeholder="Enter role name" name="name" required />
          </div>

          <div class="mb-4">
            <label class="form-label" for="edit-role-description">Description</label>
            <textarea class="form-control" id="edit-role-description" name="description" rows="3" placeholder="Enter role description"></textarea>
          </div>

          <div class="mb-4">
            <h6>Role Permissions</h6>
            <!-- Select All Permissions -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="editSelectAllPermissions">
              <label class="form-check-label fw-bold text-primary" for="editSelectAllPermissions">
                Select All Permissions
              </label>
            </div>
            <hr class="mb-3">

            <div class="permissions-container" style="max-height: 300px; overflow-y: auto;">
              @if(isset($permissions) && count($permissions) > 0)
                @foreach($permissions as $module => $modulePermissions)
                <div class="mb-3">
                  <h6 class="text-primary mb-2">{{ $module }}</h6>
                  @foreach($modulePermissions as $permission)
                  <div class="form-check mb-2">
                    <input class="form-check-input edit-permission" type="checkbox" id="edit_permission_{{ str_replace([' ', '.', '-'], '_', $permission->name) }}" name="permissions[]" value="{{ $permission->name }}">
                    <label class="form-check-label" for="edit_permission_{{ str_replace([' ', '.', '-'], '_', $permission->name) }}">
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

          <div class="mb-4">
            <h6>Current Users in Role</h6>
            <div id="editRoleUsers" class="mb-3">
              <!-- Current users will be loaded here -->
            </div>
          </div>

          <div class="mb-4">
            <h6>Add Users to Role</h6>
            <div class="mb-3">
              <label class="form-label" for="edit-role-users">Select Users</label>
              <select class="form-select" id="edit-role-users" multiple>
                <option value="">Search for users to add...</option>
              </select>
              <div class="form-text">Selected users will be added to the role when you click "Update Role"</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="editRoleForm">
          <i class="bx bx-save me-1"></i>Update Role
        </button>
      </div>
    </div>
  </div>
</div>
@endsection
