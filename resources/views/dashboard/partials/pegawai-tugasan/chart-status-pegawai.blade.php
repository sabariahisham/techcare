<div class="col-xl-4 col-lg-6 col-md-12 d-flex align-items-stretch dashboard-main-cards chart-card">
    <div class="card flex-fill" style="height: 332px;">
        <div class="card-header" style="padding: 0.75rem 1.25rem;">
            <h5 class="card-title mb-0" style="font-size: 1rem;">Status Tiket (Tugasan Anda)</h5>
            <p class="card-subtitle mb-0" style="font-size: 0.75rem;">Sehingga
                {{ \Carbon\Carbon::now()->format('d M Y') }} <small class="text-muted"><i>(tahun semasa)</i></small></p>
        </div>
        <div class="card-body" style="padding: 0.75rem 1rem;">
            <div style="height: 250px;">
                <canvas id="pegawaiStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>
