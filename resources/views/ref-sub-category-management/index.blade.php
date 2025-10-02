@extends('layouts/layoutMaster')

@section('title', 'Senarai Sub-Kategori')

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
  'resources/assets/js/app-ref-sub-category-list.js',
  'resources/assets/js/app-ref-sub-category-add.js'
])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI SUB-KATEGORI</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-refcategory table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          {{-- <th>ID</th> --}}
          <th>Kategori</th>
          <th>Sub-Kategori</th>
          <th>Deskripsi</th>
          <th>Created_at</th>
          <th>Updated_at</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Offcanvas to add new sub category -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddSubCategory" aria-labelledby="offcanvasAddSubCategoryLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddSubCategoryLabel" class="offcanvas-title">Tambah Sub-Kategori</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="add-new-subcategory pt-0" id="addNewSubCategoryForm" onsubmit="return false">
      @csrf
      <div class="mb-6">
        <label class="form-label" for="add-cat-id">Kategori</label>
        <select class="form-select" id="add-cat-id" name="cat_id" required>
          <option value="">Pilih Kategori</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="add-subcat-name">Nama Sub-Kategori</label>
        <input type="text" class="form-control" id="add-subcat-name" placeholder="Nama Sub-Kategori" name="subcat_name" required />
      </div>
      <div class="mb-6">
        <label class="form-label" for="add-subcat-desc">Deskripsi</label>
        <textarea id="add-subcat-desc" class="form-control" placeholder="Deskripsi Sub-Kategori" name="subcat_desc" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

<!-- Offcanvas to edit sub category -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditSubCategory" aria-labelledby="offcanvasEditSubCategoryLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditSubCategoryLabel" class="offcanvas-title">Edit Sub-Kategori</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-subcategory pt-0" id="editSubCategoryForm" onsubmit="return false">
      @csrf
      <input type="hidden" id="edit_subcategory_id" name="id">
      <div class="mb-6">
        <label class="form-label" for="edit_cat_id">Kategori</label>
        <select class="form-select" id="edit_cat_id" name="cat_id" required>
          <option value="">Pilih Kategori</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_subcat_name">Nama Sub-Kategori</label>
        <input type="text" class="form-control" id="edit_subcat_name" placeholder="Nama Sub-Kategori" name="subcat_name" required />
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_subcat_desc">Deskripsi</label>
        <textarea id="edit_subcat_desc" class="form-control" placeholder="Deskripsi Sub-Kategori" name="subcat_desc" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

@endsection
