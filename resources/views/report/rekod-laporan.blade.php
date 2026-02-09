@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Laporan Aduan Sistem Helpdesk ICT')

{{-- Vendor Style --}}
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

{{-- Vendor Script --}}
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

{{-- Page Script --}}
@section('page-script')
@vite([
  'resources/assets/js/report/app-rekod-laporan.js',
])
@endsection

{{-- Executive Report Style --}}
@section('page-style')
<style>
/* ===== Executive Report Theme ===== */
.report-title {
    color: #1f3a5f;
    letter-spacing: 0.5px;
}

.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #1f3a5f;
    border-bottom: 3px solid #1f3a5f;
    background-color: transparent;
}

.table thead th {
    background-color: #1f3a5f;
    color: #ffffff;
    font-size: 0.75rem;
    text-transform: uppercase;
    vertical-align: middle;
    font-weight: 600;
}

#table-ringkasan thead th,
#table-ringkasan tbody td {
    text-align: center;
}

#table-ringkasan thead th:first-child,
#table-ringkasan tbody td:first-child {
    text-align: left;
}

.status-selesai {
    color: #043012;
    font-weight: 600;
}

.text-muted-small {
    font-size: 0.75rem;
    color: #6c757d;
}

.btn-export {
    background-color: #6c757d;
    color: #fff;
    border: none;
}

.btn-export:hover {
    background-color: #1f3a5f;
}

.table tbody tr:nth-child(odd) {
    background-color: #f9fafb;
}
</style>
@endsection

@section('content')
<div class="card">

    {{-- HEADER --}}
    <div class="card-header bg-white">
        <h5 class="fw-semibold text-uppercase mb-3 report-title">
            Senarai Rekod Laporan Aduan Sistem TechCare 
        </h5>

        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" id="filter-from" class="form-control">
            </div>
            <div class="col-md-3">
                <input type="date" id="filter-to" class="form-control">
            </div>
            <div class="col-md-3">
                <select id="filter-category" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->cat_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-end" id="export-btn">
                {{-- Button export di-render oleh JS --}}
            </div>
        </div>
    </div>

    {{-- BODY --}}
    <div class="card-body">

        {{-- TABS --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ringkasan">
                    Ringkasan
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-perincian">
                    Perincian
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- RINGKASAN --}}
            <div class="tab-pane fade show active" id="tab-ringkasan">

                <div class="mb-3">
                    <span class="fw-semibold">Jumlah Aduan Direkodkan:</span>
                    <span class="fs-5" id="kpi-jumlah">0</span>
                </div>

                <table class="table table-bordered" id="table-ringkasan">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Aduan Selesai</th>
                            <th>Jumlah Aduan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="text-muted-small mt-2">
                    Nota: Statistik berdasarkan aduan yang direkodkan dalam sistem sehingga tarikh laporan.
                </div>

            </div>

            {{-- PERINCIAN --}}
            <div class="tab-pane fade" id="tab-perincian">
                <table class="table table-striped align-middle" id="table-perincian">
                    <thead>
                        <tr>
                            <th>Bil</th>
                            <th>Nama Pengadu</th>
                            <th>No.Log</th>
                            <th>Aduan Diterima</th>
                            <th>Kategori</th>
                            <th>Bahagian</th>
                            <th>Status & Tempoh</th>
                            <th>Tarikh Selesai</th>
                            <th>Pegawai Tugasan</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
