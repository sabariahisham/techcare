{{-- @php
    use App\Models\Ticket;
    use Illuminate\Support\Facades\Auth;

    // Get current user ID
    $userId = Auth::id();

    // Count tickets by status for current user
    $newTickets = Ticket::where('user_id', $userId)->where('status', 'new')->count();
    $processingTickets = Ticket::where('user_id', $userId)->where('status', 'processing')->count();
    $completedTickets = Ticket::where('user_id', $userId)->where('status', 'completed')->count();
    $pendingTickets = Ticket::where('user_id', $userId)->where('status', 'pending')->count();
@endphp --}}


<div class="row g-4 mb-6">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Baru</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">1{{-- number_format($newTickets) --}}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-plus-circle bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Laporan anda telah diterima</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Diproses</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">2{{-- number_format($processingTickets) --}}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="bx bx-time-five bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan sedang disemak dan diambil tindakan</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Selesai</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">3{{-- number_format($completedTickets) --}}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="bx bx-check-shield bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan telah diselesaikan</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left flex-grow-1">
                        <span class="text-heading">Tertunda</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">4{{-- number_format($pendingTickets) --}}</h4>
                        </div>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="bx bx-pause-circle bx-lg"></i>
                        </span>
                    </div>
                </div>
                <small class="text-muted mt-auto">Aduan masih dalam proses semakan</small>
            </div>
        </div>
    </div>
</div>