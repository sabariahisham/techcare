/**
 * Prestasi Pegtugas Report
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

  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      placeholder: 'Pilih',
      allowClear: true
    });
  }

  // Prestasi Pegtugas DataTable
  // --------------------------------------------------------------------

  var dt_prestasi_pegtugas_table = $('.datatables-prestasi-pegtugas');

  if (dt_prestasi_pegtugas_table.length) {
    var dt_prestasi_pegtugas = dt_prestasi_pegtugas_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/report/prestasi-pegawai-tugasan/data',
        data: function (d) {
          d.officer = $('#filter-officer').val();
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'pegawai_tugasan', name: 'u.nama' },
        { data: 'tiket_diterima', name: 'pp.tiket_diterima' },
        { data: 'tiket_selesai', name: 'pp.tiket_selesai' },
        { data: 'tiket_dalam_tindakan', name: 'pp.tiket_dalam_tindakan' },
        { data: 'tiket_lewat_sla', name: 'pp.tiket_lewat_sla' },
        { data: 'tiket_selesai_dalam_sla', name: 'pp.tiket_selesai_dalam_sla' },
        { data: 'high', name: 'pp.high' },
        { data: 'medium', name: 'pp.medium' },
        { data: 'low', name: 'pp.low' }
      ],
      columnDefs: [
        {
          // Bil
          targets: 0,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return meta.row + 1;
          }
        },
        {
          // Pegawai Tugasan
          targets: 1,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        },
        {
          // Tiket Diterima
          targets: 2,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-primary">' + data + '</span>';
          }
        },
        {
          // Tiket Diselesai
          targets: 3,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-success">' + data + '</span>';
          }
        },
        {
          // Tiket Dalam Tindakan
          targets: 4,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-warning">' + data + '</span>';
          }
        },
        {
          // Tiket Lewat SLA
          targets: 5,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-danger">' + data + '</span>';
          }
        },
        {
          // Tiket Selesai Dalam SLA
          targets: 6,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-success">' + data + '</span>';
          }
        },
        {
          // HIGH
          targets: 7,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-danger">' + data + '</span>';
          }
        },
        {
          // MEDIUM
          targets: 8,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-warning">' + data + '</span>';
          }
        },
        {
          // LOW
          targets: 9,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-info">' + data + '</span>';
          }
        }
      ],
      order: [[2, 'desc']], // Sort by tiket diterima descending
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
        searchPlaceholder: 'Carian...',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-18px"></i>',
          previous: '<i class="bx bx-chevron-left bx-18px"></i>'
        },
        info: 'Paparan _START_ hingga _END_ daripada _TOTAL_ rekod',
        infoEmpty: 'Tiada rekod',
        infoFiltered: '(ditapis daripada _MAX_ jumlah rekod)',
        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
      },
      buttons: [
        {
          text: '<i class="bx bx-file me-1"></i>EXCEL',
          className: 'btn btn-success btn-sm mb-6 mb-md-0 waves-effect waves-light me-2',
          action: function (e, dt, button, config) {
            // Get current filters
            var filters = {
              officer: $('#filter-officer').val()
            };

            // Build query string
            var queryParams = $.param(filters);
            var exportUrl = '/report/prestasi-pegawai-tugasan/export-excel?' + queryParams;

            // Check if Swal is available
            if (typeof Swal !== 'undefined') {
              // Show loading
              Swal.fire({
                title: 'Memproses...',
                text: 'Sila tunggu sebentar',
                allowOutsideClick: false,
                didOpen: function() {
                  Swal.showLoading();
                }
              });

              // Trigger download
              window.location.href = exportUrl;

              // Close loading after a short delay
              setTimeout(function() {
                Swal.close();
              }, 2000);
            } else {
              // Fallback if Swal is not available
              window.location.href = exportUrl;
            }
          }
        },
        {
          text: '<i class="bx bx-file me-1"></i>PDF',
          className: 'btn btn-primary btn-sm mb-6 mb-md-0 waves-effect waves-light',
          action: function (e, dt, button, config) {
            // Get current filters
            var filters = {
              officer: $('#filter-officer').val()
            };

            // Build query string
            var queryParams = $.param(filters);
            var exportUrl = '/report/prestasi-pegawai-tugasan/export-pdf?' + queryParams;

            // Check if Swal is available
            if (typeof Swal !== 'undefined') {
              // Show loading
              Swal.fire({
                title: 'Memproses PDF...',
                text: 'Sila tunggu sebentar',
                allowOutsideClick: false,
                didOpen: function() {
                  Swal.showLoading();
                }
              });

              // Open in new window for PDF printing
              var pdfWindow = window.open(exportUrl, '_blank');

              // Close loading after a short delay
              setTimeout(function() {
                Swal.close();
                if (pdfWindow) {
                  // Auto-trigger print dialog after page loads
                  setTimeout(function() {
                    pdfWindow.print();
                  }, 1000);
                }
              }, 1000);
            } else {
              // Fallback if Swal is not available
              window.open(exportUrl, '_blank');
            }
          }
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Butiran Prestasi - ' + data['pegawai_tugasan'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
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

    // Apply filters on change
    $('#filter-officer').on('change', function () {
      dt_prestasi_pegtugas.ajax.reload();
    });
  }
});
