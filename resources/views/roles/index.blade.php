@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Roles - Apps')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  ])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/toastr/toastr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  ])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-access-roles.js',
  'resources/assets/js/modal-add-role.js',
  'resources/assets/js/modal-edit-role.js',
  'resources/assets/js/modal-delete-role.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])
@endsection

@section('content')
<h4 class="mb-1">Roles List</h4>

<p class="mb-6">View, create, edit or delete roles.</p>
<!-- Role cards -->
<div id="rolesDiv" class="row g-6">

  @foreach($roles as $role)
    <div class="col-xl-4 col-lg-6 col-md-6">
      <div class="card">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-normal mb-0 text-body">Total {{ $role->users->count() }} users</h6>
            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">

              @foreach($role->users->take(3) as $user)
                @php
                  $initials = implode('', array_map(function($word) {
                    return strtoupper(substr($word, 0, 1));
                  }, array_slice(explode(' ', $user->name), 0, 3)));
                @endphp
                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="{{ $user->name }}" class="avatar pull-up">
                  @if($user->avatar)
                    <img class="rounded-circle" src="{{ asset($user->avatar) }}" alt="Avatar">
                  @else
                    <span class="avatar-initial rounded-circle bg-label-primary">{{ $initials }}</span>
                  @endif
                </li>
              @endforeach

              @if($role->users->count() > 3)
                <li class="avatar">
                  <span class="avatar-initial rounded-circle pull-up" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $role->users->count() - 3 }} more">+{{ $role->users->count() - 3 }}</span>
                </li>
              @endif
            </ul>
          </div>

          <div class="d-flex justify-content-between align-items-end">
            <div class="role-heading">
              <h5 class="mb-1">{{ $role->name }}</h5>
              <div class="role-actions">
                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-role-id="{{ $role->id }}" class="role-edit-modal me-2"><span>Edit Role</span></a>
                <!-- <a href="javascript:;" onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')" class="text-danger"><span>Delete Role</span></a> -->

                <a href="javascript:void(0);"
                  onclick="window.deleteRole({{ $role->id }}, '{{ $role->name }}')"
                  class="text-danger">
                  <span>Delete Role</span>
                </a>
              </div>
            </div>
            <a href="javascript:void(0);"><i class="bx bx-copy bx-md text-muted"></i></a>
          </div>
        </div>
      </div>
    </div>
  @endforeach


  <!-- add new roles card -->
  <div class="col-xl-4 col-lg-6 col-md-6">
    <div class="card h-100">
      <div class="row h-100">
        <div class="col-sm-5">
          <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-4 ps-6">
            <img src="{{asset('assets/img/illustrations/lady-with-laptop-'.$configData['style'].'.png')}}" class="img-fluid" alt="Image" width="120" data-app-light-img="illustrations/lady-with-laptop-light.png" data-app-dark-img="illustrations/lady-with-laptop-dark.png">
          </div>
        </div>
        <div class="col-sm-7">
          <div class="card-body text-sm-end text-center ps-sm-0">

            <button onclick="checkModules({{ $hasModules }})" class="btn btn-sm btn-primary mb-4 text-nowrap add-new-role"
              {{ $hasModules ? 'data-bs-target="#addRoleModal" data-bs-toggle="modal"' : '' }}
              >Add New Role
            </button>

            <p class="mb-0"> Add new role, <br> if it doesn't exist.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- end of add new roles card -->
</div>
<!--/ Role cards -->

<!-- Add Role Modal -->
@include('_partials/_modals/modal-add-role')
@include('_partials/_modals/modal-edit-role')
<!-- / Add Role Modal -->
@endsection
