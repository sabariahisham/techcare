/**
 * Log Ticket Report
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

  // Store all subcategory options for filtering
  var allSubCategories = [];
  $('#filter-subcategory option').each(function () {
    allSubCategories.push({
      id: $(this).val(),
      text: $(this).text(),
      category: $(this).data('category')
    });
  });

  // Filter Sub Kategori based on Kategori selection
  $('#filter-category').on('change', function () {
    var categoryId = $(this).val();
    var $subCategorySelect = $('#filter-subcategory');

    // Destroy Select2
    $subCategorySelect.select2('destroy');

    // Clear existing options
    $subCategorySelect.empty();

    // Add filtered options
    if (categoryId) {
      // Add "Semua" option
      $subCategorySelect.append('<option value="">Semua</option>');
      // Add only subcategories matching the selected category
      allSubCategories.forEach(function (item) {
        if (item.id !== '' && item.category == categoryId) {
          $subCategorySelect.append('<option value="' + item.id + '" data-category="' + item.category + '">' + item.text + '</option>');
        }
      });
    } else {
      // Add all subcategories
      allSubCategories.forEach(function (item) {
        var dataAttr = item.category ? ' data-category="' + item.category + '"' : '';
        $subCategorySelect.append('<option value="' + item.id + '"' + dataAttr + '>' + item.text + '</option>');
      });
    }

    // Reinitialize Select2
    $subCategorySelect.select2({
      placeholder: 'Pilih',
      allowClear: true
    });
  });

  // Initialize Flatpickr for date range
  if ($('#filter-date-range').length) {
    $('#filter-date-range').flatpickr({
      mode: 'range',
      dateFormat: 'd-m-Y',
      locale: {
        firstDayOfWeek: 1,
        rangeSeparator: ' hingga '
      }
    });
  }

  // Log Ticket DataTable
  // --------------------------------------------------------------------

  var dt_log_ticket_table = $('.datatables-log-ticket');

  if (dt_log_ticket_table.length) {
    var dt_log_ticket = dt_log_ticket_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/report/senarai-log-tiket/data',
        data: function (d) {
          d.status = $('#filter-status').val();
          d.category = $('#filter-category').val();
          d.subcategory = $('#filter-subcategory').val();
          d.officer = $('#filter-officer').val();
          d.bahagian = $('#filter-department').val();
          d.month = $('#filter-month').val();
          d.year = $('#filter-year').val();
        }
      },
      columns: [{
          data: ''
        },
        {
          data: 'ticket_nolog'
        },
        {
          data: 'pengadu'
        },
        {
          data: 'bahagian'
        },
        {
          data: 'tarikh_tiket'
        },
        {
          data: 'status_badge'
        },
        {
          data: 'kategori'
        },
        {
          data: 'sub_kategori'
        },
        {
          data: 'pegawai_tugasan'
        },
        {
          data: 'severity'
        },
        {
          data: 'sla_days'
        }
      ],
      columnDefs: [{
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
          // No Log Tiket
          targets: 1,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        },
        {
          // Pengadu
          targets: 2
        },
        {
          // Bahagian
          targets: 3
        },
        {
          // Tarikh Tiket
          targets: 4
        },
        {
          // Status Tiket
          targets: 5,
          orderable: false
        },
        {
          // Kategori
          targets: 6
        },
        {
          // Sub Kategori
          targets: 7
        },
        {
          // Pegawai Tugasan
          targets: 8,
          width: '200px'
        },
        {
          // Severity
          targets: 9,
          orderable: false
        },
        {
          // Jumlah Hari SLA
          targets: 10
        }
      ],
      order: [
        [1, 'desc']
      ], // Sort by ticket number descending
      dom: '<"row"' +
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
        infoFiltered: '(ditapis daripada _MAX_ jumlah rekod)'
      },
      buttons: [{
          text: '<i class="bx bx-file me-1"></i>EXCEL',
          className: 'btn btn-success btn-sm mb-6 mb-md-0 waves-effect waves-light me-2',
          action: function (e, dt, button, config) {
            // Get current filters
            var filters = {
              status: $('#filter-status').val(),
              category: $('#filter-category').val(),
              subcategory: $('#filter-subcategory').val(),
              officer: $('#filter-officer').val(),
              bahagian: $('#filter-department').val(),
              month: $('#filter-month').val(),
              year: $('#filter-year').val()
            };

            // Build query string
            var queryParams = $.param(filters);
            var exportUrl = '/report/senarai-log-tiket/export-excel?' + queryParams;

            // Check if Swal is available
            if (typeof Swal !== 'undefined') {
              // Show loading
              Swal.fire({
                title: 'Memproses...',
                text: 'Sila tunggu sebentar',
                allowOutsideClick: false,
                didOpen: function () {
                  Swal.showLoading();
                }
              });

              // Trigger download
              window.location.href = exportUrl;

              // Close loading after a short delay
              setTimeout(function () {
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
              status: $('#filter-status').val(),
              category: $('#filter-category').val(),
              subcategory: $('#filter-subcategory').val(),
              officer: $('#filter-officer').val(),
              bahagian: $('#filter-department').val(),
              month: $('#filter-month').val(),
              year: $('#filter-year').val()
            };

            // Build query string
            var queryParams = $.param(filters);
            var exportUrl = '/report/senarai-log-tiket/export-pdf?' + queryParams;

            // Check if Swal is available
            if (typeof Swal !== 'undefined') {
              // Show loading
              Swal.fire({
                title: 'Memproses PDF...',
                text: 'Sila tunggu sebentar',
                allowOutsideClick: false,
                didOpen: function () {
                  Swal.showLoading();
                }
              });

              // Open in new window for PDF printing
              var pdfWindow = window.open(exportUrl, '_blank');

              // Close loading after a short delay
              setTimeout(function () {
                Swal.close();
                if (pdfWindow) {
                  // Auto-trigger print dialog after page loads
                  setTimeout(function () {
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
              return 'Butiran Tiket - ' + data['ticket_nolog'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' ?
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
      }
    });

    $('.dataTables_length').addClass('my-0');

    // Apply filters on change
    $('#filter-status, #filter-category, #filter-subcategory, #filter-officer, #filter-department, #filter-month, #filter-year').on('change', function () {
      dt_log_ticket.ajax.reload();
    });
  }
});
