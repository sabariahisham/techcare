/**
 * Laporan Aduan Tahunan (Annual Complaints Report)
 * With Month Filter Support
 */

'use strict';

$(function () {
  // Initialize Select2
  $('.select2').each(function () {
    var isMonth = $(this).attr('id') === 'filter-month';
    var isCategory = $(this).attr('id') === 'filter-category';

    $(this).select2({
      placeholder: isMonth ? 'Pilih bulan...' : (isCategory ? 'Pilih kategori...' : 'Pilih'),
      allowClear: true,
      width: '100%'
    });
  });

  var dt_table = $('#table-aduan-tahunan');
  var dt_instance;

  // MAPPING: Bulan (1-12) → Column indices untuk DataTables
  // Format: { bulan: [headerCol, col_aduan, col_selesai, col_peratus] }
  var monthColumnMapping = {
    1: {
      cols: [1, 2, 3]
    }, // Jan
    2: {
      cols: [4, 5, 6]
    }, // Feb
    3: {
      cols: [7, 8, 9]
    }, // Mac
    4: {
      cols: [10, 11, 12]
    }, // Apr
    5: {
      cols: [13, 14, 15]
    }, // Mei
    6: {
      cols: [16, 17, 18]
    }, // Jun
    7: {
      cols: [19, 20, 21]
    }, // Jul
    8: {
      cols: [22, 23, 24]
    }, // Ogos
    9: {
      cols: [25, 26, 27]
    }, // Sep
    10: {
      cols: [28, 29, 30]
    }, // Okt
    11: {
      cols: [31, 32, 33]
    }, // Nov
    12: {
      cols: [34, 35, 36]
    } // Dis
  };

  var totalCols = [37, 38, 39]; // JUMLAH: Aduan, Selesai, %

  if (dt_table.length) {
    dt_instance = dt_table.DataTable({
      processing: true,
      serverSide: true,
      paging: false,
      searching: false,
      info: false,
      ordering: false,
      scrollX: false,
      scrollCollapse: false,
      autoWidth: false,
      orderCellsTop: true,
      ajax: {
        url: '/report/aduan-tahunan/data',
        type: 'POST',
        data: function (d) {
          d.year = $('#filter-year').val();
          //d.category = $('#filter-category').val();
          var categories = $('#filter-category').val() || [];
          d.category = categories.join(',');
          d._token = $('meta[name="csrf-token"]').attr('content');
        },
        error: function (xhr, error) {
          console.error('AJAX Error:', error, xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Ralat!',
            text: 'Gagal memuatkan data.'
          });
        }
      },
      columns: [{
          data: 'cat_name',
          className: 'fw-semibold'
        },
        {
          data: 'jan_aduan',
          className: 'text-center'
        },
        {
          data: 'jan_selesai',
          className: 'text-center'
        },
        {
          data: 'jan_peratus',
          className: 'text-center'
        },
        {
          data: 'feb_aduan',
          className: 'text-center'
        },
        {
          data: 'feb_selesai',
          className: 'text-center'
        },
        {
          data: 'feb_peratus',
          className: 'text-center'
        },
        {
          data: 'mac_aduan',
          className: 'text-center'
        },
        {
          data: 'mac_selesai',
          className: 'text-center'
        },
        {
          data: 'mac_peratus',
          className: 'text-center'
        },
        {
          data: 'apr_aduan',
          className: 'text-center'
        },
        {
          data: 'apr_selesai',
          className: 'text-center'
        },
        {
          data: 'apr_peratus',
          className: 'text-center'
        },
        {
          data: 'mei_aduan',
          className: 'text-center'
        },
        {
          data: 'mei_selesai',
          className: 'text-center'
        },
        {
          data: 'mei_peratus',
          className: 'text-center'
        },
        {
          data: 'jun_aduan',
          className: 'text-center'
        },
        {
          data: 'jun_selesai',
          className: 'text-center'
        },
        {
          data: 'jun_peratus',
          className: 'text-center'
        },
        {
          data: 'jul_aduan',
          className: 'text-center'
        },
        {
          data: 'jul_selesai',
          className: 'text-center'
        },
        {
          data: 'jul_peratus',
          className: 'text-center'
        },
        {
          data: 'ogos_aduan',
          className: 'text-center'
        },
        {
          data: 'ogos_selesai',
          className: 'text-center'
        },
        {
          data: 'ogos_peratus',
          className: 'text-center'
        },
        {
          data: 'sep_aduan',
          className: 'text-center'
        },
        {
          data: 'sep_selesai',
          className: 'text-center'
        },
        {
          data: 'sep_peratus',
          className: 'text-center'
        },
        {
          data: 'okt_aduan',
          className: 'text-center'
        },
        {
          data: 'okt_selesai',
          className: 'text-center'
        },
        {
          data: 'okt_peratus',
          className: 'text-center'
        },
        {
          data: 'nov_aduan',
          className: 'text-center'
        },
        {
          data: 'nov_selesai',
          className: 'text-center'
        },
        {
          data: 'nov_peratus',
          className: 'text-center'
        },
        {
          data: 'dis_aduan',
          className: 'text-center'
        },
        {
          data: 'dis_selesai',
          className: 'text-center'
        },
        {
          data: 'dis_peratus',
          className: 'text-center'
        },
        {
          data: 'jumlah_aduan',
          className: 'text-center fw-bold'
        },
        {
          data: 'jumlah_selesai_total',
          className: 'text-center fw-bold'
        },
        {
          data: 'jumlah_peratus',
          className: 'text-center fw-bold'
        }
      ],
      columnDefs: [{
          targets: 0,
          render: function (data) {
            // Decode HTML entities (e.g., &amp; to &)
            var decodedData = data ? $('<textarea/>').html(data).text() : '-';
            return '<span style="white-space:normal; max-width:180px; display:inline-block;">' + decodedData + '</span>';
          }
        },
        {
          targets: [1, 4, 7, 10, 13, 16, 19, 22, 25, 28, 31, 34, 37],
          render: function (data) {
            var val = parseInt(data) || 0;
            return val === 0 ? '<span class="text-muted">0</span>' : '<span class="fw-medium">' + val + '</span>';
          }
        },
        {
          targets: [2, 5, 8, 11, 14, 17, 20, 23, 26, 29, 32, 35, 38],
          render: function (data) {
            var val = parseInt(data) || 0;
            return val === 0 ? '<span class="text-muted">0</span>' : '<span class="text-success fw-medium">' + val + '</span>';
          }
        },
        {
          targets: [3, 6, 9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 39],
          render: function (data) {
            var pct = parseFloat(data) || 0;
            var cls = 'badge-success-enterprise';

            if (pct === 100) {
              cls = 'badge-success-enterprise';
            } else if (pct >= 75 && pct < 100) {
              cls = 'badge-info-enterprise';
            } else if (pct >= 50 && pct < 75) {
              cls = 'badge-warning-enterprise';
            } else if (pct > 0 && pct < 50) {
              cls = 'badge-danger-enterprise';
            }

            return '<span class="badge ' + cls + '">' + data + '</span>';
          }
        }
      ],
      language: {
        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        emptyTable: 'Tiada data tersedia',
        zeroRecords: 'Tiada rekod dijumpai'
      },
      dom: '<"row mb-3"<"col-12 text-end"B>>t',
      buttons: [{
          text: '<i class="bx bx-download me-1"></i>EXCEL',
          className: 'btn btn-success btn-sm me-2',
          action: function (e, dt, node, config) {
            e.preventDefault();
            e.stopPropagation();
            exportReport('excel');
            return false;
          }
        },
        {
          text: '<i class="bx bx-printer me-1"></i>PDF / PRINT',
          className: 'btn btn-primary btn-sm',
          action: function (e, dt, node, config) {
            e.preventDefault();
            e.stopPropagation();
            exportReport('pdf');
            return false;
          }
        }
      ],
      footerCallback: function (row, data, start, end, display) {
        var api = this.api();

        // Helper function
        var intVal = function (i) {
          return typeof i === 'string' ? parseInt(i.replace(/[^\d-]/g, '')) || 0 : (typeof i === 'number' ? i : 0);
        };

        // Get selected months
        var selectedMonths = $('#filter-month').val() || [];
        var monthsToCalculate = selectedMonths.length > 0 ? selectedMonths.map(Number) : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        // Calculate footer for each visible month
        monthsToCalculate.forEach(function (month) {
          var mapping = monthColumnMapping[month];
          if (!mapping) return;

          var colAduan = mapping.cols[0];
          var colSelesai = mapping.cols[1];
          var colPeratus = mapping.cols[2];

          var totalAduan = api.column(colAduan).data().reduce(function (a, b) {
            return intVal(a) + intVal(b);
          }, 0);

          var totalSelesai = api.column(colSelesai).data().reduce(function (a, b) {
            return intVal(a) + intVal(b);
          }, 0);

          var persen = totalAduan === 0 ? '100%' : Math.round((totalSelesai / totalAduan) * 100) + '%';

          $(api.column(colAduan).footer()).html('<strong>' + totalAduan + '</strong>');
          $(api.column(colSelesai).footer()).html('<strong>' + totalSelesai + '</strong>');
          $(api.column(colPeratus).footer()).html('<strong>' + persen + '</strong>');
        });

        // JUMLAH KESELURUHAN (based on visible months only)
        var totalAduanAll = 0;
        var totalSelesaiAll = 0;

        monthsToCalculate.forEach(function (month) {
          var mapping = monthColumnMapping[month];
          if (!mapping) return;

          totalAduanAll += api.column(mapping.cols[0]).data().reduce(function (a, b) {
            return intVal(a) + intVal(b);
          }, 0);

          totalSelesaiAll += api.column(mapping.cols[1]).data().reduce(function (a, b) {
            return intVal(a) + intVal(b);
          }, 0);
        });

        var totalPeratusAll = totalAduanAll === 0 ? '100%' : Math.round((totalSelesaiAll / totalAduanAll) * 100) + '%';

        $(api.column(37).footer()).html('<strong>' + totalAduanAll + '</strong>');
        $(api.column(38).footer()).html('<strong>' + totalSelesaiAll + '</strong>');
        $(api.column(39).footer()).html('<strong>' + totalPeratusAll + '</strong>');

        setTimeout(function () {
          $('.dataTables_scrollFootInner table')
            .css('width', $('.dataTables_scrollHeadInner table').width());
        }, 0);
      },
      drawCallback: function () {
        var api = this.api();

        setTimeout(function () {
          api.columns.adjust();
          $('.dataTables_scrollFootInner table')
            .css('width', $('.dataTables_scrollHeadInner table').width());
        }, 50);
      }
    });
  }

  // =============================================
  // FUNCTION: Show/Hide Columns Based on Selected Months
  // =============================================
  function applyMonthFilter() {

    if (!dt_instance) return;

    var api = dt_instance;
    var selectedMonths = $('#filter-month').val() || [];
    var showAll = selectedMonths.length === 0;

    // Hide semua bulan dahulu
    for (var m = 1; m <= 12; m++) {
      monthColumnMapping[m].cols.forEach(i => api.column(i).visible(false, false));
    }

    // Papar hanya bulan dipilih
    if (showAll) {
      for (var m = 1; m <= 12; m++) {
        monthColumnMapping[m].cols.forEach(i => api.column(i).visible(true, false));
      }
    } else {
      selectedMonths.forEach(function (m) {
        monthColumnMapping[m].cols.forEach(i => api.column(i).visible(true, false));
      });
    }

    // JUMLAH sentiasa visible
    [37, 38, 39].forEach(i => api.column(i).visible(true, false));

    api.columns.adjust().draw(false);

  }

  // =============================================
  // Filter button - Load data + Apply month filter
  // =============================================
  $('#btn-papar').on('click', function () {
    var year = $('#filter-year').val();

    // Validation: Tahun mesti dipilih
    if (!year) {
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian!',
        text: 'Sila pilih tahun terlebih dahulu.',
        confirmButtonColor: '#1E3A8A'
      });
      return;
    }
    applyMonthFilter();
    dt_instance.ajax.reload();
  });

  // =============================================
  // Month filter change - Apply filter immediately if data already loaded
  // =============================================
  $('#filter-month').on('change', function () {
    var year = $('#filter-year').val();
    if (!year) {
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian!',
        text: 'Sila pilih tahun terlebih dahulu.',
        confirmButtonColor: '#1E3A8A'
      });
      return;
    }
    if (dt_instance && dt_instance.data().count() > 0) {
      applyMonthFilter();
    }
  });

  // =============================================
  // Category filter change - Apply filter immediately if data already loaded
  // =============================================

  $('#filter-category').on('select2:opening', function (e) {

    var year = $('#filter-year').val();
    var month = $('#filter-month').val();

    if (!year || !month || month.length === 0) {
      e.preventDefault();

      Swal.fire({
        icon: 'warning',
        title: 'Perhatian',
        text: 'Sila pilih Tahun dan sekurang-kurangnya satu Bulan terlebih dahulu.',
        confirmButtonColor: '#1E3A8A'
      });

      return false;
    }
  });

  // =============================================
  // Export function
  // =============================================
  function exportReport(type) {
    var year = $('#filter-year').val();
    if (!year) {
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian!',
        text: 'Sila pilih tahun terlebih dahulu.',
        confirmButtonColor: '#1E3A8A'
      });
      return;
    }

    // MULTI CATEGORY
    var categories = $('#filter-category').val() || [];
    var categoryParam = categories.length ? '&category=' + categories.join(',') : '';

    // MULTI MONTH
    var months = $('#filter-month').val() || [];
    var monthsParam = months.length ? '&months=' + months.join(',') : '';

    var url = '/report/aduan-tahunan/export-' + type + '?year=' + year + categoryParam + monthsParam;

    if (type === 'excel') {
      window.location.href = url;
    } else {
      window.open(url, '_blank');
    }
  }
});
