/**
 * ============================================================================
 * DASHBOARD ADMIN/HELPDESK/PENGURUSAN - AUTO-REFRESH SYSTEM
 * ============================================================================
 *
 * Purpose: Automatically refresh dashboard data without page reload
 * Supports: Administrator, Pentadbir Sistem, Penyelia Helpdesk, Helpdesk On Duty, Pengurusan MOF
 *
 * Features:
 * - Auto-refresh every 60 seconds
 * - Updates all cards, charts, and tables
 * - Pause when tab is hidden (saves resources)
 * - Shows last refresh time
 * - Smart chart updates (no recreation/flicker)
 */

import logger from '../logger.js';

'use strict';

(function () {
  // =============================================================================
  // CONFIGURATION
  // =============================================================================

  const REFRESH_INTERVAL = 60000; // 60 seconds

  // =============================================================================
  // STATE MANAGEMENT
  // =============================================================================

  let refreshIntervalId = null;
  let isPaused = false;
  let isRefreshing = false;
  let lastRefreshTime = null;
  let clockIntervalId = null; // For real-time clock update

  // Store chart instances to avoid recreating them
  const chartInstances = {
    statusChart: null,
    categoryChart: null,
    totalChart: null,
    trendChart: null,
    assignedChart: null,
    pendingTicketsChart: null,
    weeklyCompletedChart: null
  };

  // Store DataTable instance
  let overdueTable = null;

  // =============================================================================
  // CARD REFRESH FUNCTIONS
  // =============================================================================

  /**
   * Refresh Today's Logs Count
   */
  async function refreshTodayLogs() {
    try {
      const response = await fetch('/dashboard/today-logs-count');
      const data = await response.json();

      if (data.success) {
        const element = document.getElementById('today-logs-count');
        if (element) {
          element.textContent = data.count || 0;
        }
      }
    } catch (error) {
      logger.error('Error refreshing today logs:', error);
    }
  }

  /**
   * Refresh Overdue SLA Count
   */
  async function refreshOverdueSLA() {
    try {
      const response = await fetch('/dashboard/overdue-sla-count');
      const data = await response.json();

      if (data.success) {
        const element = document.getElementById('overdue-sla-count');
        if (element) {
          element.textContent = data.count || 0;
        }
      }
    } catch (error) {
      logger.error('Error refreshing overdue SLA:', error);
    }
  }

  /**
   * Refresh Pending Tickets (with semi-circle chart)
   */
  async function refreshPendingTickets() {
    try {
      const response = await fetch('/dashboard/pending-stats');
      const data = await response.json();

      if (data.success) {
        // Update counts
        const pendingElement = document.getElementById('pending-tickets-count');
        const totalElement = document.getElementById('total-tickets-count');
        const percentElement = document.getElementById('pending-percentage');

        if (pendingElement) pendingElement.textContent = data.pending || 0;
        if (totalElement) totalElement.textContent = data.total || 0;
        if (percentElement) percentElement.textContent = data.percentage + '%';

        // Update semi-circle chart
        updatePendingTicketsChart(data);
      }
    } catch (error) {
      logger.error('Error refreshing pending tickets:', error);
    }
  }

  /**
   * Refresh Weekly Completed Tickets (with bar chart)
   */
  async function refreshWeeklyCompleted() {
    try {
      const response = await fetch('/dashboard/weekly-completed');
      const data = await response.json();

      if (data.success) {
        const element = document.getElementById('weekly-completed-count');
        if (element) {
          element.textContent = data.count || 0;
        }

        // Update weekly bar chart
        updateWeeklyCompletedChart(data);
      }
    } catch (error) {
      logger.error('Error refreshing weekly completed:', error);
    }
  }

  // =============================================================================
  // CHART REFRESH FUNCTIONS
  // =============================================================================

  /**
   * Refresh Status Chart (Horizontal Bar)
   */
  async function refreshStatusChart() {
    try {
      const response = await fetch('/dashboard/status-tickets');
      const data = await response.json();

      if (data.success && chartInstances.statusChart) {
        const labels = data.data.map(item => item.st_name);
        const counts = data.data.map(item => item.count);

        // Update chart data
        chartInstances.statusChart.data.labels = labels;
        chartInstances.statusChart.data.datasets[0].data = counts;
        chartInstances.statusChart.update('none'); // No animation for smooth update
      }
    } catch (error) {
      logger.error('Error refreshing status chart:', error);
    }
  }

  /**
   * Refresh Category Chart (Pie Chart)
   */
  async function refreshCategoryChart() {
    try {
      const response = await fetch('/dashboard/category-tickets');
      const data = await response.json();

      if (data.success && chartInstances.categoryChart) {
        const labels = data.data.map(item => item.cat_name);
        const counts = data.data.map(item => item.count);

        // Update chart data
        chartInstances.categoryChart.data.labels = labels;
        chartInstances.categoryChart.data.datasets[0].data = counts;
        chartInstances.categoryChart.update('none');
      }
    } catch (error) {
      logger.error('Error refreshing category chart:', error);
    }
  }

  /**
   * Refresh Total Chart (Doughnut)
   */
  async function refreshTotalChart() {
    try {
      const response = await fetch('/dashboard/total-tickets');
      const data = await response.json();

      if (data.success) {
        // Update total count
        const totalElement = document.getElementById('totalCount');
        if (totalElement) {
          totalElement.textContent = data.total || 0;
        }

        // Update chart if exists
        if (chartInstances.totalChart) {
          const counts = [data.completed || 0, data.pending || 0];
          chartInstances.totalChart.data.datasets[0].data = counts;
          chartInstances.totalChart.update('none');
        }
      }
    } catch (error) {
      logger.error('Error refreshing total chart:', error);
    }
  }

  /**
   * Refresh Trend Chart (Line Chart - Monthly)
   */
  async function refreshTrendChart() {
    try {
      const selectedYear = document.getElementById('selectedYear')?.textContent || new Date().getFullYear();
      const response = await fetch(`/dashboard/monthly-trend?year=${selectedYear}`);
      const data = await response.json();

      if (data.success && chartInstances.trendChart) {
        chartInstances.trendChart.data.datasets[0].data = data.data;
        chartInstances.trendChart.update('none');
      }
    } catch (error) {
      logger.error('Error refreshing trend chart:', error);
    }
  }

  /**
   * Refresh Assigned Chart (if visible - admin & helpdesk only)
   */
  async function refreshAssignedChart() {
    const assignedChartElement = document.getElementById('assignedChart');
    if (!assignedChartElement) return; // Not visible on this dashboard

    try {
      // Get current filters
      const categoryId = document.querySelector('#categoryDropdown .dropdown-item.active')?.dataset.categoryId || '';
      const subcategoryId = document.querySelector('#subcategoryDropdown .dropdown-item.active')?.dataset.subcategoryId || '';
      const month = document.getElementById('selectedMonth')?.textContent || new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

      const response = await fetch(`/dashboard/total-assigned?category=${categoryId}&subcategory=${subcategoryId}&month=${month}`);
      const data = await response.json();

      if (data.success && chartInstances.assignedChart) {
        const labels = data.data.map(item => item.name);
        const counts = data.data.map(item => item.count);

        chartInstances.assignedChart.data.labels = labels;
        chartInstances.assignedChart.data.datasets[0].data = counts;
        chartInstances.assignedChart.update('none');
      }
    } catch (error) {
      logger.error('Error refreshing assigned chart:', error);
    }
  }

  /**
   * Update Pending Tickets Semi-Circle Chart
   */
  function updatePendingTicketsChart(data) {
    if (!chartInstances.pendingTicketsChart) return;

    const percentage = parseFloat(data.percentage) || 0;
    const remaining = 100 - percentage;

    chartInstances.pendingTicketsChart.data.datasets[0].data = [percentage, remaining];
    chartInstances.pendingTicketsChart.update('none');
  }

  /**
   * Update Weekly Completed Bar Chart
   */
  function updateWeeklyCompletedChart(data) {
    if (!chartInstances.weeklyCompletedChart) return;

    // Assuming data has daily counts (if backend provides this)
    if (data.daily && Array.isArray(data.daily)) {
      chartInstances.weeklyCompletedChart.data.datasets[0].data = data.daily;
      chartInstances.weeklyCompletedChart.update('none');
    }
  }

  // =============================================================================
  // DATATABLE REFRESH
  // =============================================================================

  /**
   * Refresh Overdue Tickets DataTable (if visible - admin & helpdesk only)
   */
  function refreshOverdueTable() {
    const tableElement = document.getElementById('table-overdue');
    if (!tableElement) return; // Not visible on this dashboard

    if (overdueTable) {
      overdueTable.ajax.reload(null, false); // Reload without resetting pagination
    }
  }

  /**
   * Initialize Overdue DataTable
   */
  function initializeOverdueTable() {
    const tableElement = document.getElementById('table-overdue');
    if (!tableElement || $.fn.DataTable.isDataTable(tableElement)) return;

    overdueTable = $(tableElement).DataTable({
      processing: true,
      serverSide: true,
      ajax: '/dashboard/overdue-tickets',
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'ticket_no', name: 'ticket_no' },
        { data: 'title', name: 'title' },
        { data: 'category', name: 'category' },
        { data: 'status', name: 'status' },
        { data: 'assigned_to', name: 'assigned_to' },
        { data: 'overdue_days', name: 'overdue_days' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      order: [[6, 'desc']], // Order by overdue days
      pageLength: 10,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ms.json'
      }
    });
  }

  // =============================================================================
  // REFRESH ORCHESTRATION
  // =============================================================================

  /**
   * Refresh all dashboard data
   */
  async function refreshAllData() {
    if (isRefreshing) {
      logger.log('⏳ Refresh already in progress, skipping...');
      return;
    }

    // Check if page is hidden (tab inactive)
    if (document.hidden) {
      logger.log('👁️ Page hidden, skipping refresh');
      return;
    }

    isRefreshing = true;
    logger.log('🔄 Refreshing all admin dashboard data...');

    try {
      // Refresh all cards
      await Promise.all([
        refreshTodayLogs(),
        refreshOverdueSLA(),
        refreshPendingTickets(),
        refreshWeeklyCompleted()
      ]);

      // Refresh all charts
      await Promise.all([
        refreshStatusChart(),
        refreshCategoryChart(),
        refreshTotalChart(),
        refreshTrendChart(),
        refreshAssignedChart()
      ]);

      // Refresh DataTable
      refreshOverdueTable();

      // Update last refresh time
      updateLastRefreshTime();

      logger.log('✅ Dashboard refresh completed');
    } catch (error) {
      logger.error('❌ Error during dashboard refresh:', error);
    } finally {
      isRefreshing = false;
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

    const element = document.getElementById('admin-last-refresh-time');
    if (element) {
      element.textContent = timeString;
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
  // AUTO-REFRESH CONTROL
  // =============================================================================

  /**
   * Start auto-refresh interval
   */
  function startAutoRefresh() {
    if (refreshIntervalId) {
      logger.log('Auto-refresh already running');
      return;
    }

    logger.log(`🚀 Starting auto-refresh (every ${REFRESH_INTERVAL / 1000} seconds)`);

    // Initial refresh
    refreshAllData();

    // Start real-time clock
    startRealtimeClock();

    // Set interval
    refreshIntervalId = setInterval(() => {
      if (!isPaused && !document.hidden) {
        refreshAllData();
      }
    }, REFRESH_INTERVAL);
  }

  /**
   * Stop auto-refresh interval
   */
  function stopAutoRefresh() {
    if (refreshIntervalId) {
      clearInterval(refreshIntervalId);
      refreshIntervalId = null;
      logger.log('⏹️ Auto-refresh stopped');
    }
    stopRealtimeClock();
  }

  // =============================================================================
  // CHART INSTANCE CAPTURE
  // =============================================================================

  /**
   * Capture existing chart instances created by dashboard.js
   */
  function captureChartInstances() {
    // Wait a bit for charts to be initialized by dashboard.js
    setTimeout(() => {
      // Capture all chart instances
      const chartIds = ['statusChart', 'categoryChart', 'totalChart', 'trendChart', 'assignedChart', 'pendingTicketsChart', 'weeklyCompletedChart'];

      chartIds.forEach(chartId => {
        const canvas = document.getElementById(chartId);
        if (canvas) {
          const chartInstance = Chart.getChart(canvas);
          if (chartInstance) {
            chartInstances[chartId] = chartInstance;
            logger.log(`✅ Captured ${chartId} instance`);
          }
        }
      });

      logger.log('Chart instances captured:', Object.keys(chartInstances).filter(key => chartInstances[key] !== null));
    }, 2000); // Wait 2 seconds for dashboard.js to initialize charts
  }

  // =============================================================================
  // INITIALIZATION
  // =============================================================================

  /**
   * Initialize admin dashboard auto-refresh
   */
  function initializeAdminDashboard() {
    // Check if we're on admin/helpdesk/pengurusan dashboard
    const isAdminDashboard = document.getElementById('today-logs-count') !== null;

    if (!isAdminDashboard) {
      logger.log('Not on admin/helpdesk/pengurusan dashboard, skipping initialization');
      return;
    }

    logger.log('🚀 Initializing Admin/Helpdesk/Pengurusan Dashboard Auto-Refresh...');

    // Initialize DataTable if exists
    initializeOverdueTable();

    // Capture chart instances
    captureChartInstances();

    // Start auto-refresh
    startAutoRefresh();

    logger.log('✅ Admin Dashboard Auto-Refresh initialized');
  }

  // =============================================================================
  // PAGE VISIBILITY HANDLING
  // =============================================================================

  // Handle page visibility (refresh when tab becomes visible)
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      logger.log('👁️ Page hidden, pausing refresh');
    } else {
      logger.log('👁️ Page visible, refreshing data');
      if (!isPaused) {
        refreshAllData();
      }
    }
  });

  // =============================================================================
  // PAGE LOAD
  // =============================================================================

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdminDashboard);
  } else {
    initializeAdminDashboard();
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

    // Destroy DataTable
    if (overdueTable) {
      overdueTable.destroy();
      overdueTable = null;
    }
  });

  // =============================================================================
  // EXPORT PUBLIC API
  // =============================================================================

  window.AdminDashboard = {
    refresh: refreshAllData,
    startAutoRefresh: startAutoRefresh,
    stopAutoRefresh: stopAutoRefresh,
    initialize: initializeAdminDashboard,
    getRefreshInterval: () => REFRESH_INTERVAL,
    isPaused: () => isPaused,
    isRefreshing: () => isRefreshing
  };

})();
