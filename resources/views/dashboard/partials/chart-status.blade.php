<div class="col-lg-4 col-md-12 d-flex align-items-stretch">
    <div class="card flex-fill">
        <div class="card-header d-flex justify-content-between">
            <div>
                <h5 class="card-title mb-1">Status Tiket</h5>
                <p class="card-subtitle">Sehingga {{ \Carbon\Carbon::now()->format('d M Y') }}</p>
            </div>
        </div>
        <div class="card-body">
            <canvas id="statusChart" height="300"></canvas>
        </div>
    </div>
</div>
