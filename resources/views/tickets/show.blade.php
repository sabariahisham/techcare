@extends('layouts.layoutMaster')

@section('title', 'Lihat Tiket - ' . $ticket->ticket_nolog)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Maklumat Penuh Tiket</h4>
                    <p class="mb-0 text-muted">{{ $ticket->ticket_nolog }}</p>
                </div>
                <div>
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-3"></i>Kembali ke Senarai
                    </a>
                    {{-- <button type="button" class="btn btn-warning" onclick="$('#commentModal').modal('show')">
                        <i class="bx bx-message me-3"></i>Komen
                    </button> --}}
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bx bx-printer me-3"></i>Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('tickets.partials.comment-modal')

    <div class="row">
        <div class="col-12">
            <!-- Maklumat Pengadu -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-user me-2"></i>
                        @if ($ticket->ticket_type == 1)
                            Maklumat Pengadu
                        @else
                            Maklumat Pengadu (Bagi Pihak)
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if ($ticket->user)
                        <div class="row">
                            <!-- Maklumat Peribadi -->
                            <div class="col-12 col-md-4 col-lg-4 mb-3 mb-md-0">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg me-3">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ strtoupper(substr($ticket->user->nama ?? 'U', 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $ticket->user->nama ?? 'Tidak Diketahui' }}</h6>
                                        <h6 class="mb-0">{{ $ticket->user->kod_jwtn ?? '-' }}</h6>
                                        <small class="text-muted">{{ $ticket->user->email ?? '-' }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Pemisah Vertikal - Hidden on mobile -->
                            <div class="col-1 d-none d-md-flex justify-content-center">
                                <div class="vr h-100"></div>
                            </div>

                            <!-- Maklumat Organisasi -->
                            <div class="col-12 col-md-7 col-lg-7">
                                <div class="row mb-3">
                                    @if ($ticket->user->sektor || $ticket->user->kod_sektor)
                                        <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                            <h6 class="text-primary mb-1">
                                                <i class="bx bx-buildings me-1"></i>Sektor
                                            </h6>
                                            <p class="mb-0 small">
                                                {{ $ticket->user->sektor->nama_sektor ?? ($ticket->user->kod_sektor ?? '-') }}
                                            </p>
                                        </div>
                                    @endif
                                    @if ($ticket->user->bahagian || $ticket->user->kod_bah)
                                        <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                            <h6 class="text-primary mb-1">
                                                <i class="bx bx-sitemap me-1"></i>Bahagian
                                            </h6>
                                            <p class="mb-0 small">
                                                {{ $ticket->user->bahagian->nama_bah ?? ($ticket->user->kod_bah ?? '-') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                                <div class="row">
                                    @if ($ticket->user->seksyen || $ticket->user->kod_sec)
                                        <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                            <h6 class="text-primary mb-1">
                                                <i class="bx bx-git-branch me-1"></i>Seksyen
                                            </h6>
                                            <p class="mb-0 small">
                                                {{ $ticket->user->seksyen->nama_sec ?? ($ticket->user->kod_sec ?? '-') }}
                                            </p>
                                        </div>
                                    @endif
                                    @if ($ticket->user->unit || $ticket->user->kod_unit)
                                        <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                            <h6 class="text-primary mb-1">
                                                <i class="bx bx-group me-1"></i>Unit
                                            </h6>
                                            <p class="mb-0 small">
                                                {{ $ticket->user->unit->nama_unit ?? ($ticket->user->kod_unit ?? '-') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Main Content -->
        <div class="col-xl-6 col-lg-6 col-md-6">
            <!-- Maklumat Aduan Tiket -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-file-blank me-2"></i>Maklumat Aduan Tiket
                        <span class="badge bg-primary fs-6 float-end">{{ $ticket->ticket_nolog }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Tajuk Aduan</h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            {{ $ticket->ticket_title }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Kategori</h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            {{ $ticket->category->cat_name ?? '-' }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Sub Kategori</h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            {{ $ticket->subCategory->subcat_name ?? '-' }}
                            @if ($currentSLA && $currentSLA->count() > 0)
                                <div class="mt-2">
                                    <small class="text-info fw-bold">
                                        <i class="bx bx-info-circle me-1"></i>
                                        SLA Tersedia:
                                        @foreach ($currentSLA as $sla)
                                            <span class="badge bg-info ms-1">{{ ucfirst(strtolower($sla->sla_severity)) }}
                                                ({{ $sla->sla_days }} hari)</span>
                                        @endforeach
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">No. Siri <br> <small>(Aset / Sewaan)</small></h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            {{ $ticket->serial_number ?? '-' }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Keterangan Aduan</h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            {!! nl2br(e(ucfirst($ticket->ticket_desc))) !!}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Lampiran</h6>
                        </div>
                        <div class="col-sm-9">
                            @if ($ticket->attachments && $ticket->attachments->count() > 0)
                                <div class="attachment-list">
                                    @foreach ($ticket->attachments as $attachment)
                                        <div class="attachment-item mb-2">
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $extension = strtolower(
                                                        pathinfo($attachment->doc_name, PATHINFO_EXTENSION),
                                                    );
                                                    $iconClass = 'bx-file';
                                                    $iconColor = 'text-muted';

                                                    if (in_array($extension, ['pdf'])) {
                                                        $iconClass = 'bxs-file-pdf';
                                                        $iconColor = 'text-danger';
                                                    } elseif (in_array($extension, ['doc', 'docx'])) {
                                                        $iconClass = 'bxs-file-doc';
                                                        $iconColor = 'text-primary';
                                                    } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                                                        $iconClass = 'bxs-file-image';
                                                        $iconColor = 'text-success';
                                                    }
                                                @endphp

                                                <i class="bx {{ $iconClass }} {{ $iconColor }} me-2 fs-5"></i>
                                                <div class="flex-grow-1">
                                                    <span class="fw-medium">{{ $attachment->doc_name }}</span>
                                                    <small class="text-muted d-block">
                                                        Dimuat naik: {{ $attachment->created_at->format('d M Y, H:i') }}
                                                    </small>
                                                </div>
                                                <a href="{{ route('ticket-attachments.download', $attachment->id) }}"
                                                    class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                    title="Muat turun {{ $attachment->doc_name }}">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mb-0 text-muted">Tiada lampiran tersedia.</p>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mt-6 mb-1">
                        <div class="col-sm">
                            <button type="button" class="btn btn-warning btn-sm "
                                onclick="$('#commentModal').modal('show')">
                                <i class="bx bx-message me-3"></i>Komen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if (auth()->user()->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan']))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-cog me-2"></i>Maklumat Tindakan Pegawai
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Status Tiket</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <select class="form-select" name="tick_status" id="tick_status">
                                    <option value="">--Pilih Status--</option>
                                    @foreach ($allStatuses as $status)
                                        <option value="{{ $status->id }}"
                                            {{ $ticket->tick_status == $status->id ? 'selected' : '' }}>
                                            {{ $status->st_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Kategori</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <select class="form-select" name="cat_id" id="cat_id">
                                    <option value="">--Pilih Kategori--</option>
                                    @foreach ($allCategories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ $ticket->cat_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->cat_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Sub Kategori</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <select class="form-select" name="subcat_id" id="subcat_id">
                                    <option value="">--Pilih Sub Kategori--</option>
                                    @foreach ($allSubCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-cat-id="{{ $subCategory->cat_id }}"
                                            {{ $ticket->subcat_id == $subCategory->id ? 'selected' : '' }}>
                                            {{ $subCategory->subcat_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Tahap Keparahan</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <select class="form-select" name="sla_severity" id="sla_severity">
                                    <option value="">--Pilih Tahap Keparahan--</option>
                                    <!-- Options will be populated via AJAX based on category and subcategory -->
                                </select>
                                <small class="text-muted">Tahap keparahan akan dipaparkan berdasarkan kategori dan sub
                                    kategori yang dipilih.</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Pegawai Tugasan</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <select class="form-select" name="assigned_officers[]" id="assigned_officers" multiple>
                                    @foreach ($officers as $officer)
                                        @php
                                            $isCurrentUser = $officer->id === auth()->user()->id;
                                        @endphp
                                        <option value="{{ $officer->id }}"
                                            data-current-user="{{ $isCurrentUser ? 'true' : 'false' }}"
                                            {{ $isCurrentUser ? 'selected' : '' }}>
                                            {{ $officer->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Tarikh Tindakan</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <input type="date" class="form-control" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Tindakan Oleh</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                {{ auth()->user()->nama }}
                            </div>
                        </div>
                        <hr>
                        <div class="row mt-6 mb-1">
                            <div class="col-sm">
                                <button type="button" class="btn btn-warning btn-sm "
                                    onclick="$('#commentModal').modal('show')">
                                    <i class="bx bx-message me-3"></i>Komen
                                </button>
                                <button class="btn btn-primary btn-sm float-end">
                                    <i class="bx bx-save me-1"></i>Kemaskini
                                </button>
                                <button class="btn btn-secondary btn-sm float-end me-2">
                                    <i class="bx bx-save me-1"></i>Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Center Column - Kronologi -->
        <div class="col-xl-6 col-lg-6 col-md-6">
            <!-- Kronologi Tiket -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-time me-2"></i>Kronologi Tiket
                        <small class="text-muted">[Jangka Siap:]</small>
                    </h5>
                </div>
                <div class="card-body">
                    @if ($ticketHistory && $ticketHistory->count() > 0)
                        <div class="timeline">
                            @foreach ($ticketHistory as $index => $history)
                                @php
                                    // Determine icon and color based on exact status names
                                    $statusName = $history['status'] ?? ($history['action'] ?? '');
                                    $iconClass = 'bx-edit';
                                    $colorClass = 'bg-secondary';

                                    // Status color mapping based on your requirements
                                    switch ($statusName) {
                                        case 'Aduan Baru':
                                            $iconClass = 'bx-plus-circle';
                                            $colorClass = 'bg-primary'; // Biru
                                            break;
                                        case 'Aduan Diproses':
                                            $iconClass = 'bx-cog';
                                            $colorClass = 'bg-warning'; // Orange
                                            break;
                                        case 'Serahan Kepada':
                                            $iconClass = 'bx-user-plus';
                                            $colorClass = 'bg-info'; // Info
                                            break;
                                        case 'Aduan Dirujuk':
                                            $iconClass = 'bx-share-alt';
                                            $colorClass = 'bg-danger'; // Merah
                                            break;
                                        case 'Aduan DiBatal':
                                            $iconClass = 'bx-x-circle';
                                            $colorClass = 'bg-secondary'; // Kelabu
                                            break;
                                        case 'Aduan Selesai':
                                            $iconClass = 'bx-check-circle';
                                            $colorClass = 'bg-success'; // Hijau
                                            break;
                                    }
                                @endphp

                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <div class="timeline-marker-icon {{ $colorClass }}">
                                            <i class="bx {{ $iconClass }}"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="timeline-title mb-0">
                                                {{-- {{ $history['action'] ?? 'Tindakan Tiket' }} --}}</h6>
                                            <span class="badge {{ $colorClass }} badge-sm">{{ $statusName }}</span>
                                        </div>
                                        <p class="timeline-text">
                                            {{ $history['description'] ?? 'Tiada penerangan tersedia.' }}</p>

                                        <div class="mt-2">
                                            <small class="timeline-time d-block mb-1">
                                                <i class="bx bx-edit"></i>
                                                <strong>Catatan:</strong>
                                            </small>

                                            <small class="timeline-time d-block ps-5">
                                                @if (isset($history['notes']) && $history['notes'])
                                                    {!! nl2br(e($history['notes'])) !!}
                                                @elseif(isset($history['type']) && $history['type'] === 'comment')
                                                    <em>Komen telah ditambahkan.</em>
                                                @else
                                                    <em>Tiada catatan untuk tindakan ini.</em>
                                                @endif
                                            </small>
                                        </div>
                                        <small class="timeline-time mt-3">
                                            <i class="bx bx-user"></i>
                                            <strong>Oleh:</strong> {{ $history['user'] }} [ Tarikh:
                                            {{ isset($history['timestamp']) ? $history['timestamp']->format('d/m/Y, H:i a') : now()->format('d/m/Y, H:i a') }}
                                            ]<br>
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="timeline-empty-state">
                                <i class="bx bx-history display-1 text-muted mb-3"></i>
                                <h6 class="text-muted mb-2">Tiada Kronologi Tersedia</h6>
                                <p class="text-muted small mb-0">Kronologi tiket akan dipaparkan di sini apabila
                                    terdapat aktiviti.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Enhanced Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 2.5rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1.25rem;
            top: 0.5rem;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #696cff 0%, #e3e6f0 50%, #e3e6f0 100%);
            border-radius: 1.5px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 0.5rem;
            padding-left: 1rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-marker {
            position: absolute;
            left: -2.5rem;
            top: 0.25rem;
            z-index: 2;
        }

        .timeline-marker-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .timeline-marker-icon::after {
            content: '';
            position: absolute;
            width: 1rem;
            height: 1rem;
            background: inherit;
            border-radius: 50%;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        .timeline-marker-icon.bg-primary {
            background: linear-gradient(135deg, #696cff, #5a67d8);
        }

        .timeline-marker-icon.bg-secondary {
            background: linear-gradient(135deg, #8592a3, #6c757d);
        }

        .timeline-marker-icon.bg-success {
            background: linear-gradient(135deg, #71dd37, #28a745);
        }

        .timeline-marker-icon.bg-warning {
            background: linear-gradient(135deg, #ffab00, #fd7e14);
        }

        .timeline-marker-icon.bg-danger {
            background: linear-gradient(135deg, #ff3e1d, #dc3545);
        }

        .timeline-marker-icon.bg-purple {
            background: linear-gradient(135deg, #8e44ad, #6f42c1);
        }

        .timeline-marker-icon.bg-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }

        /* Badge colors to match timeline markers
                                                                                                                                                                                            .badge.bg-purple {
                                                                                                                                                                                                background: linear-gradient(135deg, #8e44ad, #6f42c1) !important;
                                                                                                                                                                                                color: white;
                                                                                                                                                                                            } */

        .badge.badge-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .timeline-content {
            background: #ffffff;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e3e6f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            transition: all 0.3s ease;
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 1rem;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 8px solid #ffffff;
            filter: drop-shadow(-1px 0 1px rgba(0, 0, 0, 0.1));
        }

        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .timeline-title {
            margin-bottom: 0.25rem;
            color: #5a5c69;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .timeline-text {
            margin-bottom: 0.75rem;
            color: #6c757d;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .timeline-details {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e9ecef;
        }

        .timeline-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #8592a3;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .timeline-time i {
            font-size: 0.875rem;
        }

        /* Pulse animation for active/recent items */
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                opacity: 0.7;
            }

            70% {
                transform: scale(1);
                opacity: 0.1;
            }

            100% {
                transform: scale(0.95);
                opacity: 0.7;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .timeline {
                padding-left: 2rem;
            }

            .timeline::before {
                left: 1rem;
            }

            .timeline-marker {
                left: -2rem;
            }

            .timeline-marker-icon {
                width: 2rem;
                height: 2rem;
                font-size: 0.875rem;
            }

            .timeline-content {
                padding: 0.75rem;
            }

            .timeline-item {
                margin-bottom: 1rem;
            }
        }

        /* Print Styles */
        @media print {

            .btn,
            .card-header .btn {
                display: none !important;
            }

            .timeline-marker-icon {
                box-shadow: none !important;
                border: 2px solid #000 !important;
            }

            .timeline-content {
                box-shadow: none !important;
            }

            .timeline-marker-icon::after {
                display: none;
            }
        }

        /* Attachment Styles */
        .attachment-item {
            padding: 0.75rem;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .attachment-item:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .attachment-list .attachment-item:last-child {
            margin-bottom: 0;
        }

        /* Compact Chat Interface Styles */
        .chat-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .chat-message {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-end;
        }

        .chat-message.complainant-message {
            justify-content: flex-start;
        }

        .chat-message.officer-message {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 80%;
            display: flex;
            flex-direction: column;
        }

        .complainant-message .message-content {
            align-items: flex-start;
        }

        .officer-message .message-content {
            align-items: flex-end;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
            gap: 0.5rem;
        }

        .officer-message .message-header {
            flex-direction: row-reverse;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .officer-message .user-info {
            flex-direction: row-reverse;
        }

        .avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.7rem;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .avatar-complainant {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .avatar-officer {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .user-details {
            text-align: left;
        }

        .officer-message .user-details {
            text-align: right;
        }

        .user-name {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: #495057;
        }

        .message-time {
            color: #6c757d;
            font-size: 0.65rem;
        }

        .badge-complainant {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.6rem;
            padding: 0.15rem 0.35rem;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(102, 126, 234, 0.3);
        }

        .badge-officer {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-size: 0.6rem;
            padding: 0.15rem 0.35rem;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(240, 147, 251, 0.3);
        }

        .message-bubble {
            position: relative;
            padding: 0.5rem 0.75rem;
            border-radius: 12px;
            margin-bottom: 0.25rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            max-width: fit-content;
            word-wrap: break-word;
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .bubble-complainant {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            /* background: #667eea; */
            color: white;
            border-bottom-left-radius: 4px;
            margin-left: 0;
            margin-right: auto;
        }

        .bubble-officer {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            /* background: #f5576c; */
            color: white;
            border-bottom-right-radius: 4px;
            margin-left: auto;
            margin-right: 0;
        }

        .message-text {
            margin: 0;
            color: inherit;
        }

        .empty-chat {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        /* Scrollbar Styling */
        .chat-container::-webkit-scrollbar {
            width: 4px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        /* Animation for new messages */
        .chat-message {
            animation: slideIn 0.2s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .message-content {
                max-width: 90%;
            }

            .message-bubble {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .avatar {
                width: 1.5rem;
                height: 1.5rem;
                font-size: 0.6rem;
            }

            .chat-container {
                padding: 0.5rem;
            }

            .user-name {
                font-size: 0.7rem;
            }

            .message-time {
                font-size: 0.6rem;
            }
        }

        /* Print Styles for Chat */
        @media print {
            .chat-container {
                background: none !important;
                box-shadow: none !important;
            }

            .message-bubble {
                background: #f8f9fa !important;
                color: #000 !important;
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }

            .avatar {
                background: #6c757d !important;
                box-shadow: none !important;
            }
        }

        /* Chat Input Styling */
        .chat-input-container {
            padding: 0.75rem 1rem;
            background: #ffffff;
            border-top: 1px solid #e9ecef;
            margin: 0;
        }

        .chat-input-form {
            width: 100%;
        }

        .chat-input-wrapper {
            position: relative;
            display: flex;
            align-items: flex-end;
        }

        .chat-input {
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 8px 40px 8px 12px;
            resize: none;
            min-height: 36px;
            max-height: 80px;
            font-size: 0.8rem;
            line-height: 1.3;
            box-shadow: none;
            transition: all 0.3s ease;
            width: 100%;
        }

        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.15rem rgba(102, 126, 234, 0.2);
            outline: none;
        }

        .chat-send-btn {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 4px;
            width: 28px;
            height: 28px;
            background: #667eea;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: none;
            color: white;
            z-index: 2;
            font-size: 0.8rem;
        }

        .chat-send-btn:hover {
            background: #5a6fd8;
            transform: translateY(-50%) scale(1.05);
            box-shadow: none;
        }

        .chat-send-btn:active {
            transform: translateY(-50%) scale(0.95);
            box-shadow: none;
        }

        .chat-send-btn:focus {
            box-shadow: none;
            outline: none;
        }


        .chat-input {
            overflow-y: hidden;
        }

        /* Mobile responsiveness */
        @media (max-width: 576px) {
            .chat-input-container {
                padding: 0.5rem;
            }

            .chat-send-btn {
                width: 24px;
                height: 24px;
                right: 3px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 0.7rem;
            }

            .chat-send-btn:hover {
                transform: translateY(-50%) scale(1.05);
            }

            .chat-send-btn:active {
                transform: translateY(-50%) scale(0.95);
            }

            .chat-input {
                padding: 6px 32px 6px 10px;
                min-height: 32px;
                font-size: 0.75rem;
            }
        }

        /* Select2 Custom Styling for Current User */
        .select2-container .select2-results__option[data-current-user="true"] {
            background-color: #28a745 !important;
            color: white !important;
            font-weight: bold !important;
        }

        .select2-container .select2-results__option[data-current-user="true"]:hover {
            background-color: #218838 !important;
            color: white !important;
        }

        .select2-container .select2-results__option[data-current-user="true"][aria-selected="true"] {
            background-color: #1e7e34 !important;
            color: white !important;
        }

        /* Alternative approach using class */
        .select2-container .select2-results__option.current-user-option {
            background-color: #28a745 !important;
            color: white !important;
            font-weight: bold !important;
        }

        .select2-container .select2-results__option.current-user-option:hover {
            background-color: #218838 !important;
            color: white !important;
        }

        .select2-container .select2-results__option.current-user-option[aria-selected="true"] {
            background-color: #1e7e34 !important;
            color: white !important;
        }

        /* Styling for selected items (chips/tags) */
        .select2-container--default .select2-selection__choice[data-current-user="true"] {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }

        .select2-container--default .select2-selection__choice.current-user-choice {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }

        .select2-container--default .select2-selection__choice__remove[data-current-user="true"] {
            color: white !important;
            border-right-color: rgba(255, 255, 255, 0.3) !important;
        }

        .select2-container--default .select2-selection__choice__remove[data-current-user="true"]:hover {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.2) !important;
        }
    </style>

    <script>
        // Auto-resize textarea functionality
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            const chatForm = document.querySelector('.chat-input-form');

            if (chatInput) {
                chatInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Handle Enter key to send message (Shift+Enter for new line)
                chatInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const form = this.closest('form');
                        if (this.value.trim()) {
                            form.submit();
                        }
                    }
                });
            }

            // Add form submission handling
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    console.log('Form submitted');
                    const commentText = chatForm.querySelector('[name="comment"]').value.trim();
                    if (!commentText) {
                        e.preventDefault();
                        alert('Sila masukkan komen sebelum hantar.');
                        return false;
                    }
                    console.log('Comment text:', commentText);
                });
            }

            // Category and subcategory filtering functionality with SLA integration
            const categorySelect = document.querySelector('#cat_id');
            const subcategorySelect = document.querySelector('#subcat_id');
            const severitySelect = document.querySelector('#sla_severity');
            const assignedOfficersSelect = document.querySelector('#assigned_officers');

            if (categorySelect && subcategorySelect) {
                // Store all subcategory options
                const allSubcategoryOptions = Array.from(subcategorySelect.options);

                // Function to load SLA data and update severity options
                function loadSLAData(categoryId, subcategoryId) {
                    if (categoryId && subcategoryId) {
                        fetch(`/api/sla-data/${categoryId}/${subcategoryId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update severity options
                                    severitySelect.innerHTML =
                                        '<option value="">--Pilih Tahap Keparahan--</option>';
                                    data.severities.forEach(severity => {
                                        const option = document.createElement('option');
                                        option.value = severity.value;
                                        option.textContent = severity.label;
                                        option.dataset.slaId = severity.sla_id;
                                        severitySelect.appendChild(option);
                                    });

                                    // Clear severity selection when SLA changes
                                    severitySelect.value = '';

                                    // Trigger officer filtering
                                    filterOfficersBySLA();
                                } else {
                                    // Clear severity options if no SLA found
                                    severitySelect.innerHTML =
                                        '<option value="">Tiada tahap keparahan ditetapkan</option>';
                                    resetOfficersList();
                                }
                            })
                            .catch(error => {
                                console.error('Error loading SLA data:', error);
                                severitySelect.innerHTML = '<option value="">Ralat memuat data SLA</option>';
                                resetOfficersList();
                            });
                    } else {
                        // Clear severity options when category or subcategory not selected
                        severitySelect.innerHTML = '<option value="">--Pilih Tahap Keparahan--</option>';
                        resetOfficersList();
                    }
                }

                // Function to filter officers based on SLA criteria
                function filterOfficersBySLA() {
                    const categoryId = categorySelect.value;
                    const subcategoryId = subcategorySelect.value;
                    const severity = severitySelect.value;

                    if (categoryId && subcategoryId && severity) {
                        fetch(`/api/officers-by-sla/${categoryId}/${subcategoryId}/${severity}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.officers.length > 0) {
                                    updateOfficersList(data.officers);
                                } else {
                                    // Show all officers if no specific SLA officers found
                                    resetOfficersList();
                                }
                            })
                            .catch(error => {
                                console.error('Error loading SLA officers:', error);
                                resetOfficersList();
                            });
                    } else {
                        resetOfficersList();
                    }
                }

                // Function to update officers list
                function updateOfficersList(officers) {
                    if ($('#assigned_officers').data('select2')) {
                        $('#assigned_officers').empty();
                    }

                    officers.forEach(officer => {
                        const isCurrentUser = officer.id === {{ auth()->user()->id }};
                        const option = new Option(officer.nama, officer.id, false, isCurrentUser);
                        $(option).attr('data-current-user', isCurrentUser ? 'true' : 'false');
                        $('#assigned_officers').append(option);
                    });

                    if ($('#assigned_officers').data('select2')) {
                        $('#assigned_officers').trigger('change');
                    }
                }

                // Function to reset officers list to original state
                function resetOfficersList() {
                    if ($('#assigned_officers').data('select2')) {
                        $('#assigned_officers').empty();
                    }

                    // Restore original officers list
                    @foreach ($officers as $officer)
                        @php
                            $isCurrentUser = $officer->id === auth()->user()->id;
                        @endphp
                        const option{{ $officer->id }} = new Option('{{ $officer->nama }}', {{ $officer->id }},
                            false, {{ $isCurrentUser ? 'true' : 'false' }});
                        $(option{{ $officer->id }}).attr('data-current-user',
                            '{{ $isCurrentUser ? 'true' : 'false' }}');
                        $('#assigned_officers').append(option{{ $officer->id }});
                    @endforeach

                    if ($('#assigned_officers').data('select2')) {
                        $('#assigned_officers').trigger('change');
                    }
                }

                // Category change event
                categorySelect.addEventListener('change', function() {
                    const selectedCategoryId = this.value;

                    // Clear current subcategory options except the first one
                    subcategorySelect.innerHTML = '<option value="">--Pilih Sub Kategori--</option>';

                    if (selectedCategoryId) {
                        // Filter and add relevant subcategories
                        allSubcategoryOptions.forEach(option => {
                            if (option.dataset.catId === selectedCategoryId) {
                                subcategorySelect.appendChild(option.cloneNode(true));
                            }
                        });
                    } else {
                        // If no category selected, show all subcategories
                        allSubcategoryOptions.forEach(option => {
                            if (option.value !== '') {
                                subcategorySelect.appendChild(option.cloneNode(true));
                            }
                        });
                    }

                    // Clear severity and reset officers when category changes
                    severitySelect.innerHTML = '<option value="">--Pilih Tahap Keparahan--</option>';
                    resetOfficersList();
                });

                // Subcategory change event
                subcategorySelect.addEventListener('change', function() {
                    const categoryId = categorySelect.value;
                    const subcategoryId = this.value;
                    loadSLAData(categoryId, subcategoryId);
                });

                // Severity change event
                if (severitySelect) {
                    severitySelect.addEventListener('change', function() {
                        filterOfficersBySLA();
                    });
                }

                // Trigger category change on page load to filter subcategories
                if (categorySelect.value) {
                    categorySelect.dispatchEvent(new Event('change'));

                    // If subcategory is also selected, load SLA data
                    if (subcategorySelect.value) {
                        loadSLAData(categorySelect.value, subcategorySelect.value);
                    }
                }
            }

            // Initialize Select2 for assigned officers
            if ($('#assigned_officers').length) {
                $('#assigned_officers').select2({
                    placeholder: 'Pilih Pegawai Tugasan (boleh pilih lebih dari satu)',
                    width: '100%',
                    multiple: true,
                    templateResult: function(option) {
                        if (!option.id) {
                            return option.text;
                        }

                        var $option = $(option.element);
                        var isCurrentUser = $option.data('current-user') === 'true';

                        var $result = $('<div class="select2-result-option">').text(option.text);

                        if (isCurrentUser) {
                            $result.attr('data-current-user', 'true');
                        }

                        return $result;
                    },
                    templateSelection: function(option) {
                        if (!option.id) {
                            return option.text;
                        }

                        var $option = $(option.element);
                        var isCurrentUser = $option.data('current-user') === 'true';

                        var $result = $('<span>');
                        if (isCurrentUser) {
                            $result.addClass('text-white fw-bold').attr('data-current-user', 'true');
                        }
                        $result.text(option.text);

                        return $result;
                    }
                });

                // Add data attributes to Select2 result options after initialization
                $('#assigned_officers').on('select2:open', function() {
                    setTimeout(function() {
                        $('.select2-results__option').each(function() {
                            var optionId = $(this).attr('id');
                            if (optionId) {
                                var optionValue = optionId.split('-').pop();
                                var originalOption = $('#assigned_officers option[value="' +
                                    optionValue + '"]');
                                if (originalOption.length && originalOption.data(
                                        'current-user') === 'true') {
                                    $(this).attr('data-current-user', 'true');
                                    $(this).addClass('current-user-option');
                                }
                            }
                        });
                    }, 50);
                });

                // Handle styling of selected items (chips)
                $('#assigned_officers').on('select2:select', function(e) {
                    setTimeout(function() {
                        var selectedValue = e.params.data.id;
                        var originalOption = $('#assigned_officers option[value="' + selectedValue +
                            '"]');

                        if (originalOption.data('current-user') === 'true') {
                            // Find the newly created selection chip and style it
                            $('.select2-selection__choice').each(function() {
                                var choiceText = $(this).find(
                                    '.select2-selection__choice__display').text().trim();
                                var optionText = originalOption.text().trim();

                                if (choiceText === optionText) {
                                    $(this).addClass('current-user-choice');
                                    $(this).css({
                                        'background-color': '#28a745 !important',
                                        'color': 'white !important',
                                        'border-color': '#28a745 !important'
                                    });
                                }
                            });
                        }
                    }, 100);
                });
            }
        });
    </script>
@endsection
