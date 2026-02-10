@extends('layouts/layoutMaster')

@section('title', 'Log SLA')

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
            baseUrl: "{{ url('audit/logs-sla') }}"
        };
    </script>
    @vite(['resources/assets/js/audit/app-audit-log.js'])
@endsection



@section('content')

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-1">LOG SLA</h4>
            <p class="mb-6">Papar rekod log SLA sistem.</p>

            {{-- Date Filter --}}
            <div class="row">
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Pilih Tarikh:</label>
                    <input type="date" class="form-control" id="dateFilter" value="{{ $selectedDate ?? '' }}"
                        onchange="filterByDate()">
                    <small class="text-muted">Sila pilih tarikh untuk melihat log</small>
                </div>
                <div class="col-md-3">
                    <label for="categoryFilter" class="form-label">Pilih Kategori:</label>
                    <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                        <option value="" {{ $selectedCategory == '' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="REMINDER" {{ $selectedCategory == 'REMINDER' ? 'selected' : '' }}>REMINDER</option>
                        <option value="OVERDUE" {{ $selectedCategory == 'OVERDUE' ? 'selected' : '' }}>OVERDUE</option>
                    </select>
                    <small class="text-muted">Tapis mengikut kategori SLA</small>
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
                        @if (isset($statistics['by_category']['REMINDER']))
                            <div class="badge bg-warning p-2 text-center">
                                REMINDER: {{ $statistics['by_category']['REMINDER'] }}
                            </div>
                        @endif
                        @if (isset($statistics['by_category']['OVERDUE']))
                            <div class="badge bg-danger p-2 text-center">
                                OVERDUE: {{ $statistics['by_category']['OVERDUE'] }}
                            </div>
                        @endif
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
                    <h5 class="card-title mb-0">Senarai Log SLA</h5>
                    <p class="mb-0 text-muted">Rekod log SLA sistem</p>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="slaLogsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tarikh & Masa</th>
                                <th>No. Tiket</th>
                                <th>Kategori</th>
                                <th>Langkah</th>
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
                                        <span
                                            class="badge bg-{{ strtoupper($log['category']) === 'REMINDER' ? 'warning' : 'danger' }}">
                                            {{ strtoupper($log['category']) }}
                                        </span>
                                    </td>
                                    <td>{{ $log['step'] }}</td>
                                    <td>
                                        @if ($log['status'] === 'SENT')
                                            <span class="badge bg-success">SENT</span>
                                        @elseif ($log['status'] === 'FAILED')
                                            <span class="badge bg-danger">FAILED</span>
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
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bx bx-calendar bx-lg"></i><br>
                                        <strong>{{ $selectedDate ? 'Tiada rekod log untuk tarikh ini.' : 'Sila pilih tarikh untuk melihat log SLA.' }}</strong>
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
                                        <button type="button" class="btn btn-primary" onclick="showLogDetails('{{ $log['date'] }}', '{{ $log['filename'] }}')">Lihat</button>
                                        <a href="{{ route('audit.logs.download', $log['date']) }}" class="btn btn-success">Download</a>
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
            const selectedCategory = document.getElementById('categoryFilter').value;
            let url = "{{ route('log-sla-index') }}?date=" + selectedDate;
            if (selectedCategory) {
                url += "&category=" + selectedCategory;
            }
            window.location.href = url;
        }

        function filterByCategory() {
            const selectedDate = document.getElementById('dateFilter').value;
            if (!selectedDate) {
                alert('Sila pilih tarikh terlebih dahulu.');
                return;
            }
            const selectedCategory = document.getElementById('categoryFilter').value;
            let url = "{{ route('log-sla-index') }}?date=" + selectedDate;
            if (selectedCategory) {
                url += "&category=" + selectedCategory;
            }
            window.location.href = url;
        }

        function resetFilters() {
            // Reset to empty date and all categories
            window.location.href = "{{ route('log-sla-index') }}";
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if (!empty($logs) && count($logs) > 0)
                if (typeof jQuery !== 'undefined') {
                    jQuery('#slaLogsTable').DataTable({
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
