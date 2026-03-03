@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Senarai Peranan')

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
<script>
  window.currentUserRoles = @json(auth()->user()->getRoleNames());
</script>
@vite([
  'resources/assets/js/roles/app-roles-validation.js',
  'resources/assets/js/roles/app-roles-list.js',
  'resources/assets/js/roles/app-roles-add.js',
  'resources/assets/js/roles/app-roles-edit.js',
  'resources/assets/js/roles/app-roles-delete.js',
  'resources/assets/js/roles/role-statistics.js',
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
            <span class="text-heading">Jumlah Peranan</span>
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
            <span class="text-heading">Jumlah Pengguna Dengan Peranan</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="total-users-with-roles">{{ $stats['total_users_with_roles'] ?? 0 }}</h4>
              <p class="text-success mb-0" id="active-users-with-roles">({{ $stats['active_users_with_roles'] ?? 0 }} aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
               <i class="bx bx-user-plus bx-lg"></i>
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
            <span class="text-heading">Jumlah Modul Sistem</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="total-modules">{{ $stats['total_modules'] ?? 0 }}</h4>
              <p class="text-success mb-0" id="active-modules">({{ $stats['active_modules'] ?? 0 }} aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
               <i class="bx bx-grid-alt bx-lg"></i>
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
            <span class="text-heading">Jumlah Capaian (Permissions)</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="total-permissions">{{ $stats['total_permissions'] ?? 0 }}</h4>
              <p class="text-success mb-0" id="active-permissions">({{ $stats['active_permissions'] ?? 0 }} aktif)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
               <i class="bx bx-list-ul bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
<!-- Header -->


{{-- <p class="mb-6">Lihat, buat, edit atau padam roles.</p> --}}
<!-- Roles Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI PERANAN</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-roles table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Peranan</th>
          {{-- <th>Deskripsi</th> --}}
          <th>Nama Pengguna</th>
          <th>Nama Modul</th>
          <th>Jumlah Pengguna</th>
          <th>Tindakan</th>
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
        <h5 class="modal-title" id="modalAddRoleLabel">Tambah Peranan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="add-new-role pt-0" id="addNewRoleForm" onsubmit="return false">
          @csrf
          <div class="mb-4">
            <label class="form-label" for="add-role-name">Nama Peranan</label>
            <input type="text" class="form-control" id="add-role-name" placeholder="Masukkan nama peranan" name="name" required />
          </div>

          {{-- <div class="mb-4">
            <label class="form-label" for="add-role-description">Deskripsi</label>
            <textarea class="form-control" id="add-role-description" name="description" rows="3" placeholder="Masukkan deskripsi peranan"></textarea>
          </div> --}}

          <div class="mb-4">
            <h6>Capaian Peranan</h6>
            <!-- Select All Permissions -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="selectAllPermissions">
              <label class="form-check-label fw-bold text-primary" for="selectAllPermissions">
                Pilih Semua Capaian
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
            <h6>Tambah Pengguna kepada Peranan</h6>
            <div class="mb-3">
              <label class="form-label" for="add-role-users">Pilih Pengguna</label>
              <select class="form-select" id="add-role-users" name="users[]" multiple>
              </select>
              <div class="form-text">Cari dan pilih beberapa pengguna untuk ditugaskan kepada peranan ini</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Batalkan</button>
        <button type="submit" class="btn btn-primary" form="addNewRoleForm">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal to edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-labelledby="modalEditRoleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditRoleLabel">Kemaskini Peranan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editRoleForm" onsubmit="return false">
          @csrf
          @method('PUT')

          <div class="mb-4">
            <label class="form-label" for="edit-role-name">Nama Peranan</label>
            <input type="text" class="form-control" id="edit-role-name" placeholder="Masukkan nama peranan" name="name" required />
          </div>

          {{-- <div class="mb-4">
            <label class="form-label" for="edit-role-description">Deskripsi</label>
            <textarea class="form-control" id="edit-role-description" name="description" rows="3" placeholder="Masukkan deskripsi peranan"></textarea>
          </div> --}}
          <hr class="mb-3">

          <div class="mb-4">
            <h6>Capaian Peranan</h6>
            <!-- Select All Permissions -->
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="editSelectAllPermissions">
              <label class="form-check-label fw-bold text-primary" for="editSelectAllPermissions">
                Pilih semua capaian
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
<hr class="mb-3">
          <div class="mb-4">
            <h6>Pengguna Dalam Peranan</h6>
            <div id="editRoleUsers" class="mb-3">
              <!-- Current users will be loaded here -->
            </div>
          </div>

          <div class="mb-4">
            <h6>Tambah Pengguna ke Peranan</h6>
            <div class="mb-3">
              <label class="form-label" for="edit-role-users">Pilih Pengguna</label>
              <select class="form-select" id="edit-role-users" multiple>
                <option value="">Cari pengguna untuk ditambah...</option>
              </select>
              <div class="form-text">Pengguna yang dipilih akan ditambahkan ke peranan apabila anda mengklik "Kemaskini Peranan"</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" form="editRoleForm">
          <i class="bx bx-save me-1"></i>Kemaskini Peranan
        </button>
      </div>
    </div>
  </div>
</div>
@endsection
