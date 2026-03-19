{{-- AUTO-REFRESH CONTROLS untuk Dashboard Pegawai Tugasan --}}
<div class="col-lg-12 col-md-12 align-items-stretch">
    <div class="card flex-fill">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                {{-- Left side: Auto-refresh status --}}
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-radio-circle-marked text-success" style="font-size: 0.875rem;"></i>
                    <span class="text-muted" style="font-size: 0.875rem;">
                        Auto-refresh aktif (setiap 60 saat)
                    </span>
                </div>

                {{-- Right side: Current time display --}}
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-time-five text-muted" style="font-size: 0.875rem;"></i>
                    <span class="text-muted" style="font-size: 0.875rem;">
                        Masa Semasa:
                        <strong id="pegawai-last-refresh-time" class="text-primary">
                            {{ now()->format('h:i:s A') }}
                        </strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
