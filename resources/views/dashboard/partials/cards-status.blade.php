<div class="col-lg-4 col-md-12 dashboard-main-cards">
    <div class="row g-3 row-cols-1 row-cols-sm-2 h-100 status-cards-container">
        <!-- Card 1: Log Hari Ini (Number only) -->
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
                        <h2 class="fw-bold mb-0" id="today-logs-count" style="font-size: 3.5rem; line-height: 1;">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-3"></span>
                            </span>
                        </h2>
                    </div>
                    <div class="mt-auto text-center">
                        <small class="d-block mb-1 text-muted small">Data log harian</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Selesai Minggu Ini (Number with bar chart) -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column">
                    <span class="d-block fw-medium mb-1">Selesai Minggu Ini</span>
                    <h3 class="card-title mb-4" style="font-size: 2.5rem;">
                        <span id="weekly-completed-count"></span>
                        <small class="text-muted">Tiket</small>
                    </h3>

                    <div class="mt-auto">
                        <div class="weekly-chart-container">
                            <canvas id="weeklyCompletedChart" height="40"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Belum Selesai (Number with semi pie chart) -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column">
                    <span class="d-block fw-medium mb-1">Belum Selesai</span>
                    <h3 class="card-title mb-1" style="font-size: 2.5rem;">
                        <span id="pending-tickets-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-4"></span>
                            </span>
                        </span>
                        <small class="text-muted">jumlah tiket</small>
                    </h3>

                    <div class="mt-0">
                        <div class="pending-chart-container position-relative">
                            <canvas id="pendingTicketsChart" height="60"></canvas>
                            <div id="pending-percentage" class="position-absolute"
                                style="top: 50%; left: 50%; transform: translate(-50%, -30%); font-weight: 600; font-size: 16px; color: #384551;">
                                <span class="placeholder-glow">
                                    <span class="placeholder placeholder-xs col-3"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Lewat SLA (Number only) -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h2 class="fw-bold mb-0" style="font-size: 3.5rem; line-height: 1; color: #ff6b47;">
                            <span id="overdue-sla-count"></span>
                        </h2>
                        <p class="d-block fw-medium mb-5">Lewat SLA</p>
                        <small class="d-block text-muted small">
                            Melebihi had masa SLA yang ditetapkan
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
