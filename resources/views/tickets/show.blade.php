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

                            <hr class="my-3">

                            <!-- Organizational Information -->
                            @if ($ticket->user->sektor || $ticket->user->kod_sektor)
                                <div class="mb-3">
                                    <h6 class="text-primary mb-1">
                                        <i class="bx bx-buildings me-1"></i>Sektor
                                    </h6>
                                    <p class="mb-0 small">
                                        {{ $ticket->user->sektor->nama_sektor ?? ($ticket->user->kod_sektor ?? '-') }}
                                    </p>
                                    @if ($ticket->user->kod_sektor)
                                        <small class="text-muted">Kod: {{ $ticket->user->kod_sektor }}</small>
                                    @endif
                                </div>
                            @endif

                            @if ($ticket->user->bahagian || $ticket->user->kod_bah)
                                <div class="mb-3">
                                    <h6 class="text-primary mb-1">
                                        <i class="bx bx-sitemap me-1"></i>Bahagian
                                    </h6>
                                    <p class="mb-0 small">
                                        {{ $ticket->user->bahagian->nama_bah ?? ($ticket->user->kod_bah ?? '-') }}
                                    </p>
                                    @if ($ticket->user->kod_bah)
                                        <small class="text-muted">Kod: {{ $ticket->user->kod_bah }}</small>
                                    @endif
                                </div>
                            @endif

                            @if ($ticket->user->seksyen || $ticket->user->kod_sec)
                                <div class="mb-3">
                                    <h6 class="text-primary mb-1">
                                        <i class="bx bx-git-branch me-1"></i>Seksyen
                                    </h6>
                                    <p class="mb-0 small">
                                        {{ $ticket->user->seksyen->nama_sec ?? ($ticket->user->kod_sec ?? '-') }}
                                    </p>
                                    @if ($ticket->user->kod_sec)
                                        <small class="text-muted">Kod: {{ $ticket->user->kod_sec }}</small>
                                    @endif
                                </div>
                            @endif

                            @if ($ticket->user->unit || $ticket->user->kod_unit)
                                <div class="mb-3">
                                    <h6 class="text-primary mb-1">
                                        <i class="bx bx-group me-1"></i>Unit
                                    </h6>
                                    <p class="mb-0 small">
                                        {{ $ticket->user->unit->nama_unit ?? ($ticket->user->kod_unit ?? '-') }}
                                    </p>
                                    @if ($ticket->user->kod_unit)
                                        <small class="text-muted">Kod: {{ $ticket->user->kod_unit }}</small>
                                    @endif
                                </div>
                            @endif

                            <hr class="my-3">

                            <!-- Creator Information -->
                            <div class="mb-3">
                                <h6 class="text-secondary mb-1">
                                    <i class="bx bx-user-plus me-1"></i>Dicipta Oleh
                                </h6>
                                <p class="mb-0 small">
                                    {{ $ticket->createdBy->nama ?? 'Sistem' }}
                                </p>
                                <small class="text-muted">
                                    {{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : '-' }}
                                </small>
                            </div>

                            @if ($ticket->updatedBy && $ticket->updated_at != $ticket->created_at)
                                <div class="mb-3">
                                    <h6 class="text-secondary mb-1">
                                        <i class="bx bx-edit me-1"></i>Dikemaskini Oleh
                                    </h6>
                                    <p class="mb-0 small">
                                        {{ $ticket->updatedBy->nama ?? 'Sistem' }}
                                    </p>
                                    <small class="text-muted">
                                        {{ $ticket->updated_at ? $ticket->updated_at->format('d/m/Y H:i:s') : '-' }}
                                    </small>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-user-x display-4 text-muted"></i>
                                <p class="text-muted mt-2">Maklumat pengguna tidak tersedia.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
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

                                    // Set status badge color based on status
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
            </div>

            <!-- Sidebar -->
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
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <div
                                                class="timeline-marker-icon {{ $index == 0 ? 'bg-primary' : 'bg-secondary' }}">
                                                @if ($index == 0)
                                                    <i class="bx bx-plus"></i>
                                                @else
                                                    <i class="bx bx-edit"></i>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">{{ $history['action'] }}</h6>
                                            <p class="timeline-text">{{ $history['description'] }}</p>

                                            @if (!empty($history['details']))
                                                <div class="timeline-details">
                                                    @foreach ($history['details'] as $key => $value)
                                                        <small class="text-muted d-block">
                                                            <strong>{{ $key }}:</strong> {{ $value }}
                                                        </small>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <small class="timeline-time text-muted">
                                                <i class="bx bx-time"></i>
                                                {{ $history['timestamp']->format('d/m/Y H:i:s') }}
                                                oleh {{ $history['user'] }}
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-info-circle display-4 text-muted"></i>
                                <p class="text-muted mt-2">Tiada kronologi tersedia buat masa ini.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 3rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e3e6f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-marker {
            position: absolute;
            left: -3rem;
            top: 0;
        }

        .timeline-marker-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e3e6f0;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border-left: 4px solid #e3e6f0;
        }

        .timeline-title {
            margin-bottom: 0.5rem;
            color: #5a5c69;
        }

        .timeline-text {
            margin-bottom: 1rem;
            color: #6c757d;
        }

        .timeline-details {
            background: white;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid #e3e6f0;
        }

        .timeline-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        }
    </style>
@endsection
