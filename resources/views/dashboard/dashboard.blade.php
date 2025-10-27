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

        @include('dashboard/partials/cards-status')
        @include('dashboard/partials/chart-bar-assigned')
        @include('dashboard/partials/chart-doughnut-status')
        @include('dashboard/partials/chart-line-trend')
        @include('dashboard/partials/chart-polar-category')
        @include('dashboard/partials/table-ticket-overdue')
        @include('dashboard/partials/card-status-user')

    @endsection
