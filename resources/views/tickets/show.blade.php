@extends('layouts.layoutMaster')

@section('title', 'Lihat Tiket - ' . $ticket->ticket_nolog)

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Maklumat Penuh Tiket</h4>
                        <p class="mb-0 text-muted">{{ $ticket->ticket_nolog }}</p>
                    </div>
                    <div>
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Kembali ke Senarai
                        </a>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bx bx-printer me-1"></i>Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Header -->
            <div class="col-12">
                <!-- Maklumat Pengadu -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-user me-2"></i>
                            @if ($ticket->ticket_type == 1)
                                Maklumat Pengadu
                            @else
                                Maklumat Bagi Pihak
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
            <div class="col-xl-8 col-lg-7 col-md-7">
                <!-- Maklumat Aduan Tiket -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-file-blank me-2"></i>Maklumat Aduan Tiket
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <h6 class="mb-0">No. Log Tiket</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <span class="badge bg-primary fs-6">{{ $ticket->ticket_nolog }}</span>
                            </div>
                        </div>
                        <hr>

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
                                <h6 class="mb-0">Penerangan Aduan</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                <div class="bg-light p-3 rounded">
                                    {!! nl2br(e($ticket->ticket_desc)) !!}
                                </div>
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
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Jenis Tiket</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                @if ($ticket->ticket_type == 1)
                                    <span class="badge bg-info">Diri Sendiri</span>
                                @else
                                    <span class="badge bg-warning">Bagi Pihak</span>
                                @endif
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Status</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                @php
                                    $statusClass = 'bg-secondary';
                                    $statusName = $ticket->status->st_name ?? 'Tidak Diketahui';

                                    // Status color mapping to match timeline system
                                    switch ($statusName) {
                                        case 'Aduan Baru':
                                            $statusClass = 'bg-primary'; // Biru
                                            break;
                                        case 'Aduan Diproses':
                                            $statusClass = 'bg-warning'; // Orange
                                            break;
                                        case 'Serahan Kepada':
                                            $statusClass = 'bg-purple'; // Purple
                                            break;
                                        case 'Aduan Dirujuk':
                                            $statusClass = 'bg-danger'; // Merah
                                            break;
                                        case 'Aduan DiBatal':
                                            $statusClass = 'bg-secondary'; // Kelabu
                                            break;
                                        case 'Aduan Selesai':
                                            $statusClass = 'bg-success'; // Hijau
                                            break;
                                        default:
                                            // Fallback for other status names
                                            switch (strtolower($statusName)) {
                                                case 'open':
                                                case 'baru':
                                                case 'dibuka':
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case 'in progress':
                                                case 'dalam proses':
                                                case 'sedang diproses':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'resolved':
                                                case 'selesai':
                                                case 'diselesaikan':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'closed':
                                                case 'ditutup':
                                                    $statusClass = 'bg-dark';
                                                    break;
                                                case 'cancelled':
                                                case 'dibatalkan':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                            }
                                    }
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $statusName }}</span>
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-sm-3">
                                <h6 class="mb-0">Tarikh Dicipta</h6>
                            </div>
                            <div class="col-sm-9 text-secondary">
                                {{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : '-' }}
                            </div>
                        </div>

                        @if ($ticket->tick_duedate)
                            <hr>
                            <div class="row">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Tarikh Due</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    {{ $ticket->tick_duedate->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Maklumat Tindakan Pegawai (View for Administrator|Pentadbir Sistem|Penyelia Helpdesk|Helpdesk On Duty|Pegawai Tugasan) -->
                @can('log-tiket.edit')
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
                                    <select class="form-select">
                                        <option selected>{{ $ticket->status->st_name ?? 'Tidak Diketahui' }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Kategori</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <select class="form-select">
                                        <option selected>{{ $ticket->category->cat_name ?? '-' }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Sub Kategori</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <select class="form-select">
                                        <option selected>{{ $ticket->subCategory->subcat_name ?? '-' }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Severity Level</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <select class="form-select">
                                        <option selected></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>

            <!-- Right Column - Sidebar -->
            <div class="col-xl-4 col-lg-5 col-md-5">
                <!-- Kronologi Tiket -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-time me-2"></i>Kronologi Tiket
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
                                                $colorClass = 'bg-purple'; // Purple
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
                                                <span
                                                    class="badge {{ $colorClass }} badge-sm">{{ $statusName }}</span>
                                            </div>
                                            <p class="timeline-text">
                                                {{ $history['description'] ?? 'Tiada penerangan tersedia.' }}</p>
                                            <small class="timeline-time">
                                                <i class="bx bx-user"></i>
                                                Oleh: {{ $history['user'] ?? 'Sistem' }}<br>
                                            </small>
                                            <small class="timeline-time">
                                                <i class="bx bx-time"></i>
                                                Tarikh:
                                                {{ isset($history['timestamp']) ? $history['timestamp']->format('d/m/Y, H:i a') : now()->format('d/m/Y, H:i a') }}
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

        /* Badge colors to match timeline markers */
        .badge.bg-purple {
            background: linear-gradient(135deg, #8e44ad, #6f42c1) !important;
            color: white;
        }

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
    </style>
@endsection
