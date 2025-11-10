@extends('layouts/layoutMaster')

@section('title', 'Senarai Module ')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
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
  'resources/assets/js/modules/app-modules-list.js',
  'resources/assets/js/modules/app-modules-add.js',
  'resources/assets/js/modules/app-modules-edit.js',
  'resources/assets/js/modules/app-modules-delete.js',
  'resources/assets/js/ui-toasts.js',
  'resources/assets/js/extended-ui-sweetalert2.js',
  ])

<style>
.form-control.is-invalid {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.form-control.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Modul</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="total-modules">-</h4>
              {{-- <p class="text-success mb-0">(100%)</p> --}}
            </div>
            <small class="mb-0">Tarikh dikemaskini : {{ date('d-m-Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
               <i class="bx bx-list-ul bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- <p class="mb-6">Lihat, buat, edit atau padam modul.</p> --}}
<!-- Module Table -->
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">SENARAI MODUL</h5>
    <div class="card-header-elements ms-auto">
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddModule">
        <i class="bx bx-plus me-1"></i>Tambah Modul
      </button>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-modules table" id="modules-table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Nama Modul</th>
          <th>Tarikh Dicipta</th>
          <th>Tindakan</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Module Table -->

<!-- Offcanvas to add new module -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddModule" aria-labelledby="offcanvasAddModuleLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddModuleLabel" class="offcanvas-title">Tambah Modul Baharu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="add-new-module pt-0" id="addNewModuleForm">
      @csrf
      <div class="mb-6">
        <label class="form-label" for="add-module-name">Nama Modul <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="add-module-name" placeholder="Masukkan nama modul" name="module_name" required />
        <div class="invalid-feedback"></div>
        <small class="form-text text-muted">Sistem akan menjana 4 permission secara automatik: {nama_modul}.create, {nama_modul}.edit, {nama_modul}.view, {nama_modul}.manage</small>
      </div>
      <button type="submit" class="btn btn-primary me-3" id="btn-add-module">
        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
        Simpan
      </button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

<!-- Offcanvas to edit module -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditModule" aria-labelledby="offcanvasEditModuleLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditModuleLabel" class="offcanvas-title">Edit Modul</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-module pt-0" id="editModuleForm">
      @csrf
      <input type="hidden" id="edit-module-uuid" name="module_uuid" />
      <div class="mb-6">
        <label class="form-label" for="edit-module-name">Nama Modul <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit-module-name" placeholder="Masukkan nama modul" name="module_name" required />
        <div class="invalid-feedback"></div>
        <small class="form-text text-muted">Perubahan nama modul akan mengemaskini permission yang berkaitan secara automatik</small>
      </div>
      <button type="submit" class="btn btn-primary me-3" id="btn-edit-module">
        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
        Kemaskini
      </button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var modulesTable = $('#modules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('modules.data') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'module_name', name: 'module_name' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
        language: {
            sLengthMenu: '_MENU_',
            search: '',
            searchPlaceholder: 'Cari...'
        },
        drawCallback: function() {
            // Update total modules count
            var info = modulesTable.page.info();
            $('#total-modules').text(info.recordsTotal);
        }
    });

    // Add Module Form Submission
    $('#addNewModuleForm').on('submit', function(e) {
        e.preventDefault();

        var btn = $('#btn-add-module');
        var spinner = btn.find('.spinner-border');
        var formData = new FormData(this);

        // Reset validation states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Show loading state
        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: "{{ route('modules.store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#offcanvasAddModule').offcanvas('hide');
                    $('#addNewModuleForm')[0].reset();
                    modulesTable.ajax.reload();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var input = $('input[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error('Ralat berlaku. Sila cuba lagi.');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Edit Module Form Submission
    $('#editModuleForm').on('submit', function(e) {
        e.preventDefault();

        var btn = $('#btn-edit-module');
        var spinner = btn.find('.spinner-border');
        var uuid = $('#edit-module-uuid').val();
        var formData = new FormData(this);

        // Reset validation states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Show loading state
        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: "/modules/update/" + uuid,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#offcanvasEditModule').offcanvas('hide');
                    $('#editModuleForm')[0].reset();
                    modulesTable.ajax.reload();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        var input = $('input[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    toastr.error('Ralat berlaku. Sila cuba lagi.');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });
});

// Edit Module Function
function editModule(uuid) {
    $.ajax({
        url: "/modules/" + uuid + "/edit",
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#edit-module-uuid').val(response.data.uuid);
                $('#edit-module-name').val(response.data.module_name);
                $('#offcanvasEditModule').offcanvas('show');
            }
        },
        error: function() {
            toastr.error('Ralat berlaku semasa mengambil data modul.');
        }
    });
}

// Delete Module Function
function deleteModule(uuid) {
    Swal.fire({
        title: 'Adakah anda pasti?',
        text: "Modul dan semua permission yang berkaitan akan dipadamkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, padam!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/modules/" + uuid,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Berjaya!', response.message, 'success');
                        $('#modules-table').DataTable().ajax.reload();
                    }
                },
                error: function() {
                    Swal.fire('Ralat!', 'Ralat berlaku semasa mempadam modul.', 'error');
                }
            });
        }
    });
}
</script>

@endsection
