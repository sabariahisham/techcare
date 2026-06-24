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
            <p class="mb-6">Papar rekod log email sistem.
                <br><small class="text-muted">Rekod log email sistem merujuk kepada fail Mail Log yang dijana oleh sistem selama 14 hari dari tarikh semasa (path: storage/logs/mail-YYYY-MM-DD.log).</small>
            </p>
           

            {{-- Filters --}}
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="dateFrom" class="form-label">Tarikh Dari:</label>
                    <input type="date" class="form-control" id="dateFrom" value="{{ $dateFrom ?? '' }}">
                    <small class="text-muted">Pilihan (jika ada no. tiket)</small>
                </div>
                <div class="col-md-2">
                    <label for="dateTo" class="form-label">Tarikh Hingga:</label>
                    <input type="date" class="form-control" id="dateTo" value="{{ $dateTo ?? '' }}">
                    <small class="text-muted">Pilihan (jika ada no. tiket)</small>
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Pilih Status:</label>
                    <select class="form-select" id="statusFilter">
                        <option value="" {{ $selectedStatus == '' ? 'selected' : '' }}>Semua Status</option>
                        <option value="SENT" {{ $selectedStatus == 'SENT' ? 'selected' : '' }}>SENT</option>
                        <option value="FAILED" {{ $selectedStatus == 'FAILED' ? 'selected' : '' }}>FAILED</option>
                    </select>
                    <small class="text-muted">Status email</small>
                </div>
                <div class="col-md-2">
                    <label for="ticketFilter" class="form-label">No. Tiket:</label>
                    <input type="text" class="form-control" id="ticketFilter" value="{{ $selectedTicket ?? '' }}"
                        placeholder="Contoh: 0001/HELP-YYYY-MM-0001">
                    <small class="text-muted">Boleh cari tanpa pilih tarikh</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="bx bx-filter"></i> Tapis
                    </button>
                    <small class="text-muted">&nbsp;</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bx bx-refresh"></i> Reset Filter
                    </button>
                    <small class="text-muted">&nbsp;</small>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="badge bg-primary p-2 text-center">
                            <i class="bx bx-list-ul"></i> Jumlah: {{ $statistics['total'] }}
                        </div>
                        @foreach ($statistics['by_status'] as $status => $count)
                            <div
                                class="badge bg-{{ $status === 'SENT' ? 'success' : ($status === 'FAILED' ? 'danger' : 'secondary') }} p-2 text-center">
                                {{ $status }}: {{ $count }}
                            </div>
                        @endforeach
                    </div>
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
                                        @if (!empty($log['ticket_uuid']))
                                            <a href="{{ route('tickets.show', $log['ticket_uuid']) }}"
                                                class="fw-semibold text-primary">
                                                {{ $log['ticket'] }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $log['ticket'] }}</span>
                                        @endif
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
                                        @if (!empty($log['recipients']) && count($log['recipients']) > 1)
                                            <strong>Pegawai ({{ count($log['recipients']) }}):</strong><br>
                                            @foreach ($log['recipients'] as $recipient)
                                                <small>&bull; {{ $recipient['name'] }}
                                                    ({{ $recipient['email'] ?? 'N/A' }})</small><br>
                                            @endforeach
                                        @else
                                            <strong>Pegawai:</strong> {{ $log['recipient_name'] ?? 'N/A' }}<br>
                                            <small><strong>Email:</strong> {{ $log['email'] ?? 'N/A' }}</small><br>
                                        @endif
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
                                        <strong>
                                            @if ($dateFrom || $dateTo || $selectedTicket)
                                                Tiada rekod log untuk tapisan yang dipilih.
                                            @else
                                                Sila pilih julat tarikh atau masukkan no. tiket.
                                            @endif
                                        </strong>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const selectedTicket = document.getElementById('ticketFilter').value.trim();

            if (!dateFrom && !dateTo && !selectedTicket) {
                alert('Sila pilih julat tarikh atau masukkan no. tiket.');
                return;
            }

            const url = new URL("{{ route('log-mail-index') }}", window.location.origin);

            if (dateFrom) {
                url.searchParams.set('date_from', dateFrom);
            }

            if (dateTo) {
                url.searchParams.set('date_to', dateTo);
            }

            const selectedStatus = document.getElementById('statusFilter').value;
            if (selectedStatus) {
                url.searchParams.set('status', selectedStatus);
            }

            if (selectedTicket) {
                url.searchParams.set('ticket', selectedTicket);
            }

            window.location.href = url.toString();
        }

        function resetFilters() {
            window.location.href = "{{ route('log-mail-index') }}";
        }

        function filterTicketInTable(searchValue) {
            if (!window.mailLogsTable) {
                return;
            }

            window.mailLogsTable.column(1).search(searchValue).draw();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ticketFilter = document.getElementById('ticketFilter');

            @if (!empty($logs) && count($logs) > 0)
                if (typeof jQuery !== 'undefined') {
                    window.mailLogsTable = jQuery('#mailLogsTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100, -1],
                            [10, 25, 50, 100, "Semua"]
                        ],
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                        order: [
                            [0, 'desc']
                        ],
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

                    if (ticketFilter && ticketFilter.value.trim() !== '') {
                        filterTicketInTable(ticketFilter.value.trim());
                    }
                }
            @endif

            if (ticketFilter) {
                ticketFilter.addEventListener('input', function() {
                    filterTicketInTable(this.value.trim());
                });

                ticketFilter.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        applyFilters();
                    }
                });
            }
        });
    </script>
    @include('audit.logs.partials.log-details-modal')

@endsection
