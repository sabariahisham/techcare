/**
 * Report List
 */

'use strict';

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

  // Report list datatable
  // --------------------------------------------------------------------

  var dt_report_table = $('.datatables-refcategory');

  if (dt_report_table.length) {
    // Get current date and time in D-M-Y H:M format
    var now = new Date();
    var day = now.getDate();
    var month = now.getMonth() + 1;
    var year = now.getFullYear();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var currentDateTime = day + '-' + month + '-' + year + ' ' + hours + ':' + minutes;

    var dt_report = dt_report_table.DataTable({
      data: [
        {
          id: 1,
          name: 'Senarai Log Tiket',
          description: 'Laporan lengkap log aktiviti dan perubahan status tiket',
          updated_at: currentDateTime
        },
        {
          id: 2,
          name: 'Prestasi Pegawai Tugasan',
          description: 'Laporan prestasi pegawai berdasarkan tiket yang ditugaskan',
          updated_at: currentDateTime
        },
        {
          id: 3,
          name: 'Laporan Bulanan Aduan',
          description: 'Laporan bulanan aduan yang diterima mengikut kategori dan tahunan',
          updated_at: currentDateTime
        },
        {
          id: 4,
          name: 'Senarai Rekod Laporan ',
          description: 'Senarai Rekod Laporan TechCare',
          updated_at: currentDateTime
        },
        {
          id: 5,
          name: 'Statistik Log Aduan' ,
          description: 'Statistik Log Aduan TechCare Mengikut Kategori ',
          updated_at: currentDateTime
        },
        {
          id: 6,
          name: 'Laporan Bulanan Aduan Mengikut Status Aduan ',
          description: 'Laporan bulanan aduan yang diterima mengikut status aduan',
          updated_at: currentDateTime
        }

      ],
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'updated_at' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // Bil
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return meta.row + 1;
          }
        },
        {
          // Nama Laporan
          targets: 2,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            var $name = full['name'];
            // Make "(under development)" blink
            if ($name.includes('(Dalam Pembangunan)')) {
              $name = $name.replace('(Dalam Pembangunan)', '<span class="blink-text text-warning fw-bold">(Dalam Pembangunan)</span>');
            }
            return '<span class="fw-medium">' + $name + '</span>';
          }
        },
        {
          // Deskripsi Laporan
          targets: 3,
          render: function (data, type, full, meta) {
            var $description = full['description'];
            return '<span class="text-truncate d-inline-block" style="max-width: 400px;">' + $description + '</span>';
          }
        },
        {
          // Tarikh kemaskini
          targets: 4,
          render: function (data, type, full, meta) {
            var $updated = full['updated_at'];
            return '<span>' + $updated + '</span>';
          }
        },
        {
          // Actions
          targets: -1,
          title: 'Lihat Laporan',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var $id = full['id'];
            var $name = full['name'];

            // Determine the route based on report ID
            var viewUrl = '#';
            if ($id === 1) {
              // Route for Senarai Log Tiket
              viewUrl = '/report/senarai-log-tiket';
            } else if ($id === 2) {
              // Route for Prestasi Pegawai Tugasan
              viewUrl = '/report/prestasi-pegawai-tugasan';
            } else if ($id === 3) {
              // Route for Laporan Bulanan Aduan
              viewUrl = '/report/aduan-tahunan';
            }
            else if ($id === 4) {
              // Route for Senarai Rekod Laporan Helpdesk
              viewUrl = '/report/rekod-laporan';
            }
            else if ($id === 5) {
              // Route for Statistik Log Aduan
              viewUrl = '/report/statistik-log';
            }
            else if ($id === 6) {
              // Route for Laporan Bulanan Aduan Mengikut Status Aduan
              viewUrl = '/report/laporan-status-aduan-tahunan';
            } 

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="' + viewUrl + '" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect waves-light" data-bs-toggle="tooltip" title="Lihat Laporan">' +
              '<i class="bx bx-show bx-md"></i>' +
              '</a>' +
              '</div>'
            );
          }
        }
      ],
      order: [[1, 'asc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-4"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Cari Laporan',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        }
      },
      buttons: [
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2 align-top"></i><span class="d-none d-sm-inline-block">Tambah Laporan</span>',
          className: 'add-new btn btn-primary mb-6 mb-md-0 waves-effect waves-light',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddReport'
          }
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Butiran ' + data['name'];
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
      }
    });

    $('.dataTables_length').addClass('my-0');
  }

  // Add blinking animation CSS
  if (!document.getElementById('blink-animation-style')) {
    var style = document.createElement('style');
    style.id = 'blink-animation-style';
    style.innerHTML = `
      @keyframes blink-animation {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
      }
      .blink-text {
        animation: blink-animation 1.5s ease-in-out infinite;
        display: inline-block;
      }
    `;
    document.head.appendChild(style);
  }

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
