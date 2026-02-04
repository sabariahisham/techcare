<div class="col-lg-4 col-md-12 dashboard-main-cards">
    <div class="row g-3 row-cols-1 row-cols-sm-2 h-100 status-cards-container">
        <!-- Card 1: Log Hari Ini (Number only) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="avatar flex-shrink-0 w-px-40 h-px-40">
                            <span
                                class="avatar-initial rounded bg-label-info d-flex align-items-center justify-content-center w-100 h-100">
                                <i class="bx bx-lock-open bx-lg text-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-5 d-block text-center">
                        <p class="mb-5">Aduan Hari Ini</p>
                        <a href="{{ route('tickets.index') }}" title="Klik untuk Lihat Aduan"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            style="text-decoration: none; color: inherit; cursor: pointer;">
                            <h2 class="fw-bold mb-0" id="pegawai-today-logs-count"
                                style="font-size: 3.5rem; line-height: 1; transition: color 0.2s;">
                                <span class="placeholder-glow">
                                    <span class="placeholder placeholder-lg col-3"></span>
                                </span>
                            </h2>
                        </a>
                    </div>
                    <div class="mt-auto text-center">
                        <small class="d-block mb-1 text-muted small">Data log harian (Tugasan Anda)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Lewat SLA (Number only) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h2 class="fw-bold mb-0" style="font-size: 3.5rem; line-height: 1; color: #ff6b47;">
                            <span id="pegawai-overdue-sla-count"></span>
                        </h2>
                        <p class="d-block fw-medium mb-5">Lewat SLA</p>
                        <small class="d-block text-muted small">
                            Tugasan melebihi had masa SLA
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Belum Selesai (Number with semi pie chart) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column">
                    <span class="d-block fw-medium mb-2">Tiket Belum Selesai</span>
                    <h3 class="card-title mb-0" style="font-size: 2.5rem;">
                        <span id="pegawai-pending-tickets-count" class="fw-bold">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-4"></span>
                            </span>
                        </span>
                        <span class="fw-bold" style="font-size: 1.5rem;">Tiket</span>
                    </h3>
                    <small class="text-muted mb-2">
                        daripada <span id="pegawai-total-tickets-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-xs col-2"></span>
                            </span>
                        </span> tugasan anda
                    </small>

                    <div class="mt-1 text-center">
                        <div class="pending-chart-container position-relative mx-auto" style="max-width:120px;">
                            <canvas id="pegawaiPendingTicketsChart" height="40"></canvas>

                            <div id="pegawai-pending-percentage" class="position-absolute"
                                style="
                top: 50%;
                left: 50%;
                transform: translate(-50%, -45%);
                font-weight: 600;
                font-size: 12px;
                color: #384551;
            ">
                                80%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Selesai Minggu Ini (Number with bar chart) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column">
                    <span class="d-block fw-medium mb-1">Selesai Minggu Ini</span>
                    <h3 class="card-title mb-2" style="font-size: 2.5rem;">
                        <span id="pegawai-weekly-completed-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-3"></span>
                            </span>
                        </span>
                        <small class="text-muted">Tiket</small>
                    </h3>

                    <div class="mt-auto">
                        <div class="weekly-chart-container">
                            <canvas id="pegawaiWeeklyCompletedChart" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
