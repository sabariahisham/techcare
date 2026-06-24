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

    {{-- Alert: Setup Instructions if no logs for today --}}
    @if ((!$dateFrom && !$dateTo && !$selectedTicket) || ($dateFrom === date('Y-m-d') && ($dateTo === date('Y-m-d') || !$dateTo)))
        @php
            $todayLogFile = storage_path('logs/sla-' . date('Y-m-d') . '.log');
            $hasLogsToday = file_exists($todayLogFile) && filesize($todayLogFile) > 0;
        @endphp

        @if (!$hasLogsToday)
            <div class="alert alert-warning alert-dismissible" role="alert">
                <h5 class="alert-heading mb-2">
                    <i class="bx bx-info-circle"></i> Tiada Log SLA Hari Ini
                </h5>
                <p class="mb-2">
                    SLA notifications belum auto-run kerana <strong>Laravel Scheduler belum disetup</strong> (dijadualkan
                    7:30 AM).
                </p>
                <p class="mb-0">
                    <strong>Setup:</strong> <code>.\setup-task-scheduler.ps1</code> (PowerShell Admin) |
                    <strong>Test manual:</strong> <code>php artisan sla:send-notifications --type=all</code>
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-1">LOG SLA</h4>
            <p class="mb-6">Papar rekod log SLA sistem.
                <br><small class="text-muted">Rekod log SLA sistem merujuk kepada fail SLA Log yang dijana oleh sistem
                    selama 14 hari dari tarikh semasa (path: storage/logs/sla-YYYY-MM-DD.log).</small>
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
                    <label for="categoryFilter" class="form-label">Pilih Kategori:</label>
                    <select class="form-select" id="categoryFilter">
                        <option value="" {{ $selectedCategory == '' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="REMINDER" {{ $selectedCategory == 'REMINDER' ? 'selected' : '' }}>REMINDER</option>
                        <option value="OVERDUE" {{ $selectedCategory == 'OVERDUE' ? 'selected' : '' }}>OVERDUE</option>
                    </select>
                    <small class="text-muted">Kategori SLA</small>
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
                <div>
                    <button type="button" class="btn btn-sm btn-secondary" id="runTaskBtn" onclick="runSlaTask()">
                        <i class="bx bx-play-circle"></i> <span id="btnText">RUN TASK</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                        data-bs-target="#runTaskInfoModal">
                        <i class="bx bx-info-circle"></i>
                    </button>
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
                                {{-- <th>Langkah</th> --}}
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
                                        <span
                                            class="badge bg-{{ strtoupper($log['category']) === 'REMINDER' ? 'warning' : 'danger' }}">
                                            {{ strtoupper($log['category']) }}
                                        </span>
                                    </td>
                                    {{-- <td>{{ $log['step'] }}</td> --}}
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
        function runSlaTask() {
            const btn = document.getElementById('runTaskBtn');
            const btnText = document.getElementById('btnText');
            const originalText = btnText.textContent;

            Swal.fire({
                title: 'Jalankan SLA Task?',
                html: 'Adakah anda pasti untuk menjalankan SLA notifications task sekarang?<br><small class="text-muted">Ini akan menghantar email reminder dan overdue kepada pegawai yang berkaitan.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#8592a3',
                confirmButtonText: '<i class="bx bx-play-circle me-1"></i>Ya, Jalankan',
                cancelButtonText: '<i class="bx bx-x me-1"></i>Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                btn.disabled = true;
                btnText.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sedang dijalankan...';

                Swal.fire({
                    title: 'Sedang Dijalankan...',
                    text: 'Sila tunggu semasa SLA notifications task dijalankan.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('log-sla-run-task') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Task Berjaya Dijalankan!',
                                html: `
                                    <div class="text-start">
                                        <p class="mb-2"><strong>Keputusan:</strong></p>
                                        <ul class="mb-2">
                                            <li>Dihantar: <strong>${data.results.sent}</strong></li>
                                            <li>Gagal: <strong>${data.results.failed}</strong></li>
                                            <li>Dilangkau: <strong>${data.results.skipped}</strong></li>
                                        </ul>
                                        <small class="text-muted">Masa: ${data.timestamp}</small>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonColor: '#696cff',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                if (data.results.sent > 0) {
                                    const today = new Date().toISOString().split('T')[0];
                                    window.location.href =
                                        "{{ route('log-sla-index') }}?date_from=" + today + "&date_to=" + today;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ralat',
                                text: data.message,
                                confirmButtonColor: '#696cff',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Ralat',
                            text: 'Gagal menjalankan task. Sila cuba lagi.',
                            confirmButtonColor: '#696cff',
                            confirmButtonText: 'OK'
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btnText.textContent = originalText;
                    });
            });
        }

        function applyFilters() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const selectedTicket = document.getElementById('ticketFilter').value.trim();

            if (!dateFrom && !dateTo && !selectedTicket) {
                alert('Sila pilih julat tarikh atau masukkan no. tiket.');
                return;
            }

            const url = new URL("{{ route('log-sla-index') }}", window.location.origin);

            if (dateFrom) {
                url.searchParams.set('date_from', dateFrom);
            }

            if (dateTo) {
                url.searchParams.set('date_to', dateTo);
            }

            const selectedCategory = document.getElementById('categoryFilter').value;
            if (selectedCategory) {
                url.searchParams.set('category', selectedCategory);
            }

            if (selectedTicket) {
                url.searchParams.set('ticket', selectedTicket);
            }

            window.location.href = url.toString();
        }

        function resetFilters() {
            window.location.href = "{{ route('log-sla-index') }}";
        }

        function filterTicketInTable(searchValue) {
            if (!window.slaLogsTable) {
                return;
            }

            window.slaLogsTable.column(1).search(searchValue).draw();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ticketFilter = document.getElementById('ticketFilter');

            @if (!empty($logs) && count($logs) > 0)
                if (typeof jQuery !== 'undefined') {
                    window.slaLogsTable = jQuery('#slaLogsTable').DataTable({
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

    {{-- Modal: Run Task Info --}}
    <div class="modal fade" id="runTaskInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bx bx-info-circle"></i> Maklumat: RUN TASK Button
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>🎯 Tujuan Button:</strong><br>
                        Button "RUN TASK" membolehkan anda menjalankan SLA notifications secara manual (on-demand) tanpa
                        menunggu scheduled time (7:30 AM).
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">📋 Fungsi Utama:</h6>
                    <ul>
                        <li><strong>Manual Execution:</strong> Menjalankan command
                            <code>sla:send-notifications --type=all</code> dengan segera
                        </li>
                        <li><strong>Real-time Results:</strong> Papar keputusan (sent/failed/skipped) selepas execution
                        </li>
                        <li><strong>Audit Trail:</strong> Semua manual runs direkod dalam log SLA dengan user info</li>
                        <li><strong>Direct Email:</strong> Hantar email terus kepada pegawai (NO QUEUE)</li>
                    </ul>

                    <h6 class="fw-bold mt-4 mb-3">💻 Kegunaan Untuk Local Development:</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p class="mb-2"><strong>✅ SESUAI untuk:</strong></p>
                            <ul class="mb-0">
                                <li><strong>Testing:</strong> Uji SLA notifications tanpa tunggu 7:30 AM</li>
                                <li><strong>Debugging:</strong> Check sama ada email configuration betul</li>
                                <li><strong>Development:</strong> Test perubahan code dengan segera</li>
                                <li><strong>Quick Verification:</strong> Verify working days calculation, email template,
                                    dll
                                </li>
                            </ul>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">🚀 Kegunaan Untuk Production:</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p class="mb-2"><strong>✅ SESUAI untuk:</strong></p>
                            <ul class="mb-3">
                                <li><strong>Backup Solution:</strong> Jika Task Scheduler gagal atau tidak berjalan</li>
                                <li><strong>Immediate Notifications:</strong> Hantar notifications segera tanpa tunggu
                                    scheduled time</li>
                                <li><strong>Recovery:</strong> Re-send notifications jika ada masalah sebelum ini</li>
                                <li><strong>Emergency Alerts:</strong> Hantar urgent SLA reminders bila diperlukan</li>
                            </ul>

                            <div class="alert alert-warning mb-0">
                                <strong>⚠️ AMARAN:</strong><br>
                                <ul class="mb-0 mt-2">
                                    <li>Button ini akan <strong>hantar email sebenar</strong> kepada pegawai</li>
                                    <li>Elakkan gunakan berulang kali - boleh menyebabkan spam emails</li>
                                    <li>Best practice: Hanya guna bila Task Scheduler bermasalah</li>
                                    <li>Semua execution direkod dan boleh di-audit</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">🔄 Workflow Normal vs Backup:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success text-white">
                                    <strong>✅ Normal Operation</strong>
                                </div>
                                <div class="card-body">
                                    <ol class="mb-0 small">
                                        <li>Task Scheduler run every minute</li>
                                        <li>Laravel checks schedule</li>
                                        <li>At 7:30 AM → Auto-run SLA task</li>
                                        <li>Logs generated automatically</li>
                                        <li><em>NO manual intervention needed</em></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning mb-3">
                                <div class="card-header bg-warning">
                                    <strong>⚠️ Backup/Emergency</strong>
                                </div>
                                <div class="card-body">
                                    <ol class="mb-0 small">
                                        <li>Task Scheduler bermasalah/tidak jalan</li>
                                        <li>Pegawai tidak terima notifications</li>
                                        <li>Admin click "RUN TASK" button</li>
                                        <li>Task run manually dengan segera</li>
                                        <li><em>Temporary solution sahaja</em></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">📊 Apa Yang Berlaku Bila Click Button?</h6>
                    <ol>
                        <li>System akan <strong>check tickets</strong> yang:
                            <ul>
                                <li>SLA due date = tomorrow (REMINDER)</li>
                                <li>SLA due date = yesterday (OVERDUE)</li>
                            </ul>
                        </li>
                        <li><strong>Send emails</strong> kepada pegawai berkaitan</li>
                        <li><strong>Generate logs</strong> di <code>storage/logs/sla-YYYY-MM-DD.log</code></li>
                        <li><strong>Show results</strong> dalam popup (sent/failed/skipped)</li>
                        <li><strong>Reload page</strong> untuk papar logs terkini</li>
                    </ol>

                    <div class="alert alert-success mt-4 mb-0">
                        <strong>💡 Best Practice:</strong><br>
                        <ul class="mb-0 mt-2">
                            <li><strong>Development:</strong> Guna button ini sesuka hati untuk testing</li>
                            <li><strong>Production:</strong> Setup Task Scheduler untuk auto-run (recommended)</li>
                            <li><strong>Backup:</strong> Guna button hanya bila scheduler gagal atau emergency</li>
                            <li><strong>Monitoring:</strong> Check logs page regularly untuk verify auto-run berjalan</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endsection
