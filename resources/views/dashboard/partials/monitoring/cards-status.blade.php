<div class="h-100">
    <div class="row gx-3 gy-2 row-cols-1 row-cols-sm-2 row-cols-md-4 h-100 status-cards-container">

        <!-- Card 1: Aduan Hari Ini -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-1 justify-content-end">                        
                        <div class="avatar flex-shrink-0">     
                            <a href="{{ route('tickets.admin') }}">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class='bx bx bx-clipboard bx-lg text-primary'></i>
                                </span> 
                            </a>
                        </div>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center text-center">
                        <p class="mb-1 md-label-lg"><strong>Aduan Hari Ini</strong></p>
                        <h2 class="fw-bold mb-0 md-count-lg text-primary" id="today-logs-count">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-3"></span>
                            </span>
                        </h2>
                        <small class="d-block text-muted md-label-sm">Data log harian</small>
                    </div>                   
                </div>
            </div>
        </div>

        <!-- Card 2: Lewat SLA -->
        <div class="col d-flex align-items-stretch">
            <div class="card flex-fill">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="text-center">
                        <h2 class="fw-bold mb-1 md-count-md" style="color: #ff6b47;">
                            <span id="overdue-sla-count"></span>
                        </h2>
                        <p class="d-block fw-medium mb-1 md-label-lg"><strong>Lewat SLA</strong></p>
                        <small class="d-block text-muted md-label-sm">
                            Melebihi had masa SLA yang ditetapkan
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Belum Selesai -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill" style="padding-left: 10px; padding-right: 10px;">
                <div class="card-body d-flex flex-column justify-content-center>
                    <span class="d-block fw-medium mb-1 md-label-md"><strong>Tiket Belum Selesai</strong></span>
                    <h3 class="card-title mb-3 md-count-md text-warning">
                        <span id="pending-tickets-count" class="fw-bold">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-4"></span>
                            </span>
                        </span>
                        <span class="fw-medium md-label-md"> Tiket</span>
                    </h3>
                    <div class="pending-stats-wrapper">
                        <!-- Keseluruhan -->
                        <div class="pending-stat-row d-flex align-items-center justify-content-between">
                            <span class="text-muted md-label-xs">
                                <i class="bx bx-bar-chart-alt-2 me-1"></i>Keseluruhan
                            </span>
                            <span class="fw-bold md-pending-pct">
                                <span id="pending-overall-count">
                                    <span class="placeholder-glow">
                                        <span class="placeholder placeholder-lg col-3"></span>
                                    </span>
                                </span> / <span id="total-tickets-count">
                                    <span class="placeholder-glow">
                                        <span class="placeholder placeholder-lg col-3"></span>
                                    </span>
                                </span>
                            </span>
                        </div>

                        <!-- Bulan Semasa -->
                        <div class="pending-stat-row d-flex align-items-center justify-content-between">
                            <span class="text-muted md-label-xs">
                                <i class="bx bx-calendar me-1"></i><span id="pending-current-month-label">Bulan
                                    Semasa</span>
                            </span>
                            <span class="fw-bold md-pending-pct">
                                <span id="pending-month-count">
                                    <span class="placeholder-glow">
                                        <span class="placeholder placeholder-lg col-3"></span>
                                    </span>
                                </span> / <span id="total-tickets-month-count">
                                    <span class="placeholder-glow">
                                        <span class="placeholder placeholder-lg col-3"></span>
                                    </span>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Selesai Minggu Ini -->
        <div class="col d-flex align-items-stretch weekly-completed-card">
            <div class="card flex-fill" style="padding-left: 10px; padding-right: 10px;">
                <div class="card-body d-flex flex-column">
                    <span class="d-block fw-medium mb-1 md-label-md"><strong>Selesai Minggu Ini</strong></span>
                    <h3 class="card-title mb-1 md-count-sm text-success">
                        <span id="weekly-completed-count" class="fw-bold">
                            <span class="placeholder-glow">
                                <span class="placeholder placeholder-lg col-3"></span>
                            </span>
                        </span>
                        <small class="text-muted md-label-md"> Tiket</small>
                    </h3>

                    <div class="mt-auto">
                        <div class="weekly-chart-container">
                            <canvas id="weeklyCompletedChart" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
