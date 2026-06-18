@extends('layouts/layoutMaster')

@section('title', 'Log Email')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/config.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            <h4 class="mb-1">LOG EMAIL</h4>
            <p class="mb-6">Papar rekod log email sistem.</p>

            {{-- Date Filter --}}
            <div class="row">
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Pilih Tarikh:</label>
                    <input type="date" class="form-control" id="dateFilter" value="{{ $selectedDate ?? '' }}"
                        onchange="filterByDate()">
                    <small class="text-muted">Sila pilih tarikh untuk melihat log</small>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Pilih Status:</label>
                    <select class="form-select" id="statusFilter" onchange="filterByStatus()">
                        <option value="" {{ $selectedStatus == '' ? 'selected' : '' }}>Semua Status</option>
                        <option value="SENT" {{ $selectedStatus == 'SENT' ? 'selected' : '' }}>SENT</option>
                        <option value="FAILED" {{ $selectedStatus == 'FAILED' ? 'selected' : '' }}>FAILED</option>
                    </select>
                    <small class="text-muted">Tapis mengikut status email</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bx bx-refresh"></i> Reset Filter
                    </button>
                    <small class="text-muted">&nbsp;</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="badge bg-primary p-2 text-center">
                            <i class="bx bx-list-ul"></i> Jumlah: {{ $statistics['total'] }}
                        </div>
                        {{-- @foreach ($statistics['by_type'] as $type => $count)
                            <div class="badge bg-{{ $type === 'REMINDER' ? 'warning' : 'danger' }} p-2 text-center">
                                {{ $type }}: {{ $count }}
                            </div>
                        @endforeach --}}
                        @foreach ($statistics['by_status'] as $status => $count)
                            <div
                                class="badge bg-{{ $status === 'SENT' ? 'success' : ($status === 'FAILED' ? 'danger' : 'secondary') }} p-2 text-center">
                                {{ $status }}: {{ $count }}
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">&nbsp;</small>
                </div>
            </div>
        </div>
    </div>


    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0">Senarai Log Email</h5>
                    <p class="mb-0 text-muted">Rekod log email sistem</p>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="mailLogsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tarikh & Masa</th>
                                <th>No. Tiket</th>
                                <th>Jenis Email</th>
                                <th>Status Email</th>
                                <th>Maklumat Tiket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td><small>{{ $log['date'] }}, {{ $log['time'] }}</small></td>
                                    <td>
                                        <span class="fw-semibold">{{ $log['ticket'] }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $emailType = strtoupper($log['type']);
                                            $badgeColor = match ($emailType) {
                                                'TICKET_CLOSED' => 'success',
                                                'TICKET_ASSIGNED' => 'warning',
                                                'TICKET_CREATED' => 'primary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badgeColor }}">
                                            {{ $emailType }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($log['status'] === 'SENT')
                                            <span class="badge bg-success">SENT</span>
                                        @elseif ($log['status'] === 'FAILED')
                                            <span class="badge bg-danger">FAILED</span>
                                        @elseif ($log['status'] === 'SENDING')
                                            <span class="badge bg-secondary">SENDING</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $log['status'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>Pegawai:</strong> {{ $log['recipient_name'] ?? 'N/A' }}<br>
                                        <small><strong>Email:</strong> {{ $log['email'] ?? 'N/A' }}</small><br>
                                        @php
                                            $statusName = $log['ticket_status'] ?? 'N/A';
                                            $badgeClass = match ($statusName) {
                                                'Aduan Baru' => 'bg-primary',
                                                'Aduan Diproses' => 'bg-warning',
                                                'Serahan Kepada' => 'bg-info',
                                                'Aduan Dirujuk' => 'bg-danger',
                                                'Aduan DiBatal' => 'bg-secondary',
                                                'Aduan Selesai' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <small><strong>Status:</strong> <span
                                                class="badge {{ $badgeClass }}">{{ $statusName }}</span></small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bx bx-calendar bx-lg"></i><br>
                                        <strong>{{ $selectedDate ? 'Tiada rekod log untuk tarikh ini.' : 'Sila pilih tarikh untuk melihat log email.' }}</strong>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- <table class="table table-hover" id="logsTable" style="width:100%">
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
                    </table> --}}
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterByDate() {
            const selectedDate = document.getElementById('dateFilter').value;
            if (!selectedDate) {
                alert('Sila pilih tarikh terlebih dahulu.');
                return;
            }
            const selectedStatus = document.getElementById('statusFilter').value;
            let url = "{{ route('log-mail-index') }}?date=" + selectedDate;
            if (selectedStatus) {
                url += "&status=" + selectedStatus;
            }
            window.location.href = url;
        }

        function filterByStatus() {
            const selectedDate = document.getElementById('dateFilter').value;
            if (!selectedDate) {
                alert('Sila pilih tarikh terlebih dahulu.');
                return;
            }
            const selectedStatus = document.getElementById('statusFilter').value;
            let url = "{{ route('log-mail-index') }}?date=" + selectedDate;
            if (selectedStatus) {
                url += "&status=" + selectedStatus;
            }
            window.location.href = url;
        }

        function resetFilters() {
            // Reset to empty date and all statuses
            window.location.href = "{{ route('log-mail-index') }}";
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if (!empty($logs) && count($logs) > 0)
                if (typeof jQuery !== 'undefined') {
                    jQuery('#mailLogsTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100, -1],
                            [10, 25, 50, 100, "Semua"]
                        ],
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                        order: [
                            [0, 'desc']
                        ], // Sort by time descending
                        language: {
                            search: "Cari:",
                            lengthMenu: "Papar _MENU_ rekod",
                            info: "Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod",
                            infoEmpty: "Tiada rekod",
                            infoFiltered: "(ditapis daripada _MAX_ jumlah rekod)",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Seterusnya",
                                previous: "Sebelumnya"
                            },
                            zeroRecords: "Tiada rekod dijumpai"
                        }
                    });
                }
            @endif
        });
    </script>
    @include('audit.logs.partials.log-details-modal')

@endsection
