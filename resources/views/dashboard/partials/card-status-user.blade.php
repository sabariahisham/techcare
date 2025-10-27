<div class="row g-4 mb-6">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-border-shadow-primary h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Baru</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($newTickets) + number_format($newTicketsOnBehalf) }}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-plus-circle bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Termasuk aduan yang dicipta untuk anda.</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-border-shadow-warning h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Diproses</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($inProgressTickets) + number_format($inProgressTicketsOnBehalf) }}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="bx bx-time-five bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan sedang disemak dan diambil tindakan.</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-border-shadow-danger h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Dirujuk</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($referredTickets) + number_format($referredTicketsOnBehalf) }}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="bx bx-pause-circle bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan dirujuk kepada pihak yang berkenaan.</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card card-border-shadow-success h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Selesai</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($closedTickets) + number_format($closedTicketsOnBehalf) }}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="bx bx-check-circle bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan telah diselesaikan.</small>
            </div>
        </div>
    </div>
</div>
