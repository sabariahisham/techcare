@extends('layouts/layoutMaster')

@section('title', 'Senarai Cuti Umum')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss',
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
   'resources/assets/js/public-holidays/app-public-holidays-list.js',
  'resources/assets/js/public-holidays/app-public-holidays-add.js',
  'resources/assets/js/public-holidays/app-public-holidays-edit.js',
  'resources/assets/js/public-holidays/app-public-holidays-delete.js',
])
@endsection

@section('content')

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Jumlah Cuti Umum Tahun Ini</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalPublicHolidays }}</h4>
              <p class="text-success mb-0">Hari</p>
            </div>
            <small class="mb-0">Tahun {{ now()->year }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="bx bx-calendar bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Cuti Umum Nasional</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $nationalHolidays }}</h4>
              <p class="text-success mb-0">Hari</p>
            </div>
            <small class="mb-0">Tahun {{ now()->year }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-star bx-lg"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bx bx-calendar me-2"></i>SENARAI CUTI UMUM
        <span class="badge bg-label-primary ms-2">Cuti Umum</span>
      </h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddPublicHoliday" aria-controls="offcanvasAddPublicHoliday">
        <i class="bx bx-plus me-2"></i>Tambah Cuti Umum
      </button>
    </div>
     
    
  </div>
  <div class="card-datatable">
    <div class="row g-3 px-4 py-4 border-bottom">
      <div class="col-md-6">
        <label class="form-label mb-3">Filter Negeri</label>
        
        <!-- Selected badges -->
        <div id="selected-states-badges" class="mb-3 d-flex flex-wrap gap-2"></div>
        
        <!-- Dropdown with checkboxes -->
        <div class="dropdown">
          <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="statesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Pilih Negeri...
          </button>
          <div class="dropdown-menu w-100" aria-labelledby="statesDropdown" style="max-height: 300px; overflow-y: auto;">
            <div class="px-3 py-2">
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-national" value="national">
                <label class="form-check-label" for="check-national">Nasional</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-johor" value="Johor">
                <label class="form-check-label" for="check-johor">Johor</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-kedah" value="Kedah">
                <label class="form-check-label" for="check-kedah">Kedah</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-kelantan" value="Kelantan">
                <label class="form-check-label" for="check-kelantan">Kelantan</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-melaka" value="Melaka">
                <label class="form-check-label" for="check-melaka">Melaka</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-ns" value="Negeri Sembilan">
                <label class="form-check-label" for="check-ns">Negeri Sembilan</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-pahang" value="Pahang">
                <label class="form-check-label" for="check-pahang">Pahang</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-pulau-pinang" value="Pulau Pinang">
                <label class="form-check-label" for="check-pulau-pinang">Pulau Pinang</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-perak" value="Perak">
                <label class="form-check-label" for="check-perak">Perak</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-perlis" value="Perlis">
                <label class="form-check-label" for="check-perlis">Perlis</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-selangor" value="Selangor">
                <label class="form-check-label" for="check-selangor">Selangor</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-terengganu" value="Terengganu">
                <label class="form-check-label" for="check-terengganu">Terengganu</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-sabah" value="Sabah">
                <label class="form-check-label" for="check-sabah">Sabah</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-sarawak" value="Sarawak">
                <label class="form-check-label" for="check-sarawak">Sarawak</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-kl" value="Wilayah Persekutuan Kuala Lumpur">
                <label class="form-check-label" for="check-kl">Wilayah Persekutuan Kuala Lumpur</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-labuan" value="Wilayah Persekutuan Labuan">
                <label class="form-check-label" for="check-labuan">Wilayah Persekutuan Labuan</label>
              </div>
              <div class="form-check">
                <input class="form-check-input state-checkbox" type="checkbox" id="check-putrajaya" value="Wilayah Persekutuan Putrajaya">
                <label class="form-check-label" for="check-putrajaya">Wilayah Persekutuan Putrajaya</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
    <table class="datatables-public-holidays table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Bil.</th>
          <th>Tarikh</th>
          <th>Hari</th>
          <th>Nama Cuti Umum</th>
          <th>Negeri Terlibat</th>
          <th>Negeri Berkecuali(Except)</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        @forelse($publicHolidays as $index => $holiday)
          @php
            // Prepare data for filtering
            $affectedStates = is_array($holiday->states) ? $holiday->states : [];
            $exceptStates = is_array($holiday->states_except) ? $holiday->states_except : [];
            $isNational = is_null($holiday->states) || in_array('Nasional', $affectedStates);
            $allStatesList = json_encode($affectedStates);
            $allExceptList = json_encode($exceptStates);
          @endphp
          <tr data-states="{{ $allStatesList }}" data-states-except="{{ $allExceptList }}" data-is-national="{{ $isNational ? 'true' : 'false' }}">
            <td></td>
            <td>{{ $index + 1 }}</td>
            <td>{{ $holiday->holiday_date->format('d/m/Y') }}</td>
            <td>
              @php
                $dayName = [
                  'Monday' => 'Isnin',
                  'Tuesday' => 'Selasa',
                  'Wednesday' => 'Rabu',
                  'Thursday' => 'Khamis',
                  'Friday' => 'Jumaat',
                  'Saturday' => 'Sabtu',
                  'Sunday' => 'Ahad'
                ];
                $hari = $dayName[$holiday->day] ?? $holiday->day;
              @endphp
              <small class="text-muted">{{ $hari }}</small>
            </td>
            <td>{{ $holiday->holiday_name }}</td>
            <td>
              @php
                $allStates = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Pulau Pinang', 'Perak', 'Perlis', 'Selangor', 'Terengganu', 'Sabah', 'Sarawak', 'Wilayah Persekutuan Kuala Lumpur', 'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya'];
                
                // Use states array from JSON - ensure it's an array
                $affectedStates = is_array($holiday->states) ? $holiday->states : [];
                // National if states is null (legacy), marked "Nasional", or lists every state
                $isNational = is_null($holiday->states) || in_array('Nasional', $affectedStates) || count($affectedStates) === count($allStates);
              @endphp
              
              @if($isNational)
                <span class="badge bg-label-success">Nasional</span>
              @else
                <div class="d-flex flex-wrap gap-1">
                  @foreach($affectedStates as $state)
                    <span class="badge bg-label-info">{{ $state }}</span>
                  @endforeach
                </div>
              @endif
            </td>
            <td>
              @php
                // Ensure states_except is an array
                $exceptStates = is_array($holiday->states_except) ? $holiday->states_except : [];
              @endphp
              
              @if(count($exceptStates) > 0)
                <div class="d-flex flex-wrap gap-1">
                  @foreach($exceptStates as $state)
                    <span class="badge bg-label-danger">{{ $state }}</span>
                  @endforeach
                </div>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditPublicHoliday" onclick="editPublicHoliday({{ $holiday->id }})">
                  <i class="bx bx-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-text-danger rounded-pill" onclick="deletePublicHoliday({{ $holiday->id }})">
                  <i class="bx bx-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted">Tiada data cuti umum</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Offcanvas to add new public holiday -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPublicHoliday" aria-labelledby="offcanvasAddPublicHolidayLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddPublicHolidayLabel" class="offcanvas-title">Tambah Cuti Umum</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-public-holiday pt-0" id="addNewPublicHolidayForm">
        @csrf
        <div class="mb-6">
          <label class="form-label" for="add-holiday-date">Tarikh <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="add-holiday-date" name="holiday_date" required>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-holiday-name">Nama Cuti Umum <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="add-holiday-name" name="holiday_name" placeholder="Contoh: Tahun Baru Islam" required>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-holiday-states">Nama Negeri <span class="text-danger">*</span></label>
          <select class="form-select" id="add-holiday-states" name="states[]" multiple required>
            <option value="national">Nasional</option>
            <option value="Johor">Johor</option>
            <option value="Kedah">Kedah</option>
            <option value="Kelantan">Kelantan</option>
            <option value="Melaka">Melaka</option>
            <option value="Negeri Sembilan">Negeri Sembilan</option>
            <option value="Pahang">Pahang</option>
            <option value="Pulau Pinang">Pulau Pinang</option>
            <option value="Perak">Perak</option>
            <option value="Perlis">Perlis</option>
            <option value="Selangor">Selangor</option>
            <option value="Terengganu">Terengganu</option>
            <option value="Sabah">Sabah</option>
            <option value="Sarawak">Sarawak</option>
            <option value="Wilayah Persekutuan Kuala Lumpur">Wilayah Persekutuan Kuala Lumpur</option>
            <option value="Wilayah Persekutuan Labuan">Wilayah Persekutuan Labuan</option>
            <option value="Wilayah Persekutuan Putrajaya">Wilayah Persekutuan Putrajaya</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label" for="add-holiday-states-except">Negeri Berkecuali (Except)</label>
          <select class="form-select" id="add-holiday-states-except" name="states_except[]" multiple>
            <option value="Johor">Johor</option>
            <option value="Kedah">Kedah</option>
            <option value="Kelantan">Kelantan</option>
            <option value="Melaka">Melaka</option>
            <option value="Negeri Sembilan">Negeri Sembilan</option>
            <option value="Pahang">Pahang</option>
            <option value="Pulau Pinang">Pulau Pinang</option>
            <option value="Perak">Perak</option>
            <option value="Perlis">Perlis</option>
            <option value="Selangor">Selangor</option>
            <option value="Terengganu">Terengganu</option>
            <option value="Sabah">Sabah</option>
            <option value="Sarawak">Sarawak</option>
            <option value="Wilayah Persekutuan Kuala Lumpur">Wilayah Persekutuan Kuala Lumpur</option>
            <option value="Wilayah Persekutuan Labuan">Wilayah Persekutuan Labuan</option>
            <option value="Wilayah Persekutuan Putrajaya">Wilayah Persekutuan Putrajaya</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Simpan</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

<!-- Offcanvas to edit public holiday -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditPublicHoliday" aria-labelledby="offcanvasEditPublicHolidayLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditPublicHolidayLabel" class="offcanvas-title">Kemaskini Cuti Umum</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="edit-public-holiday pt-0" id="editPublicHolidayForm">
      @csrf
      @method('PUT')
      <input type="hidden" id="edit-holiday-id" name="holiday_id" />
      <div class="mb-6">
        <label class="form-label" for="edit-holiday-date">Tarikh <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="edit-holiday-date" name="holiday_date" required>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-holiday-name">Nama Cuti Umum <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit-holiday-name" name="holiday_name" required>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-holiday-states">Nama Negeri <span class="text-danger">*</span></label>
        <select class="form-select" id="edit-holiday-states" name="states[]" multiple required>
          <option value="national">Nasional</option>
          <option value="Johor">Johor</option>
          <option value="Kedah">Kedah</option>
          <option value="Kelantan">Kelantan</option>
          <option value="Melaka">Melaka</option>
          <option value="Negeri Sembilan">Negeri Sembilan</option>
          <option value="Pahang">Pahang</option>
          <option value="Pulau Pinang">Pulau Pinang</option>
          <option value="Perak">Perak</option>
          <option value="Perlis">Perlis</option>
          <option value="Selangor">Selangor</option>
          <option value="Terengganu">Terengganu</option>
          <option value="Sabah">Sabah</option>
          <option value="Sarawak">Sarawak</option>
          <option value="Wilayah Persekutuan Kuala Lumpur">Wilayah Persekutuan Kuala Lumpur</option>
          <option value="Wilayah Persekutuan Labuan">Wilayah Persekutuan Labuan</option>
          <option value="Wilayah Persekutuan Putrajaya">Wilayah Persekutuan Putrajaya</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="edit-holiday-states-except">Negeri Berkecuali (Except)</label>
        <select class="form-select" id="edit-holiday-states-except" name="states_except[]" multiple>
          <option value="Johor">Johor</option>
          <option value="Kedah">Kedah</option>
          <option value="Kelantan">Kelantan</option>
          <option value="Melaka">Melaka</option>
          <option value="Negeri Sembilan">Negeri Sembilan</option>
          <option value="Pahang">Pahang</option>
          <option value="Pulau Pinang">Pulau Pinang</option>
          <option value="Perak">Perak</option>
          <option value="Perlis">Perlis</option>
          <option value="Selangor">Selangor</option>
          <option value="Terengganu">Terengganu</option>
          <option value="Sabah">Sabah</option>
          <option value="Sarawak">Sarawak</option>
          <option value="Wilayah Persekutuan Kuala Lumpur">Wilayah Persekutuan Kuala Lumpur</option>
          <option value="Wilayah Persekutuan Labuan">Wilayah Persekutuan Labuan</option>
          <option value="Wilayah Persekutuan Putrajaya">Wilayah Persekutuan Putrajaya</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary me-3 data-submit">Kemaskini</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Batal</button>
    </form>
  </div>
</div>

@endsection
