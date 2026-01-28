{{--
CONTEXT:
This is the main Dashboard Blade view for a Laravel 11 application.
This dashboard supports multiple roles:
- Administrator
- Pentadbir Sistem
- Penyelia Helpdesk
- Helpdesk On Duty
- Pegawai Tugasan
- Pengurusan MOF

GOAL:
Refactor this Blade to follow best practices without changing UI or behavior.

REFACTORING RULES:
- DO NOT change any HTML structure, layout, Bootstrap classes, CSS, or JS includes.
- DO NOT rename or remove any @include partials.
- DO NOT add or remove any widgets or sections.

ARCHITECTURE RULES:
- Blade must NOT contain role logic (hasRole, hasAnyRole).
- Blade must NOT decide access based on Auth::user().
- Blade must ONLY use boolean flags or data passed from Controller / DashboardService.

DATA ASSUMPTION:
The Controller provides:
- $user (authenticated user object)
- $sections (array of booleans, example:
  [
    'pegawai' => true|false,
    'admin' => true|false,
    'pengurusan' => true|false,
    'helpdesk' => true|false
  ])

TASK:
- Replace all role-based conditionals with checks against $sections.
- Replace Auth::user() usage with $user.
- Keep all existing behavior identical.
--}}



@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
    @vite(['resources/assets/css/dashboard-layout.css', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    <script>
        window.sessionConfig = {
            lifetime: {{ config('session.lifetime', 120) }}
        };
    </script>
    @vite(['resources/assets/js/dashboard.js', 'resources/assets/js/session-timeout.js'])
@endsection

@section('content')
    <!-- Impersonation Card -->
    @include('dashboard/partials/impersonate-card')

    <!-- Dashboard Content -->
    <div class="row g-4 mb-6">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="text-primary mb-1">Selamat datang, {{ $user->nama }}!</h4>
                    <p class="mb-0">Paparan ini memaparkan rumusan aktiviti, statistik dan prestasi semasa sistem untuk
                        rujukan pantas anda.</p>
                </div>
            </div>
        </div>
        <!-- Session Timer Display -->
        <div class="col-md-4">
            <div id="sessionTimerWidget" class="h-100">
                <div class="card shadow-sm border-warning h-100">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-time-five text-primary fs-4 me-2"></i>
                            <small class="text-muted fw-semibold">Masa Sesi</small>
                        </div>
                        <div id="sessionTimerDisplay" class="d-flex justify-content-center gap-3">
                            <div class="text-center">
                                <div id="timerHours" class="fs-3 fw-bold text-primary">00</div>
                                <small class="text-muted">JAM</small>
                            </div>
                            <div class="fs-3 fw-bold text-primary">:</div>
                            <div class="text-center">
                                <div id="timerMinutes" class="fs-3 fw-bold text-primary">00</div>
                                <small class="text-muted">MINIT</small>
                            </div>
                            <div class="fs-3 fw-bold text-primary">:</div>
                            <div class="text-center">
                                <div id="timerSeconds" class="fs-3 fw-bold text-primary">00</div>
                                <small class="text-muted">SAAT</small>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div id="sessionTimerProgress" class="progress-bar bg-primary" role="progressbar"
                                style="width: 100%;"></div>
                        </div>
                        <button id="btnRefreshSession" class="btn btn-sm btn-outline-primary mt-2 w-100">
                            <i class="bx bx-refresh me-1"></i>Lanjutkan Sesi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages for Impersonation -->
    @if (session('impersonate_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('impersonate_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('impersonate_leave'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bx bx-info-circle me-2"></i>
            {{ session('impersonate_leave') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Debug Role Information - Remove after role assignment is complete -->
    @if (config('app.debug', false))
        <div class="row g-4 mb-4">
            <!-- Debug Role Info -->
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    <h6><i class="fas fa-bug"></i> Debug Role Information:</h6>
                    <strong>Current User:</strong> {{ $user->nama }}<br>
                    <strong>User Roles:</strong> {{ $user->getRoleNames()->implode(', ') ?: 'No roles assigned' }}<br>
                    @if ($user->getRoleNames()->isEmpty())
                        <div class="mt-2 p-2 bg-light border-left border-warning">
                            <small><strong>Note:</strong> This user has no roles assigned. They will see the default
                                dashboard.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- User Ticket Status Cards - Shown to ALL users -->
    <div class="row g-4 mb-6">
        @include('dashboard/partials/card-status-user')
    </div>

    <!-- Pegawai Tugasan Specific Section -->
    @if ($sections['pegawai'] ?? false)
        <div class="card mb-6 bg-transparent">
            <div class="card-header">
                <div class="p-2 bg-white rounded shadow-sm d-flex align-items-center">
                    <i class='bx bxs-bell-ring fs-2 me-2' style='color:#1431eb'></i>
                    <h4 class="text-primary mb-0">TUGASAN ANDA!</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="row dashboard-row-consistent">
                    @include('dashboard/partials/pegawai-tugasan/my-tasks') <!-- My Tasks Section -->
                </div>
            </div>
        </div>
    @endif

    <!-- Administrator, Pengurusan MOF, Pentadbir Sistem, Penyelia Helpdesk, Helpdesk On Duty Section -->
    @if ($sections['admin'] ?? false)
        <div class="row g-4 mb-6 dashboard-row-consistent">
            @include('dashboard/partials/cards-status') <!-- Card Status -->
            @include('dashboard/partials/chart-status') <!-- Ticket Status -->
            @include('dashboard/partials/chart-category') <!-- Ticket Category -->
            @include('dashboard/partials/chart-trend') <!-- Monthly Ticket Trend -->
            @include('dashboard/partials/chart-total') <!-- Total Tickets -->
            @include('dashboard/partials/chart-assigned') <!-- Assigned Tickets -->
        </div>
        <div class="row g-4 mb-6">
            @include('dashboard/partials/table-ticket-overdue') <!-- Overdue Tickets Table -->
        </div>
    @elseif($sections['pengurusan'] ?? false)
        <div class="row g-4 mb-6 dashboard-row-consistent">
            @include('dashboard/partials/cards-status')
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
            @include('dashboard/partials/chart-trend')
            @include('dashboard/partials/chart-total')
        </div>
        {{-- <div class="row g-4 mb-6">
            @include('dashboard/partials/table-ticket-overdue') <!-- Overdue Tickets Table -->
        </div> --}}
    @elseif($sections['helpdesk'] ?? false)
        <div class="row g-4 mb-6 dashboard-row-consistent">
            @include('dashboard/partials/cards-status')
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
            @include('dashboard/partials/chart-trend')
            @include('dashboard/partials/chart-total')
            @include('dashboard/partials/chart-assigned')
        </div>
        <div class="row g-4 mb-6">
            @include('dashboard/partials/table-ticket-overdue') <!-- Overdue Tickets Table -->
        </div>
    @endif

@endsection
