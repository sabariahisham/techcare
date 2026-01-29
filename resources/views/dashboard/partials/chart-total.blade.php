<div class="col-lg-3 col-md-12 d-flex align-items-stretch">
    <div class="card flex-fill">
        <div class="card-header">
            <h5 class="card-title mb-1">Jumlah Keseluruhan</h5>
            <p class="card-subtitle">Sehingga kini {{ \Carbon\Carbon::now()->format('d M Y') }}</p>
        </div>
        <div class="card-body d-flex flex-column justify-content-center align-items-center">
            <div class="chart-container" style="position: relative; width: 200px; height: 200px;">
                <canvas id="totalChart"></canvas>
            </div>
            <div class="text-center mt-3">
                <h4 class="mb-0" id="totalCount">-</h4>
                <small class="text-muted">Jumlah Keseluruhan</small>
            </div>
        </div>
    </div>
</div>
