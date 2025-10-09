/**
 * App roles list (jquery)
 */

'use strict';

$(function () {
  // Note: Edit button click handler is now in app-roles-edit.js

  var dataTableRoles = $('.datatables-roles'),
    dt_role;

  // Roles List datatable
  if (dataTableRoles.length) {
    dt_role = dataTableRoles.DataTable({
      ajax: '/admin/roles/data', // Point to your backend route
      serverSide: false, // Changed to false since backend doesn't support server-side processing
      processing: true,
      columns: [
        { data: '' },
        { data: null },
        { data: 'name' },
        { data: 'user_names' },
        { data: 'module_names' },
        { data: 'users_count' },
        {
            data: null,
            render: function (data, type, row) {
                return `
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-icon edit-role" data-id="${row.id}" data-name="${row.name}" title="Edit Role">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon delete-record" data-id="${row.id}" title="Delete Role">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>`;
            }
        }
      ],
      order: [[2, 'asc']],
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
          text: '<i class="bx bx-plus bx-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Tambah Peranan</span>',
          className: 'add-new btn btn-primary mb-6 mb-md-0',
          attr: {
            'data-bs-toggle': 'modal',
            'data-bs-target': '#modalAddRole'
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
          // Role Name
          targets: 2,
          orderable: true,
        },
        {
          // Users
          targets: 3,
          orderable: true,
          render: function (data, type, full, meta) {
            if (full['user_names'] && Array.isArray(full['user_names']) && full['user_names'].length > 0) {
              // Display user names as badges
              let userBadges = full['user_names'].map(function(userName) {
                return '<span class="badge bg-label-primary me-1 mb-1">' + userName + '</span>';
              }).join('');

              return '<div class="d-flex flex-wrap">' + userBadges + '</div>';
            } else {
              return '<span class="badge bg-label-secondary">No users assigned</span>';
            }
          }
        },
        {
          // Module Names
          targets: 4,
          orderable: true,
          title: 'Nama Module',
          render: function (data, type, full, meta) {
            // The module_names column is already rendered in the controller
            return data;
          }
        },
        {
          // Total Users
          targets: 5,
          orderable: true,
          render: function (data, type, full, meta) {
            const count = typeof full['users_count'] === 'number' ? full['users_count'] : (full['users_count'] || 0);
            return '<span class="badge bg-label-info">' + count + ' pengguna</span>';
          }
        },
        {
          // Actions
          targets: 6,
          searchable: false,
          title: 'Tindakan',
          orderable: false,
          width: '120px',
          className: 'text-center',
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

  // Note: Delete functionality is handled in app-roles-delete.js
});
