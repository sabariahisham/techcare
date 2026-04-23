/**
 * Session Timeout Handler
 * Features:
 * 1. Frontend idle timer with warning before session expires
 * 2. Multi-tab synchronization (logout in one tab logs out all tabs)
 * 3. Session extension on user activity via AJAX ping
 * 4. Countdown modal before automatic logout
 */

import logger from './logger.js';

'use strict';

(function () {
  // Configuration - sync with Laravel session lifetime (in minutes)
  const SESSION_LIFETIME = window.sessionConfig ? window.sessionConfig.lifetime || 120 : 120; // minutes
  const WARNING_TIME = 5; // Show warning 5 minutes before expiry
  const PING_INTERVAL = 5; // Ping server every 5 minutes to extend session

  // Convert to milliseconds
  const SESSION_TIMEOUT = SESSION_LIFETIME * 60 * 1000;
  const WARNING_TIMEOUT = (SESSION_LIFETIME - WARNING_TIME) * 60 * 1000;
  const PING_INTERVAL_MS = PING_INTERVAL * 60 * 1000;

  // Storage keys for cross-tab sync
  const STORAGE_KEY_LAST_ACTIVITY = 'session_last_activity';
  const STORAGE_KEY_LOGOUT = 'session_logout';
  const BROADCAST_CHANNEL_NAME = 'session_channel';

  // State
  let warningTimer = null;
  let logoutTimer = null;
  let countdownInterval = null;
  let pingInterval = null;
  let displayInterval = null;
  let lastActivity = Date.now();
  let isWarningShown = false;
  let broadcastChannel = null;

  /**
   * Initialize the session timeout handler
   */
  function init() {
    // Only initialize for authenticated users
    if (!document.querySelector('meta[name="csrf-token"]')) {
      return;
    }

    // Initialize cross-tab communication
    initBroadcastChannel();

    // Set up activity listeners
    setupActivityListeners();

    // Set up storage event listener for cross-tab sync
    window.addEventListener('storage', handleStorageEvent);

    // Start timers
    resetTimers();

    // Start session ping
    startSessionPing();

    // Start digital timer display
    startTimerDisplay();

    // Setup refresh button
    setupRefreshButton();

    // Update last activity in storage
    updateLastActivity();

    logger.log('Session timeout handler initialized. Session lifetime:', SESSION_LIFETIME, 'minutes');
  }

  /**
   * Initialize BroadcastChannel for cross-tab communication
   */
  function initBroadcastChannel() {
    if ('BroadcastChannel' in window) {
      broadcastChannel = new BroadcastChannel(BROADCAST_CHANNEL_NAME);
      broadcastChannel.onmessage = function (event) {
        if (event.data.type === 'logout') {
          performLogout(false); // Don't broadcast again
        } else if (event.data.type === 'activity') {
          lastActivity = event.data.timestamp;
          resetTimers();
          hideWarningModal();
        }
      };
    }
  }

  /**
   * Set up activity listeners
   */
  function setupActivityListeners() {
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];

    events.forEach(function (event) {
      document.addEventListener(event, handleActivity, {
        passive: true
      });
    });
  }

  /**
   * Handle user activity
   */
  function handleActivity() {
    const now = Date.now();

    // Throttle activity updates to once per second
    if (now - lastActivity < 1000) {
      return;
    }

    lastActivity = now;
    updateLastActivity();

    // If warning is shown, hide it and reset timers
    if (isWarningShown) {
      hideWarningModal();
    }

    resetTimers();

    // Broadcast activity to other tabs
    broadcastActivity();
  }

  /**
   * Update last activity in localStorage
   */
  function updateLastActivity() {
    try {
      localStorage.setItem(STORAGE_KEY_LAST_ACTIVITY, lastActivity.toString());
    } catch (e) {
      console.warn('Could not update localStorage:', e);
    }
  }

  /**
   * Broadcast activity to other tabs
   */
  function broadcastActivity() {
    if (broadcastChannel) {
      broadcastChannel.postMessage({
        type: 'activity',
        timestamp: lastActivity
      });
    }
  }

  /**
   * Handle storage events for cross-tab sync
   */
  function handleStorageEvent(event) {
    if (event.key === STORAGE_KEY_LOGOUT) {
      // Another tab triggered logout
      performLogout(false);
    } else if (event.key === STORAGE_KEY_LAST_ACTIVITY) {
      // Activity in another tab
      const newActivity = parseInt(event.newValue, 10);
      if (newActivity > lastActivity) {
        lastActivity = newActivity;
        resetTimers();
        hideWarningModal();
      }
    }
  }

  /**
   * Reset session timers
   */
  function resetTimers() {
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);

    // Set warning timer
    warningTimer = setTimeout(showWarningModal, WARNING_TIMEOUT);

    // Set logout timer
    logoutTimer = setTimeout(function () {
      performLogout(true);
    }, SESSION_TIMEOUT);
  }

  /**
   * Start session ping to keep session alive on server
   */
  function startSessionPing() {
    pingInterval = setInterval(function () {
      pingSession();
    }, PING_INTERVAL_MS);
  }

  /**
   * Start the digital timer display
   */
  function startTimerDisplay() {
    updateTimerDisplay();
    displayInterval = setInterval(updateTimerDisplay, 1000);
  }

  /**
   * Update the digital timer display
   */
  function updateTimerDisplay() {
    const hoursEl = document.getElementById('timerHours');
    const minutesEl = document.getElementById('timerMinutes');
    const secondsEl = document.getElementById('timerSeconds');
    const progressEl = document.getElementById('sessionTimerProgress');
    const widgetEl = document.getElementById('sessionTimerWidget');

    if (!hoursEl || !minutesEl || !secondsEl) return;

    const elapsed = Date.now() - lastActivity;
    const remaining = Math.max(0, SESSION_TIMEOUT - elapsed);
    const remainingSeconds = Math.floor(remaining / 1000);
    const hours = Math.floor(remainingSeconds / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;

    // Format time display
    hoursEl.textContent = (hours < 10 ? '0' : '') + hours;
    minutesEl.textContent = (minutes < 10 ? '0' : '') + minutes;
    secondsEl.textContent = (seconds < 10 ? '0' : '') + seconds;

    // Update progress bar
    if (progressEl) {
      const progressPercent = (remaining / SESSION_TIMEOUT) * 100;
      progressEl.style.width = progressPercent + '%';

      // Change colors based on remaining time
      const totalMinutes = Math.floor(remainingSeconds / 60);
      if (totalMinutes < 1) {
        progressEl.className = 'progress-bar bg-danger';
        hoursEl.className = 'fs-3 fw-bold text-danger';
        minutesEl.className = 'fs-3 fw-bold text-danger';
        secondsEl.className = 'fs-3 fw-bold text-danger';
        widgetEl.classList.add('animate__animated', 'animate__pulse', 'animate__infinite');
      } else if (totalMinutes < WARNING_TIME) {
        progressEl.className = 'progress-bar bg-warning';
        hoursEl.className = 'fs-3 fw-bold text-warning';
        minutesEl.className = 'fs-3 fw-bold text-warning';
        secondsEl.className = 'fs-3 fw-bold text-warning';
        widgetEl.classList.remove('animate__animated', 'animate__pulse', 'animate__infinite');
      } else {
        progressEl.className = 'progress-bar bg-primary';
        hoursEl.className = 'fs-3 fw-bold text-primary';
        minutesEl.className = 'fs-3 fw-bold text-primary';
        secondsEl.className = 'fs-3 fw-bold text-primary';
        widgetEl.classList.remove('animate__animated', 'animate__pulse', 'animate__infinite');
      }
    }

    // If time is up
    if (remaining <= 0) {
      clearInterval(displayInterval);
      performLogout(true);
    }
  }

  /**
   * Setup refresh session button
   */
  function setupRefreshButton() {
    const refreshBtn = document.getElementById('btnRefreshSession');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function () {
        extendSession();
      });
    }
  }

  /**
   * Ping server to extend session
   */
  function pingSession() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) return;

    fetch('/session/ping', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken.content,
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (response.status === 401 || response.status === 419) {
          // Session expired on server
          performLogout(true);
        }
        return response.json();
      })
      .then(function (data) {
        if (data && data.authenticated === false) {
          performLogout(true);
        }
      })
      .catch(function (error) {
        console.warn('Session ping failed:', error);
      });
  }

  /**
   * Show warning modal
   */
  function showWarningModal() {
    if (isWarningShown) return;
    isWarningShown = true;

    // Create modal if it doesn't exist
    let modal = document.getElementById('sessionTimeoutModal');
    if (!modal) {
      modal = createWarningModal();
      document.body.appendChild(modal);
    }

    // Show the modal
    const bsModal = new bootstrap.Modal(modal, {
      backdrop: 'static',
      keyboard: false
    });
    bsModal.show();

    // Start countdown
    startCountdown();
  }

  /**
   * Create the warning modal element
   */
  function createWarningModal() {
    const modalHtml = `
      <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title">
                <i class="bx bx-time-five me-2"></i>Amaran Sesi Tamat
              </h5>
            </div>
            <div class="modal-body text-center py-4">
              <div class="mb-3">
                <i class="bx bx-error-circle text-warning" style="font-size: 4rem;"></i>
              </div>
              <h4 class="mb-3">Sesi anda akan tamat tidak lama lagi</h4>
              <p class="text-muted mb-3">
                Anda telah tidak aktif untuk tempoh yang lama. Sesi anda akan tamat dalam:
              </p>
              <div class="countdown-timer mb-3">
                <span id="sessionCountdown" class="display-4 fw-bold text-danger">5:00</span>
              </div>
              <p class="text-muted small">
                Klik "Teruskan Sesi" untuk kekal log masuk, atau anda akan dilog keluar secara automatik.
              </p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-outline-secondary" id="btnLogoutNow">
                <i class="bx bx-log-out me-1"></i>Log Keluar Sekarang
              </button>
              <button type="button" class="btn btn-primary" id="btnExtendSession">
                <i class="bx bx-refresh me-1"></i>Teruskan Sesi
              </button>
            </div>
          </div>
        </div>
      </div>
    `;

    const div = document.createElement('div');
    div.innerHTML = modalHtml.trim();
    const modal = div.firstChild;

    // Add event listeners
    modal.querySelector('#btnExtendSession').addEventListener('click', function () {
      extendSession();
    });

    modal.querySelector('#btnLogoutNow').addEventListener('click', function () {
      performLogout(true);
    });

    return modal;
  }

  /**
   * Start countdown timer
   */
  function startCountdown() {
    let remainingSeconds = WARNING_TIME * 60;
    const countdownEl = document.getElementById('sessionCountdown');

    if (countdownInterval) {
      clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(function () {
      remainingSeconds--;

      if (remainingSeconds <= 0) {
        clearInterval(countdownInterval);
        performLogout(true);
        return;
      }

      const minutes = Math.floor(remainingSeconds / 60);
      const seconds = remainingSeconds % 60;
      countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

      // Add urgency styling when under 1 minute
      if (remainingSeconds < 60) {
        countdownEl.classList.add('text-danger', 'animate__animated', 'animate__pulse', 'animate__infinite');
      }
    }, 1000);
  }

  /**
   * Hide warning modal
   */
  function hideWarningModal() {
    isWarningShown = false;

    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }

    const modal = document.getElementById('sessionTimeoutModal');
    if (modal) {
      const bsModal = bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    }
  }

  /**
   * Extend session
   */
  function extendSession() {
    hideWarningModal();

    // Ping server to extend session
    pingSession();

    // Reset timers
    lastActivity = Date.now();
    updateLastActivity();
    resetTimers();

    // Broadcast to other tabs
    broadcastActivity();

    // Show success notification if SweetAlert is available
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'Sesi Dilanjutkan',
        text: 'Sesi anda telah dilanjutkan.',
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
      });
    }
  }

  /**
   * Perform logout
   */
  function performLogout(broadcast) {
    // Clear all timers
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);
    clearInterval(countdownInterval);
    clearInterval(pingInterval);

    // Broadcast logout to other tabs
    if (broadcast) {
      try {
        localStorage.setItem(STORAGE_KEY_LOGOUT, Date.now().toString());

        if (broadcastChannel) {
          broadcastChannel.postMessage({
            type: 'logout'
          });
        }
      } catch (e) {
        console.warn('Could not broadcast logout:', e);
      }
    }

    // Submit logout form
    const logoutForm = document.getElementById('logout-form');
    if (logoutForm) {
      logoutForm.submit();
    } else {
      // Fallback: redirect to logout route
      window.location.href = '/logout';
    }
  }

  /**
   * Check session on page load
   */
  function checkSessionOnLoad() {
    // Check if there's a recent logout signal
    try {
      const lastLogout = localStorage.getItem(STORAGE_KEY_LOGOUT);
      if (lastLogout) {
        const logoutTime = parseInt(lastLogout, 10);
        // If logout happened in last 5 seconds, perform logout
        if (Date.now() - logoutTime < 5000) {
          performLogout(false);
          return;
        }
        // Clear old logout signal
        localStorage.removeItem(STORAGE_KEY_LOGOUT);
      }
    } catch (e) {
      console.warn('Could not check localStorage:', e);
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      checkSessionOnLoad();
      init();
    });
  } else {
    checkSessionOnLoad();
    init();
  }
})();
