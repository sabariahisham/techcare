/**
 * App user list (jquery)
 */

'use strict';

$(function () {
  // Edit button click handler is now in app-modules-edit.js

  var dataTableModules = $('.datatables-modules'),
    dt_module,
    userList = baseUrl + 'app/user/list';

  // Users List datatable
  if (dataTableModules.length) {
    dt_module = dataTableModules.DataTable({
      ajax: {
        url: '/modules',
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        error: function (xhr, error, code) {
          console.error('DataTable AJAX Error:', error, code);
          console.error('Response Status:', xhr.status);
          console.error('Response Text:', xhr.responseText);
        }
      },
      serverSide: false, // Changed to false since backend doesn't support server-side processing
      processing: true,
      columns: [
        { data: null, orderable: false, searchable: false },
        { data: null, orderable: false, searchable: false },
        { data: 'module_name' },
        { data: 'created_at' },
         { data: 'updated_at' },
        {
            data: null,
            render: function (data, type, row) {
                return `
                    <div class="d-inline-block">
                        <button class="btn btn-sm btn-icon edit-record" data-id="${row.id}" data-name="${row.module_name}">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon delete-module" data-id="${row.id}" data-name="${row.module_name}">
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
        searchPlaceholder: 'Carian Modul',
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

  // Delete functionality has been moved to delete.js for better organization
  // The delete.js file handles all module deletion with proper validation
});
