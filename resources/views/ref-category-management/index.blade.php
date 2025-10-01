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
  'resources/assets/js/app-ref-category-list.js'
])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI KATEGORI</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-refcategory table border-top">
      <thead>
        <tr>
          <th></th>
          <th>No.</th>
          <th>ID</th>
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
      <h5 id="offcanvasAddCategoryLabel" class="offcanvas-title">Add Category</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-category pt-0" id="addNewCategoryForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-category-name">Category Name</label>
          <input type="text" class="form-control" id="add-category-name" placeholder="Category Name" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-category-description">Description</label>
          <input type="text" id="add-category-description" class="form-control" placeholder="Category Description" name="description" required />
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </form>
    </div>
  </div>

@endsection
