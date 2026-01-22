/**
 * Statistik dan Log Aduan ICT
 * Horizontal Bar Chart with DataTable
 */

'use strict';

$(function () {
  let chartInstance = null;
  let dt_table = $('#table-statistik-log');
  let dt_instance;

  // Initialize DataTable with dynamic columns
  function initDataTable() {
    const year = $('#filter-year').val();

    $.ajax({
      url: '/report/statistik-log/data',
      type: 'GET',
      data: { year: year },
      success: function(response) {
        const categories = response.categories;
        const data = response.data;

        // Build category headers for second row
        let categoryHeadersHtml = '';
        categories.forEach(cat => {
          categoryHeadersHtml += '<th class="text-center">' + cat.cat_name + '</th>';
        });
        $('#category-headers').html(categoryHeadersHtml);

        // Update colspan for KATEGORI header dynamically
        $('#kategori-colspan').attr('colspan', categories.length);

        // Build tfoot dynamically
        let tfootHtml = '<tr class="table-secondary fw-bold"><td>JUMLAH</td>';
        categories.forEach(cat => {
          tfootHtml += '<td class="text-center" id="total-cat-' + cat.id + '">0</td>';
        });
        tfootHtml += '<td class="text-center" id="total-jumlah">0</td></tr>';
        $('#table-statistik-log tfoot').html(tfootHtml);

        // Destroy existing DataTable if exists
        if (dt_instance) {
          dt_instance.destroy();
        }

        // Build columns configuration
        let columns = [{ data: 'bulan', className: 'fw-medium' }];
        categories.forEach(cat => {
          columns.push({
            data: 'cat_' + cat.id,
            className: 'text-center',
            render: function(data) {
              let val = parseInt(data) || 0;
              if (val === 0) return '<span class="text-muted">0</span>';
              return '<span class="badge bg-primary">' + val + '</span>';
            }
          });
        });
        columns.push({
          data: 'jumlah',
          className: 'text-center fw-bold',
          render: function(data) {
            let val = parseInt(data) || 0;
            if (val === 0) return '<span class="text-muted">0</span>';
            return '<span class="badge bg-success">' + val + '</span>';
          }
        });

        // Initialize DataTable
        dt_instance = dt_table.DataTable({
          data: data,
          columns: columns,
          responsive: true,
          paging: false,
          searching: false,
          ordering: false,
          info: false,
          drawCallback: function() {
            updateFooterTotals(categories);
          }
        });

        updateFooterTotals(categories);
      },
      error: function(xhr, error) {
        console.error('AJAX Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Ralat!',
          text: 'Gagal memuatkan data.',
          confirmButtonText: 'OK'
        });
      }
    });
  }

  if (dt_table.length) {
    initDataTable();
  }

  // Initialize Chart
  function loadChart(year) {
    $.ajax({
      url: '/report/statistik-log/chart',
      type: 'GET',
      data: { year: year },
      success: function (response) {
        renderChart(response);
      },
      error: function (xhr, error) {
        console.error('Chart Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Ralat!',
          text: 'Gagal memuatkan graf.',
          confirmButtonText: 'OK'
        });
      }
    });
  }

  // Render Horizontal Bar Chart
  function renderChart(data) {
    const ctx = document.getElementById('chartStatistikLog');
    
    if (!ctx) {
      console.error('Canvas element not found');
      return;
    }

    // Destroy existing chart instance
    if (chartInstance) {
      chartInstance.destroy();
    }

    // Create new chart
    chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.labels,
        datasets: data.datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              boxWidth: 12,
              padding: 10,
              font: {
                size: 11
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.parsed.y + ' aduan';
              }
            }
          }
        },
        scales: {
          x: {
            title: {
              display: true,
              text: 'Bulan',
              font: {
                weight: 'bold'
              }
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Jumlah Aduan',
              font: {
                weight: 'bold'
              }
            },
            ticks: {
              stepSize: 5
            }
          }
        }
      }
    });
  }

  // Update footer totals
  function updateFooterTotals(categories) {
    if (!dt_instance) return;

    const data = dt_instance.rows().data();
    let totals = { jumlah: 0 };

    // Initialize category totals
    categories.forEach(cat => {
      totals['cat_' + cat.id] = 0;
    });

    // Calculate totals
    data.each(function(row) {
      categories.forEach(cat => {
        const key = 'cat_' + cat.id;
        totals[key] += parseInt(row[key]) || 0;
      });
      totals.jumlah += parseInt(row.jumlah) || 0;
    });

    // Update footer cells
    categories.forEach(cat => {
      $('#total-cat-' + cat.id).text(totals['cat_' + cat.id]);
    });
    $('#total-jumlah').text(totals.jumlah);
  }

  // Filter by year
  $('#filter-year').on('change', function () {
    const year = $(this).val();
    
    // Reload DataTable
    initDataTable();
    
    // Reload Chart
    loadChart(year);
  });

  // Export Excel
  $('#btn-export-excel').on('click', function () {
    const year = $('#filter-year').val();
    window.location.href = '/report/statistik-log/export-excel?year=' + year;
  });

  // Export PDF
  $('#btn-export-pdf').on('click', function () {
    const year = $('#filter-year').val();
    window.location.href = '/report/statistik-log/export-pdf?year=' + year;
  });

  // Initial load
  loadChart($('#filter-year').val());
});