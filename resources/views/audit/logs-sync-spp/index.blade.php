@extends('layouts/layoutMaster')

@section('title', 'Log Sync SPP')

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
    {{-- Setup configuration for JS file --}}
    <script>
        window.syncSppConfig = {
            routes: {
                index: "{{ route('log-sync-spp-index') }}",
                runTask: "{{ route('log-sync-spp-run-task') }}",
                killStuckJobs: "{{ route('log-sync-spp-kill-stuck-jobs') }}"
            },
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    
    {{-- Load external JS file --}}
    @vite(['resources/assets/js/audit/app-audit-logSyncUsers.js'])
@endsection

@section('content')

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-1">LOG SYNC SPP</h4>
            <p class="mb-6">Papar rekod log job command SyncUserSPP.</p>

            {{-- Date Filter --}}
            <div class="row">
                <div class="col-md-3">
                    <label for="dateFrom" class="form-label">Tarikh Dari:</label>
                    <input type="date" class="form-control" id="dateFrom" value="{{ $dateFrom ?? '' }}">
                    <small class="text-muted">Pilih tarikh mula</small>
                </div>
                <div class="col-md-3">
                    <label for="dateTo" class="form-label">Tarikh Hingga:</label>
                    <input type="date" class="form-control" id="dateTo" value="{{ $dateTo ?? '' }}">
                    <small class="text-muted">Pilih tarikh akhir</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" onclick="filterByDate()">
                        <i class="bx bx-filter"></i> Tapis
                    </button>
                    <small class="text-muted">&nbsp;</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="bx bx-refresh"></i> Reset Filter
                    </button>
                    <small class="text-muted">&nbsp;</small>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="badge bg-primary p-2 text-center">
                            <i class="bx bx-list-ul"></i> Jumlah: {{ $statistics['total'] }}
                        </div>
                        <div class="badge bg-success p-2 text-center">
                            <i class="bx bx-check-circle"></i> Success: {{ $statistics['success'] }}
                        </div>
                        <div class="badge bg-danger p-2 text-center">
                            <i class="bx bx-x-circle"></i> Failed: {{ $statistics['failed'] }}
                        </div>
                        <div class="badge bg-info p-2 text-center">
                            <i class="bx bx-loader"></i> Running: {{ $statistics['running'] }}
                        </div>
                        <div class="badge bg-secondary p-2 text-center">
                            <i class="bx bx-plus"></i> Total Inserted: {{ number_format($statistics['total_inserted']) }}
                        </div>
                        {{-- <div class="badge bg-warning p-2 text-center">
                            <i class="bx bx-edit"></i> Total Updated: {{ number_format($statistics['total_updated']) }}
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0">Senarai Log Sync SPP</h5>
                    <p class="mb-0 text-muted">Rekod job command SyncUserSPP</p>
                </div>
                <div>
                    @if($statistics['running'] > 0)
                        <button type="button" class="btn btn-sm btn-danger" id="killJobsBtn" onclick="killStuckJobs()">
                            <i class="bx bx-stop-circle"></i> <span id="killBtnText">KILL STUCK JOBS ({{ $statistics['running'] }})</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-secondary" id="runSyncBtn" onclick="runSyncTask()">
                        <i class="bx bx-play-circle"></i> <span id="btnText">RUN SYNC</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                        data-bs-target="#runTaskInfoModal">
                        <i class="bx bx-info-circle"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="syncSppLogsTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Bil</th>
                                <th>Status</th>
                                <th>Total Records</th>
                                <th>Inserted</th>
                                <th>Updated</th>
                                <th>Execution Time (s)</th>
                                <th>Started</th>
                                <th>Finished</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $index => $log)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if ($log->status === 'success')
                                            <span class="badge bg-success">SUCCESS</span>
                                        @elseif ($log->status === 'failed')
                                            <span class="badge bg-danger">FAILED</span>
                                        @elseif ($log->status === 'running')
                                            <span class="badge bg-info">RUNNING</span>
                                        @else
                                            <span class="badge bg-secondary">{{ strtoupper($log->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($log->total_records ?? 0) }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ number_format($log->inserted_records ?? 0) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ number_format($log->updated_records ?? 0) }}</span>
                                    </td>
                                    <td>
                                        @if ($log->execution_time)
                                            <span class="badge bg-info">{{ number_format($log->execution_time, 2) }}s</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->started_at)
                                            <small>{{ \Carbon\Carbon::parse($log->started_at)->format('d/m/Y H:i:s') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->finished_at)
                                            <small>{{ \Carbon\Carbon::parse($log->finished_at)->format('d/m/Y H:i:s') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->message)
                                            <small class="text-muted" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" 
                                                   title="{{ $log->message }}">
                                                {{ $log->message }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bx bx-calendar bx-lg"></i><br>
                                        <strong>Tiada rekod log untuk tempoh ini.</strong>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Run Task Info --}}
    <div class="modal fade" id="runTaskInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bx bx-info-circle"></i> Maklumat: RUN SYNC Button
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>🎯 Tujuan Button:</strong><br>
                        Button "RUN SYNC" membolehkan anda menjalankan sync user SPP secara manual (on-demand) tanpa
                        menunggu cron job.
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">📋 Fungsi Utama:</h6>
                    <ul>
                        <li><strong>Manual Execution:</strong> Menjalankan command <code>sync:users-spp</code> dengan segera</li>
                        <li><strong>Real-time Results:</strong> Papar keputusan (inserted/updated/execution time) selepas sync</li>
                        <li><strong>Audit Trail:</strong> Semua manual runs direkod dalam table sync_jobs dengan user info</li>
                        <li><strong>Data Sync:</strong> Sync data pengguna dari sistem SPP ke database lokal</li>
                    </ul>

                    <h6 class="fw-bold mt-4 mb-3">💻 Kegunaan Untuk Local Development:</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p class="mb-2"><strong>✅ SESUAI untuk:</strong></p>
                            <ul class="mb-0">
                                <li><strong>Testing:</strong> Uji sync process tanpa tunggu cron job</li>
                                <li><strong>Debugging:</strong> Check sama ada sync berjalan dengan betul</li>
                                <li><strong>Development:</strong> Test perubahan code dengan segera</li>
                                <li><strong>Quick Verification:</strong> Verify data sync dari SPP ke local database</li>
                            </ul>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">🚀 Kegunaan Untuk Production:</h6>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <p class="mb-2"><strong>✅ SESUAI untuk:</strong></p>
                            <ul class="mb-3">
                                <li><strong>Backup Solution:</strong> Jika cron job gagal atau tidak berjalan</li>
                                <li><strong>Immediate Sync:</strong> Sync data segera tanpa tunggu scheduled time</li>
                                <li><strong>Recovery:</strong> Re-sync data jika ada masalah sebelum ini</li>
                                <li><strong>Emergency Updates:</strong> Update data pengguna bila diperlukan dengan segera</li>
                            </ul>

                            <div class="alert alert-warning mb-0">
                                <strong>⚠️ AMARAN:</strong><br>
                                <ul class="mb-0 mt-2">
                                    <li>Button ini akan <strong>sync data sebenar</strong> dari sistem SPP</li>
                                    <li>Elakkan gunakan berulang kali - boleh menyebabkan beban pada server</li>
                                    <li>Best practice: Hanya guna bila cron job bermasalah</li>
                                    <li>Semua execution direkod dan boleh di-audit</li>
                                    <li>Process boleh ambil masa (30s - 2min) bergantung pada data size</li>
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
                                        <li>Cron job run setiap hari</li>
                                        <li>Auto-sync users dari SPP</li>
                                        <li>Logs generated automatically</li>
                                        <li>Records saved to sync_jobs table</li>
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
                                        <li>Cron job bermasalah/tidak jalan</li>
                                        <li>Data tidak sync atau outdated</li>
                                        <li>Admin click "RUN SYNC" button</li>
                                        <li>Sync run manually dengan segera</li>
                                        <li><em>Temporary solution sahaja</em></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-3">📊 Apa Yang Berlaku Bila Click Button?</h6>
                    <ol>
                        <li>System akan <strong>connect ke API SPP</strong></li>
                        <li><strong>Fetch user data</strong> dari sistem SPP</li>
                        <li><strong>Compare dengan local database</strong>
                            <ul>
                                <li>New users → INSERT</li>
                                <li>Existing users → UPDATE</li>
                            </ul>
                        </li>
                        <li><strong>Save results</strong> ke table sync_jobs</li>
                        <li><strong>Show results</strong> dalam popup (records/inserted/updated/time)</li>
                        <li><strong>Reload page</strong> untuk papar logs terkini</li>
                    </ol>

                    <div class="alert alert-success mt-4 mb-0">
                        <strong>💡 Best Practice:</strong><br>
                        <ul class="mb-0 mt-2">
                            <li><strong>Development:</strong> Guna button ini sesuka hati untuk testing</li>
                            <li><strong>Production:</strong> Setup cron job untuk auto-sync (recommended)</li>
                            <li><strong>Backup:</strong> Guna button hanya bila cron job gagal atau emergency</li>
                            <li><strong>Monitoring:</strong> Check logs page regularly untuk verify auto-sync berjalan</li>
                            <li><strong>Performance:</strong> Jangan run terlalu kerap - boleh bebankan server</li>
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
