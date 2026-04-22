/**
 * Dashboard Pegawai Tugasan - Specific Charts and Cards
 * This file contains all dashboard functionality specific to Pegawai Tugasan role
 * All data is filtered by assigned tasks (escalate_to = current user)
 */

'use strict';

// Ensure config is available (using Sneat template colors)
if (typeof window.config === 'undefined') {
  window.config = {
    colors: {
      primary: '#696cff',
      secondary: '#8592a3',
      success: '#71dd37',
      info: '#03c3ec',
      warning: '#ffab00',
      danger: '#ff3e1d',
      cardColor: '#fff',
      bodyBg: '#f5f5f9',
      bodyColor: '#646E78',
      headingColor: '#384551',
      textMuted: '#a7acb2',
      borderColor: '#e4e6e8'
    }
  };
}

(function () {
  // Color Variables using Sneat template config
  const borderColor = window.config?.colors?.borderColor || '#e4e6e8';
  const bodyBg = window.config?.colors?.bodyBg || '#f5f5f9';
  const headingColor = window.config?.colors?.headingColor || '#384551';
  const bodyColor = window.config?.colors?.bodyColor || '#646E78';
  const cardColor = window.config?.colors?.cardColor || '#fff';
  const labelColor = window.config?.colors?.textMuted || '#a7acb2';

  // Status colors mapping using lighter/softer Sneat template colors
  const statusColors = {
    'Aduan Baharu': '#a5abff',      // Lighter Primary - Soft Purple
    'Aduan Diproses': '#ffc947',    // Lighter Warning - Soft Orange
    'Serahan Kepada': '#26d0f0',    // Lighter Info - Soft Cyan
    'Aduan Dirujuk': '#ff6b47',     // Lighter Danger - Soft Red
    'Aduan DiBatal': '#a8b1ba',     // Lighter Secondary - Soft Gray
    'Aduan Selesai': '#98e85d'      // Lighter Success - Soft Green
  };

  // =============================================================================
  // PEGAWAI TUGASAN DASHBOARD CARD FUNCTIONS
  // These load data from dedicated Pegawai endpoints that always filter by assigned tasks
  // =============================================================================

  /**
   * Load Today's Logs Count for Pegawai Tugasan
   */
  function loadTodayLogsCountPegawai() {
    fetch('/dashboard/pegawai/today-logs-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('pegawai-today-logs-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai today logs count:', error);
        const element = document.getElementById('pegawai-today-logs-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  /**
   * Load Weekly Completed Tickets for Pegawai Tugasan
   */
  function loadWeeklyCompletedTicketsPegawai() {
    fetch('/dashboard/pegawai/weekly-completed')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('pegawai-weekly-completed-count');
        if (countElement && data.success) {
          countElement.innerHTML = data.total;
        }

        // Create weekly sparkline bar chart for pegawai
        const chartCanvas = document.getElementById('pegawaiWeeklyCompletedChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Map day names to abbreviations
          const dayAbbreviations = {
            'Isnin': 'I',
            'Selasa': 'S',
            'Rabu': 'R',
            'Khamis': 'K',
            'Jumaat': 'J'
          };

          // Ensure canvas has proper height for sparkline
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.daily_data.map(item => dayAbbreviations[item.day] || item.day),
              datasets: [{
                data: data.daily_data.map(item => item.count),
                backgroundColor: data.daily_data.map(item => item.count > 0 ? '#98e85d' : '#e4e6e8'),
                borderColor: data.daily_data.map(item => item.count > 0 ? '#71dd37' : '#e4e6e8'),
                borderWidth: 1,
                borderRadius: 2,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  backgroundColor: 'rgba(0, 0, 0, 0.8)',
                  padding: 8,
                  titleFont: { size: 11, weight: 'bold' },
                  bodyFont: { size: 11 },
                  displayColors: false,
                  callbacks: {
                    title: function (context) {
                      const fullDayNames = {
                        'I': 'Isnin',
                        'S': 'Selasa',
                        'R': 'Rabu',
                        'K': 'Khamis',
                        'J': 'Jumaat'
                      };
                      return fullDayNames[context[0].label] || context[0].label;
                    },
                    label: function (context) {
                      return `${context.parsed.y} tiket selesai`;
                    }
                  }
                }
              },
              scales: {
                x: {
                  display: true,
                  grid: {
                    display: false,
                    drawBorder: false
                  },
                  ticks: {
                    font: { size: 10, weight: '500' },
                    color: '#a7acb2',
                    padding: 2
                  }
                },
                y: {
                  display: false,
                  beginAtZero: true,
                  grid: { display: false }
                }
              },
              layout: {
                padding: {
                  top: 5,
                  bottom: 0
                }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai weekly completed tickets:', error);
        const element = document.getElementById('pegawai-weekly-completed-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  /**
   * Load Pending Tickets Stats for Pegawai Tugasan
   */
  function loadPendingTicketsStatsPegawai() {
    fetch('/dashboard/pegawai/pending-stats')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('pegawai-pending-tickets-count');
        const totalElement = document.getElementById('pegawai-total-tickets-count');
        const percentageElement = document.getElementById('pegawai-pending-percentage');

        if (countElement && data.success) {
          countElement.innerHTML = `${data.pending_count}`;
        }

        if (totalElement && data.success) {
          totalElement.innerHTML = `${data.total_count}`;
        }

        if (percentageElement && data.success) {
          const completedPercentage = (100 - data.percentage).toFixed(1);
          percentageElement.innerHTML = `${completedPercentage}%<br><small style="font-size: 9px; font-weight: normal;">Telah Disiapkan</small>`;
        }

        // Create semi pie chart for pegawai
        const chartCanvas = document.getElementById('pegawaiPendingTicketsChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Ensure canvas has proper height
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          const completedPercentage = 100 - data.percentage;
          const completedCount = data.total_count - data.pending_count;

          new Chart(ctx, {
            type: 'doughnut',
            data: {
              datasets: [{
                data: [completedPercentage, data.percentage],
                backgroundColor: [
                  '#28c76f',  // Success green for completed
                  '#f0f2f4'   // Very light gray for pending
                ],
                borderWidth: 0,
                cutout: '75%'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              rotation: -90,  // Start from top
              circumference: 180,  // Semi circle
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  callbacks: {
                    label: function (context) {
                      if (context.dataIndex === 0) {
                        return `Telah Disiapkan: ${completedCount} tiket (${completedPercentage.toFixed(1)}%)`;
                      } else {
                        return `Belum Selesai: ${data.pending_count} tiket (${data.percentage}%)`;
                      }
                    }
                  }
                }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai pending tickets stats:', error);
        const countElement = document.getElementById('pegawai-pending-tickets-count');
        const percentageElement = document.getElementById('pegawai-pending-percentage');
        if (countElement) countElement.innerHTML = '0';
        if (percentageElement) percentageElement.innerHTML = '0%';
      });
  }

  /**
   * Load Overdue SLA Count for Pegawai Tugasan
   */
  function loadOverdueSLACountPegawai() {
    fetch('/dashboard/pegawai/overdue-sla-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('pegawai-overdue-sla-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai overdue SLA count:', error);
        const element = document.getElementById('pegawai-overdue-sla-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  /**
   * Initialize all Pegawai Tugasan dashboard cards
   */
  function initializePegawaiDashboardCards() {
    loadTodayLogsCountPegawai();
    loadWeeklyCompletedTicketsPegawai();
    loadPendingTicketsStatsPegawai();
    loadOverdueSLACountPegawai();
  }

  // =============================================================================
  // PEGAWAI TUGASAN DASHBOARD CHART FUNCTIONS
  // These load chart data from dedicated Pegawai endpoints
  // =============================================================================

  /**
   * Status Tiket Chart for Pegawai Tugasan (horizontal bar chart)
   */
  const pegawaiStatusChart = document.getElementById('pegawaiStatusChart');
  if (pegawaiStatusChart) {
    console.log('Pegawai status chart canvas found, initializing...');

    // Fetch data from pegawai endpoint
    fetch('/dashboard/pegawai/status-tickets')
      .then(response => {
        console.log('Pegawai status response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Pegawai status chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.st_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map(item => statusColors[item.st_name] || '#6c757d');

          console.log('Pegawai status chart labels:', labels);
          console.log('Pegawai status chart counts:', counts);

          // Create the horizontal bar chart for pegawai
          const pegawaiStatusChartVar = new Chart(pegawaiStatusChart, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 1,
                  borderColor: colors.map(color => color.replace('0.8)', '1)')),
                  borderRadius: 4,
                  borderSkipped: false
                }
              ]
            },
            options: {
              indexAxis: 'y',
              responsive: true,
              maintainAspectRatio: false,
              layout: {
                padding: {
                  top: 5,
                  bottom: 5,
                  left: 0,
                  right: 0
                }
              },
              animation: {
                duration: 800
              },
              scales: {
                x: {
                  beginAtZero: true,
                  grid: {
                    color: borderColor,
                    drawBorder: false
                  },
                  ticks: {
                    color: labelColor,
                    font: {
                      size: 10
                    },
                    stepSize: 1,
                    callback: function (value) {
                      return Number.isInteger(value) ? value : '';
                    }
                  }
                },
                y: {
                  grid: {
                    display: false
                  },
                  ticks: {
                    color: headingColor,
                    font: {
                      size: 10,
                      weight: '500'
                    },
                    callback: function (value, index) {
                      const label = this.getLabelForValue(value);
                      return label.length > 12 ? label.substring(0, 12) + '...' : label;
                    }
                  }
                }
              },
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed.x;
                      return ` ${label}: ${value} tiket`;
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor,
                  titleFont: {
                    size: 11,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 10
                  }
                }
              }
            }
          });

          console.log('Pegawai status chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai status data:', error);
      });
  }

  // ============================================================================
  // CLOSED AFTER UAT (04032026) - KEEPING CODE FOR FUTURE USE
  // Kategori Tiket Chart for Pegawai Tugasan (Doughnut Chart)
  // ============================================================================
  /*
  const pegawaiCategoryChart = document.getElementById('pegawaiCategoryChart');
  if (pegawaiCategoryChart) {
    console.log('Pegawai category chart canvas found, initializing...');

    // Category colors using lighter/softer Sneat template colors
    const categoryColors = [
      '#a5abff',  // Soft Purple (Primary)
      '#ffc947',  // Soft Orange (Warning)
      '#26d0f0',  // Soft Cyan (Info)
      '#98e85d',  // Soft Green (Success)
      '#ff6b47',  // Soft Red (Danger)
      '#a8b1ba',  // Soft Gray (Secondary)
      '#d4a5ff'   // Soft Lavender (Additional)
    ];

    // Fetch data from pegawai category endpoint
    fetch('/dashboard/pegawai/category-tickets')
      .then(response => {
        console.log('Pegawai category response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Pegawai category chart data received:', data);

        if (data.success) {
          // Display all categories assigned to this Pegawai Tugasan (including count = 0)
          const labels = data.data.map(item => item.cat_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => categoryColors[index % categoryColors.length]);

          console.log('Pegawai category labels:', labels);
          console.log('Pegawai category counts:', counts);

          // Create the category doughnut chart for pegawai
          const pegawaiCategoryChartVar = new Chart(pegawaiCategoryChart, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 2,
                  borderColor: cardColor,
                  hoverBorderWidth: 3,
                  hoverBorderColor: '#fff',
                  pointStyle: 'rectRounded'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 1000,
                animateRotate: true,
                animateScale: false
              },
              cutout: '60%',
              plugins: {
                legend: {
                  display: true,
                  position: 'right',
                  align: 'center',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 15,
                    boxWidth: 10,
                    boxHeight: 10,
                    color: headingColor,
                    font: {
                      size: 11,
                      weight: '500'
                    },
                    generateLabels: function (chart) {
                      const data = chart.data;
                      if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, index) => {
                          const dataset = data.datasets[0];
                          const value = dataset.data[index];
                          return {
                            text: `${label}: ${value}`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.borderWidth,
                            hidden: false,
                            index: index
                          };
                        });
                      }
                      return [];
                    }
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed;
                      const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                      const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                      return ` ${label}: ${value} tiket (${percentage}%)`;
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor,
                  titleFont: {
                    size: 12,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 11
                  }
                }
              }
            }
          });

          console.log('Pegawai category chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai category data:', error);
      });
  }
  */

  /**
   * Sub Kategori Tiket Chart for Pegawai Tugasan (Doughnut Chart)
   */
  const pegawaiSubcategoryChart = document.getElementById('pegawaiSubcategoryChart');
  if (pegawaiSubcategoryChart) {
    console.log('Pegawai subcategory chart canvas found, initializing...');

    // Subcategory colors using lighter/softer Sneat template colors
    const subcategoryColors = [
      '#a5abff',  // Soft Purple (Primary)
      '#ffc947',  // Soft Orange (Warning)
      '#26d0f0',  // Soft Cyan (Info)
      '#98e85d',  // Soft Green (Success)
      '#ff6b47',  // Soft Red (Danger)
      '#a8b1ba',  // Soft Gray (Secondary)
      '#d4a5ff',  // Soft Lavender (Additional)
      '#ffb1c1',  // Soft Pink
      '#b8e994',  // Soft Lime
      '#74b9ff',  // Soft Blue
      '#fdcb6e',  // Soft Yellow
      '#fd79a8'   // Soft Rose
    ];

    // Fetch data from pegawai subcategory endpoint
    fetch('/dashboard/pegawai/subcategory-tickets')
      .then(response => {
        console.log('Pegawai subcategory response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Pegawai subcategory chart data received:', data);

        if (data.success) {
          // Display all subcategories assigned to this Pegawai Tugasan (including count = 0)
          const labels = data.data.map(item => item.subcat_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => subcategoryColors[index % subcategoryColors.length]);

          console.log('Pegawai subcategory labels:', labels);
          console.log('Pegawai subcategory counts:', counts);

          // Create the subcategory doughnut chart for pegawai
          const pegawaiSubcategoryChartVar = new Chart(pegawaiSubcategoryChart, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 2,
                  borderColor: cardColor,
                  hoverBorderWidth: 3,
                  hoverBorderColor: '#fff',
                  pointStyle: 'rectRounded'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              layout: {
                padding: {
                  top: 5,
                  bottom: 5,
                  left: 5,
                  right: 5
                }
              },
              animation: {
                duration: 1000,
                animateRotate: true,
                animateScale: false
              },
              cutout: '60%',
              plugins: {
                legend: {
                  display: true,
                  position: 'right',
                  align: 'center',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 8,
                    boxWidth: 8,
                    boxHeight: 8,
                    color: headingColor,
                    font: {
                      size: 10,
                      weight: '500'
                    },
                    generateLabels: function (chart) {
                      const data = chart.data;
                      if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, index) => {
                          const dataset = data.datasets[0];
                          const value = dataset.data[index];
                          // Truncate long labels
                          const displayLabel = label.length > 15 ? label.substring(0, 15) + '...' : label;
                          return {
                            text: `${displayLabel}: ${value}`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.borderWidth,
                            hidden: false,
                            index: index
                          };
                        });
                      }
                      return [];
                    }
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed;
                      const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                      const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                      return ` ${label}: ${value} tiket (${percentage}%)`;
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor,
                  titleFont: {
                    size: 11,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 10
                  }
                }
              }
            }
          });

          console.log('Pegawai subcategory chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai subcategory data:', error);
      });
  }

  /**
   * Initialize Overdue Tickets DataTable for Pegawai Tugasan
   */
  function initializeOverdueTablePegawai() {
    const tableElement = document.getElementById('table-overdue-pegawai');
    if (!tableElement) {
      console.log('Pegawai overdue table element not found on this page');
      return;
    }

    console.log('Initializing pegawai overdue tickets DataTable...');

    // Check if DataTables is available
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
      console.error('jQuery or DataTables not available for pegawai overdue table initialization');
      return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#table-overdue-pegawai')) {
      $('#table-overdue-pegawai').DataTable().destroy();
    }

    const overdueTablePegawai = $('#table-overdue-pegawai').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/dashboard/pegawai/list-overdue',
        type: 'GET',
        error: function (xhr, error, thrown) {
          console.error('Pegawai DataTable error:', error);
          console.error('XHR Response:', xhr);
          console.error('Response Text:', xhr.responseText);
          console.error('Status:', xhr.status);
          if (typeof toastr !== 'undefined') {
            toastr.error('Gagal memuat data tiket overdue: ' + (xhr.responseJSON?.message || error));
          }
        },
        dataSrc: function (json) {
          console.log('Pegawai DataTable response:', json);
          return json.data;
        }
      },
      columns: [{
        data: 'DT_RowIndex',
        name: 'DT_RowIndex',
        width: '5%',
        className: 'text-center',
        orderable: false,
        searchable: false
      },
      {
        data: 'ticket_nolog',
        name: 'ticket_nolog',
        width: '15%'
      },
      {
        data: 'kategori',
        name: 'kategori',
        width: '15%'
      },
      {
        data: 'tajuk',
        name: 'tajuk',
        width: '25%'
      },
      {
        data: 'status',
        name: 'status',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'assigned_to',
        name: 'assigned_to',
        width: '10%'
      },
      {
        data: 'sla_duedate',
        name: 'sla_duedate',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'days_overdue',
        name: 'days_overdue',
        width: '8%',
        className: 'text-center'
      },
      {
        data: 'action',
        name: 'action',
        width: '7%',
        orderable: false,
        searchable: false,
        className: 'text-center'
      }
      ],
      pageLength: 10,
      lengthMenu: [
        [5, 10, 25, 50],
        [5, 10, 25, 50]
      ],
      order: [
        [7, 'desc']
      ], // Sort by days_overdue descending
      language: {
        processing: "Memproses...",
        lengthMenu: "Papar _MENU_ rekod",
        zeroRecords: "Tiada tiket overdue dijumpai",
        info: "Memapar _START_ hingga _END_ daripada _TOTAL_ rekod",
        infoEmpty: "Memapar 0 hingga 0 daripada 0 rekod",
        infoFiltered: "(ditapis daripada _MAX_ jumlah rekod)",
        search: "Cari:",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Seterusnya",
          previous: "Sebelumnya"
        }
      },
      responsive: true,
      autoWidth: false,
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
      drawCallback: function (settings) {
        // Re-initialize tooltips after table redraw
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
          $('[title]').tooltip();
        }

        // Add click handler for view ticket buttons
        $('.view-record').off('click').on('click', function () {
          const ticketUuid = $(this).data('uuid');
          // Navigate to the detailed view page
          window.location.href = '/tickets/' + ticketUuid;
        });
      }
    });

    // Refresh table every 5 minutes for real-time updates
    setInterval(function () {
      if (overdueTablePegawai) {
        overdueTablePegawai.ajax.reload(null, false);
      }
    }, 300000); // 5 minutes

    console.log('Pegawai overdue tickets DataTable initialized successfully');
  }

  // =============================================================================
  // INITIALIZE PEGAWAI TUGASAN DASHBOARD
  // =============================================================================

  /**
   * Initialize all Pegawai Tugasan dashboard components
   */
  function initializePegawaiDashboard() {
    // Load Pegawai Tugasan cards (if present on page)
    if (document.getElementById('pegawai-today-logs-count')) {
      initializePegawaiDashboardCards();
    }

    // Initialize Pegawai overdue table (if present on page)
    if (document.getElementById('table-overdue-pegawai')) {
      initializeOverdueTablePegawai();
    }
  }

  // Initialize on page ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initializePegawaiDashboard();
    });
  } else {
    initializePegawaiDashboard();
  }

  // Export functions for external use if needed
  window.PegawaiTugasanDashboard = {
    initializeCards: initializePegawaiDashboardCards,
    initializeOverdueTable: initializeOverdueTablePegawai,
    initialize: initializePegawaiDashboard
  };

})();
