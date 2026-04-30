<div class="col-xl-4 col-lg-6 col-md-12 dashboard-main-cards">
    <div class="row g-2 row-cols-1 row-cols-sm-2 h-100 status-cards-container">
        <!-- Card 1: Log Hari Ini (Number only) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill" style="height: 162px;">
                <div class="card-body d-flex flex-column justify-content-between p-3">
                    <div class="d-flex align-items-center mb-1">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-lock-open bx-sm text-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center text-center">
                        <p class="mb-1 text-muted" style="font-size: 0.8125rem;">Tugasan Anda</p>
                        <a href="{{ route('tickets.assigned') }}" title="Klik Lihat Tiket" data-bs-toggle="tooltip"
                            data-bs-placement="top" style="text-decoration: none; color: inherit; cursor: pointer;">
                            <h2 class="fw-bold mb-0" id="pegawai-today-logs-count"
                                style="font-size: 2rem; line-height: 1.1; transition: color 0.2s;">
                                <span class="placeholder-glow">
                                    <span class="placeholder placeholder-lg col-3"></span>
                                </span>
                            </h2>
                        </a>
                    </div>
                    <div class="mt-auto text-center">
                        <small class="d-block text-muted" style="font-size: 0.7rem;">Jumlah tugasan semasa anda</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Lewat SLA (Number only) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill" style="height: 162px;">
                <div class="card-body d-flex flex-column justify-content-center p-3">
                    <div class="text-center">
                        <h2 class="fw-bold mb-1" style="font-size: 2rem; line-height: 1.1; color: #ff6b47;">
                            <span id="pegawai-overdue-sla-count"></span>
                        </h2>
                        <p class="d-block fw-medium mb-1" style="font-size: 0.9375rem;">Lewat SLA</p>
                        <small class="d-block text-muted" style="font-size: 0.7rem;">
                            Tugasan melebihi had masa SLA
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Belum Selesai (Number with semi pie chart) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill" style="height: 162px;">
                <div class="card-body d-flex flex-column p-3">
                    <span class="d-block fw-medium mb-1" style="font-size: 0.8125rem;">Tiket Belum Selesai</span>
                    <h3 class="card-title mb-0" style="font-size: 1.5rem;">
                        <span id="pegawai-pending-tickets-count" class="fw-bold">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-4"></span>
                            </span>
                        </span>
                        <span class="fw-medium" style="font-size: 0.875rem;">Tiket</span>
                    </h3>
                    <small class="text-muted mb-1" style="font-size: 0.7rem;">
                        daripada <span id="pegawai-total-tickets-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-xs col-2"></span>
                            </span>
                        </span> tugasan
                    </small>

                    <div class="mt-auto text-center">
                        <div class="pending-chart-container position-relative mx-auto"
                            style="max-width:80px; height:50px;">
                            <canvas id="pegawaiPendingTicketsChart" height="30"></canvas>

                            <div id="pegawai-pending-percentage" class="position-absolute"
                                style="
                top: 55%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-weight: 600;
                font-size: 9px;
                color: #384551;
                text-align: center;
                line-height: 1.2;
            ">
                                <span class="placeholder-glow">
                                    <span class="placeholder placeholder-xs col-8"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Selesai Minggu Ini (Number with bar chart) - PEGAWAI TUGASAN -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill" style="height: 162px;">
                <div class="card-body d-flex flex-column p-3">
                    <span class="d-block fw-medium mb-1" style="font-size: 0.8125rem;">Selesai Minggu Ini</span>
                    <h3 class="card-title mb-1" style="font-size: 1.5rem;">
                        <span id="pegawai-weekly-completed-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-3"></span>
                            </span>
                        </span>
                        <small class="text-muted" style="font-size: 0.875rem;">Tiket</small>
                    </h3>

                    <div class="mt-auto">
                        <div class="weekly-chart-container">
                            <canvas id="pegawaiWeeklyCompletedChart" height="35"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
