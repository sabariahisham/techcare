/**
 * Monitoring Dashboard Real-Time Updates
 *
 * This script handles real-time data updates for the monitoring dashboard
 * Designed for TV display with auto-refresh every 1 minute
 */

import ChartDataLabels from 'chartjs-plugin-datalabels';

// Global variables
let overdueTable = null;
let actionTable = null;
let refreshInterval = 60000; // 1 minute


/**
 * Register ChartDataLabels plugin once Chart.js is available.
 * Must run before inline chart scripts on DOMContentLoaded.
 */
function registerMonitoringChartPlugins() {
  if (typeof window.Chart === 'undefined') {
    return false;
  }

  if (!window.__monitoringChartDataLabelsRegistered) {
    window.Chart.register(ChartDataLabels);
    window.ChartDataLabels = ChartDataLabels;
    window.__monitoringChartDataLabelsRegistered = true;
  }

  if (!window.__monitoringDoughnutLabelsRegistered) {
    if (typeof monitoringDoughnutLineLabelPlugin !== 'undefined') {
      window.Chart.register(monitoringDoughnutLineLabelPlugin);
      window.__monitoringDoughnutLabelsRegistered = true;
    } else {
      console.warn('monitoringDoughnutLineLabelPlugin is not defined; skipping plugin registration.');
    }
  }

  return true;
}

registerMonitoringChartPlugins();
window.registerMonitoringChartPlugins = registerMonitoringChartPlugins;

/**
 * Return responsive font size for Chart.js based on viewport width
 */
function isMonitoringMobile() {
  return window.innerWidth < 768;
}

function getMonitoringChartFontSize(multiplier = 1) {
  const width = window.innerWidth;

  if (width >= 3840) return Math.round(32 * multiplier); // 4K TV
  if (width >= 2560) return Math.round(28 * multiplier); // QHD
  if (width >= 1920) return Math.round(20 * multiplier); // Full HD
  if (width >= 1366) return Math.round(10 * multiplier); // Laptop
  if (width >= 768) return Math.round(9 * multiplier); // Tablet

  return Math.round(9 * multiplier); // Mobile
}

function getMonitoringDoughnutLegendOptions(legendMultiplier = 1) {
  const mobile = isMonitoringMobile();
  const width = window.innerWidth;
  const hd = width >= 1920;
  // Laptop (1366–1919): label legend bawah donut terlalu kecil jika ikut skala default
  const isLaptop = width >= 1366 && width < 1920;

  let sizeMultiplier;
  if (mobile) {
    sizeMultiplier = 0.85 * legendMultiplier;
  } else if (hd) {
    sizeMultiplier = 0.85 * legendMultiplier;
  } else if (isLaptop) {
    // ~16px (12 * 1.5) supaya mudah dibaca pada skrin laptop
    sizeMultiplier = 1.5;
  } else {
    sizeMultiplier = legendMultiplier;
  }

  return {
    position: 'bottom',
    labels: {
      color: '#4b5563',
      boxWidth: mobile ? 10 : (hd ? 12 : (isLaptop ? 14 : 16)),
      boxHeight: mobile || hd ? 8 : 10,
      // Rapatkan jarak antara item legend supaya donut lebih besar
      padding: mobile || hd ? 4 : (isLaptop ? 8 : 6),
      usePointStyle: mobile,
      font: {
        size: getMonitoringChartFontSize(sizeMultiplier)
      }
    }
  };
}

function getMonitoringDoughnutDataLabelOptions(labelMultiplier = 1) {
  const mobile = isMonitoringMobile();
  const hd = window.innerWidth >= 1920;

  return {
    color: '#fff',
    font: {
      weight: 'bold',
      size: getMonitoringChartFontSize(
        (mobile ? 0.8 : (hd ? 0.85 : 1)) * labelMultiplier
      )
    },
    formatter: function (value) {
      return value > 0 ? value : '';
    }
  };
}

function getMonitoringDoughnutLayoutOptions() {
  const mobile = isMonitoringMobile();
  const hd = window.innerWidth >= 1920;

  return {
    padding: {
      top: mobile ? 4 : (hd ? 4 : 8),
      bottom: mobile ? 2 : (hd ? 2 : 4)
    }
  };
}

function getMonitoringBarScaleOptions() {
  const mobile = isMonitoringMobile();
  const width = window.innerWidth;
  const isLaptop = width >= 1366 && width < 1920;

  return {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        font: {
          size: getMonitoringChartFontSize(mobile ? 0.9 : 0.85)
        }
      }
    },
    x: {
      ticks: {
        // Paparkan SEMUA label status — jangan skip bila sempit
        maxRotation: mobile ? 45 : 35,
        minRotation: mobile ? 30 : 0,
        autoSkip: false,
        font: {
          // Laptop: ~16px supaya label bawah mudah dibaca
          size: getMonitoringChartFontSize(mobile ? 0.9 : (isLaptop ? 1.35 : 0.85))
        }
      }
    }
  };
}

function getMonitoringBarDataLabelOptions() {
  const mobile = isMonitoringMobile();

  return {
    anchor: 'end',
    align: 'top',
    color: '#2c3e50',
    font: {
      weight: 'bold',
      size: getMonitoringChartFontSize(mobile ? 0.9 : 1)
    },
    formatter: function (value) {
      return value;
    }
  };
}

// Expose for inline chart scripts in blade partials
window.isMonitoringMobile = isMonitoringMobile;
window.getMonitoringChartFontSize = getMonitoringChartFontSize;
window.getMonitoringDoughnutLegendOptions = getMonitoringDoughnutLegendOptions;
window.getMonitoringDoughnutDataLabelOptions = getMonitoringDoughnutDataLabelOptions;
window.getMonitoringDoughnutLayoutOptions = getMonitoringDoughnutLayoutOptions;
window.getMonitoringBarScaleOptions = getMonitoringBarScaleOptions;
window.getMonitoringBarDataLabelOptions = getMonitoringBarDataLabelOptions;

function getChartFont(multiplier = 1) {
  return getMonitoringChartFontSize(multiplier);
}

window.getChartFont = getChartFont;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
  console.log('Monitoring Dashboard initialized');

  // Auto-focus on monitoring dashboard
  focusMonitoringDashboard();

  // Load initial data
  loadCardsData();
  initializeOverdueTable();
  initializeActionTable();
  updateDateTime();

  // Set up periodic refresh (every 1 minute)
  setInterval(function () {
    loadCardsData();
    refreshOverdueTable();
    refreshActionTable();
    updateDateTime();
  }, refreshInterval);

  // Update date/time every second
  setInterval(updateDateTime, 1000);

  // Menu toggle functionality
  initializeMenuToggle();

  // Redraw charts bila orientasi/resize berubah (mobile)
  window.monitoringChartReloaders = window.monitoringChartReloaders || [];
  let resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      (window.monitoringChartReloaders || []).forEach(function (reloadFn) {
        reloadFn();
      });
    }, 350);
  });
});

/**
 * Auto-focus on monitoring dashboard when page loads
 */
function focusMonitoringDashboard() {
  const dashboard = document.getElementById('monitoringDashboard');
  if (dashboard) {
    dashboard.focus();
    dashboard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    console.log('Monitoring dashboard focused');
  }
}

/**
 * Initialize menu toggle for sidebar
 */
function initializeMenuToggle() {
  const menuIcon = document.getElementById('menu-toggle-icon');
  const layoutMenu = document.getElementById('layout-menu');

  console.log('Menu Icon:', menuIcon);
  console.log('Layout Menu:', layoutMenu);

  if (!menuIcon) {
    console.error('Menu icon not found!');
    return;
  }

  if (!layoutMenu) {
    console.error('Layout menu not found!');
    return;
  }

  // Add click handler to menu icon
  menuIcon.addEventListener('click', function (e) {
    console.log('Menu icon clicked!');
    e.preventDefault();
    e.stopPropagation();

    const isVisible = layoutMenu.classList.contains('menu-visible');

    if (isVisible) {
      layoutMenu.classList.remove('menu-visible');
      console.log('Menu hidden');
    } else {
      layoutMenu.classList.add('menu-visible');
      console.log('Menu shown');
    }
  });

  // Close sidebar when clicking outside
  document.addEventListener('click', function (event) {
    const isClickInsideMenu = layoutMenu.contains(event.target);
    const isClickOnMenuIcon = menuIcon.contains(event.target);

    if (!isClickInsideMenu && !isClickOnMenuIcon && layoutMenu.classList.contains('menu-visible')) {
      layoutMenu.classList.remove('menu-visible');
      console.log('Menu closed by clicking outside');
    }
  });

  console.log('Menu toggle initialized successfully');
}

/**
 * Update current date and time display
 */
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  };

  const dateTimeString = now.toLocaleString('ms-MY', options);
  const dateTimeElement = document.getElementById('currentDateTime');

  if (dateTimeElement) {
    const mobile = window.innerWidth < 768;
    const displayString = mobile
      ? now.toLocaleString('ms-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      })
      : dateTimeString;

    dateTimeElement.innerHTML = `<i class="bx bx-time-five me-1 me-sm-2"></i>${displayString}`;
  }
}

/**
 * Load cards data (Aduan Hari Ini, Belum Selesai, Lewat SLA, Selesai Hari Ini)
 */
function loadCardsData() {
  fetch('/dashboard/monitoring/cards-data')
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        updateCardsUI(result.data);
      } else {
        resetCardsUIToZero();
      }
    })
    .catch(error => {
      console.error('Error loading cards data:', error);
      // Elak kad tersekat pada skeleton loading — papar 0 sebagai fallback
      resetCardsUIToZero();
    });
}

/**
 * Update cards UI with fetched data
 */
function updateCardsUI(data) {
  setCountText('today-logs-count', data.aduan_hari_ini);
  setCountText('overdue-sla-count', data.lewat_sla);
  setCountText('pending-tickets-count', data.belum_selesai);
  setCountText('weekly-completed-count', data.selesai_hari_ini);

  // Belum Selesai — Keseluruhan & Bulan Semasa (paparan pecahan "x / y")
  updatePendingStats(data);

  // Update weekly chart
  updateWeeklyChart(data.weekly_chart || []);
}

/**
 * Update pending tickets stats — Keseluruhan (x / total) & Bulan Semasa (x / total)
 * Displayed as plain fraction figures so it's instantly readable, no chart needed.
 */
function updatePendingStats(data) {
  setCountText('pending-overall-count', data.belum_selesai);
  setCountText('total-tickets-count', data.total_tickets);
  setCountText('pending-month-count', data.belum_selesai_bulan_semasa);
  setCountText('total-tickets-month-count', data.total_tickets_bulan_semasa);

  const monthLabelElement = document.getElementById('pending-current-month-label');
  if (monthLabelElement) {
    const monthYear = new Date().toLocaleDateString('ms-MY', { month: 'long', year: 'numeric' });
    monthLabelElement.textContent = monthYear;
  }
}

/**
 * Reset all card counts to 0 — used when the API call fails or returns no data,
 * so the UI never shows a blank/empty value or gets stuck on the skeleton loader.
 */
function resetCardsUIToZero() {
  ['today-logs-count', 'overdue-sla-count', 'pending-tickets-count', 'weekly-completed-count',
    'pending-overall-count', 'total-tickets-count', 'pending-month-count', 'total-tickets-month-count'
  ].forEach(id => setCountText(id, 0));
}

/**
 * Set an element's text content as a number, defaulting to 0 when the value
 * is missing/null/undefined/empty so counts are never left blank.
 */
function setCountText(elementId, value) {
  const element = document.getElementById(elementId);
  if (!element) return;

  const isEmpty = value === null || value === undefined || value === '';
  const numValue = isEmpty || isNaN(value) ? 0 : Number(value);
  element.textContent = numValue;
}

/**
 * Update weekly completed tickets chart
 */
function updateWeeklyChart(weeklyData) {
  const ctx = document.getElementById('weeklyCompletedChart');
  if (!ctx) return;

  const labels = weeklyData.map(item => item.day);
  const counts = weeklyData.map(item => item.count);

  // Check if chart already exists
  if (window.weeklyChart) {
    window.weeklyChart.destroy();
  }

  window.weeklyChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Selesai',
        data: counts,
        backgroundColor: 'rgba(113, 221, 55, 0.8)',
        borderColor: 'rgba(113, 221, 55, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          top: 32,
          right: 6,
          left: 6,
          bottom: 0
        }
      },
      plugins: {
        legend: {
          display: false
        },
        datalabels: window.getMonitoringBarDataLabelOptions ?
          Object.assign({}, window.getMonitoringBarDataLabelOptions(), {
            offset: 6,
            font: {
              weight: 'bold',
              size: getMonitoringChartFontSize(1.0)
            }
          }) : {
            anchor: 'end',
            align: 'top',
            color: '#2c3e50',
            offset: 6,
            font: {
              weight: 'bold',
              size: getMonitoringChartFontSize(1.0)
            },
            formatter: function (value) {
              return value;
            }
          }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            font: {
              size: getMonitoringChartFontSize(0.85)
            }
          }
        },
        x: {
          ticks: {
            font: {
              size: getMonitoringChartFontSize(0.8)
            }
          }
        }
      }
    }
  });
}

/**
 * Initialize overdue tickets DataTable
 */
function initializeOverdueTable() {
  overdueTable = $('#table-overdue').DataTable({
    ajax: {
      url: '/dashboard/monitoring/overdue-tickets',
      dataSrc: 'data'
    },
    columns: [
      {
        data: null,
        render: function (data, type, row, meta) {
          return meta.row + 1;
        }
      },
      { data: 'ticket_nolog' },
      {
        data: null,
        render: function (data) {
          return `${data.category}<br><small class="text-muted">${data.subcategory}</small>`;
        }
      },
      {
        data: 'ticket_title',
        render: function (data) {
          return data.length > 50 ? data.substring(0, 50) + '...' : data;
        }
      },
      {
        data: 'status',
        render: function (data) {
          let badgeClass = 'bg-label-info';
          if (data === 'Aduan Baru') badgeClass = 'bg-label-primary';
          if (data === 'Aduan Diproses') badgeClass = 'bg-label-warning';
          if (data === 'Aduan Selesai') badgeClass = 'bg-label-success';
          return `<span class="badge ${badgeClass}">${data}</span>`;
        }
      },
      {
        data: 'assigned_to',
        render: function (data) {
          return data || '<span class="text-muted">Belum ditugaskan</span>';
        }
      },
      {
        data: 'tick_duedate',
        render: function (data) {
          return data ? new Date(data).toLocaleDateString('ms-MY') : '-';
        }
      },
      {
        data: 'hours_overdue',
        render: function (data) {
          const days = Math.floor(data / 24);
          const hours = data % 24;
          return `<span class="text-danger fw-bold">${days}h ${hours}j</span>`;
        }
      },
      {
        data: 'ticket_nolog',
        render: function (data) {
          return `<a href="/tickets/${data}" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bx bx-show"></i> Lihat
                    </a>`;
        }
      }
    ],
    order: [[7, 'desc']], // Order by hours overdue
    pageLength: 10,
    responsive: true,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ms.json'
    }
  });
}

/**
 * Initialize action tickets DataTable
 */
function initializeActionTable() {
  actionTable = $('#table-action-tickets').DataTable({
    ajax: {
      url: '/dashboard/monitoring/action-tickets',
      dataSrc: 'data'
    },
    columns: [
      {
        data: null,
        render: function (data, type, row, meta) {
          return meta.row + 1;
        }
      },
      { data: 'ticket_nolog' },
      {
        data: null,
        render: function (data) {
          return `${data.category}<br><small class="text-muted">${data.subcategory}</small>`;
        }
      },
      {
        data: 'ticket_title',
        render: function (data) {
          return data.length > 50 ? data.substring(0, 50) + '...' : data;
        }
      },
      {
        data: 'status',
        render: function (data) {
          let badgeClass = 'bg-label-info';
          if (data === 'Aduan Baru') badgeClass = 'bg-label-primary';
          if (data === 'Aduan Diproses') badgeClass = 'bg-label-warning';
          if (data === 'Aduan Selesai') badgeClass = 'bg-label-success';
          return `<span class="badge ${badgeClass}">${data}</span>`;
        }
      },
      {
        data: 'assigned_to',
        render: function (data) {
          return data || '<span class="text-muted">Belum ditugaskan</span>';
        }
      },
      {
        data: 'tick_duedate',
        render: function (data) {
          return data ? new Date(data).toLocaleDateString('ms-MY') : '-';
        }
      },
      {
        data: 'hours_remaining',
        render: function (data) {
          if (data < 0) {
            const absHours = Math.abs(data);
            const days = Math.floor(absHours / 24);
            const hours = absHours % 24;
            return `<span class="text-danger fw-bold">Lewat ${days}h ${hours}j</span>`;
          } else {
            const days = Math.floor(data / 24);
            const hours = data % 24;
            let textClass = data < 24 ? 'text-warning' : 'text-success';
            return `<span class="${textClass}">${days}h ${hours}j</span>`;
          }
        }
      },
      {
        data: 'ticket_nolog',
        render: function (data) {
          return `<a href="/tickets/${data}" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bx bx-show"></i> Lihat
                    </a>`;
        }
      }
    ],
    order: [[6, 'asc']], // Order by due date
    pageLength: 10,
    responsive: true,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ms.json'
    }
  });
}

/**
 * Refresh overdue tickets table
 */
function refreshOverdueTable() {
  if (overdueTable) {
    overdueTable.ajax.reload(null, false); // false = keep current page
  }
}

/**
 * Refresh action tickets table
 */
function refreshActionTable() {
  if (actionTable) {
    actionTable.ajax.reload(null, false);
  }
}

// Export functions for debugging
window.monitoringDashboard = {
  loadCardsData,
  refreshOverdueTable,
  refreshActionTable,
  updateDateTime
};
