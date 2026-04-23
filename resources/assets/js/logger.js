/**
 * Development Logger Utility
 *
 * This logger only outputs logs in development mode (when import.meta.env.DEV is true).
 * In production, all logging methods except error are completely silent.
 *
 * Usage:
 *   import logger from './logger.js';
 *   logger.log('Debug message');
 *   logger.info('Info message');
 *   logger.debug('Debug message');
 *   logger.warn('Warning message');
 *   logger.error('Error message'); // Always works
 */

const isDevelopment = import.meta.env.DEV; // Check if we're in development mode

/**
 * Custom logger that only outputs in development mode
 */
const logger = {
  /**
   * Log general debug information (only in development)
   * @param {...any} args - Arguments to log
   */
  log(...args) {
    if (isDevelopment) {
      console.log(...args);
    }
  },

  /**
   * Log informational messages (only in development)
   * @param {...any} args - Arguments to log
   */
  info(...args) {
    if (isDevelopment) {
      console.info(...args);
    }
  },

  /**
   * Log debug messages (only in development)
   * @param {...any} args - Arguments to log
   */
  debug(...args) {
    if (isDevelopment) {
      console.debug(...args);
    }
  },

  /**
   * Log warning messages (only in development)
   * @param {...any} args - Arguments to log
   */
  warn(...args) {
    if (isDevelopment) {
      console.warn(...args);
    }
  },

  /**
   * Log error messages (ALWAYS works, even in production)
   * @param {...any} args - Arguments to log
   */
  error(...args) {
    console.error(...args);
  }
};

// In production mode, disable console.log, console.info, and console.debug globally
// but keep console.error working
if (import.meta.env.PROD) {
  // Store original methods in case they're needed
  const originalLog = console.log;
  const originalInfo = console.info;
  const originalDebug = console.debug;

  // Override with no-op functions in production
  console.log = function () { };
  console.info = function () { };
  console.debug = function () { };

  // Keep console.error working
  // console.warn is also kept for important warnings
}

export default logger;
