/**
 * Dashboard Pegawai Tugasan - AUTO REFRESH VERSION
 * This file contains all dashboard functionality with automatic data refresh
 * All data is filtered by assigned tasks (escalate_to = current user)
 *
 * Features:
 * - Auto-refresh every 60 seconds (configurable)
 * - Manual refresh button
 * - Pause/Resume controls
 * - Visual refresh indicator
 * - Smart chart updates (no recreation)
 */

import logger from '../logger.js';

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
  // =============================================================================
  // CONFIGURATION & GLOBAL VARIABLES
  // =============================================================================

  const REFRESH_INTERVAL = 60000; // 60 seconds (60000ms)
  let refreshIntervalId = null;
  let isRefreshing = false;
  let isPaused = false;
  let lastRefreshTime = null;
  let clockIntervalId = null; // For real-time clock update

  // Store chart instances for updating
  const chartInstances = {
    weeklyCompleted: null,
    pendingTickets: null,
    statusChart: null,
    subcategoryChart: null
  };

  // Store DataTable instance
  let overdueTableInstance = null;

  // Color Variables using Sneat template config
  const borderColor = window.config?.colors?.borderColor || '#e4e6e8';
  const bodyBg = window.config?.colors?.bodyBg || '#f5f5f9';
  const headingColor = window.config?.colors?.headingColor || '#384551';
  const bodyColor = window.config?.colors?.bodyColor || '#646E78';
  const cardColor = window.config?.colors?.cardColor || '#fff';
  const labelColor = window.config?.colors?.textMuted || '#a7acb2';

  // Status colors mapping
  const statusColors = {
    'Aduan Baharu': '#a5abff',
    'Aduan Diproses': '#ffc947',
    'Serahan Kepada': '#26d0f0',
    'Aduan Dirujuk': '#ff6b47',
    'Aduan DiBatal': '#a8b1ba',
    'Aduan Selesai': '#98e85d'
  };

  // Subcategory colors
  const subcategoryColors = [
    '#a5abff', '#ffc947', '#26d0f0', '#98e85d', '#ff6b47', '#a8b1ba',
    '#d4a5ff', '#ffb1c1', '#b8e994', '#74b9ff', '#fdcb6e', '#fd79a8'
  ];

  // =============================================================================
  // UTILITY FUNCTIONS
  // =============================================================================

  /**
   * Show refresh indicator
   */
  function showRefreshIndicator() {
    const indicator = document.getElementById('pegawai-refresh-indicator');
    if (indicator) {
      indicator.style.display = 'flex';
      indicator.classList.add('refreshing');
    }
  }

  /**
   * Hide refresh indicator
   */
  function hideRefreshIndicator() {
    const indicator = document.getElementById('pegawai-refresh-indicator');
    if (indicator) {
      setTimeout(() => {
        indicator.style.display = 'none';
        indicator.classList.remove('refreshing');
      }, 500);
    }
  }

  /**
   * Update current time display (real-time clock)
   */
  function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });

    const timeElement = document.getElementById('pegawai-last-refresh-time');
    if (timeElement) {
      timeElement.textContent = timeString;
    }
  }

  /**
   * Start real-time clock (updates every second)
   */
  function startRealtimeClock() {
    if (clockIntervalId) {
      clearInterval(clockIntervalId);
    }

    // Update immediately
    updateCurrentTime();

    // Update every second
    clockIntervalId = setInterval(updateCurrentTime, 1000);
    logger.log('⏰ Real-time clock started');
  }

  /**
   * Stop real-time clock
   */
  function stopRealtimeClock() {
    if (clockIntervalId) {
      clearInterval(clockIntervalId);
      clockIntervalId = null;
      logger.log('⏰ Real-time clock stopped');
    }
  }

  /**
   * Update last refresh time display
   */
  function updateLastRefreshTime() {
    lastRefreshTime = new Date();
    // Update the clock display
    updateCurrentTime();
  }

  // =============================================================================
  // CARD DATA LOADING FUNCTIONS (WITH UPDATE CAPABILITY)
  // =============================================================================

  /**
   * Load Today's Logs Count for Pegawai Tugasan
   */
  async function loadTodayLogsCountPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/today-logs-count');
      const data = await response.json();

      const element = document.getElementById('pegawai-today-logs-count');
      if (element && data.success) {
        // Animate the number change
        animateNumberChange(element, data.count);
      }
    } catch (error) {
      console.error('Error fetching pegawai today logs count:', error);
    }
  }

  /**
   * Load Overdue SLA Count for Pegawai Tugasan
   */
  async function loadOverdueSLACountPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/overdue-sla-count');
      const data = await response.json();

      const element = document.getElementById('pegawai-overdue-sla-count');
      if (element && data.success) {
        animateNumberChange(element, data.count);
      }
    } catch (error) {
      console.error('Error fetching pegawai overdue SLA count:', error);
    }
  }

  /**
   * Load Weekly Completed Tickets for Pegawai Tugasan
   */
  async function loadWeeklyCompletedTicketsPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/weekly-completed');
      const data = await response.json();

      if (!data.success) return;

      // Update count
      const countElement = document.getElementById('pegawai-weekly-completed-count');
      if (countElement) {
        animateNumberChange(countElement, data.total);
      }

      // Update or create chart
      const chartCanvas = document.getElementById('pegawaiWeeklyCompletedChart');
      if (chartCanvas) {
        const dayAbbreviations = {
          'Isnin': 'I', 'Selasa': 'S', 'Rabu': 'R', 'Khamis': 'K', 'Jumaat': 'J'
        };

        const labels = data.daily_data.map(item => dayAbbreviations[item.day] || item.day);
        const counts = data.daily_data.map(item => item.count);
        const colors = counts.map(count => count > 0 ? '#98e85d' : '#e4e6e8');

        if (chartInstances.weeklyCompleted) {
          // Update existing chart
          chartInstances.weeklyCompleted.data.labels = labels;
          chartInstances.weeklyCompleted.data.datasets[0].data = counts;
          chartInstances.weeklyCompleted.data.datasets[0].backgroundColor = colors;
          chartInstances.weeklyCompleted.data.datasets[0].borderColor = colors.map(c => c === '#98e85d' ? '#71dd37' : '#e4e6e8');
          chartInstances.weeklyCompleted.update('none'); // Update without animation during refresh
        } else {
          // Create new chart (first time only)
          const ctx = chartCanvas.getContext('2d');
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          chartInstances.weeklyCompleted = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{
                data: counts,
                backgroundColor: colors,
                borderColor: colors.map(c => c === '#98e85d' ? '#71dd37' : '#e4e6e8'),
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
                      const fullDayNames = { 'I': 'Isnin', 'S': 'Selasa', 'R': 'Rabu', 'K': 'Khamis', 'J': 'Jumaat' };
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
                  grid: { display: false, drawBorder: false },
                  ticks: { font: { size: 10, weight: '500' }, color: '#a7acb2', padding: 2 }
                },
                y: { display: false, beginAtZero: true, grid: { display: false } }
              },
              layout: { padding: { top: 5, bottom: 0 } }
            }
          });
        }
      }
    } catch (error) {
      console.error('Error fetching pegawai weekly completed tickets:', error);
    }
  }

  /**
   * Load Pending Tickets Stats for Pegawai Tugasan
   */
  async function loadPendingTicketsStatsPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/pending-stats');
      const data = await response.json();

      if (!data.success) return;

      // Update counts
      const countElement = document.getElementById('pegawai-pending-tickets-count');
      const totalElement = document.getElementById('pegawai-total-tickets-count');
      const percentageElement = document.getElementById('pegawai-pending-percentage');

      if (countElement) {
        animateNumberChange(countElement, data.pending_count);
      }

      if (totalElement) {
        animateNumberChange(totalElement, data.total_count);
      }

      const completedPercentage = (100 - data.percentage).toFixed(1);
      if (percentageElement) {
        percentageElement.innerHTML = `${completedPercentage}%<br><small style="font-size: 9px; font-weight: normal;">Telah Disiapkan</small>`;
      }

      // Update or create chart
      const chartCanvas = document.getElementById('pegawaiPendingTicketsChart');
      if (chartCanvas) {
        const completedCount = data.total_count - data.pending_count;

        if (chartInstances.pendingTickets) {
          // Update existing chart
          chartInstances.pendingTickets.data.datasets[0].data = [parseFloat(completedPercentage), data.percentage];
          chartInstances.pendingTickets.options.plugins.tooltip.callbacks.label = function (context) {
            if (context.dataIndex === 0) {
              return `Telah Disiapkan: ${completedCount} tiket (${completedPercentage}%)`;
            } else {
              return `Belum Selesai: ${data.pending_count} tiket (${data.percentage}%)`;
            }
          };
          chartInstances.pendingTickets.update('none');
        } else {
          // Create new chart
          const ctx = chartCanvas.getContext('2d');
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          chartInstances.pendingTickets = new Chart(ctx, {
            type: 'doughnut',
            data: {
              datasets: [{
                data: [parseFloat(completedPercentage), data.percentage],
                backgroundColor: ['#28c76f', '#f0f2f4'],
                borderWidth: 0,
                cutout: '75%'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              rotation: -90,
              circumference: 180,
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  callbacks: {
                    label: function (context) {
                      if (context.dataIndex === 0) {
                        return `Telah Disiapkan: ${completedCount} tiket (${completedPercentage}%)`;
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
      }
    } catch (error) {
      console.error('Error fetching pegawai pending tickets stats:', error);
    }
  }

  /**
   * Animate number change with smooth transition
   */
  function animateNumberChange(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;

    if (currentValue === newValue) return;

    // Add animation class
    element.style.transition = 'all 0.3s ease';
    element.style.transform = 'scale(1.1)';
    element.style.color = window.config?.colors?.primary || '#696cff';

    setTimeout(() => {
      element.textContent = newValue;
      setTimeout(() => {
        element.style.transform = 'scale(1)';
        element.style.color = '';
      }, 150);
    }, 150);
  }

  // =============================================================================
  // CHART DATA LOADING FUNCTIONS
  // =============================================================================

  /**
   * Load and update Status Tiket Chart
   */
  async function loadStatusChartPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/status-tickets');
      const data = await response.json();

      if (!data.success) return;

      const chartCanvas = document.getElementById('pegawaiStatusChart');
      if (!chartCanvas) return;

      const labels = data.data.map(item => item.st_name);
      const counts = data.data.map(item => item.count);
      const colors = data.data.map(item => statusColors[item.st_name] || '#6c757d');

      if (chartInstances.statusChart) {
        // Update existing chart
        chartInstances.statusChart.data.labels = labels;
        chartInstances.statusChart.data.datasets[0].data = counts;
        chartInstances.statusChart.data.datasets[0].backgroundColor = colors;
        chartInstances.statusChart.data.datasets[0].borderColor = colors;
        chartInstances.statusChart.update('none');
      } else {
        // Create new chart
        chartInstances.statusChart = new Chart(chartCanvas, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              data: counts,
              backgroundColor: colors,
              borderWidth: 1,
              borderColor: colors,
              borderRadius: 4,
              borderSkipped: false
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 5, bottom: 5, left: 0, right: 0 } },
            animation: { duration: 800 },
            scales: {
              x: {
                beginAtZero: true,
                grid: { color: borderColor, drawBorder: false },
                ticks: {
                  color: labelColor,
                  font: { size: 10 },
                  stepSize: 1,
                  callback: function (value) {
                    return Number.isInteger(value) ? value : '';
                  }
                }
              },
              y: {
                grid: { display: false },
                ticks: {
                  color: headingColor,
                  font: { size: 10, weight: '500' },
                  callback: function (value) {
                    const label = this.getLabelForValue(value);
                    return label.length > 12 ? label.substring(0, 12) + '...' : label;
                  }
                }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    return ` ${context.label}: ${context.parsed.x} tiket`;
                  }
                },
                backgroundColor: cardColor,
                titleColor: headingColor,
                bodyColor: bodyColor,
                borderWidth: 1,
                borderColor: borderColor,
                titleFont: { size: 11, weight: '600' },
                bodyFont: { size: 10 }
              }
            }
          }
        });
      }

      logger.log('Pegawai status chart updated successfully');
    } catch (error) {
      console.error('Error fetching pegawai status data:', error);
    }
  }

  /**
   * Load and update Subcategory Chart
   */
  async function loadSubcategoryChartPegawai() {
    try {
      const response = await fetch('/dashboard/pegawai/subcategory-tickets');
      const data = await response.json();

      if (!data.success) return;

      const chartCanvas = document.getElementById('pegawaiSubcategoryChart');
      if (!chartCanvas) return;

      const labels = data.data.map(item => item.subcat_name);
      const counts = data.data.map(item => item.count);
      const colors = data.data.map((item, index) => subcategoryColors[index % subcategoryColors.length]);

      if (chartInstances.subcategoryChart) {
        // Update existing chart
        chartInstances.subcategoryChart.data.labels = labels;
        chartInstances.subcategoryChart.data.datasets[0].data = counts;
        chartInstances.subcategoryChart.data.datasets[0].backgroundColor = colors;
        chartInstances.subcategoryChart.update('none');
      } else {
        // Create new chart
        chartInstances.subcategoryChart = new Chart(chartCanvas, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              data: counts,
              backgroundColor: colors,
              borderWidth: 2,
              borderColor: cardColor,
              hoverBorderWidth: 3,
              hoverBorderColor: '#fff',
              pointStyle: 'rectRounded'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 5, bottom: 5, left: 5, right: 5 } },
            animation: { duration: 1000, animateRotate: true, animateScale: false },
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
                  font: { size: 10, weight: '500' },
                  generateLabels: function (chart) {
                    const data = chart.data;
                    if (data.labels.length && data.datasets.length) {
                      return data.labels.map((label, index) => {
                        const dataset = data.datasets[0];
                        const value = dataset.data[index];
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
                titleFont: { size: 11, weight: '600' },
                bodyFont: { size: 10 }
              }
            }
          }
        });
      }

      logger.log('Pegawai subcategory chart updated successfully');
    } catch (error) {
      console.error('Error fetching pegawai subcategory data:', error);
    }
  }

  // =============================================================================
  // DATATABLE FUNCTIONS
  // =============================================================================

  /**
   * Initialize or refresh Overdue Tickets DataTable
   */
  function initializeOverdueTablePegawai() {
    const tableElement = document.getElementById('table-overdue-pegawai');
    if (!tableElement) return;

    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
      console.error('jQuery or DataTables not available');
      return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#table-overdue-pegawai')) {
      $('#table-overdue-pegawai').DataTable().destroy();
    }

    overdueTableInstance = $('#table-overdue-pegawai').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/dashboard/pegawai/list-overdue',
        type: 'GET',
        error: function (xhr, error) {
          console.error('DataTable error:', error);
          if (typeof toastr !== 'undefined') {
            toastr.error('Gagal memuat data tiket overdue');
          }
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', width: '5%', className: 'text-center', orderable: false, searchable: false },
        { data: 'ticket_nolog', name: 'ticket_nolog', width: '15%' },
        { data: 'kategori', name: 'kategori', width: '15%' },
        { data: 'tajuk', name: 'tajuk', width: '25%' },
        { data: 'status', name: 'status', width: '10%', className: 'text-center' },
        { data: 'assigned_to', name: 'assigned_to', width: '10%' },
        { data: 'sla_duedate', name: 'sla_duedate', width: '10%', className: 'text-center' },
        { data: 'days_overdue', name: 'days_overdue', width: '8%', className: 'text-center' },
        { data: 'action', name: 'action', width: '7%', orderable: false, searchable: false, className: 'text-center' }
      ],
      pageLength: 10,
      lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
      order: [[7, 'desc']],
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
      drawCallback: function () {
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
          $('[title]').tooltip();
        }
        $('.view-record').off('click').on('click', function () {
          const ticketUuid = $(this).data('uuid');
          window.location.href = '/tickets/' + ticketUuid;
        });
      }
    });

    logger.log('Pegawai overdue tickets DataTable initialized successfully');
  }

  /**
   * Refresh overdue table data
   */
  function refreshOverdueTable() {
    if (overdueTableInstance && $.fn.DataTable.isDataTable('#table-overdue-pegawai')) {
      overdueTableInstance.ajax.reload(null, false); // Reload without resetting pagination
    }
  }

  // =============================================================================
  // AUTO-REFRESH MAIN FUNCTIONS
  // =============================================================================

  /**
   * Refresh all dashboard data
   */
  async function refreshAllData() {
    if (isRefreshing || isPaused) return;

    isRefreshing = true;
    showRefreshIndicator();
    logger.log('🔄 Refreshing Pegawai Tugasan dashboard data...');

    try {
      // Refresh all components in parallel for better performance
      await Promise.all([
        loadTodayLogsCountPegawai(),
        loadOverdueSLACountPegawai(),
        loadWeeklyCompletedTicketsPegawai(),
        loadPendingTicketsStatsPegawai(),
        loadStatusChartPegawai(),
        loadSubcategoryChartPegawai()
      ]);

      // Refresh DataTable if it exists
      if (document.getElementById('table-overdue-pegawai')) {
        refreshOverdueTable();
      }

      updateLastRefreshTime();
      logger.log('✅ Dashboard data refreshed successfully');
    } catch (error) {
      console.error('❌ Error refreshing dashboard data:', error);
    } finally {
      isRefreshing = false;
      hideRefreshIndicator();
    }
  }

  /**
   * Start auto-refresh with interval
   */
  function startAutoRefresh() {
    if (refreshIntervalId) {
      clearInterval(refreshIntervalId);
    }

    isPaused = false;

    // Initial load
    refreshAllData();

    // Start real-time clock
    startRealtimeClock();

    // Set interval for periodic refresh
    refreshIntervalId = setInterval(() => {
      if (!isPaused) {
        refreshAllData();
      }
    }, REFRESH_INTERVAL);

    logger.log(`✅ Auto-refresh started (every ${REFRESH_INTERVAL / 1000} seconds)`);
  }

  /**
   * Stop auto-refresh
   */
  function stopAutoRefresh() {
    if (refreshIntervalId) {
      clearInterval(refreshIntervalId);
      refreshIntervalId = null;
    }
    stopRealtimeClock();
    logger.log('⏸️ Auto-refresh stopped');
  }

  // Button controls removed - auto-refresh runs automatically

  // =============================================================================
  // INITIALIZATION
  // =============================================================================

  /**
   * Initialize all Pegawai Tugasan dashboard components
   */
  function initializePegawaiDashboard() {
    // Check if we're on the pegawai dashboard page
    const isDashboardPage = document.getElementById('pegawai-today-logs-count') !== null;

    if (!isDashboardPage) {
      logger.log('Not on Pegawai Tugasan dashboard page, skipping initialization');
      return;
    }

    logger.log('🚀 Initializing Pegawai Tugasan Dashboard with Auto-Refresh...');

    // Initialize DataTable if table exists
    if (document.getElementById('table-overdue-pegawai')) {
      initializeOverdueTablePegawai();
    }

    // Start auto-refresh
    startAutoRefresh();

    logger.log('✅ Pegawai Tugasan Dashboard initialized successfully');
  }

  // Button controls removed - auto-refresh runs automatically

  // =============================================================================
  // PAGE LOAD & VISIBILITY HANDLING
  // =============================================================================

  // Handle page visibility (pause when tab is hidden, resume when visible)
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      logger.log('👁️ Page hidden, pausing refresh');
      // Don't actually pause, just skip refresh if page is hidden
    } else {
      logger.log('👁️ Page visible, refreshing data');
      if (!isPaused) {
        refreshAllData();
      }
    }
  });

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePegawaiDashboard);
  } else {
    initializePegawaiDashboard();
  }

  // Cleanup on page unload
  window.addEventListener('beforeunload', function () {
    stopAutoRefresh();

    // Destroy all chart instances
    Object.keys(chartInstances).forEach(key => {
      if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
      }
    });
  });

  // =============================================================================
  // EXPORT PUBLIC API
  // =============================================================================

  window.PegawaiTugasanDashboard = {
    refresh: refreshAllData,
    startAutoRefresh: startAutoRefresh,
    stopAutoRefresh: stopAutoRefresh,
    initialize: initializePegawaiDashboard,
    getRefreshInterval: () => REFRESH_INTERVAL,
    isPaused: () => isPaused,
    isRefreshing: () => isRefreshing
  };

})();
