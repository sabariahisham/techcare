<div class="col-lg-12 col-md-12">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="mb-1">Jumlah Tiket Mengikut Pegawai Tugasan</h5>
                <p class="card-subtitle" id="assignedSubtitle">Total tiket bulan ini</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-label-primary" id="selectedMonth">{{ date('F Y') }}</button>
                <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu" id="monthDropdown">
                    <!-- Months will be populated dynamically -->
                </ul>
            </div>
        </div>
        <div class="card-body">
            <canvas id="assignedChart" height="400"></canvas>
        </div>
    </div>
</div>
