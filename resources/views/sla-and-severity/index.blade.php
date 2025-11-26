@extends('layouts/layoutMaster')

@section('title', 'Senarai Tahap Pegawai Tugasan &  Tahap Keterukan')

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
'resources/assets/js/sla/app-sla-and-severity-list.js',
'resources/assets/js/sla/app-sla-and-severity-add.js',
'resources/assets/js/sla/app-sla-and-severity-edit.js',
'resources/assets/js/sla/app-sla-and-severity-delete.js',

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
              <p class="text-success mb-0">({{ $categoryPercentage }}%)</p>
            </div>
            <small class="mb-0">{{ $categoriesWithSla }} daripada {{ $totalCategories }} kategori telah diisytihar SLA</small>
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
            <span class="text-heading">Jumlah Sub-Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalSubCategories }}</h4>
              <p class="text-success mb-0">({{ $subCategoryPercentage }}%)</p>
            </div>
            <small class="mb-0">{{ $subCategoriesWithSla }} daripada {{ $totalSubCategories }} sub-kategori telah diisytihar SLA</small>
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
  <div class="col-sm-6 col-xl-4">
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
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI TAHAP PEGAWAI TUGASAN & SEVERITY</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-sla table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          {{-- <th>Kategori</th> --}}
          <th>Sub Kategori</th>
          <th>Tahap Pegawai Tugasan</th>
          <th>Severity</th>
          <th>Hari</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Offcanvas to add new SLA -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddSla" aria-labelledby="offcanvasAddSlaLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddSlaLabel" class="offcanvas-title">Tambah SLA</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-sla pt-0" id="addNewSlaForm" onsubmit="return false">
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
          <label class="form-label" for="add-subcat-id">Sub Kategori</label>
          <select class="form-select" id="add-subcat-id" name="subcat_id" required>
            <option value="">Pilih Sub Kategori</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-sla-level">Tahap Pegawai Tugasan</label>
          <select class="form-select" id="add-sla-level" name="sla_level" required>
            <option value="">Pilih Tahap Pegawai Tugasan</option>
            <option value="1">1</option>
            <option value="2">2</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-sla-severity">Severity</label>
          <select class="form-select" id="add-sla-severity" name="sla_severity" required>
            <option value="">Pilih Severity</option>
            <option value="High">HIGH</option>
            <option value="Medium">MEDIUM</option>
            <option value="Low">LOW</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-sla-days">Hari</label>
          <input type="number" id="add-sla-days" class="form-control" placeholder="SLA Days" name="sla_days" required />
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

<!-- Offcanvas to edit SLA -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditSla" aria-labelledby="offcanvasEditSlaLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditSlaLabel" class="offcanvas-title">Edit SLA</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-sla pt-0" id="editSlaForm" onsubmit="return false">
      @csrf
      @method('PUT')
      <input type="hidden" id="edit_sla_id" name="id">
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
        <label class="form-label" for="edit_subcat_id">Sub Kategori</label>
        <select class="form-select" id="edit_subcat_id" name="subcat_id" required>
          <option value="">Pilih Sub Kategori</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_sla_level">Tahap Pegawai Tugasan</label>
        <select class="form-select" id="edit_sla_level" name="sla_level" required>
          <option value="">Pilih Tahap Pegawai Tugasan</option>
          <option value="1">1</option>
          <option value="2">2</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_sla_severity">Severity</label>
        <select class="form-select" id="edit_sla_severity" name="sla_severity" required>
          <option value="">Pilih Severity</option>
          <option value="High">HIGH</option>
          <option value="Medium">MEDIUM</option>
          <option value="Low">LOW</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_sla_days">Hari</label>
        <input type="number" id="edit_sla_days" class="form-control" placeholder="SLA Days" name="sla_days" required />
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

@endsection
