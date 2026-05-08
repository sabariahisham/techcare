@extends('layouts.layoutMaster')

@section('title', 'Lihat Tiket - ' . $ticket->ticket_nolog)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/js/quill-editor.js'])
    <style>
        /* Custom styling for disabled dropdowns */
        select.form-select.disabled,
        select.form-select:disabled {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.8 !important;
        }

        select.form-select.disabled:focus,
        select.form-select:disabled:focus {
            border-color: #ced4da !important;
            box-shadow: none !important;
        }

        /* Ensure disabled state shows clearly */
        .form-select[disabled] option,
        .form-select[readonly] option {
            color: #6c757d !important;
        }

        /* Custom styling for current user in Select2 */
        .select2-container .select2-results__option .current-user {
            background-color: #28a745 !important;
            color: white !important;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 4px;
        }

        .select2-container .select2-results__option:hover .current-user {
            background-color: #218838 !important;
            color: white !important;
        }

        /* Selected current user styling */
        .select2-container .select2-selection__choice .current-user {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
            padding: 2px 8px;
            border-radius: 3px;
        }

        /* Alternative approach - direct styling for current user */
        .select2-container .current-user-option {
            background-color: #28a745 !important;
            color: white !important;
        }

        .select2-container .current-user-option:hover {
            background-color: #218838 !important;
            color: white !important;
        }

        /* Location info alignment */
        .location-info .d-flex {
            margin-bottom: 0.25rem;
        }

        .location-label {
            display: inline-block;
            min-width: 90px;
            font-weight: 500;
        }

        .location-value {
            margin-left: 0.5rem;
        }

        /* Notification badge animation for new comments */
        @keyframes pulse-badge {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        /* Badge shake animation on hover */
        .btn:hover .badge {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-3px);
            }

            75% {
                transform: translateX(3px);
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-x-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error me-2"></i>
            <strong>Ralat!</strong> Sila betulkan ralat berikut:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Maklumat Penuh Tiket</h4>
                    <p class="mb-0 text-muted">{{ $ticket->ticket_nolog }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
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
                    <small class="text-muted fst-italic d-block">                        
                        Data dalam ruangan ini diselaraskan daripada <strong>Sistem Pergerakan Pegawai (SPP)</strong>.
                        Sebarang kemas kini hendaklah dibuat melalui sistem tersebut.
                    </small>
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
                                <div class="row mb-3">
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
                                <div class="row">
                                    <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                        <h6 class="text-primary mb-1">
                                            <i class="bx bx-phone me-1"></i>Telefon Pejabat
                                        </h6>
                                        <p class="mb-0 small">
                                            {{ $ticket->user->tel_no_p ?: 'Tiada Rekod' }}
                                        </p>
                                    </div>
                                    <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                        <h6 class="text-primary mb-1">
                                            <i class="bx bx-mobile me-1"></i>Telefon Bimbit
                                        </h6>
                                        <p class="mb-0 small">
                                            {{ $ticket->user->tel_no_hp ?: 'Tiada Rekod' }}
                                        </p>
                                    </div>

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
        <div class="col-xl-7 col-lg-7 col-md-6">
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
                        <div class="col-sm-4">
                            <h6 class="mb-0">Tajuk Aduan</h6>
                        </div>
                        <div class="col-sm-8 text-secondary">
                            {{ $ticket->ticket_title }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4">
                            <h6 class="mb-0">Kategori</h6>
                        </div>
                        <div class="col-sm-8 text-secondary">
                            {{ $ticket->category->cat_name ?? '-' }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4">
                            <h6 class="mb-0">Sub Kategori</h6>
                        </div>
                        <div class="col-sm-8 text-secondary">
                            {{ $ticket->subCategory->subcat_name ?? '-' }}
                        </div>
                    </div>
                    @if ($ticket->category && $ticket->category->cat_name === 'Sewaan')
                        <hr>
                        <div class="row">
                            <div class="col-sm-4">
                                <h6 class="mb-0">No. Siri <br> <small>(Sewaan)</small></h6>
                            </div>
                            <div class="col-sm-8 text-secondary">
                                {{ $ticket->serial_number ?? '-' }}
                            </div>
                        </div>
                    @endif
                    <hr>
                    <div class="row">
                        <div class="col-sm-4">
                            <h6 class="mb-0">Lokasi</h6>
                        </div>
                        <div class="col-sm-8 text-secondary">
                            @if ($location)
                                @php
                                    // Mapping for building codes
                                    $buildingMap = [
                                        'P1' => 'Perbendaharaan 1',
                                        'P2' => 'Perbendaharaan 2',
                                    ];

                                    // Mapping for block codes
                                    $blockMap = [
                                        'U' => 'Utara',
                                        'TU' => 'Tengah-Utara',
                                        'T' => 'Tengah',
                                        'TS' => 'Tengah-Selatan',
                                        'S' => 'Selatan',
                                    ];
                                @endphp
                                Aras {{ $location->level_loc ?? '-' }}
                                {{ $location->block_loc ? ', Blok ' . ($blockMap[$location->block_loc] ?? $location->block_loc) : '' }}
                                {{ $location->building_loc ? ', ' . ($buildingMap[$location->building_loc] ?? $location->building_loc) : '' }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4">
                            <h6 class="mb-0">Keterangan Aduan</h6>
                        </div>
                        <div class="col-sm-8 text-secondary">
                            {!! nl2br(e(ucfirst($ticket->ticket_desc))) !!}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4">
                            <h6 class="mb-0">Lampiran</h6>
                        </div>
                        <div class="col-sm-8">
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
                                                <a href="{{ route('ticket-attachments.download', ['uuid' => $attachment->uuid, 'view' => 1]) }}"
                                                    class="btn btn-sm btn-outline-info me-1" data-bs-toggle="tooltip"
                                                    title="Lihat {{ $attachment->doc_name }}" target="_blank">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('ticket-attachments.download', $attachment->uuid) }}"
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
                            @if ($ticket->user_id == auth()->user()->id)
                                <button type="button" class="btn btn-warning btn-sm"
                                    onclick="$('#commentModal').modal('show')">
                                    <i class="bx bx-message me-1"></i>Komen
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="$('#commentModal').modal('show')" disabled>
                                    <i class="bx bx-message me-1"></i>Komen
                                </button>
                            @endif
                            <button type="button" class="btn btn-primary btn-sm float-end"
                                onclick="$('#actionComment').modal('show')">
                                <i class="bx bx-save me-1"></i>Catatan Tindakan
                                @if (isset($newCommentsCount) && $newCommentsCount > 0)
                                    <span class="badge bg-danger ms-2 position-relative"
                                        style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 10px; animation: pulse-badge 2s infinite;">
                                        {{ $newCommentsCount > 9 ? '9+' : $newCommentsCount }}
                                    </span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Get currently assigned officers from latest chronologies for permission check
                $assignedOfficerIdsForPermission = $ticket
                    ->chronologies()
                    ->whereNotNull('escalate_to')
                    ->where('tick_status', $ticket->tick_status)
                    ->latest()
                    ->pluck('escalate_to')
                    ->unique()
                    ->toArray();

                // Check if user can see this section
                $canSeeTindakanPegawai = false;

                // Admins, Pentadbir Sistem, Penyelia Helpdesk, and Helpdesk On Duty can always see
                if (
                    auth()
                        ->user()
                        ->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty'])
                ) {
                    $canSeeTindakanPegawai = true;
                }
                // Pegawai Tugasan can only see if they are assigned to this ticket
                elseif (
                    auth()->user()->hasRole('Pegawai Tugasan') &&
                    in_array(auth()->user()->id, $assignedOfficerIdsForPermission)
                ) {
                    $canSeeTindakanPegawai = true;
                }

                // Check if current ticket status is one of the final statuses (read-only)
                // Status 4 (Aduan Dirujuk) - EDITABLE for assigned officer
                // Status 5 (Aduan Dibatal), Status 6 (Aduan Selesai) - READ ONLY
                $isReadOnlyView = false;
                $lastChronologyUpdate = null;

                // Get the latest chronology entry for current status
                $lastChronologyUpdate = $ticket
                    ->chronologies()
                    ->where('tick_status', $ticket->tick_status)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // If no chronology for current status or no severity, get the latest one with severity
                if (!$lastChronologyUpdate || !$lastChronologyUpdate->sla_severity) {
                    $lastChronologyUpdate = $ticket
                        ->chronologies()
                        ->whereNotNull('sla_severity')
                        ->orderBy('created_at', 'desc')
                        ->first();
                }

                // Set read-only view ONLY for Dibatal(5) and Selesai(6)
                // For Dirujuk(4), assigned officers should be able to edit
                if (in_array($ticket->tick_status, [5, 6])) {
                    $isReadOnlyView = true;
                }
            @endphp

            @if ($canSeeTindakanPegawai)
                <div class="card mb-4">
                    <div class="card-header {{ $isReadOnlyView ? 'bg-label-secondary' : '' }} mb-4">

                        <div class="d-flex align-items-start">
                            <i class="bx {{ $isReadOnlyView ? 'bx-lock-alt' : 'bx-cog' }} fs-4 me-3 mt-1"></i>

                            <div>
                                <h5 class="card-title mb-1">
                                    {{ $isReadOnlyView ? 'Maklumat Tindakan (Telah Dihantar)' : 'Maklumat Tindakan Pegawai' }}
                                </h5>

                                @if ($isReadOnlyView)
                                    <small class="text-secondary d-block">
                                        Paparan ini adalah untuk rujukan bagi status Aduan Dibatal atau Aduan Selesai.
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body mt-3">
                        <form id="updateTicketForm" action="{{ route('ticket-assignments.update', $ticket->uuid) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Status Tiket</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <select class="form-select" name="tick_status" id="tick_status" required
                                        {{ $isReadOnlyView ? 'disabled' : '' }}>
                                        <option value="">Pilih Status</option>
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
                                    <select class="form-select" name="cat_id" id="cat_id"
                                        {{ $isReadOnlyView ? 'disabled' : '' }}>
                                        <option value="">Pilih Kategori</option>
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
                                    <select class="form-select" name="subcat_id" id="subcat_id"
                                        {{ $isReadOnlyView ? 'disabled' : '' }}>
                                        <option value="">Pilih Sub Kategori</option>
                                        @foreach ($allSubCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}"
                                                data-cat-id="{{ $subCategory->cat_id }}"
                                                {{ $ticket->subcat_id == $subCategory->id ? 'selected' : '' }}>
                                                {{ $subCategory->subcat_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Tahap Severity</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    @if ($isReadOnlyView && $lastChronologyUpdate)
                                        {{-- Read-only view: Display severity level --}}
                                        @php
                                            $severityValue = $lastChronologyUpdate->sla_severity ?? '-';
                                            $severityBadgeClass = 'secondary';
                                            if ($severityValue === 'High') {
                                                $severityBadgeClass = 'danger';
                                            } elseif ($severityValue === 'Medium') {
                                                $severityBadgeClass = 'warning';
                                            } elseif ($severityValue === 'Low') {
                                                $severityBadgeClass = 'success';
                                            }
                                        @endphp
                                        <div class="alert alert-{{ $severityBadgeClass }} mb-0">
                                            <i class="bx bx-error-circle me-1"></i>
                                            <strong>{{ $severityValue }}</strong>
                                        </div>
                                    @else
                                        {{-- Editable view: Show dropdown with pre-selected value --}}
                                        @php
                                            // Get current severity from last chronology or ticket
                                            $currentSeverity = $lastChronologyUpdate->sla_severity ?? null;
                                        @endphp
                                        <select class="form-select" name="sla_severity" id="sla_severity">
                                            <option value="">Pilih Tahap Severity</option>
                                            <option value="High" {{ $currentSeverity === 'High' ? 'selected' : '' }}>
                                                High</option>
                                            <option value="Medium" {{ $currentSeverity === 'Medium' ? 'selected' : '' }}>
                                                Medium</option>
                                            <option value="Low" {{ $currentSeverity === 'Low' ? 'selected' : '' }}>Low
                                            </option>
                                        </select>

                                        <small class="text-muted d-block mt-1">Tahap severity akan dipaparkan berdasarkan
                                            kategori dan sub kategori yang dipilih.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Pegawai Tugasan</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    @if ($isReadOnlyView)
                                        {{-- Read-only view: Display assigned officers --}}
                                        @php
                                            $displayOfficers = $ticket
                                                ->chronologies()
                                                ->whereNotNull('escalate_to')
                                                ->where('tick_status', $ticket->tick_status)
                                                ->with('escalatedTo')
                                                ->get()
                                                ->pluck('escalatedTo.nama')
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        @endphp
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bx bx-user-check me-1"></i>
                                            @if ($displayOfficers->count() > 0)
                                                {{ $displayOfficers->implode(', ') }}
                                            @else
                                                Tiada pegawai ditugaskan
                                            @endif
                                        </div>
                                    @else
                                        {{-- Editable view: Show multi-select dropdown --}}
                                        <select class="form-select" name="assigned_officers[]" id="assigned_officers"
                                            multiple>
                                            @php
                                                // Get currently assigned officers from latest chronologies
                                                $assignedOfficerIds = $ticket
                                                    ->chronologies()
                                                    ->whereNotNull('escalate_to')
                                                    ->where('tick_status', $ticket->tick_status)
                                                    ->latest()
                                                    ->pluck('escalate_to')
                                                    ->unique()
                                                    ->toArray();

                                                // Keep original assigned officers for dropdown selection
                                                // Don't add current user as fallback

// Get currently assigned officers and their levels
$currentAssignedOfficerData = \Illuminate\Support\Facades\DB::table(
    'officers',
)
    ->whereIn('user_id', $assignedOfficerIds)
    ->whereNull('deleted_at')
    ->get(['user_id', 'level_tugasan']);

// Determine what level officers to show based on assigned officers
$showLevelTugasan = null; // Default to show all

// If any assigned officer has level 1, show level 2 officers
foreach ($currentAssignedOfficerData as $officerData) {
    if ($officerData->level_tugasan == 1) {
        $showLevelTugasan = 2;
        break;
    }
}

// Get current ticket category and subcategory
$currentCatId = $ticket->cat_id;
$currentSubcatId = $ticket->subcat_id;

// Get officers from officers table with level, category and subcategory filtering
$officersQuery = \Illuminate\Support\Facades\DB::table('officers')
    ->join('users', 'officers.user_id', '=', 'users.id')
    ->whereNull('officers.deleted_at');

// Filter by category and subcategory if available
if ($currentCatId) {
    $officersQuery->where('officers.cat_id', $currentCatId);
}
if ($currentSubcatId) {
    $officersQuery->where('officers.subcat_id', $currentSubcatId);
}

// Filter by level if required
if ($showLevelTugasan !== null) {
    $officersQuery->where('officers.level_tugasan', $showLevelTugasan);
}

$filteredOfficers = $officersQuery
    ->select('users.id', 'users.nama', 'users.email')
    ->distinct()
    ->orderBy('users.nama')
    ->get();

// IMPORTANT: Always include the logged-in user in the dropdown (with green tag)
// even if they don't match the category/subcategory filter
                                                $currentUserId = auth()->user()->id;
                                                $currentUserExists = $filteredOfficers->contains('id', $currentUserId);

                                                if (!$currentUserExists) {
                                                    // Add current user to the list
                                                    $currentUser = (object) [
                                                        'id' => auth()->user()->id,
                                                        'nama' => auth()->user()->nama,
                                                        'email' => auth()->user()->email,
                                                    ];
                                                    $filteredOfficers->prepend($currentUser);
                                                }
                                            @endphp
                                            @foreach ($filteredOfficers as $officer)
                                                <option value="{{ $officer->id }}"
                                                    data-current-user="{{ $officer->id === auth()->user()->id ? 'true' : 'false' }}"
                                                    {{ in_array($officer->id, $assignedOfficerIds) ? 'selected' : '' }}>
                                                    {{ $officer->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Tarikh Tindakan</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    @if ($isReadOnlyView && $lastChronologyUpdate)
                                        {{-- Read-only view: Display submitted date --}}
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bx bx-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($lastChronologyUpdate->created_at)->format('d/m/Y, H:i') }}
                                        </div>
                                    @else
                                        {{-- Editable view: Show datetime input --}}
                                        <input type="datetime-local" class="form-control" name="action_date"
                                            id="action_date" value="{{ now()->format('Y-m-d\TH:i') }}" />
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Tindakan Oleh</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    @php
                                        // Display who actually performed the action (from chronology frm_user_id)
                                        $actionByText = '-';

                                        if (
                                            $isReadOnlyView &&
                                            $lastChronologyUpdate &&
                                            $lastChronologyUpdate->fromUser
                                        ) {
                                            // For read-only view: Show who performed the last action
                                            $actionByText = $lastChronologyUpdate->fromUser->nama;
                                        } else {
                                            // For editable view: Show currently assigned officers
                                            $assignedOfficerNames = $ticket
                                                ->chronologies()
                                                ->whereNotNull('escalate_to')
                                                ->where('tick_status', $ticket->tick_status)
                                                ->with('escalatedTo')
                                                ->get()
                                                ->pluck('escalatedTo.nama')
                                                ->filter()
                                                ->unique()
                                                ->values()
                                                ->toArray();

                                            $actionByText = !empty($assignedOfficerNames)
                                                ? implode(', ', $assignedOfficerNames)
                                                : '-';
                                        }
                                    @endphp
                                    @if ($isReadOnlyView)
                                        <div class="alert alert-secondary mb-0">
                                            <i class="bx bx-user me-1"></i>
                                            {{ $actionByText }}
                                        </div>
                                    @else
                                        <span id="action_by_display">{{ $actionByText }}</span>
                                        <input type="hidden" name="action_by" id="action_by"
                                            value="{{ auth()->user()->id }}" />
                                    @endif
                                </div>
                            </div>
                            @if (!$isReadOnlyView)
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Catatan Tindakan Yang Diambil</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <textarea class="form-control" name="notes" id="notes" rows="3"
                                            placeholder="Masukkan catatan tindakan tambahan (pilihan)"></textarea>
                                    </div>
                                </div>
                            @endif
                            <hr>
                            @if (!$isReadOnlyView)
                                <div class="row mt-6 mb-1">
                                    <div class="col-sm">
                                        {{-- <button type="button" class="btn btn-warning btn-sm "
                                            onclick="$('#commentModal').modal('show')">
                                            <i class="bx bx-message me-3"></i>Komen
                                        </button> --}}
                                        <button type="submit" class="btn btn-primary btn-sm float-end">
                                            <i class="bx bx-save me-1"></i>Kemaskini
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm float-end me-2"
                                            onclick="cancelUpdate()">
                                            <i class="bx bx-x me-1"></i>Batal
                                        </button>
                                    </div>
                                </div>
                            @endif
                            @if ($isReadOnlyView)
                                <div class="d-flex justify-content-center align-items-center mt-2 text-secondary">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <small class="text-center fst-italic">
                                        Tindakan telah berjaya direkodkan dan sedang menunggu tindakan seterusnya.
                                    </small>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <!-- Center Column - Kronologi -->
        <div class="col-xl-5 col-lg-5 col-md-6">
            <!-- Kronologi Tiket -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-time me-2"></i>Kronologi Tiket
                    </h5>
                </div>
                <div class="card-body">
                    @if ($ticket->status || $ticket->chronologies->count() > 0)
                        <div class="timeline">
                            @php
                                // Get all chronologies for this ticket ordered by date (ascending for proper flow)
                                $allChronologies = $ticket
                                    ->chronologies()
                                    ->with(['status', 'createdBy', 'escalatedTo'])
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                // Check if "Aduan Baru" exists in chronologies
                                $hasAduanBaru = $allChronologies->contains(function ($chron) {
                                    return $chron->status && $chron->status->st_name === 'Aduan Baru';
                                });

                                // Function to get status description
                                $getStatusDescription = function ($statusName) {
                                    switch ($statusName) {
                                        case 'Aduan Baru':
                                            return 'Tiket di log dalam sistem.';
                                        case 'Aduan Diproses':
                                            return 'Tiket sedang dalam proses tindakan.';
                                        case 'Serahan Kepada':
                                            return 'Tiket diserahkan untuk tindakan lanjut.';
                                        case 'Aduan Dirujuk':
                                            return 'Tiket telah dirujuk kepada pihak berkaitan.';
                                        case 'Aduan DiBatal':
                                            return 'Tiket telah dibatalkan dan tidak akan diproses.';
                                        case 'Aduan Selesai':
                                            return 'Tiket berjaya diselesai dan ditutup.';
                                        default:
                                            return 'Status tiket dikemaskini.';
                                    }
                                };

                                // Function to get status styling
                                $getStatusStyling = function ($statusName) {
                                    switch ($statusName) {
                                        case 'Aduan Baru':
                                            return ['icon' => 'bx-plus-circle', 'color' => 'bg-primary'];
                                        case 'Aduan Diproses':
                                            return ['icon' => 'bx-cog', 'color' => 'bg-warning'];
                                        case 'Serahan Kepada':
                                            return ['icon' => 'bx-user-plus', 'color' => 'bg-info'];
                                        case 'Aduan Dirujuk':
                                            return ['icon' => 'bx-share-alt', 'color' => 'bg-danger'];
                                        case 'Aduan DiBatal':
                                            return ['icon' => 'bx-x-circle', 'color' => 'bg-secondary'];
                                        case 'Aduan Selesai':
                                            return ['icon' => 'bx-check-circle', 'color' => 'bg-success'];
                                        default:
                                            return ['icon' => 'bx-edit', 'color' => 'bg-secondary'];
                                    }
                                };

                                // Function to get action verb
                                $getActionVerb = function ($statusName) {
                                    switch ($statusName) {
                                        case 'Aduan Baru':
                                            return 'Direkod (Log) oleh:';
                                        case 'Aduan Diproses':
                                            return 'Diproses oleh:';
                                        case 'Serahan Kepada':
                                            return 'Diserahkan oleh:';
                                        case 'Aduan Dirujuk':
                                            return 'Dirujuk oleh:';
                                        case 'Aduan DiBatal':
                                            return 'Dibatalkan oleh:';
                                        case 'Aduan Selesai':
                                            return 'Diselesaikan oleh:';
                                        default:
                                            return 'Dikemaskini oleh:';
                                    }
                                };

                                // Build complete timeline array starting with "Aduan Baru"
                                $timelineItems = collect();

                                // Always add "Aduan Baru" as first item if it doesn't exist
if (!$hasAduanBaru) {
    // Get all assigned officers from chronologies for Aduan Baru
    $initialAssignedOfficers = $ticket
        ->chronologies()
        ->with('escalatedTo')
        ->whereNotNull('escalate_to')
        ->where('tick_status', 1)
        ->orderBy('created_at', 'asc')
        ->get()
        ->pluck('escalatedTo.nama')
        ->filter()
        ->unique()
        ->values();

    $timelineItems->push(
        (object) [
            'status_name' => 'Aduan Baru',
            'description' => $getStatusDescription('Aduan Baru'),
            'created_by' => $ticket->user ? $ticket->user->nama : 'Pengguna',
            'created_at' => $ticket->created_at,
            'escalated_to_list' => $initialAssignedOfficers->toArray(),
            'escalated_to' =>
                $initialAssignedOfficers->isNotEmpty()
                    ? $initialAssignedOfficers->implode(', ')
                    : null,
            'is_current' => false,
            'is_initial' => true,
        ],
    );
}

// Add chronological entries (group records with same status and timestamp)
$groupedChronologies = $allChronologies
    ->filter(function ($chron) {
        return $chron->status !== null;
    })
    ->groupBy(function ($chron) {
        // Group by status + exact timestamp + actor, so multi-assignment appears in one status card
        return $chron->tick_status .
            '|' .
            $chron->created_at->format('Y-m-d H:i:s') .
            '|' .
            ($chron->created_by ?? 'system');
    });

foreach ($groupedChronologies as $chronologyGroup) {
    $firstChron = $chronologyGroup->first();

    // Get new category and subcategory info if this is "Aduan Dirujuk"
    $newCategoryName = null;
    $newSubcategoryName = null;

    if ($firstChron->status->st_name === 'Aduan Dirujuk') {
        // For "Aduan Dirujuk", get the current category and subcategory from the ticket at that chronology point
        // These represent the NEW category/subcategory after referral
        if ($ticket->cat_id) {
            $newCat = \Illuminate\Support\Facades\DB::table('ref_categories')
                ->where('id', $ticket->cat_id)
                ->first(['cat_name']);
            $newCategoryName = $newCat ? $newCat->cat_name : null;
        }
        if ($ticket->subcat_id) {
            $newSubcat = \Illuminate\Support\Facades\DB::table('ref_sub_categories')
                ->where('id', $ticket->subcat_id)
                ->first(['subcat_name']);
            $newSubcategoryName = $newSubcat ? $newSubcat->subcat_name : null;
        }
    }

    $assignedOfficerNames = $chronologyGroup
        ->pluck('escalatedTo.nama')
        ->filter()
        ->unique()
        ->values();

    $timelineItems->push(
        (object) [
            'status_name' => $firstChron->status->st_name,
            'description' => $getStatusDescription($firstChron->status->st_name),
            'created_by' => $firstChron->createdBy ? $firstChron->createdBy->nama : 'Sistem',
            'created_at' => $firstChron->created_at,
            'escalated_to_list' => $assignedOfficerNames->toArray(),
            'escalated_to' => $assignedOfficerNames->isNotEmpty()
                ? $assignedOfficerNames->implode(', ')
                : null,
            'new_category_name' => $newCategoryName,
            'new_subcategory_name' => $newSubcategoryName,
            'is_current' => $firstChron->tick_status == $ticket->tick_status,
            'is_initial' => false,
        ],
    );
}

// Show all chronology records from database without deduplication
// Keep latest updates on top.
$timelineItems = $timelineItems->sortByDesc('created_at')->values();
                            @endphp

                            {{-- Display complete timeline from oldest to newest (Aduan Baru to current status) --}}
                            @foreach ($timelineItems as $index => $item)
                                @php
                                    $styling = $getStatusStyling($item->status_name);
                                    $isFirst = $index === 0; // First item is now "Aduan Baru"
                                    $isLast = $index === count($timelineItems) - 1; // Last item is current status
                                @endphp

                                <div
                                    class="timeline-item {{ $item->is_current ? 'current-status' : '' }} {{ $isFirst ? 'first-item' : '' }} {{ $isLast ? 'last-item' : '' }}">
                                    <div class="timeline-marker">
                                        <div
                                            class="timeline-marker-icon {{ $styling['color'] }} {{ $item->is_current ? 'current-marker' : '' }}">
                                            <i class="bx {{ $styling['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content {{ $item->is_current ? 'current-content' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="timeline-title mb-0">
                                                @if ($item->is_current)
                                                    <span class="text-muted small">(Semasa)</span>
                                                @elseif ($item->is_initial)
                                                    <span class="text-muted small">(Permulaan)</span>
                                                @endif
                                            </h6>
                                            <span class="badge {{ $styling['color'] }} badge-sm">
                                                {{ $item->status_name }}
                                            </span>
                                        </div>
                                        <p class="timeline-text">{{ $item->description }}</p>

                                        {{-- Show escalation/assignment info --}}
                                        @if (!empty($item->escalated_to_list) || $item->escalated_to)
                                            <div class="timeline-details mb-2">
                                                <small class="timeline-time d-block mb-1">
                                                    <i class="bx bx-user-plus text-success"></i>
                                                    <strong>{{ $item->status_name === 'Aduan Dirujuk' ? 'Dirujuk kepada:' : 'Diserahkan kepada:' }}</strong>
                                                </small>
                                                @if (!empty($item->escalated_to_list))
                                                    <ul class="mb-0 ps-6">
                                                        @foreach ($item->escalated_to_list as $officerName)
                                                            <li class="text-success fw-bold">{{ $officerName }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <ul class="mb-0 ps-6">
                                                        <li class="text-success fw-bold">{{ $item->escalated_to }}</li>
                                                    </ul>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Show action details --}}
                                        <div class="timeline-meta">
                                            <small class="timeline-time d-block mb-1">
                                                <i class="bx bx-user text-primary"></i>
                                                <strong>{{ $getActionVerb($item->status_name) }}</strong>
                                                <span class="text-primary">{{ $item->created_by }}</span>
                                            </small>

                                            {{-- Show new category and subcategory info for Aduan Dirujuk --}}
                                            @if ($item->status_name === 'Aduan Dirujuk')
                                                @if ($item->new_category_name)
                                                    <small class="timeline-time d-block mb-1">
                                                        <i class="bx bx-tag text-warning"></i>
                                                        <strong>Kategori Baharu:</strong>
                                                        <span
                                                            class="text-warning fw-bold">{{ $item->new_category_name }}</span>
                                                    </small>
                                                @endif
                                                @if ($item->new_subcategory_name)
                                                    <small class="timeline-time d-block mb-1">
                                                        <i class="bx bx-tag text-info"></i>
                                                        <strong>Subkategori Baharu:</strong>
                                                        <span
                                                            class="text-info fw-bold">{{ $item->new_subcategory_name }}</span>
                                                    </small>
                                                @endif
                                            @endif

                                            <small class="timeline-time d-block">
                                                <i class="bx bx-time text-muted"></i>
                                                <strong>Tarikh & Masa:</strong>
                                                {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y, H:i a') }}
                                                <span class="text-muted ms-2">
                                                    ({{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }})
                                                </span>
                                            </small>

                                            {{-- Show duration from previous step --}}
                                            @if (!$isFirst && isset($timelineItems[$index - 1]))
                                                @php
                                                    $prevItem = $timelineItems[$index - 1];
                                                    $durationMinutes = \Carbon\Carbon::parse(
                                                        $prevItem->created_at,
                                                    )->diffInMinutes(\Carbon\Carbon::parse($item->created_at));

                                                    $durationParts = [];

                                                    if ($durationMinutes >= 1440) {
                                                        // More than 24 hours - show days, hours, minutes (integer format)
                                                        $days = intval(floor($durationMinutes / 1440));
                                                        $remainingMinutes = $durationMinutes % 1440;
                                                        $hours = intval(floor($remainingMinutes / 60));
                                                        $minutes = intval($remainingMinutes % 60);

                                                        $durationParts[] = $days . ' hari';
                                                        if ($hours > 0) {
                                                            $durationParts[] = $hours . ' jam';
                                                        }
                                                        if ($minutes > 0) {
                                                            $durationParts[] = $minutes . ' minit';
                                                        }
                                                    } elseif ($durationMinutes >= 60) {
                                                        // Between 1 hour and 24 hours - show hours and minutes (integer format)
                                                        $hours = intval(floor($durationMinutes / 60));
                                                        $minutes = intval($durationMinutes % 60);

                                                        $durationParts[] = $hours . ' jam';
                                                        if ($minutes > 0) {
                                                            $durationParts[] = $minutes . ' minit';
                                                        }
                                                    } else {
                                                        // Less than 1 hour - show only minutes (integer format)
                                                        $minutes = intval($durationMinutes);
                                                        if ($minutes > 0) {
                                                            $durationParts[] = $minutes . ' minit';
                                                        }
                                                    }

                                                    $durationText = implode(' ', $durationParts);
                                                @endphp

                                                @if ($durationMinutes > 0)
                                                    <small class="timeline-time d-block mt-1">
                                                        <i class="bx bx-stopwatch text-warning"></i>
                                                        <strong>Tempoh:</strong>
                                                        <span class="text-warning">{{ $durationText }}</span>
                                                        <span class="text-muted">dari status sebelum</span>
                                                    </small>
                                                @endif
                                            @endif
                                        </div>
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
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            transition: all 0.3s ease;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item.current-status {
            transform: scale(1.02);
        }

        .timeline-item.first-item .timeline-content {
            border: 2px solid rgba(105, 108, 255, 0.2);
            box-shadow: 0 4px 20px rgba(105, 108, 255, 0.15);
        }

        .timeline-item.first-item .timeline-marker-icon {
            box-shadow: 0 0 15px rgba(105, 108, 255, 0.4);
        }

        .timeline-item.last-item .timeline-content {
            border: 2px solid rgba(113, 221, 55, 0.2);
            box-shadow: 0 4px 20px rgba(113, 221, 55, 0.15);
        }

        .timeline-item.last-item .timeline-marker-icon {
            box-shadow: 0 0 15px rgba(113, 221, 55, 0.4);
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

        .timeline-marker-icon.current-marker {
            animation: currentPulse 2s infinite;
            box-shadow: 0 0 20px rgba(105, 108, 255, 0.5);
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
        .badge.badge-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .timeline-content {
            background: #ffffff;
            padding: 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid #e3e6f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            transition: all 0.3s ease;
        }

        .timeline-content.current-content {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-color: rgba(105, 108, 255, 0.2);
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
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .timeline-details:hover {
            background: #e9ecef;
        }

        .timeline-meta {
            padding-top: 0.5rem;
            border-top: 1px solid #f1f3f4;
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
            width: 16px;
            text-align: center;
        }

        /* Enhanced Avatar Styles */
        .avatar.avatar-sm {
            width: 1.75rem;
            height: 1.75rem;
            font-size: 0.7rem;
        }

        /* Pulse animation for current status */
        @keyframes currentPulse {
            0% {
                box-shadow: 0 0 20px rgba(105, 108, 255, 0.5);
            }

            50% {
                box-shadow: 0 0 30px rgba(105, 108, 255, 0.8);
            }

            100% {
                box-shadow: 0 0 20px rgba(105, 108, 255, 0.5);
            }
        }

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

        /* Compact chronology layout */
        .timeline-item {
            margin-bottom: 0.85rem;
            padding-left: 0.75rem;
        }

        .timeline-marker {
            top: 0.15rem;
        }

        .timeline-marker-icon {
            width: 2.15rem;
            height: 2.15rem;
            font-size: 0.85rem;
            border-width: 2px;
        }

        .timeline-content {
            padding: 0.75rem 0.85rem;
            border-radius: 0.6rem;
        }

        .timeline-text {
            margin-bottom: 0.5rem;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .timeline-details {
            padding: 0.45rem 0.6rem;
            margin-bottom: 0.5rem;
        }

        .timeline-details ul {
            margin-bottom: 0;
            padding-left: 1.15rem !important;
        }

        /* kronologi diserahkan kepada list item */
        .timeline-details li { 
            margin-bottom: 0.15rem;
            line-height: 1.25;
            font-size: 12px;
        }

        .timeline-meta {
            padding-top: 0.35rem;
        }

        .timeline-time {
            font-size: 0.77rem;
            gap: 0.35rem;
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
    </style>

    <script>
        // Make ticket data available globally for modals
        window.ticketData = {
            id: {{ $ticket->id ?? 'null' }},
            uuid: '{{ $ticket->uuid ?? '' }}'
        };

        // Auto-resize textarea functionality
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            const chatForm = document.querySelector('.chat-input-form');
            const updateForm = document.querySelector('#updateTicketForm');

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

            // Add form submission handling for comments
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

            // Form validation and submission handling for update form
            if (updateForm) {
                updateForm.addEventListener('submit', function(e) {
                    // Basic validation
                    const statusSelect = document.getElementById('tick_status');
                    const categorySelect = document.getElementById('cat_id');
                    const subcategorySelect = document.getElementById('subcat_id');

                    if (!statusSelect.value) {
                        e.preventDefault();
                        alert('Sila pilih status tiket.');
                        statusSelect.focus();
                        return false;
                    }

                    // Only validate kategori and sub kategori if they are NOT disabled (status is not Aduan Dirujuk)
                    const selectedStatusText = statusSelect.selectedOptions[0]?.text.trim();
                    const isAduanDirujuk = selectedStatusText === 'Aduan Dirujuk';

                    // Skip validation for kategori and sub kategori when status is Aduan Dirujuk
                    if (!isAduanDirujuk) {
                        if (categorySelect.value && !categorySelect.disabled) {
                            // Category is set, continue
                        }

                        if (subcategorySelect.value && !subcategorySelect.disabled) {
                            // Subcategory is set, continue
                        }
                    }

                    // Show loading state
                    const submitBtn = updateForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Mengemaskini...';

                    // Re-enable button after 10 seconds (fallback)
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);

                    return true;
                });
            }

            // Category and subcategory filtering functionality with SLA integration
            const statusSelect = document.querySelector('#tick_status');
            const categorySelect = document.querySelector('#cat_id');
            const subcategorySelect = document.querySelector('#subcat_id');
            const severitySelect = document.querySelector('#sla_severity');
            const assignedOfficersSelect = document.querySelector('#assigned_officers');

            // Function to handle status-based dropdown enabling/disabling
            function handleStatusChange() {
                if (!statusSelect || !categorySelect || !subcategorySelect) {
                    return;
                }

                const selectedStatusText = statusSelect.selectedOptions[0]?.text.trim();

                // Kategori & Sub Kategori: Enable ONLY for "Aduan Dirujuk"
                const isAduanDirujuk = selectedStatusText === 'Aduan Dirujuk';

                // Check if status is one of the final statuses (Dibatal, Selesai) - for assigned officers
                const isFinalStatus = selectedStatusText === 'Aduan DiBatal' ||
                    selectedStatusText === 'Aduan Selesai';

                console.log('Status change - Text:', selectedStatusText, 'IsAduanDirujuk:', isAduanDirujuk,
                    'IsFinalStatus:', isFinalStatus);

                // Kategori & Sub Kategori: Enable ONLY for Aduan Dirujuk, disable for all other statuses
                categorySelect.disabled = !isAduanDirujuk;
                subcategorySelect.disabled = !isAduanDirujuk;

                // Tahap Severity: Always enabled (never disabled)
                if (severitySelect) severitySelect.disabled = false;

                // Assigned Officers: Disable only for final statuses (Dibatal, Selesai)
                if (assignedOfficersSelect) assignedOfficersSelect.disabled = isFinalStatus;

                // Remove required attributes when disabled
                if (!isAduanDirujuk) {
                    categorySelect.removeAttribute('required');
                    subcategorySelect.removeAttribute('required');
                }

                // Add visual styling for disabled state - Kategori & Sub Kategori
                if (!isAduanDirujuk) {
                    categorySelect.style.backgroundColor = '#f8f9fa';
                    categorySelect.style.color = '#6c757d';
                    subcategorySelect.style.backgroundColor = '#f8f9fa';
                    subcategorySelect.style.color = '#6c757d';
                } else {
                    categorySelect.style.backgroundColor = '';
                    categorySelect.style.color = '';
                    subcategorySelect.style.backgroundColor = '';
                    subcategorySelect.style.color = '';
                }

                // Tahap Severity: Always keep normal styling (never disabled)
                if (severitySelect) {
                    severitySelect.style.backgroundColor = '';
                    severitySelect.style.color = '';
                }

                // Assigned Officers: Add disabled styling only for final statuses
                if (assignedOfficersSelect) {
                    if (isFinalStatus) {
                        assignedOfficersSelect.style.backgroundColor = '#f8f9fa';
                        assignedOfficersSelect.style.color = '#6c757d';
                    } else {
                        assignedOfficersSelect.style.backgroundColor = '';
                        assignedOfficersSelect.style.color = '';
                    }
                }
            }

            // Add status change event listener - MUST be first!
            if (statusSelect) {
                statusSelect.addEventListener('change', handleStatusChange);
                // Trigger on initial page load with slight delay to ensure DOM is ready
                setTimeout(() => {
                    handleStatusChange();
                }, 50);
            }

            if (categorySelect && subcategorySelect) {
                // Store all subcategory options
                const allSubcategoryOptions = Array.from(subcategorySelect.options);

                // Track if page is still loading
                let isPageLoading = true;
                setTimeout(() => {
                    isPageLoading = false;
                }, 150); // Set to false after initial load

                // Function to load SLA data and update severity options
                function loadSLAData(categoryId, subcategoryId) {
                    if (categoryId && subcategoryId) {
                        // Store current severity value before updating options
                        const currentSeverityValue = severitySelect.value;

                        fetch(`/api/sla-data/${categoryId}/${subcategoryId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update severity options
                                    severitySelect.innerHTML =
                                        '<option value="">Pilih Tahap Severity</option>';
                                    data.severities.forEach(severity => {
                                        const option = document.createElement('option');
                                        option.value = severity.value;
                                        option.textContent = severity.label;
                                        option.dataset.slaId = severity.sla_id;
                                        severitySelect.appendChild(option);
                                    });

                                    // Preserve severity selection if it was already set (from database)
                                    if (currentSeverityValue) {
                                        severitySelect.value = currentSeverityValue;
                                    }

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
                                severitySelect.innerHTML =
                                    '<option value="">Ralat memuat data SLA</option>';
                                resetOfficersList();
                            });
                    } else {
                        // Clear severity options when category or subcategory not selected
                        severitySelect.innerHTML = '<option value="">Pilih Tahap Severity</option>';
                        resetOfficersList();
                    }
                }

                // Function to update officers based on category and subcategory
                function updateOfficersByCategory() {
                    const categoryId = categorySelect.value;
                    const subcategoryId = subcategorySelect.value;

                    if (categoryId && subcategoryId) {
                        // Call API to get officers filtered by category and subcategory
                        fetch(
                                `/api/available-officers?cat_id=${categoryId}&subcat_id=${subcategoryId}&level_tugasan=2`
                            )
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.officers && data.officers.length > 0) {
                                    updateOfficersList(data.officers);
                                } else {
                                    // Show original filtered officers if no specific officers found
                                    resetOfficersList();
                                }
                            })
                            .catch(error => {
                                console.error('Error loading officers by category:', error);
                                resetOfficersList();
                            });
                    } else {
                        resetOfficersList();
                    }
                }

                // Function to filter officers based on SLA criteria
                function filterOfficersBySLA() {
                    const categoryId = categorySelect.value;
                    const subcategoryId = subcategorySelect.value;
                    const severity = severitySelect.value;

                    if (categoryId && subcategoryId && severity) {
                        // Load officers from officers table based on category, subcategory, and severity
                        fetch(
                                `/api/officers-by-category-severity?cat_id=${categoryId}&subcat_id=${subcategoryId}&severity=${severity}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.officers && data.officers.length > 0) {
                                    updateOfficersList(data.officers);
                                } else {
                                    // Show all officers if no specific officers found
                                    resetOfficersList();
                                }
                            })
                            .catch(error => {
                                console.error('Error loading officers by severity:', error);
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

                    // Deduplicate by user id, then fallback by normalized name
                    const uniqueOfficersMap = new Map();
                    officers.forEach(officer => {
                        const officerId = String(officer.id ?? '').trim();
                        const normalizedName = String(officer.nama ?? '').trim().toLowerCase();
                        const dedupeKey = officerId || `name:${normalizedName}`;

                        if (!uniqueOfficersMap.has(dedupeKey)) {
                            uniqueOfficersMap.set(dedupeKey, officer);
                        }
                    });

                    Array.from(uniqueOfficersMap.values()).forEach(officer => {
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

                    // Restore filtered officers list (only from officers table)
                    @php
                        // Get officer user IDs from officers table in database
                        $officerUserIdsForJS = \Illuminate\Support\Facades\DB::table('officers')->whereNull('deleted_at')->pluck('user_id')->toArray();

                        // Filter officers to show only those in officers table
                        $filteredOfficersForJS = $officers->whereIn('id', $officerUserIdsForJS)->unique('id')->values();
                    @endphp
                    @foreach ($filteredOfficersForJS as $officer)
                        @php
                            $isCurrentUser = $officer->id === auth()->user()->id;
                        @endphp
                        const option{{ $officer->id }} = new Option('{{ $officer->nama }}',
                            {{ $officer->id }},
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
                    // Check if dropdown is disabled first
                    if (this.disabled || this.hasAttribute('readonly')) {
                        return false;
                    }

                    const selectedCategoryId = this.value;

                    // Clear current subcategory options except the first one
                    subcategorySelect.innerHTML = '<option value="">Pilih Sub Kategori</option>';

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

                    // Clear severity and reset officers when category changes (but not on page load)
                    if (!isPageLoading) {
                        severitySelect.innerHTML = '<option value="">Pilih Tahap Severity</option>';
                        resetOfficersList();
                    }
                });

                // Subcategory change event
                subcategorySelect.addEventListener('change', function() {
                    // Check if dropdown is disabled first
                    if (this.disabled || this.hasAttribute('readonly')) {
                        return false;
                    }

                    const categoryId = categorySelect.value;
                    const subcategoryId = this.value;
                    loadSLAData(categoryId, subcategoryId);

                    // Update officers list based on selected category and subcategory
                    updateOfficersByCategory();
                });

                // Add click prevention for disabled dropdowns
                [categorySelect, subcategorySelect].forEach(select => {
                    select.addEventListener('mousedown', function(e) {
                        if (this.disabled || this.hasAttribute('readonly')) {
                            e.preventDefault();
                            return false;
                        }
                    });

                    select.addEventListener('focus', function() {
                        if (this.disabled || this.hasAttribute('readonly')) {
                            this.blur();
                            return false;
                        }
                    });
                });

                // Severity change event
                if (severitySelect) {
                    severitySelect.addEventListener('change', function() {
                        filterOfficersBySLA();
                    });
                }

                // Trigger category change on page load to filter subcategories
                // Only trigger if dropdown is not disabled AND with delay to ensure handleStatusChange runs first
                setTimeout(() => {
                    if (categorySelect.value && !categorySelect.disabled) {
                        categorySelect.dispatchEvent(new Event('change'));

                        // If subcategory is also selected, load SLA data ONLY if severity is not already set
                        // (to preserve the database value on page load)
                        if (subcategorySelect.value && !subcategorySelect.disabled && !severitySelect
                            .value) {
                            loadSLAData(categorySelect.value, subcategorySelect.value);
                        }
                    }
                }, 100);
            }

            // Initialize Select2 for assigned officers (following officers page pattern)
            if ($('#assigned_officers').length) {
                $('#assigned_officers').select2({
                    placeholder: 'Pilih Pegawai (boleh pilih lebih dari satu)',
                    width: '100%',
                    multiple: true,
                    allowClear: false,
                    closeOnSelect: false,
                    templateResult: function(option) {
                        if (!option.id) {
                            return option.text;
                        }

                        console.log('Template Result - Option:', option);
                        const $option = $(option.element);
                        console.log('Data current-user:', $option.data('current-user'));
                        console.log('Auth user ID:', {{ auth()->user()->id }});
                        console.log('Option ID:', option.id);

                        const isCurrentUser = $option.data('current-user') === 'true' || option.id ==
                            {{ auth()->user()->id }};
                        console.log('Is current user:', isCurrentUser);

                        if (isCurrentUser) {
                            return $(
                                '<div style="background-color: #28a745 !important; color: white !important; padding: 6px 12px; border-radius: 4px; font-weight: 500; margin: 2px 0;">' +
                                option.text + '</div>');
                        }

                        return $('<div>' + option.text + '</div>');
                    },
                    templateSelection: function(option) {
                        if (!option.id) {
                            return option.text;
                        }

                        const $option = $(option.element);
                        const isCurrentUser = $option.data('current-user') === 'true' || option.id ==
                            {{ auth()->user()->id }};

                        if (isCurrentUser) {
                            return $(
                                '<span style="background-color: #28a745 !important; color: white !important; padding: 2px 8px; border-radius: 3px; font-weight: 500;">' +
                                option.text + '</span>');
                        }

                        return option.text;
                    }
                });

                // Update "Tindakan Oleh" when assigned officers change - but only show database assigned officers
                $('#assigned_officers').on('change', function() {
                    // Don't update "Tindakan Oleh" based on dropdown selection
                    // It should only show officers assigned from database in chronologies
                    // The display is already set correctly in PHP based on database records
                });
            }

            // Auto-capitalize for sentence case in notes textarea
            const notesTextarea = document.querySelector('#notes');
            if (notesTextarea) {
                notesTextarea.addEventListener('input', function() {
                    let value = this.value;
                    if (value.length > 0) {
                        // Convert to sentence case: capitalize first letter
                        value = value.charAt(0).toUpperCase() + value.slice(1);

                        // Capitalize first letter after sentence endings (., !, ?) followed by space
                        value = value.replace(/([.!?]\s+)([a-z])/g, function(match, p1, p2) {
                            return p1 + p2.toUpperCase();
                        });

                        // Update the value only if it changed to avoid cursor jumping
                        if (this.value !== value) {
                            const cursorPosition = this.selectionStart;
                            this.value = value;
                            this.setSelectionRange(cursorPosition, cursorPosition);
                        }
                    }
                });
            }
        });

        // Cancel update function
        function cancelUpdate() {
            if (confirm('Adakah anda pasti untuk membatalkan kemaskini? Semua perubahan akan hilang.')) {
                // Reset form to original values
                location.reload();
            }
        }
    </script>
@endsection

@include('tickets.partials.comment-modal')
@include('tickets.partials.action-comment-modal')
