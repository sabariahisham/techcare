@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard.js'])
@endsection

@section('content')

    @php
        use Illuminate\Support\Facades\Auth;
    @endphp

    <!-- Dashboard Content -->
    <div class="card mb-6">
        <div class="card-body">
            <h4 class="text-primary mb-1">Selamat datang, {{ Auth::user()->nama }}!</h4>
            <p>Paparan ini memaparkan rumusan aktiviti, statistik dan prestasi semasa sistem untuk rujukan pantas anda.</p>
        </div>
    </div>

    <!-- User Ticket Status Cards -->
    @include('dashboard/partials/card-status-user')

    <!-- Status for Top Management -->
    <div class="row">
        @include('dashboard/partials/cards-status') <!-- Card Status -->
        @include('dashboard/partials/chart-status') <!-- Ticket Status -->
        @include('dashboard/partials/chart-category') <!-- Ticket Category -->
        {{-- @include('dashboard/partials/chart-line-trend') <!-- Monthly Ticket Trend --> --}}
    </div>

    <!-- Additional Components -->
    <div class="row">
        @include('dashboard/partials/chart-trend') <!-- Monthly Ticket Trend -->
        @include('dashboard/partials/chart-total') <!-- Total Tickets -->
        @include('dashboard/partials/chart-assigned') <!-- Assigned Tickets -->

    </div>

    <!-- Assigned Tickets Table -->
    <div class="row">
        @include('dashboard/partials/table-ticket-overdue') <!-- Overdue Tickets Table -->
    </div>

@endsection
