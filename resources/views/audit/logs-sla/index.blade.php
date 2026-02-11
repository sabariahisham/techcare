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
    @if (!$selectedDate || $selectedDate === date('Y-m-d'))
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
                    Logs SLA tidak auto-generate kerana <strong>Laravel Scheduler belum disetup</strong>.
                    SLA notifications dijadualkan setiap hari pada <strong>7:30 AM</strong>, tetapi memerlukan Task
                    Scheduler untuk berjalan secara automatik.
                </p>
                <hr>
                <p class="mb-2"><strong>🔧 Cara Setup (Windows):</strong></p>
                <ol class="mb-0">
                    <li>Buka PowerShell <strong>sebagai Administrator</strong></li>
                    <li>Navigate ke folder project: <code>cd C:\Users\sabariah.hisham\Herd\ehelp-fork_29122025</code></li>
                    <li>Jalankan: <code>.\setup-task-scheduler.ps1</code></li>
                </ol>
                <p class="mt-3 mb-2"><strong>📝 Atau test manual:</strong></p>
                <p class="mb-0"><code>php artisan sla:send-notifications --type=all</code></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

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
                                        <span class="fw-semibold">{{ $log['ticket'] }}</span>
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
        function runSlaTask() {
            const btn = document.getElementById('runTaskBtn');
            const btnText = document.getElementById('btnText');
            const originalText = btnText.textContent;

            // Confirm action
            if (!confirm(
                    'Adakah anda pasti untuk menjalankan SLA notifications task sekarang?\n\nIni akan menghantar email reminder dan overdue kepada pegawai yang berkaitan.'
                )) {
                return;
            }

            // Disable button and show loading
            btn.disabled = true;
            btnText.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sedang dijalankan...';

            // Make AJAX request
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
                        // Show success message with results
                        let message = '✅ Task berjaya dijalankan!\n\n';
                        message += '📊 Keputusan:\n';
                        message += `✓ Dihantar: ${data.results.sent}\n`;
                        message += `✗ Gagal: ${data.results.failed}\n`;
                        message += `⊘ Dilangkau: ${data.results.skipped}\n\n`;
                        message += `⏰ Masa: ${data.timestamp}`;

                        alert(message);

                        // Reload page to show new logs
                        if (data.results.sent > 0) {
                            // Redirect to today's logs
                            const today = new Date().toISOString().split('T')[0];
                            window.location.href = "{{ route('log-sla-index') }}?date=" + today;
                        }
                    } else {
                        alert('❌ Ralat: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Ralat: Gagal menjalankan task. Sila cuba lagi.');
                })
                .finally(() => {
                    // Re-enable button
                    btn.disabled = false;
                    btnText.textContent = originalText;
                });
        }

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
