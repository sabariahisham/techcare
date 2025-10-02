@extends('layouts/layoutMaster')

@section('title', 'Senarai Tiket')

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
  'resources/assets/js/config.js',
  'resources/assets/vendor/libs/jquery/jquery.js',
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
  'resources/assets/js/ticket-list.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
])
@endsection

@section('content')
<h4 class="mb-1">Senarai  Tiket</h4>

<p class="mb-6">Papar, tambah, edit atau padam tiket.</p>
<!-- Ticket Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-tickets table border-top">
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

  <!-- Offcanvas to add new user -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Tambah Log Tiket</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-ticket pt-0" id="addNewTicket" onsubmit="return false">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-user-fullname">Nama Pengadu</label>
          <input type="text" class="form-control" id="add-user-fullname" placeholder="John Doe" name="name" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-user-email">Emel</label>
          <input type="email" id="add-user-email" class="form-control" placeholder="john.doe@example.com" name="email" required />
        </div>
        <div class="mb-6">
          <label class="form-label" for="user-role">Sektor</label>
          {{-- <select id="user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @if(isset($roles) && count($roles) > 0)
              @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            @endif
          </select> --}}
        </div>
        <div class="mb-6">
          <label class="form-label" for="user-role">Bahagian</label>
          {{-- <select id="user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @if(isset($roles) && count($roles) > 0)
              @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            @endif
          </select> --}}
        </div>
        <div class="mb-6">
          <label class="form-label" for="user-role">Seksyen</label>
          {{-- <select id="user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @if(isset($roles) && count($roles) > 0)
              @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            @endif
          </select> --}}
        </div>
        <div class="mb-6">
          <label class="form-label" for="user-role">Unit</label>
          {{-- <select id="user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @if(isset($roles) && count($roles) > 0)
              @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            @endif
          </select> --}}
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
          {{-- <select id="edit-user-role" class="select2 form-select" name="role" required>
            <option value="">Select Role</option>
            @foreach($roles as $role)
              <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
            @endforeach
          </select> --}}
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
<!--/ Ticket Table -->

@endsection
