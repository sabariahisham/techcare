<div class="col-lg-9 col-md-12">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="mb-1">Trend Bulanan Tiket</h5>
                <p class="card-subtitle" id="trendSubtitle">Total tiket tahun ini</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-label-primary" id="selectedYear">{{ date('Y') }}</button>
                <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu" id="yearDropdown">
                    <!-- Years will be populated dynamically -->
                </ul>
            </div>
        </div>
        <div class="card-body">
            <canvas id="trendChart" height="300"></canvas>
        </div>
    </div>
</div>
