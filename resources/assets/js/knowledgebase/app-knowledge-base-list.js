/**
 * Page KnowledgeBase List
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
  var dt_knowledgebase_table = $('.datatables-knowledgebase'),
    select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }

  // KnowledgeBase datatable
  if (dt_knowledgebase_table.length) {
    window.dt_knowledgebase = dt_knowledgebase_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: '/knowledgebase/data',
      columns: [{
          // For Responsive
          data: null,
          defaultContent: '',
          orderable: false,
          searchable: false
        },
        {
          // Index column
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          orderable: false,
          searchable: false
        },
        {
          data: 'kb_title',
          name: 'kb_title'
        },
        {
          data: 'category_name',
          name: 'category_name'
        },
        {
          data: 'subcategory_name',
          name: 'subcategory_name'
        },
        {
          data: 'kb_desc',
          name: 'kb_desc'
        },
        {
          data: 'kb_tag_formatted',
          name: 'kb_tag',
          orderable: false
        },
        {
          data: 'kb_status',
          name: 'kb_status'
        },
        {
          data: 'created_by',
          name: 'created_by'
        },
        {
          data: 'created_at',
          name: 'created_at'
        },
        {
          data: 'updated_by_name',
          name: 'updated_by_name',
          orderable: false,
          searchable: false
        },
        {
          data: 'updated_at',
          name: 'updated_at'
        },
        {
          data: 'creator_roles',
          name: 'creator_roles',
          orderable: false,
          searchable: false
        },
        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false
        }
      ],
      columnDefs: [{
          targets: 0,
          orderable: false,
          searchable: false
        },
        {
          targets: 2, // Tajuk
          width: '400px',
          className: 'text-nowrap'
        },
        {
          targets: 5, // Deskripsi
          width: '300px',
          className: 'text-wrap text-nowrap',
          render: function (data, type, row) {
            if (type === 'display' && data && data.length > 60) {
              return data.substr(0, 60) + '...';
            }
            return data;
          }
        },
        {
          targets: 6, // Tag
          className: 'text-wrap',
          orderable: false
        },
        {
          targets: 8, // Ditulis oleh
          width: '160px',
          className: 'text-nowrap'
        },
        {
          targets: 10, // Kemaskini oleh
          width: '160px',
          className: 'text-nowrap'
        }
      ],
      autoWidth: true,
      scrollX: true,
      dom: '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: [{
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle me-4 d-none', // Add d-none class to hide the button
          text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
          buttons: [{
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
        },
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">TAMBAH ARTIKEL</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'modal',
            'data-bs-target': '#knowledgeBaseModal'
          },
          init: function (api, node, config) {
            // Hide the button if user doesn't have a role
            if (typeof window.userHasRole !== 'undefined' && !window.userHasRole) {
              $(node).hide();
            }
          }
        }
      ],
      // Initialize status filter
      initComplete: function () {
        // Adding status filter once table initialized
        this.api()
          .columns(8)
          .every(function () {
            var column = this;
            var select = $(
                '<select id="KbStatus" class="form-select text-capitalize"><option value=""> Select Status </option></select>'
              )
              .appendTo('.kb_status')
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
      responsive: false,
      language: {
        search: '',
        searchPlaceholder: 'Carian'
      }
    });


  }

  // Prevent opening add modal for users without roles
  $(document).on('click', '.add-new', function (e) {
    if (typeof window.userHasRole !== 'undefined' && !window.userHasRole) {
      e.preventDefault();
      e.stopPropagation();
      Swal.fire({
        title: 'Akses Ditolak',
        html: 'Anda tidak mempunyai kebenaran untuk menambah artikel.<br><small class="text-muted">Hanya pengguna dengan peranan yang ditetapkan boleh menambah artikel.</small>',
        icon: 'error',
        confirmButtonColor: '#696cff',
        confirmButtonText: 'Faham'
      });
      return false;
    }
  });

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
