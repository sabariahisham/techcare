@extends('layouts/layoutMaster')

@section('title', 'Senarai Audit Trail')

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
    @vite(['resources/assets/js/config.js', 
    'resources/assets/vendor/libs/jquery/jquery.js', 
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

@section('page-style')
    @vite(['resources/assets/css/audit.css'])
@endsection

@section('page-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>   
</script>
<script>
    // Pass Laravel routes to JavaScript
    window.auditRoutes = {
        data: "{{ route('audit.data') }}",
        detail: "{{ url('audit-trail/detail') }}"
    };
</script>
@vite(['resources/assets/js/audit/app-audit-list.js'])
@endsection


@section('content')
    <h4 class="mb-1">AUDIT TRAIL</h4>
    <p class="mb-6">Papar rekod perubahan data dalam sistem.</p>

    <!-- Filter Section -->
    <div class="card mb-4">
        
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="dateFrom" class="form-label">Tarikh Dari</label>
                    <input type="date" class="form-control" id="dateFrom" name="date_from">
                </div>
                
                <div class="col-md-3">
                    <label for="dateTo" class="form-label">Tarikh Hingga</label>
                    <input type="date" class="form-control" id="dateTo" name="date_to">
                </div>
                
                <div class="col-md-3">
                    <label for="modelFilter" class="form-label">Model</label>
                    <select class="form-select" id="modelFilter" name="model">
                        <option value="">Semua Model</option>
                        <option value="Ticket">Ticket</option>
                        <option value="TicketChronology">Ticket Chronology</option>
                        <option value="KnowledgeBase">Knowledge Base</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="userFilter" class="form-label">Pengguna</label>
                    <select class="form-select" id="userFilter" name="user_id">
                        <option value="">Semua Pengguna</option>
                        @if(isset($users))
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->nama }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="filterBtn">
                        <i class="bx bx-search me-1"></i>Cari
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                        <i class="bx bx-reset me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Trail Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">Senarai Audit Trail</h5>
                <p class="mb-0 text-muted">Rekod perubahan data sistem</p>
            </div>
            {{-- <div>
                <!-- Legend -->
                <span class="model-ticket me-2">Ticket</span>
                <span class="model-chronology me-2">Chronology</span>
                <span class="model-knowledgebase">Knowledge Base</span>
            </div> --}}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="auditTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">BIL</th>
                            <th width="12%">TARIKH</th>
                            <th width="15%">PENGGUNA</th>
                            <th width="10%">AKTIVITI</th>
                            <th width="12%">MODEL</th>
                            <th width="15%">URL</th>
                            <th width="15%">VALUE SEBELUM</th>
                            <th width="15%">VALUE SELEPAS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

   <!-- Include audit details modal  -->
@include('audit.partials.details-modal')
    
@endsection

