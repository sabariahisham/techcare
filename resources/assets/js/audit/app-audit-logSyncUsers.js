import logger from '../logger.js';

'use strict';

// Initialize DataTable on document ready
$(document).ready(function () {
  logger.log('App-audit-logSyncUsers.js loaded');

  // Check if table exists
  if ($('#syncSppLogsTable').length) {
    $('#syncSppLogsTable').DataTable({
      order: [[6, 'desc']], // Sort by Started date (descending)
      pageLength: 25,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ms.json'
      }
    });
  }
});

/**
 * Filter logs by date range
 */
window.filterByDate = function () {
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;

  const url = new URL(window.location.href);
  if (dateFrom) {
    url.searchParams.set('date_from', dateFrom);
  } else {
    url.searchParams.delete('date_from');
  }

  if (dateTo) {
    url.searchParams.set('date_to', dateTo);
  } else {
    url.searchParams.delete('date_to');
  }

  window.location.href = url.toString();
};

/**
 * Reset all filters
 */
window.resetFilters = function () {
  // Use config from global window object
  if (window.syncSppConfig && window.syncSppConfig.routes) {
    window.location.href = window.syncSppConfig.routes.index;
  } else {
    // Fallback to current page without parameters
    window.location.href = window.location.pathname;
  }
};

/**
 * Run sync task manually
 */
window.runSyncTask = function () {
  logger.log('runSyncTask function called');

  // Check if config is available
  if (!window.syncSppConfig) {
    alert('❌ Configuration not loaded. Please refresh the page.');
    return;
  }

  // Confirm action
  if (!confirm(
    '⚠️ PERHATIAN!\n\n' +
    'Sync process akan mengambil masa 1-2 minit.\n\n' +
    '• Jangan refresh atau close browser\n' +
    '• Sistem akan auto reload selepas selesai\n\n' +
    'Teruskan?'
  )) {
    return;
  }

  const btn = document.getElementById('runSyncBtn');
  const btnText = document.getElementById('btnText');

  if (btn && btnText) {
    const originalText = btnText.textContent;
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing... (1-2 minit)';
  }

  logger.log('Calling URL:', window.syncSppConfig.routes.runTask);

  // Show processing message
  const processingAlert = document.createElement('div');
  processingAlert.id = 'syncProcessingAlert';
  processingAlert.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
  processingAlert.style.zIndex = '9999';
  processingAlert.style.minWidth = '400px';
  processingAlert.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div><div><strong>⏳ Sedang Sync...</strong><br><small>Sila tunggu 1-2 minit. Jangan refresh browser.</small></div></div>';
  document.body.appendChild(processingAlert);

  fetch(window.syncSppConfig.routes.runTask, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.syncSppConfig.csrfToken
    }
  })
    .then(response => {
      logger.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error('HTTP error! status: ' + response.status);
      }
      return response.json();
    })
    .then(data => {
      logger.log('Response data:', data);
      processingAlert.remove();

      if (data.success) {
        // Support both data.results and data.data format
        const results = data.results || data.data || {};

        let message = '✅ Sync berjaya!\n\n';
        message += '📊 Keputusan:\n';
        message += 'Status: ' + (results.status || 'success').toUpperCase() + '\n';
        message += 'Total Records: ' + (results.total_records || 0) + '\n';
        message += '➕ Inserted: ' + (results.inserted || results.inserted_records || 0) + '\n';
        message += '🔄 Updated: ' + (results.updated || results.updated_records || 0) + '\n';
        message += '⏱ Execution Time: ' + (results.execution_time || 0) + 's\n\n';
        message += '🕐 Masa: ' + (data.timestamp || new Date().toLocaleString());

        alert(message);
        setTimeout(() => window.location.reload(), 1500);
      } else {
        alert('❌ Error: ' + (data.message || 'Unknown error'));
        if (btn && btnText) {
          btn.disabled = false;
          btnText.textContent = originalText;
        }
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      processingAlert.remove();
      alert('❌ Error: ' + error.message + '\n\nJika sync mengambil masa terlalu lama, cuba jalankan command secara manual:\nphp artisan sync:users-spp');
      if (btn && btnText) {
        btn.disabled = false;
        btnText.textContent = originalText;
      }
    });
};

/**
 * Kill stuck running jobs
 */
window.killStuckJobs = function () {
  logger.log('killStuckJobs function called');

  // Check if config is available
  if (!window.syncSppConfig) {
    alert('❌ Configuration not loaded. Please refresh the page.');
    return;
  }

  if (!confirm(
    '⚠️ WARNING!\n\n' +
    'This will mark all running jobs as FAILED.\n\n' +
    'Are you sure?'
  )) {
    return;
  }

  const btn = document.getElementById('killJobsBtn');
  const btnText = document.getElementById('killBtnText');

  if (btn && btnText) {
    const originalText = btnText.textContent;
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Killing...';
  }

  logger.log('Calling URL:', window.syncSppConfig.routes.killStuckJobs);

  fetch(window.syncSppConfig.routes.killStuckJobs, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.syncSppConfig.csrfToken
    }
  })
    .then(response => {
      logger.log('Response status:', response.status);
      logger.log('Response headers:', response.headers);
      return response.json();
    })
    .then(data => {
      logger.log('Response data:', data);
      if (data.success) {
        alert('✅ SUCCESS!\n\nKilled: ' + data.killed_count + ' job(s)\n\n' + data.message);
        window.location.reload();
      } else {
        alert('❌ Error: ' + (data.message || 'Unknown error'));
        if (btn && btnText) {
          btn.disabled = false;
          btnText.textContent = originalText;
        }
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      alert('❌ Network Error: ' + error.message);
      if (btn && btnText) {
        btn.disabled = false;
        btnText.textContent = originalText;
      }
    });
};
