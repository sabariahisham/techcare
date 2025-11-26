@extends('layouts/layoutMaster')

@section('title', 'Senarai Pegawai Tugasan')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss',
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
   'resources/assets/js/officers/app-officers-list.js',
  'resources/assets/js/officers/app-officers-add.js',
  'resources/assets/js/officers/app-officers-edit.js',
  'resources/assets/js/officers/app-officers-delete.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalCategories }}</h4>
              <p class="text-success mb-0">({{ $categoryPercentage }}%)</p>
            </div>
            <small class="mb-0">{{ $categoriesWithOfficers }} daripada {{ $totalCategories }} kategori telah diisytihar pegawai tugasan</small>
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
   <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Sub-Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalSubCategories }}</h4>
              <p class="text-success mb-0">({{ $subCategoryPercentage }}%)</p>
            </div>
            <small class="mb-0">{{ $subCategoriesWithOfficers }} daripada {{ $totalSubCategories }} sub-kategori telah diisytihar pegawai tugasan</small>
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
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah SLA</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalSlas }}</h4>
              {{-- <p class="text-danger mb-0">()</p> --}}
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-list-ul bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Pegawai Tugasan</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalOfficers }}</h4>
              <span class="badge bg-label-success">Aktif</span>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-user bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
<div class="card">
  <div class="card-header border-bottom">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bx bx-user me-2"></i>SENARAI PEGAWAI TUGASAN
        <span class="badge bg-label-primary ms-2">{{ $totalOfficers }} Pegawai</span>
      </h5>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-refcategory table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Kategori</th>
          <th>Nama Sub Kategori</th>
          <th>Tahap Tugasan</th>
          <th>Pegawai Tugasan</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Offcanvas to add new officer -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddOfficer" aria-labelledby="offcanvasAddOfficerLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddOfficerLabel" class="offcanvas-title">Tambah Pegawai Tugasan</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-officer pt-0" id="addNewOfficerForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-officer-category">Kategori <span class="text-danger">*</span></label>
          <select class="form-select" id="add-officer-category" name="cat_id" required>
            <option value="">Pilih Kategori</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-officer-subcategory">Sub Kategori <span class="text-danger">*</span></label>
          <select class="form-select" id="add-officer-subcategory" name="subcat_id" required>
            <option value="">Pilih Sub Kategori</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-officer-level">
            Tahap Tugasan <span class="text-danger">*</span>
            <small class="text-muted">
              <span class="badge bg-label-success me-1">Tahap 1</span>
              <span class="badge bg-label-warning me-1">Tahap 2</span>
              <span class="badge bg-label-danger">Tahap 3</span>
            </small>
          </label>
          <select class="form-select" id="add-officer-level" name="level_tugasan" required>
            <option value="">Pilih Tahap Tugasan</option>
            <option value="1">Tahap 1 - Rendah</option>
            <option value="2">Tahap 2 - Sederhana</option>
            <option value="3">Tahap 3 - Tinggi</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-officer-user">Pegawai <span class="text-danger">*</span></label>
          <select class="form-select" id="add-officer-user" name="user_id[]" multiple required>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->nama }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

<!-- Offcanvas to edit officer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditOfficer" aria-labelledby="offcanvasEditOfficerLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditOfficerLabel" class="offcanvas-title">Kemaskini Pegawai Tugasan</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-officer pt-0" id="editOfficerForm" onsubmit="return false">
      @csrf
      @method('PUT')
      <input type="hidden" id="edit-officer-id" name="officer_id" />
      <div class="mb-6">
        <label class="form-label" for="edit-officer-category">Kategori <span class="text-danger">*</span></label>
        <select class="form-select" id="edit-officer-category" name="cat_id" required>
          <option value="">Pilih Kategori</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-officer-subcategory">Sub Kategori <span class="text-danger">*</span></label>
        <select class="form-select" id="edit-officer-subcategory" name="subcat_id" required>
          <option value="">Pilih Sub Kategori</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-officer-level">
          Tahap Tugasan <span class="text-danger">*</span>
          <small class="text-muted">
            <span class="badge bg-label-success me-1">Tahap 1</span>
            <span class="badge bg-label-warning me-1">Tahap 2</span>
            <span class="badge bg-label-danger">Tahap 3</span>
          </small>
        </label>
        <select class="form-select" id="edit-officer-level" name="level_tugasan" required>
          <option value="">Pilih Tahap Tugasan</option>
          <option value="1">Tahap 1 - Rendah</option>
          <option value="2">Tahap 2 - Sederhana</option>
          <option value="3">Tahap 3 - Tinggi</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-officer-user">Pegawai <span class="text-danger">*</span></label>
        <select class="form-select" id="edit-officer-user" name="user_id[]" multiple required>
          @foreach($users as $user)
            <option value="{{ $user->id }}">{{ $user->nama }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

@endsection
