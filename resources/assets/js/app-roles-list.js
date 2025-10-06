/**
 * App roles list (jquery)
 */

'use strict';

$(function () {
  // Add click handler for edit button
  $(document).on('click', '.edit-role', function() {
      const roleId = $(this).data('id');
      const roleName = $(this).data('name');

      $('#editRoleName').val(roleName);
      $('#editRoleForm').attr('data-role-id', roleId);
      $('#editRoleModal').modal('show');
  });

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
        { data: 'users_count' },
        {
            data: null,
            render: function (data, type, row) {
                return `
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-icon edit-role" data-id="${row.id}" data-name="${row.name}" title="Edit Role">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon delete-role" data-id="${row.id}" title="Delete Role">
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
        searchPlaceholder: 'Search Roles',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: [
        {
          text: '<i class="bx bx-plus bx-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Add Role</span>',
          className: 'add-new btn btn-primary mb-6 mb-md-0',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddRole'
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
            if (full['user_names'] && full['user_names'].length > 0) {
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
          // Total Users
          targets: 4,
          orderable: true,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-info">' + (full['users_count'] || 0) + ' users</span>';
          }
        },
        {
          // Actions
          targets: 5,
          searchable: false,
          title: 'Actions',
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

  // Add click handler for delete button
  $(document).on('click', '.delete-role', function() {
      const roleId = $(this).data('id');
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
              deleteRole(roleId);
          }
      });
  });

  function deleteRole(roleId) {
      $.ajax({
          url: '/admin/roles/' + roleId,
          type: 'DELETE',
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function(data) {
              if (data.success) {
                  // Reload the datatable
                  $('.datatables-roles').DataTable().ajax.reload();

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
              toastr.error('An error occurred while deleting the role.', '', {
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
