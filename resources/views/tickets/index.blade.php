@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

@section('vendor-style')


@section('content')
<h4 class="mb-1">Senarai  Tiket</h4>

<p class="mb-6">Papar, tambah, edit atau padam tiket.</p>
<!-- Ticket Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-users table border-top">
      <thead class="border-top">
        <tr>
          <th>Bil</th>
          <th>Tarikh Aduan</th>
          <th>Nama Pengadu</th>
          <th>Kategori</th>
          <th>Sub Kategori</th>
          <th>Status</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Ticket Table -->

@endsection
