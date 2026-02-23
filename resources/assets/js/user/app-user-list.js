/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_user_table = $('.datatables-users'),
    select2 = $('.select2'),
    userView = baseUrl + 'app/user/view/account',
    statusObj = {
      1: { title: 'Pending', class: 'bg-label-warning' },
      2: { title: 'Active', class: 'bg-label-success' },
      3: { title: 'Inactive', class: 'bg-label-secondary' }
    };

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      processing: true,
      serverSide: true,
      pageLength: 100,
      ajax: {
        url: '/user-management/data',
        error: function(xhr, error, code) {
          console.error('DataTables Ajax Error:', error);
          console.error('Status Code:', code);
          console.error('Response:', xhr.responseText);

          // Show user-friendly error message
          Swal.fire({
            title: 'Error Loading Data',
            html: `Failed to load user data. Please check the console for details.<br><small>Error: ${error}</small>`,
            icon: 'error',
            confirmButtonText: 'OK'
          });
        }
      },
      columns: [
        {
          // For Responsive
          data: null,
          defaultContent: '',
          orderable: false,
          searchable: true
        },
         {
          // Index column
          data: null,
          render: function (data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
          }
        },
        { data: 'nama', name: 'nama' },
        { data: 'role', name: 'role' },
        // { data: 'id_pgn_ldap', name: 'id_pgn_ldap' },
        { data: 'email', name: 'email' },
        { data: 'no_kp', name: 'no_kp' },
        { data: 'tel_no_p', name: 'tel_no_p' },
        { data: 'tel_no_hp', name: 'tel_no_hp' },
        // { data: 'sektor_nama', name: 'sektor_nama' },
        { data: 'bahagian_nama', name: 'bahagian_nama' },
        { data: 'seksyen_nama', name: 'seksyen_nama' },
        { data: 'unit_nama', name: 'unit_nama' },
        // { data: 'kod_skim', name: 'kod_skim' },
        // { data: 'kod_kump_khidmat', name: 'kod_kump_khidmat' },
        {data: 'kod_jwtn',name: 'kod_jwtn'},
        // { data: 'jawatan_name', name: 'jawatan_name' },
        // { data: 'nama_gred', name: 'nama_gred' },
        {
          data: 'kodaktif',
          name: 'kodaktif',
           render: function (data, type, row) {
            if (type === 'display') {
              return data == 1 ? 'AKTIF' : 'TIDAK AKTIF';
            }
            return data;
          }
        },
        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false,
          width: '150px',
          className: 'text-center'
        }

      ],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle me-4 d-none', // Add d-none class to hide the button
          text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="bx bx-printer me-2" ></i>Print',
              className: 'dropdown-item'
            },
            {
              extend: 'csv',
              text: '<i class="bx bx-file me-2" ></i>Csv',
              className: 'dropdown-item'
            },
            {
              extend: 'excel',
              text: '<i class="bx bxs-file-export me-2"></i>Excel',
              className: 'dropdown-item'
            },
            {
              extend: 'pdf',
              text: '<i class="bx bxs-file-pdf me-2"></i>Pdf',
              className: 'dropdown-item'
            },
            {
              extend: 'copy',
              text: '<i class="bx bx-copy me-2" ></i>Copy',
              className: 'dropdown-item'
            }
          ]
        }
      ],
      // Initialize role filter
      initComplete: function () {
        // Adding role filter once table initialized
        this.api()
          .columns(3)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserRole" class="form-select text-capitalize"><option value=""> Select Role </option></select>'
            )
              .appendTo('.user_role')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
              });
          });
      },
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Carian'
      },
      // Add debugging
      drawCallback: function(settings) {
        console.log('DataTable draw completed');
        console.log('Table data:', settings.json);
        // Initialize tooltips for action buttons
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Check if action buttons exist
        const actionButtons = $('.datatables-users tbody .impersonate-user');
        console.log('Found impersonate buttons:', actionButtons.length);
      }
    });
  }

  // Delete Record functionality removed - only impersonate actions are enabled

  // Handle impersonate button clicks with confirmation
  $(document).on('click', '.impersonate-user', function(e) {
    e.preventDefault();

    const impersonateUrl = $(this).attr('href');
    const userName = $(this).data('user-name') || 'this user';

    // Get the row data to check user role
    const $row = $(this).closest('tr');
    const rowData = dt_user.row($row).data();
    const userRole = rowData ? rowData.role : null;

    // Additional client-side validation for Administrator roles
    if (userRole === 'Administrator') {
      Swal.fire({
        title: 'Tindakan Tidak Dibenarkan',
        html: `Pengguna dengan peranan <strong>Administrator</strong> tidak boleh diambil alih.<br><small class="text-muted">Hanya Administrator boleh mengambil alih pengguna lain, tetapi tidak boleh mengambil alih sesama Administrator.</small>`,
        icon: 'error',
        confirmButtonColor: '#696cff',
        confirmButtonText: '<i class="bx bx-check me-1"></i>Faham'
      });
      return;
    }

    // Show confirmation dialog using SweetAlert2
    Swal.fire({
      title: 'Ambil Alih Pengguna',
      html: `Adakah anda pasti untuk mengambil alih sebagai Pengguna <strong>${userName}</strong>?<br><small class="text-muted">Semua tindakan akan dilakukan sebagai pengguna ini. Peranan: <span class="badge bg-primary">${userRole || 'Tidak diketahui'}</span></small>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#696cff',
      cancelButtonColor: '#8592a3',
      confirmButtonText: '<i class="bx bx-user-check me-1"></i>Ya, Ambil Alih!',
      cancelButtonText: '<i class="bx bx-x me-1"></i>Batal',
      showDenyButton: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading state
        Swal.fire({
          title: 'Mengambil Alih...',
          text: 'Sila tunggu semasa kami menukar ke akaun pengguna.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Redirect to impersonate URL
        window.location.href = impersonateUrl;
      }
    });
  });

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});

// Validation & Phone mask
(function () {
  const phoneMaskList = document.querySelectorAll('.phone-mask'),
    addNewUserForm = document.getElementById('addNewUserForm');

  // Phone Number
  if (phoneMaskList) {
    phoneMaskList.forEach(function (phoneMask) {
      new Cleave(phoneMask, {
        phone: true,
        phoneRegionCode: 'US'
      });
    });
  }
  // Add New User Form Validation
  const fv = FormValidation.formValidation(addNewUserForm, {
    fields: {
      userFullname: {
        validators: {
          notEmpty: {
            message: 'Please enter fullname '
          }
        }
      },
      userEmail: {
        validators: {
          notEmpty: {
            message: 'Please enter your email'
          },
          emailAddress: {
            message: 'The value is not a valid email address'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        // Use this for enabling/changing valid/invalid class
        eleValidClass: '',
        rowSelector: function (field, ele) {
          // field is the field name & ele is the field element
          return '.mb-6';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      // Submit the form when all fields are valid
      // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  });
})();
