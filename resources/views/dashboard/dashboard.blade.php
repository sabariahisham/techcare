@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
    @vite(['resources/assets/css/dashboard-layout.css', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard.js'])
@endsection

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;
    @endphp

    <!-- Impersonation Card -->
    @include('dashboard/partials/impersonate-card')

    <!-- Dashboard Content -->
    <div class="card mb-6">
        <div class="card-body">
            <h4 class="text-primary mb-1">Selamat datang, {{ Auth::user()->nama }}!</h4>
            <p>Paparan ini memaparkan rumusan aktiviti, statistik dan prestasi semasa sistem untuk rujukan pantas anda.</p>
        </div>
    </div>

    <!-- Flash Messages for Impersonation -->
    @if(session('impersonate_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('impersonate_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('impersonate_leave'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bx bx-info-circle me-2"></i>
            {{ session('impersonate_leave') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Debug Role Information - Remove after role assignment is complete -->
    @if (config('app.debug', false))
        <div class="alert alert-warning mb-4">
            <h6><i class="fas fa-bug"></i> Debug Role Information:</h6>
            <strong>Current User:</strong> {{ Auth::user()->nama }}<br>
            <strong>User Roles:</strong> {{ Auth::user()->getRoleNames()->implode(', ') ?: 'No roles assigned' }}<br>
            @if (Auth::user()->getRoleNames()->isEmpty())
                <div class="mt-2 p-2 bg-light border-left border-warning">
                    <small><strong>Note:</strong> This user has no roles assigned. They will see the default
                        dashboard.</small>
                </div>
            @endif
        </div>
    @endif

    <!-- User Ticket Status Cards - Shown to ALL users -->
    <div class="row g-4 mb-6">
        @include('dashboard/partials/card-status-user')
    </div>

    @if (Auth::user()->hasRole('Administrator'))
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
    @elseif(Auth::user()->hasRole('Pengurusan MOF'))
        <div class="row g-4 mb-6 dashboard-row-consistent">
            @include('dashboard/partials/cards-status')
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
            @include('dashboard/partials/chart-trend')
            @include('dashboard/partials/chart-total')
        </div>
    @elseif(Auth::user()->hasAnyRole(['Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']))
        <div class="row g-4 mb-6 dashboard-row-consistent">
            @include('dashboard/partials/cards-status')
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
            @include('dashboard/partials/chart-trend')
            @include('dashboard/partials/chart-total')
            @include('dashboard/partials/chart-assigned')
        </div>
    @elseif(Auth::user()->hasRole('Pegawai Tugasan'))
        <div class="row dashboard-row-consistent">
            @include('dashboard/partials/cards-status')
            @include('dashboard/partials/chart-status')
            @include('dashboard/partials/chart-category')
            @include('dashboard/partials/table-ticket-overdue')
        </div>
    @endif

@endsection
