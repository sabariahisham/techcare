@extends('layouts/layoutMaster')

@section('title', 'Log Keseluruhan')

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

@section('page-style')
     <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/config.js', 
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
<script>
    window.logRoutes = {
        baseUrl: "{{ url('audit/logs') }}"
    };
</script>
@vite(['resources/assets/js/audit/app-audit-log.js'])
@endsection



@section('content')

<div class="card mb-4">
    <div class="card-body">
    <h4 class="mb-1">LOG KESELURUHAN</h4>
    <p class="mb-6">Papar rekod log keseluruhan sistem.</p>
    </div>
 </div>


    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                 <h5 class="card-title mb-0">Senarai Log Keseluruhan</h5>
                 <p class="mb-0 text-muted">Rekod log keseluruhan sistem</p>  
                 </div>
                 </div>
            <div class="card-body"> 
                <div class="table-responsive">
                    <table class="table table-hover" id="logsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tarikh</th>
                                <th>Nama Fail</th>
                                <th>Saiz</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logFiles as $log)
                                <tr>
                                    <td>{{ $log['date'] }}</td>
                                    <td>{{ $log['filename'] }}</td>
                                    @php
                                        $size = $log['size'];
                                        if ($size >= 1048576) {
                                            $sizeText = number_format($size / 1048576, 2) . ' MB';
                                        } else {
                                            $sizeText = number_format($size / 1024, 2) . ' KB';
                                        }
                                    @endphp
                                    <td>{{ $sizeText }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="showLogDetails('{{ $log['date'] }}', '{{ $log['filename'] }}')">Lihat</button>
                                        <a href="{{ route('audit.logs.download', $log['date']) }}" class="btn btn-success btn-sm">Download</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>  
            </div>
        </div>
    </div>
    @include('audit.logs.partials.log-details-modal')
    
@endsection