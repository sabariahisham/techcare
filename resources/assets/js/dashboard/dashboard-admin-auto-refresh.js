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

  const CHART_IDS = [
    'statusChart',
    'categoryChart',
    'totalChart',
    'trendChart',
    'assignedChart',
    'pendingTicketsChart',
    'weeklyCompletedChart'
  ];

  const ENGLISH_MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  // =============================================================================
  // CHART / FILTER HELPERS
  // =============================================================================

  /**
   * Lazily capture a Chart.js instance from the canvas (dashboard.js creates charts async).
   */
  function ensureChartInstance(chartId) {
    if (chartInstances[chartId]) {
      return chartInstances[chartId];
    }

    const canvas = document.getElementById(chartId);
    if (!canvas || typeof Chart === 'undefined') {
      return null;
    }

    const instance = Chart.getChart(canvas);
    if (instance) {
      chartInstances[chartId] = instance;
    }

    return chartInstances[chartId];
  }

  /**
   * Poll until dashboard.js has finished creating charts, then start auto-refresh.
   */
  function captureChartInstances(maxAttempts = 30, intervalMs = 500) {
    return new Promise(resolve => {
      let attempts = 0;

      const tryCapture = () => {
        CHART_IDS.forEach(chartId => {
          ensureChartInstance(chartId);
        });

        const capturedOnPage = CHART_IDS.filter(id => {
          return document.getElementById(id) && chartInstances[id] !== null;
        });

        const pendingOnPage = CHART_IDS.filter(id => document.getElementById(id));
        const allCaptured = pendingOnPage.length === 0 || capturedOnPage.length === pendingOnPage.length;

        attempts += 1;

        if (allCaptured || attempts >= maxAttempts) {
          logger.log(
            'Chart instances captured:',
            Object.keys(chartInstances).filter(key => chartInstances[key] !== null)
          );
          resolve();
          return;
        }

        setTimeout(tryCapture, intervalMs);
      };

      tryCapture();
    });
  }

  /**
   * Resolve dropdown item data-* value from the visible button label.
   */
  function getDropdownSelection(dropdownId, buttonId, dataAttribute) {
    const button = document.getElementById(buttonId);
    const dropdown = document.getElementById(dropdownId);
    if (!button || !dropdown) {
      return '';
    }

    const label = button.textContent.trim();
    const items = dropdown.querySelectorAll('.dropdown-item');

    for (const item of items) {
      if (item.textContent.trim() === label) {
        return item.getAttribute(dataAttribute) || '';
      }
    }

    return '';
  }

  /**
   * Parse month/year from #selectedMonth (e.g. "May 2026").
   */
  function parseSelectedMonthYear() {
    const monthText = document.getElementById('selectedMonth')?.textContent?.trim() || '';
    const parts = monthText.split(/\s+/);

    if (parts.length >= 2) {
      const year = parseInt(parts[parts.length - 1], 10);
      const monthName = parts.slice(0, -1).join(' ');
      const month = ENGLISH_MONTHS.indexOf(monthName) + 1;

      if (!Number.isNaN(year) && month > 0) {
        return { month, year };
      }
    }

    const now = new Date();
    return { month: now.getMonth() + 1, year: now.getFullYear() };
  }

  /**
   * Build assigned-chart API URL matching dashboard.js.
   */
  function buildAssignedChartUrl() {
    const { month, year } = parseSelectedMonthYear();
    const categoryId = getDropdownSelection('categoryDropdown', 'selectedCategory', 'data-category-id');
    const subcategoryId = getDropdownSelection('subcategoryDropdown', 'selectedSubcategory', 'data-subcategory-id');

    let url = `/dashboard/total-assigned?month=${month}&year=${year}`;
    if (categoryId) {
      url += `&category_id=${encodeURIComponent(categoryId)}`;
    }
    if (subcategoryId) {
      url += `&subcategory_id=${encodeURIComponent(subcategoryId)}`;
    }

    return { url, month, year };
  }

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
        // Update counts (API returns pending_count / total_count)
        const pendingElement = document.getElementById('pending-tickets-count');
        const totalElement = document.getElementById('total-tickets-count');
        const percentElement = document.getElementById('pending-percentage');

        if (pendingElement) pendingElement.textContent = data.pending_count ?? 0;
        if (totalElement) totalElement.textContent = data.total_count ?? 0;
        if (percentElement) {
          const completedPercentage = (100 - (parseFloat(data.percentage) || 0)).toFixed(1);
          percentElement.innerHTML = `${completedPercentage}%<br><small style="font-size: 9px; font-weight: normal;">Telah Disiapkan</small>`;
        }

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
          element.textContent = data.total ?? 0;
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

      if (data.success) {
        const labels = data.data.map(item => item.st_name);
        const counts = data.data.map(item => item.count);
        const chart = ensureChartInstance('statusChart');

        if (chart) {
          chart.data.labels = labels;
          chart.data.datasets[0].data = counts;
          chart.update('none'); // No animation for smooth update
        }
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

      if (data.success) {
        const labels = data.data.map(item => item.cat_name);
        const counts = data.data.map(item => item.count);
        const chart = ensureChartInstance('categoryChart');

        if (chart) {
          chart.data.labels = labels;
          chart.data.datasets[0].data = counts;
          chart.update('none');
        }
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
        const labels = (data.data || []).map(item => item.type_name);
        const counts = (data.data || []).map(item => item.count);
        const totalCount = data.total ?? counts.reduce((sum, count) => sum + count, 0);

        const totalElement = document.getElementById('totalCount');
        if (totalElement) {
          totalElement.textContent = totalCount;
        }

        const chart = ensureChartInstance('totalChart');
        if (chart) {
          chart.data.labels = labels;
          chart.data.datasets[0].data = counts;
          chart.update('none');
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
      const selectedYear = document.getElementById('selectedYear')?.textContent?.trim() || new Date().getFullYear();
      const response = await fetch(`/dashboard/monthly-trend?year=${selectedYear}`);
      const data = await response.json();

      if (data.success) {
        const labels = (data.data || []).map(item => item.month_name);
        const counts = (data.data || []).map(item => item.count);
        const totalTickets = data.total ?? counts.reduce((sum, count) => sum + count, 0);

        const subtitleElement = document.getElementById('trendSubtitle');
        if (subtitleElement) {
          subtitleElement.textContent = `Jumlah ${totalTickets} tiket pada tahun ${selectedYear}`;
        }

        const chart = ensureChartInstance('trendChart');
        if (chart) {
          chart.data.labels = labels;
          chart.data.datasets[0].data = counts;
          chart.update('none');
        }
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
      const { url, month, year } = buildAssignedChartUrl();
      const response = await fetch(url);
      const data = await response.json();

      if (data.success) {
        const labels = (data.data || []).map(item => item.officer_name);
        const completedTickets = (data.data || []).map(item => item.completed_tickets || 0);
        const pendingTickets = (data.data || []).map(item => item.pending_tickets || 0);
        const totalTickets = data.total ?? 0;
        const totalCompleted = data.total_completed ?? 0;
        const totalPending = data.total_pending ?? 0;

        const subtitleElement =
          document.getElementById('assignedSubtitle') ||
          document.getElementById('assi1234gnedSubtitle');
        if (subtitleElement) {
          subtitleElement.textContent = `Jumlah ${totalTickets} tiket (${totalCompleted} selesai, ${totalPending} belum selesai) pada ${ENGLISH_MONTHS[month - 1]} ${year}`;
        }

        const chart = ensureChartInstance('assignedChart');
        if (chart) {
          chart.data.labels = labels;
          chart.data.datasets[0].data = completedTickets;
          if (chart.data.datasets[1]) {
            chart.data.datasets[1].data = pendingTickets;
          }
          chart.update('none');
        }
      }
    } catch (error) {
      logger.error('Error refreshing assigned chart:', error);
    }
  }

  /**
   * Update Pending Tickets Semi-Circle Chart
   */
  function updatePendingTicketsChart(data) {
    const chart = ensureChartInstance('pendingTicketsChart');
    if (!chart) return;

    const pendingPercentage = parseFloat(data.percentage) || 0;
    const completedPercentage = 100 - pendingPercentage;

    // Match dashboard.js: [completed, pending]
    chart.data.datasets[0].data = [completedPercentage, pendingPercentage];
    chart.update('none');
  }

  /**
   * Update Weekly Completed Bar Chart
   */
  function updateWeeklyCompletedChart(data) {
    const chart = ensureChartInstance('weeklyCompletedChart');
    if (!chart) return;

    // Assuming data has daily counts (if backend provides this)
    if (data.daily && Array.isArray(data.daily)) {
      chart.data.datasets[0].data = data.daily;
      chart.update('none');
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

    // Re-capture charts created asynchronously by dashboard.js
    CHART_IDS.forEach(chartId => ensureChartInstance(chartId));

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

    document.querySelectorAll('.dashboard-live-clock').forEach(element => {
      element.textContent = timeString;
    });
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

  // =============================================================================
  // INITIALIZATION
  // =============================================================================

  /**
   * Initialize admin dashboard auto-refresh
   */
  async function initializeAdminDashboard() {
    // Check if we're on admin/helpdesk/pengurusan dashboard
    const isAdminDashboard = document.getElementById('today-logs-count') !== null;

    if (!isAdminDashboard) {
      logger.log('Not on admin/helpdesk/pengurusan dashboard, skipping initialization');
      return;
    }

    logger.log('🚀 Initializing Admin/Helpdesk/Pengurusan Dashboard Auto-Refresh...');

    // Start live clock immediately (do not wait for chart capture)
    startRealtimeClock();

    // Initialize DataTable if exists
    initializeOverdueTable();

    // Wait for dashboard.js async chart creation before first refresh
    await captureChartInstances();

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
