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
<div class="container">
  <div class="card mb-3">
    <div class="card-body">
      <h4 class="text-primary mb-1">Selamat datang, {{ Auth::user()->nama }}!</h4>
      <p>Paparan ini memaparkan rumusan aktiviti, statistik dan prestasi semasa sistem untuk rujukan pantas anda.</p>
    </div>
  </div>

@include('_partials/_dashboard/cards-status')
@include('_partials/_dashboard/chart-bar-assigned')
@include('_partials/_dashboard/chart-doughnut-status')
@include('_partials/_dashboard/chart-line-trend')
@include('_partials/_dashboard/chart-polar-category')
@include('_partials/_dashboard/table-ticket-overdue')
@include('_partials/_dashboard/card-status-user')

@endsection
