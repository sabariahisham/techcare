/**
 * App user list (jquery)
 */

'use strict';

$(function () {
  console.log('Permission list script loaded');
  console.log('jQuery available:', typeof $ !== 'undefined');
  console.log('DataTables available:', typeof $.fn.DataTable !== 'undefined');
  var dataTablePermissions = $('.datatables-permissions'),
    dt_permission,
    userList = baseUrl + 'app/user/list';

  // Users List datatable
  if (dataTablePermissions.length) {
    dt_permission = dataTablePermissions.DataTable({
      ajax: '/admin/permissions/data', // Point to your backend route
      serverSide: true, // Enable server-side processing since backend supports it
      processing: true,
      rowGroup: {
        dataSrc: 'module', // Group by module column
        startRender: function (rows, group) {
          return '<strong>' + group + ' (' + rows.count() + ' capaian)</strong>';
        }
      },
      columns: [
        { data: '' },
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'name' },
        { data: 'module' },
        { data: 'description' },
        { data: 'assigned_to' },
        // { data: 'created_date' },
        { data: 'action' }
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
          orderable: false
        },
        {
          // Permission Name
          targets: 2,
          render: function (data, type, full, meta) {
            return '<span class="text-nowrap text-heading">' + data + '</span>';
          }
        },
        {
          // Module
          targets: 3,
          visible: false // Hide this column since we're using it for grouping
        },
        {
          // Assigned To (Roles) - server already renders HTML
          targets: 4,
          orderable: false
        },
        // {
        //   // Created Date
        //   targets: 5,
        //   orderable: true,
        //   render: function (data, type, full, meta) {
        //     var $date = new Date(full['created_at']);
        //     return '<span class="text-nowrap">' +
        //       $date.toLocaleDateString('en-MY', {
        //         day: 'numeric',
        //         month: 'short',
        //         year: 'numeric',
        //         timeZone: 'Asia/Kuala_Lumpur'
        //       }) + ', ' +
        //       $date.toLocaleTimeString('en-MY', {
        //         hour: 'numeric',
        //         minute: '2-digit',
        //         hour12: true,
        //         timeZone: 'Asia/Kuala_Lumpur'
        //       }) + '</span>';
        //   }
        // },
        {
          // Actions - Use server-generated HTML (backend handles button rendering)
          targets: -1,
          searchable: false,
          title: 'Tindakan',
          orderable: false,
          className: 'text-center'
        }
      ],
      order: [[3, 'asc'], [2, 'asc']], // Order by module first, then by name
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
        searchPlaceholder: 'Carian...',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: [
        {
          text: '<i class="bx bx-plus bx-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Tambah Capaian</span>',
          className: 'add-new btn btn-primary mb-6 mb-md-0',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddPermission'
          },
          init: function (api, node, config) {
            $(node).removeClass('btn-secondary');
          }
        }
      ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
        console.log('DataTable initialized');

        // Check if buttons exist after initialization
        setTimeout(function() {
          const editButtons = $('.edit-permission');
          const deleteButtons = $('.delete-permission');
          console.log('Edit buttons found:', editButtons.length);
          console.log('Delete buttons found:', deleteButtons.length);

          if (editButtons.length > 0) {
            console.log('First edit button data-id:', editButtons.first().data('id'));
          }
          if (deleteButtons.length > 0) {
            console.log('First delete button data-id:', deleteButtons.first().data('id'));
          }
        }, 1000);

        // Adding role filter once table initialized
        this.api()
          .columns(4)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserRole" class="form-select text-capitalize"><option value=""> Select Role </option></select>'
            )
              .appendTo('.user_role')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? val : '', true, false).draw();
              });

            // Get unique roles from all permissions
            var roles = new Set();
            column.data().each(function (d, j) {
              if (d && Array.isArray(d)) {
                d.forEach(function(role) {
                  roles.add(role);
                });
              }
            });

            // Add roles to select
            Array.from(roles).sort().forEach(function(role) {
              select.append('<option value="' + role + '" class="text-capitalize">' + role + '</option>');
            });
          });
      }
    });
  }

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
