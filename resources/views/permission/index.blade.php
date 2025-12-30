@extends('layouts/layoutMaster')

@section('title', 'Senarai Modul & Capaian')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
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
  'resources/assets/js/permissions/app-permission-list.js',
  'resources/assets/js/permissions/app-permission-add.js',
  'resources/assets/js/permissions/app-permission-edit.js',
  'resources/assets/js/permissions/app-permission-delete.js',
  'resources/assets/js/modules/app-modules-add.js',
  'resources/assets/js/modules/app-modules-edit.js',
  'resources/assets/js/modules/app-modules-delete.js',
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
              <p class="text-success mb-0">({{ $stats['active_modules'] ?? 0 }} aktif)</p>
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
            <span class="text-heading">Jumlah Capaian</span>
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
    <h5 class="card-title mb-0">SENARAI MODUL & CAPAIAN</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-permissions table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Capaian</th>
          <th>Nama Module</th>
          <th>Deskripsi</th>
          <th>Ditugaskan Kepada</th>
          {{-- <th>Created Date</th> --}}
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Permission Table -->

<!-- Offcanvas to add new module -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddModule" aria-labelledby="offcanvasAddModuleLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddModuleLabel" class="offcanvas-title">Tambah Modul</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="add-new-module pt-0" id="addNewModuleForm" onsubmit="return false">
      @csrf
      <div class="mb-6">
        <label class="form-label" for="add-module-name">Nama Modul</label>
        <input type="text" class="form-control" id="add-module-name" placeholder="Nama Modul" name="name" required />
      </div>
      <div class="mb-6">
        <label class="form-label" for="add-module-status">Status</label>
        <select id="add-module-status" name="status" class="form-select" required>
          <option value="">-- Sila pilih status --</option>
          <option value="1" selected>Aktif</option>
          <option value="0">Tidak Aktif</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success me-3">Simpan</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

<!-- Offcanvas to add new permission -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPermission" aria-labelledby="offcanvasAddPermissionLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddPermissionLabel" class="offcanvas-title">Tambah Capaian</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-permission pt-0" id="addNewPermissionForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-permission-name">Nama Capaian</label>
          <input type="text" class="form-control" id="add-permission-name" placeholder="Nama Permission" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-permission-module">Pilih Modul</label>
          <select id="add-permission-module" name="module" class="form-select" required>
            <option value="">-- Sila pilih modul --</option>
            @if(isset($modules) && count($modules))
              @foreach($modules as $module)
                @if(is_object($module))
                  <option value="{{ $module->id }}">{{ $module->name }}</option>
                @else
                  <option value="{{ $module }}">{{ $module }}</option>
                @endif
              @endforeach
            @endif
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-permission-description">Deskripsi Capaian</label>
          <input type="text" class="form-control" id="add-permission-description" placeholder="Deskripsi Capaian" name="description" required />
        </div>
        <button type="submit" class="btn btn-primary me-3">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

  <!-- Offcanvas to edit permission -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditPermission" aria-labelledby="offcanvasEditPermissionLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasEditPermissionLabel" class="offcanvas-title">Edit Capaian</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-permission pt-0" id="editPermissionForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="edit-permission-name">Nama Capaian</label>
          <input type="text" class="form-control" id="edit-permission-name" placeholder="Nama Capaian" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-permission-module">Pilih Modul</label>
          <select id="edit-permission-module" name="module" class="form-select" required>
            <option value="">-- Sila pilih modul --</option>
            @if(isset($modules) && count($modules))
              @foreach($modules as $module)
                @if(is_object($module))
                  <option value="{{ $module->id }}">{{ $module->name }}</option>
                @else
                  <option value="{{ $module }}">{{ $module }}</option>
                @endif
              @endforeach
            @endif
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-permission-description">Deskripsi Capaian</label>
          <input type="text" class="form-control" id="edit-permission-description" placeholder="Deskripsi Capaian" name="description" required />
        </div>
        <button type="submit" class="btn btn-primary me-3">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

  <!-- Offcanvas to edit module -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditModule" aria-labelledby="offcanvasEditModuleLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasEditModuleLabel" class="offcanvas-title">Edit Modul</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="edit-module pt-0" id="editModuleForm" onsubmit="return false">
        @csrf
        <input type="hidden" id="edit-module-id" name="id" />
        <div class="mb-6">
          <label class="form-label" for="edit-module-name">Nama Modul</label>
          <input type="text" class="form-control" id="edit-module-name" placeholder="Nama Modul" name="name" required />
        </div>
        <button type="submit" class="btn btn-success me-3">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

@endsection
