/**
 * Page SLA and Severity List
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
  var dt_sla_table = $('.datatables-sla'),
    select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Category',
      dropdownParent: $this.parent()
    });
  }

  // SLA datatable
  if (dt_sla_table.length) {
    // Make dt_sla global so it can be accessed from other files
    window.dt_sla = dt_sla_table.DataTable({
      processing: true,
      serverSide: true,
      pageLength: 50,
      ajax: {
        url: '/sla-severity/data',
        type: 'GET',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        error: function (xhr, error, thrown) {
          console.error('DataTables Ajax Error:', error, thrown);
          console.error('Response:', xhr.responseText);
        }
      },
      rowGroup: {
        dataSrc: 'cat_name',
        startRender: function (rows, group) {
          return '<strong style="color: ' + headingColor + '; background-color: ' + bodyBg + '; padding: 8px; display: block; border-left: 4px solid #696cff;">' +
            'Kategori: ' + group + ' (' + rows.count() + ' items)</strong>';
        }
      },
      order: [
        [2, 'asc'],
        [3, 'asc']
      ], // Order by category name then subcategory
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
          name: 'cat_name',
          visible: false
        },
        {
          data: 'subcat_name',
          name: 'subcat_name'
        },
        {
          data: 'sla_level',
          name: 'sla_level'
        },
        {
          data: 'sla_severity',
          name: 'sla_severity'
        },
        {
          data: 'sla_days',
          name: 'sla_days'
        },

        {
          data: 'action',
          name: 'action',
          orderable: false,
          searchable: false
        }
      ],
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
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">TAMBAH SLA</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddSla'
          }
        }
      ],
      // Initialize category filter
      initComplete: function () {
        // Adding category filter once table initialized
        this.api()
          .columns(2) // Column index for cat_name (hidden)
          .every(function () {
            var column = this;
            var select = $(
                '<select id="CategoryFilter" class="form-select text-capitalize"><option value=""> Select Category </option></select>'
              )
              .appendTo('.category_filter')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                if (d && d !== '-') {
                  select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                }
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

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
