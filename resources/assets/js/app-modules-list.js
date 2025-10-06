/**
 * App user list (jquery)
 */

'use strict';

$(function () {
  // Add click handler for edit button
  $(document).on('click', '.edit-module', function() {
      const moduleId = $(this).data('id');
      const moduleName = $(this).data('name');

      $('#editModuleName').val(moduleName);
      $('#editModuleForm').attr('data-module-id', moduleId);
      $('#editModuleModal').modal('show');
  });

  var dataTableModules = $('.datatables-modules'),
    dt_module,
    userList = baseUrl + 'app/user/list';

  // Users List datatable
  if (dataTableModules.length) {
    dt_module = dataTableModules.DataTable({
      ajax: '/modules', // Point to your backend route
      serverSide: false, // Changed to false since backend doesn't support server-side processing
      processing: true,
      columns: [
        { data: '' },
        { data: null },
        { data: 'module_name' },
        { data: 'updated_at' },
        { data: 'created_at' },
        {
            data: null,
            render: function (data, type, row) {
                return `
                    <div class="d-inline-block">
                        <button class="btn btn-sm btn-icon edit-module" data-id="${row.id}" data-name="${row.module_name}">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon delete-module" data-id="${row.id}">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>`;
            }
        }
      ],
      order: [[3, 'desc']],
      dom:
        '<"row"' +
        '<"col-sm-12 col-md-3" l>' +
        '<"col-sm-12 col-md-9"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-md-end justify-content-center flex-wrap"<"me-4 mt-n6 mt-md-0"f>B>>' +
        '>t' +
        '<"row mx-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: 'Show _MENU_',
        search: '',
        searchPlaceholder: 'Search Modules',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: [
        {
          text: '<i class="bx bx-plus bx-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Tambah Modul</span>',
          className: 'add-new btn btn-primary mb-6 mb-md-0',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddModule'
          },
          init: function (api, node, config) {
            $(node).removeClass('btn-secondary');
          }
        }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          orderable: false,
          searchable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // Bil. (Row Number)
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return meta.row + 1;
          }
        },
        {
          // Module Name
          targets: 2,
          orderable: true,
        },
        {
          // Updated Date
          targets: 3,
          orderable: true,
          render: function (data, type, full, meta) {
            var $date = new Date(full['updated_at']);
            return '<span class="text-nowrap">' +
              $date.toLocaleDateString('en-MY', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                timeZone: 'Asia/Kuala_Lumpur'
              }) + ', ' +
              $date.toLocaleTimeString('en-MY', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Kuala_Lumpur'
              }) + '</span>';
          }
        },
        {
          // Created Date
          targets: 4,
          orderable: true,
          render: function (data, type, full, meta) {
            var $date = new Date(full['created_at']);
            return '<span class="text-nowrap">' +
              $date.toLocaleDateString('en-MY', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                timeZone: 'Asia/Kuala_Lumpur'
              }) + ', ' +
              $date.toLocaleTimeString('en-MY', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Kuala_Lumpur'
              }) + '</span>';
          }
        },
        {
          // Actions
          targets: -1,
          searchable: false,
          title: 'Actions',
          orderable: false,
        }
      ]
    });

  }

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);

  // Add click handler for delete button
  $(document).on('click', '.delete-module', function() {
      const moduleId = $(this).data('id');
      Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'No, cancel!',
          customClass: {
              confirmButton: 'btn btn-primary me-3',
              cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
      }).then((result) => {
          if (result.isConfirmed) {
              deleteModule(moduleId);
          }
      });
  });

  function deleteModule(moduleId) {
      $.ajax({
          url: '/modules/' + moduleId,
          type: 'DELETE',
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function(data) {
              if (data.success) {
                  // Reload the datatable
                  $('.datatables-modules').DataTable().ajax.reload();

                  toastr.success(data.message, '', {
                      closeButton: true,
                      timeOut: 3000,
                      progressBar: true,
                      positionClass: 'toast-top-right',
                      showClass: "animate__animated animate__bounceInRight",
                      hideClass: "animate__animated animate__fadeOut",
                      "toastClass": "toast ngx-toastr bg-success text-white",
                      "closeClass": "toast-close-button",
                      "titleClass": "toast-title",
                      "messageClass": "toast-message",
                      "progressClass": "toast-progress"
                  });

              } else {
                  toastr.error(data.message, '', {
                      closeButton: true,
                      timeOut: 3000,
                      progressBar: true,
                      positionClass: 'toast-top-right',
                      showClass: "animate__animated animate__bounceInRight",
                      hideClass: "animate__animated animate__fadeOut",
                      "toastClass": "toast ngx-toastr bg-danger text-white",
                      "closeClass": "toast-close-button",
                      "titleClass": "toast-title",
                      "messageClass": "toast-message",
                      "progressClass": "toast-progress"
                  });
              }
          },
          error: function(xhr, status, error) {
              toastr.error('An error occurred while deleting the module.', '', {
                  closeButton: true,
                  timeOut: 3000,
                  progressBar: true,
                  positionClass: 'toast-top-right',
                  showClass: "animate__animated animate__bounceInRight",
                  hideClass: "animate__animated animate__fadeOut",
                  "toastClass": "toast ngx-toastr bg-danger text-white",
                  "closeClass": "toast-close-button",
                  "titleClass": "toast-title",
                  "messageClass": "toast-message",
                  "progressClass": "toast-progress"
              });
          }
      });
  }
});
