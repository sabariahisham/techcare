<div class="col-lg-3 col-md-12">
    <div class="card flex-fill">
        <div class="card-header d-flex justify-content-between">
            <div>
                <h5 class="card-title mb-1">Tiket Aduan</h5>
                <p class="card-subtitle">Sehingga kini {{ date('d-m-Y') }}</p>
            </div>
        </div>
        <div class="card-body d-flex flex-column justify-content-center align-items-center">
            <div class="chart-container" style="position: relative; width: 200px; height: 200px;">
                <canvas id="totalChart"></canvas>
            </div>
            <div class="text-center mt-3">
                <h4 class="mb-0" id="totalCount">-</h4>
                <small class="text-muted">Total Tiket</small>
            </div>
        </div>
    </div>
</div>
