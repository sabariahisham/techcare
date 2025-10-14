@extends('layouts/layoutMaster')

@section('title', 'Senarai Pengguna')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/toastr/toastr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
  'resources/assets/vendor/libs/toastr/toastr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-user-list.js',
  'resources/assets/js/app-user-add.js',
  'resources/assets/js/app-user-edit.js',
  'resources/assets/js/app-user-delete.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  {{-- <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Bahagian</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">23</h4>
              <p class="text-success mb-0">(??)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }} </small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-building bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Pengguna</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ number_format($totalUsers) }}</h4>
              <p class="text-success mb-0">(100%)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-group bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pengguna Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ number_format($activeUsers) }}</h4>
              <p class="text-success mb-0">({{ $activePercentage }}%)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-user-check bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pengguna Tidak Aktif</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ number_format($inactiveUsers) }}</h4>
              <p class="text-danger mb-0">({{ $inactivePercentage }}%)</p>
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-user-x bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Users List Table -->
<div class="card">
  {{-- <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Search Filters</h5>
    <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0 g-6">
      <div class="col-md-4 user_role"></div>
      <div class="col-md-4"></div>
      <div class="col-md-4"></div>
    </div>
  </div> --}}
  <div class="card-datatable table-responsive">
    <table class="datatables-users table border-top table-sm" style="width: 100%; min-width: 1200px; table-layout: fixed; font-size: 0.775rem;">
      <thead>
        <tr>
          {{-- <th style="width: 40px; white-space: nowrap; font-size: 0.75rem;"></th> --}}
          {{-- <th style="width: 40px; white-space: nowrap; font-size: 0.75rem;">Bil</th> --}}
          <th style="width: 120px; white-space: nowrap; font-size: 0.75rem;">Nama</th>
          {{-- <th>LDAP</th> --}}
          <th style="width: 80px; white-space: nowrap; font-size: 0.75rem;">Emel</th>
          <th style="width: 100px; white-space: nowrap; font-size: 0.75rem;">No.KP</th>
          <th style="width: 100px; white-space: nowrap; font-size: 0.75rem;">No.Tel(O)</th>
          <th style="width: 100px; white-space: nowrap; font-size: 0.75rem;">No.Tel(M)</th>
          {{-- <th>Sektor</th> --}}
          <th style="width: 120px; white-space: nowrap; font-size: 0.75rem;">Bahagian</th>
          <th style="width: 100px; white-space: nowrap; font-size: 0.75rem;">Seksyen</th>
          <th style="width: 80px; white-space: nowrap; font-size: 0.75rem;">Unit</th>
          {{-- <th>Kod Skim</th> --}}
          {{-- <th>Kod Kump Khidmat</th> --}}
          <th style="width: 80px; white-space: nowrap; font-size: 0.75rem;">Kod Jwtn</th>
          {{-- <th style="width: 100px; white-space: nowrap; font-size: 0.75rem;">Kod Jawatan</th> --}}
          {{-- <th>Gelaran Jawatan</th> --}}
          <th style="width: 70px; white-space: nowrap; font-size: 0.75rem;">Status</th>
          {{-- <th>Tindakan</th> --}}
        </tr>
      </thead>
    </table>
  </div>
  <!-- Offcanvas to add new user -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add User</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-user pt-0" id="addNewUserForm" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-user-fullname">Full Name</label>
          <input type="text" class="form-control" id="add-user-fullname" placeholder="John Doe" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-user-email">Email</label>
          <input type="email" id="add-user-email" class="form-control" placeholder="john.doe@example.com" name="email" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="user-role">User Role</label>
          <select id="user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @if(isset($roles) && count($roles) > 0)
              @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-user-agency">Agency</label>
          <input type="text" class="form-control" id="add-user-agency" placeholder="Agency Name" name="agency" required />
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Offcanvas to edit user -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditUser" aria-labelledby="offcanvasEditUserLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasEditUserLabel" class="offcanvas-title">Edit User</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="edit-user pt-0" id="editUserForm" onsubmit="return false">
        @csrf
        <input type="hidden" id="edit-user-id" name="id">
        <div class="mb-6">
          <label class="form-label" for="edit-user-fullname">Full Name</label>
          <input type="text" class="form-control" id="edit-user-fullname" placeholder="John Doe" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-user-email">Email</label>
          <input type="email" id="edit-user-email" class="form-control" placeholder="john.doe@example.com" name="email" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-user-role">User Role</label>
          <select id="edit-user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @foreach($roles as $role)
              <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="edit-user-agency">Agency</label>
          <input type="text" class="form-control" id="edit-user-agency" placeholder="Agency Name" name="agency" required />
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Update</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </form>
    </div>
  </div>

</div>

@endsection
