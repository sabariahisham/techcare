@extends('layouts/layoutMaster')

@section('title', 'Senarai Kategori')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
  'resources/assets/vendor/libs/toastr/toastr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-ref-category-list.js',
  'resources/assets/js/app-ref-category-add.js',
  'resources/assets/js/app-ref-category-edit.js',
  'resources/assets/js/app-ref-category-delete.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalCategories }}</h4>
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

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI KATEGORI</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-refcategory table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Kategori</th>
          <th>Deskripsi</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Offcanvas to add new category -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddCategory" aria-labelledby="offcanvasAddCategoryLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddCategoryLabel" class="offcanvas-title">Tambah Kategori</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-category pt-0" id="addNewCategoryForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-category-name">Nama Kategori</label>
          <input type="text" class="form-control" id="add-category-name" placeholder="Nama Kategori" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-category-description">Deskripsi</label>
          <input type="text" id="add-category-description" class="form-control" placeholder="Deskripsi Kategori" name="description" required />
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

   <!-- Offcanvas to edit category -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditCategory" aria-labelledby="offcanvasEditCategoryLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasEditCategoryLabel" class="offcanvas-title">Edit Kategori</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="edit-category pt-0" id="editCategoryForm" onsubmit="return false">
        @csrf
        <input type="hidden" id="edit-category-id" name="id">
        <div class="mb-6">
          <label class="form-label" for="edit-category-name">Nama Kategori</label>
          <input type="text" class="form-control" id="edit-category-name" placeholder="Nama Kategori" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-category-description">Deskripsi</label>
          <input type="text" id="edit-category-description" class="form-control" placeholder="Deskripsi Kategori" name="description" required />
        </div>

        <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

@endsection
