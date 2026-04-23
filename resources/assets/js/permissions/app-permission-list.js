/**
 * App user list (jquery)
 */

import logger from '../logger.js';

'use strict';

$(function () {
  logger.log('Permission list script loaded');
  logger.log('jQuery available:', typeof $ !== 'undefined');
  logger.log('DataTables available:', typeof $.fn.DataTable !== 'undefined');
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
          // Get module data from first row in group for buttons
          var firstRowData = rows.data()[0];
          var moduleId = firstRowData.module_id || null;

          var moduleActions = '';
          if (moduleId && window.isAdmin) {
            moduleActions = '<div class="float-end">' +
              '<button type="button" class="btn btn-icon btn-text-primary rounded-pill btn-sm edit-module me-1"' +
              ' data-id="' + moduleId + '"' +
              ' data-name="' + group + '"' +
              ' data-bs-toggle="tooltip"' +
              ' data-bs-placement="top"' +
              ' title="Edit Module">' +
              '<i class="bx bx-edit"></i>' +
              '</button>' +
              '<button type="button" class="btn btn-icon btn-text-danger rounded-pill btn-sm delete-module"' +
              ' data-id="' + moduleId + '"' +
              ' data-name="' + group + '"' +
              ' data-bs-toggle="tooltip"' +
              ' data-bs-placement="top"' +
              ' title="Delete Module">' +
              '<i class="bx bx-trash"></i>' +
              '</button>' +
              '</div>';
          }

          return '<div class="d-flex justify-content-between align-items-center">' +
            '<strong>' + group + ' (' + rows.count() + ' capaian)</strong>' +
            moduleActions +
            '</div>';
        }
      },
      columns: [{
        data: ''
      },
      {
        data: 'DT_RowIndex',
        orderable: false,
        searchable: false
      },
      {
        data: 'name'
      },
      {
        data: 'module'
      },
      {
        data: 'description'
      },
      {
        data: 'assigned_to'
      },
      // { data: 'created_date' },
      {
        data: 'action'
      }
      ],
      columnDefs: [{
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
        // Description
        targets: 4,
        render: function (data, type, full, meta) {
          return '<span class="text-nowrap">' + (data || '-') + '</span>';
        }
      },
      {
        // Assigned To (Roles) - server already renders HTML
        targets: 5,
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
      order: [
        [3, 'asc'],
        [2, 'asc']
      ], // Order by module first, then by name
      dom: '<"row"' +
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
      // Buttons with Dropdown - Only show for Administrator
      buttons: window.isAdmin ? [{
        text: '<i class="bx bx-cube bx-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Tambah Modul</span>',
        className: 'add-module btn btn-success mb-6 mb-md-0 me-3',
        attr: {
          'data-bs-toggle': 'offcanvas',
          'data-bs-target': '#offcanvasAddModule'
        },
        init: function (api, node, config) {
          $(node).removeClass('btn-secondary');
        }
      },
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
      ] : [],
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
                ?
                '<tr data-dt-row="' +
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
                '</tr>' :
                '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
        logger.log('DataTable initialized');

        // Check if buttons exist after initialization
        setTimeout(function () {
          const editButtons = $('.edit-permission');
          const deleteButtons = $('.delete-permission');
          logger.log('Edit buttons found:', editButtons.length);
          logger.log('Delete buttons found:', deleteButtons.length);

          if (editButtons.length > 0) {
            logger.log('First edit button data-id:', editButtons.first().data('id'));
          }
          if (deleteButtons.length > 0) {
            logger.log('First delete button data-id:', deleteButtons.first().data('id'));
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
                d.forEach(function (role) {
                  roles.add(role);
                });
              }
            });

            // Add roles to select
            Array.from(roles).sort().forEach(function (role) {
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

  // Event delegation for module edit buttons
  $(document).on('click', '.edit-module', function (e) {
    e.preventDefault();
    logger.log('Edit module button clicked');

    var moduleId = $(this).data('id');
    var moduleName = $(this).data('name');

    logger.log('Module ID:', moduleId, 'Module Name:', moduleName);

    if (!moduleId) {
      console.error('Module ID not found');
      return;
    }

    // Fetch module data and populate form
    $.ajax({
      url: '/modules/' + moduleId + '/edit',
      type: 'GET',
      success: function (response) {
        logger.log('Edit response:', response);

        if (response.success && response.data) {
          // Populate edit form
          $('#edit-module-id').val(moduleId);
          $('#edit-module-name').val(response.data.module_name);

          // Show edit module offcanvas
          $('#offcanvasEditModule').offcanvas('show');
        } else {
          toastr.error('Gagal mengambil data modul', 'Ralat!');
        }
      },
      error: function (xhr, status, error) {
        console.error('Edit fetch error:', xhr.responseJSON);
        toastr.error('Gagal mengambil data modul', 'Ralat!');
      }
    });
  });

  // Event delegation for module delete buttons
  $(document).on('click', '.delete-module', function (e) {
    e.preventDefault();
    logger.log('Delete module button clicked');

    var moduleId = $(this).data('id');
    var moduleName = $(this).data('name');

    logger.log('Module ID:', moduleId, 'Module Name:', moduleName);

    if (!moduleId) {
      console.error('Module ID not found');
      return;
    }

    // Show confirmation dialog
    Swal.fire({
      title: 'Adakah anda pasti?',
      text: 'Anda akan memadamkan modul "' + moduleName + '" dan semua capaian yang berkaitan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        deleteModule(moduleId);
      }
    });
  });

  // Function to delete module
  function deleteModule(moduleId) {
    logger.log('Deleting module:', moduleId);

    $.ajax({
      url: '/modules/' + moduleId,
      type: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        logger.log('Delete response:', response);

        if (response.status === 'success' || response.success) {
          Swal.fire(
            'Berjaya!',
            'Modul telah berjaya dipadamkan.',
            'success'
          );

          // Refresh DataTable
          if ($.fn.DataTable.isDataTable('.datatables-permissions')) {
            $('.datatables-permissions').DataTable().ajax.reload();
          }
        } else {
          Swal.fire(
            'Ralat!',
            'Terdapat masalah semasa memadamkan modul.',
            'error'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error('Delete error:', xhr.responseJSON);

        var errorMessage = 'Terdapat masalah semasa memadamkan modul.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire(
          'Ralat!',
          errorMessage,
          'error'
        );
      }
    });
  }
});
