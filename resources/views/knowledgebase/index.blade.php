@extends('layouts/layoutMaster')

@section('title', 'Knowledge Base')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/css/knowledge-base-table.css',
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
<script>
  // Pass user role information to JavaScript
  window.userHasRole = {{ $hasRole ? 'true' : 'false' }};
</script>
@vite([
  'resources/assets/js/knowledgebase/app-knowledge-base-list.js',
  'resources/assets/js/knowledgebase/app-knowledgebase-modal-add.js',
  'resources/assets/js/knowledgebase/app-knowledgebase-modal-edit.js',
  'resources/assets/js/knowledgebase/app-knowledgebase-modal-show.js',
  'resources/assets/js/knowledgebase/app-knowledgebase-delete.js',
  'resources/assets/js/knowledgebase/app-knowledgebase-aduan-selesai.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Artikel</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\KnowledgeBase::count() }}</h4>
            </div>
            <small class="mb-0">Artikel</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="bx bx-book bx-lg"></i>
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
            <span class="text-heading">Artikel Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\KnowledgeBase::where('kb_status', 'aktif')->count() }}</h4>
            </div>
            <small class="mb-0">Artikel Diterbitkan</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-check-circle bx-lg"></i>
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
            <span class="text-heading">Kategori</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\RefCategory::count() }}</h4>
            </div>
            <small class="mb-0">Kategori Tersedia</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="bx bx-category bx-lg"></i>
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
            <span class="text-heading">Artikel Tidak Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\KnowledgeBase::where('kb_status', 'tidak aktif')->count() }}</h4>
            </div>
            <small class="mb-0">Artikel Draf</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-time bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Senarai Aduan Selesai Table -->
<div class="card mb-6">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI ADUAN SELESAI</h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-aduan-selesai table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>No. Log Tiket</th>
          <th>Tarikh Aduan</th>
          <th>Nama Pengadu</th>
          <th>Kategori</th>
          <th>Sub Kategori</th>
          <th>Status</th>
          <th>Pegawai Tugasan</th>
          <th>Tajuk Aduan</th>
          <th>Keterangan Aduan</th>
          <th>Catatan Tindakan</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Knowledge Base List Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">KNOWLEDGE BASE</h5>

  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-knowledgebase table align-top">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Tajuk</th>
          <th>Kategori</th>
          <th>Sub Kategori</th>
          <th>Deskripsi</th>
          <th>Tag</th>
          <th>Status</th>
          <th>Ditulis oleh</th>
          <th>Tarikh Ditulis</th>
          <th>Kemaskini oleh</th>
          <th>Tarikh Kemaskini</th>
          <th>Peranan</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Include Knowledge Base Add Modal -->
@include('knowledgebase.partials.add-modal')

<!-- Include Knowledge Base Add from Aduan Selesai Modal -->
@include('knowledgebase.partials.add-from-aduan-modal')

<!-- Include Knowledge Base Edit Modal -->
@include('knowledgebase.partials.edit-modal')

<!-- Include Knowledge Base Show Modal -->
@include('knowledgebase.partials.show-modal')

@endsection
