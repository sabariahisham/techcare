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
  var dt_officers_table = $('.datatables-refcategory');

  // Don't initialize select2 globally here - it's handled in individual form files

  // Officers datatable
  if (dt_officers_table.length) {
    var dt_officers = dt_officers_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: '/officer/data',
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
          data: 'cat_name',
          name: 'cat_name'
        },
        {
          data: 'subcat_name',
          name: 'subcat_name'
        },
        {
          data: 'level_tugasan',
          name: 'level_tugasan',
          render: function (data, type, row) {
            var badgeClass = '';
            var levelText = '';
            switch (parseInt(data)) {
              case 1:
                badgeClass = 'bg-label-success';
                levelText = 'Level 1';
                break;
              case 2:
                badgeClass = 'bg-label-warning';
                levelText = 'Level 2';
                break;
              case 3:
                badgeClass = 'bg-label-danger';
                levelText = 'Level 3';
                break;
              default:
                badgeClass = 'bg-label-secondary';
                levelText = data;
            }
            return '<span class="badge ' + badgeClass + '">' + levelText + '</span>';
          }
        },
        {
          data: 'user_name',
          name: 'user_name'
        },
        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false
        }
      ],
      order: [
        [3, 'asc']
      ], // Order by sub kategori
      rowGroup: {
        dataSrc: 'subcat_name',
        startRender: function (rows, group) {
          return '<strong>' + group + '</strong>';
        }
      },
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
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">TAMBAH PEGAWAI</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddOfficer'
          }
        }
      ],
      // Initialize level filter
      initComplete: function () {
        // Adding level filter once table initialized
        this.api()
          .columns(4)
          .every(function () {
            var column = this;
            var select = $(
                '<select id="LevelFilter" class="form-select text-capitalize"><option value=""> Select Level </option></select>'
              )
              .appendTo('.level_filter')
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
      }
    });
  }

  // Delete functionality is handled by app-officers-delete.js

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});

// Form Initialization
(function () {
  // No specific initialization needed for the list view
})();
