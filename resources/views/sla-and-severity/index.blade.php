@extends('layouts/layoutMaster')

@section('title', 'Senarai SLA & Severity')

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
   <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Sub-Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalSubCategories }}</h4>
              {{-- <p class="text-success mb-0">(100%)</p> --}}
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
    <h5 class="card-title mb-0">SENARAI SLA & SEVERITY</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-sla table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          {{-- <th>Kategori</th> --}}
          <th>Sub Kategori</th>
          <th>Level Petugas</th>
          <th>Keutamaan</th>
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
          <label class="form-label" for="add-sla-level">Tahap SLA(1-3)</label>
          <select class="form-select" id="add-sla-level" name="sla_level" required>
            <option value="">Pilih Tahap SLA</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-sla-priority">Keutamaan</label>
          <select class="form-select" id="add-sla-priority" name="sla_priority" required>
            <option value="">Pilih Keutamaan</option>
            <option value="High">High</option>
            <option value="Medium">Medium</option>
            <option value="Low">Low</option>
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
        <label class="form-label" for="edit_sla_level">Level Petugas</label>
        <select class="form-select" id="edit_sla_level" name="sla_level" required>
          <option value="">Pilih Level Petugas</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit_sla_priority">Keutamaan</label>
        <select class="form-select" id="edit_sla_priority" name="sla_priority" required>
          <option value="">Pilih Keutamaan</option>
          <option value="High">High</option>
          <option value="Medium">Medium</option>
          <option value="Low">Low</option>
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
