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
  'resources/assets/js/modules/app-modules-list.js',
  'resources/assets/js/modules/app-modules-add.js',
  'resources/assets/js/modules/app-modules-edit.js',
  'resources/assets/js/modules/app-modules-delete.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])

<style>
.form-control.is-invalid {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.form-control.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
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
          <th>Nama Modul</th>
          <th>Tarikh Dicipta</th>
           <th>Tarikh Kemaskini</th>
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
    <h5 id="offcanvasAddModuleLabel" class="offcanvas-title">Tambah Modul Baharu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="add-new-module pt-0" id="addNewModuleForm" onsubmit="return false">
      @csrf
      <div class="mb-6">
        <label class="form-label" for="add-module-name">Nama Modul <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="add-module-name" placeholder="Masukkan nama modul" name="modalModuleName" required />
        <small class="form-text text-muted">Nama modul akan digunakan untuk menjana permission secara automatik</small>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
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
      <input type="hidden" id="edit-module-id" name="module_id" />
      <div class="mb-6">
        <label class="form-label" for="edit-module-name">Nama Modul <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit-module-name" placeholder="Masukkan nama modul" name="editModuleName" required />
        <small class="form-text text-muted">Nama modul akan digunakan untuk menjana permission secara automatik</small>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div></div>


@endsection
