@if(auth()->user() && auth()->user()->isImpersonated())
<div class="card mb-6 border-warning">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <i class="bx bx-user-circle text-warning me-2 fs-4"></i>
                    <h5 class="mb-0 text-warning">Mode Ambil Alih Aktif</h5>
                </div>
                <p class="mb-2">
                    <strong>Anda sedang menggunakan akaun:</strong>
                    <span class="text-primary fw-bold">{{ auth()->user()->nama }}</span>
                    @if(auth()->user()->email)
                        <span class="text-muted">({{ auth()->user()->email }})</span>
                    @endif
                </p>
                <p class="mb-0 text-muted small">
                    <i class="bx bx-info-circle me-1"></i>
                    Semua tindakan yang dilakukan akan dikaitkan dengan akaun pengguna yang sedang diambil alih.
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('impersonate.leave') }}" class="btn btn-outline-danger">
                    <i class="bx bx-log-out me-1"></i>
                    Keluar dari Mode Ambil Alih
                </a>
            </div>
        </div>
    </div>
</div>
@endif
